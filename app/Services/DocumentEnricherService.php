<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\DataJudClient;
use App\Models\Record;
use Exception;
use PDO;

class DocumentEnricherService
{
    private DataJudClient $client;
    private int $maxRetries = 3;

    public function __construct(array $config)
    {
        $this->client = new DataJudClient($config);
    }

    /**
     * Enriches a batch of records by fetching full document content.
     * 
     * @param int $limit Maximum number of records to process
     * @return array Statistics
     */
    public function enrichBatch(int $limit = 50): array
    {
        $stats = [
            'processed' => 0,
            'enriched' => 0,
            'cached' => 0,
            'failed' => 0,
            'start_time' => microtime(true)
        ];

        // 1. Fetch records missing 'ementa' or 'decisao'
        $stmt = DB::pdo()->prepare("SELECT id, numeroProcesso FROM records WHERE (ementa IS NULL OR decisao IS NULL) LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as $row) {
            $stats['processed']++;
            $id = $row['id'];
            $processo = $row['numeroProcesso'];

            try {
                $content = $this->getDocumentContent($id, $processo, $stats);
                
                if ($content) {
                    $updates = $this->parseContent($content);
                    
                    if (!empty($updates)) {
                        $this->updateRecord($id, $updates);
                        $stats['enriched']++;
                    }
                }
            } catch (Exception $e) {
                $stats['failed']++;
                error_log("Enrichment Error [$processo]: " . $e->getMessage());
            }
        }

        $stats['duration'] = microtime(true) - $stats['start_time'];
        return $stats;
    }

    private function getDocumentContent(string $id, string $processo, array &$stats): ?string
    {
        // 1. Check Cache
        $stmt = DB::pdo()->prepare("SELECT content FROM document_cache WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cached = $stmt->fetchColumn();

        if ($cached) {
            $stats['cached']++;
            return $cached;
        }

        // 2. Fetch from API (with retry)
        $content = null;
        $attempts = 0;
        
        while ($attempts < $this->maxRetries) {
            try {
                $content = $this->client->fetchDocumentContent($processo);
                if ($content) break;
                
                // If null (not implemented or not found), break early to avoid retry loop on 404
                break; 

            } catch (Exception $e) {
                $attempts++;
                sleep(1); // Simple backoff
            }
        }

        // 3. Store in Cache if found
        if ($content) {
            $cacheStmt = DB::pdo()->prepare("INSERT INTO document_cache (id, content, created_at, updated_at) VALUES (:id, :content, NOW(), NOW()) ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()");
            $cacheStmt->execute([':id' => $id, ':content' => $content]);
        }

        return $content;
    }

    private function parseContent(string $content): array
    {
        $updates = [];

        // Hypothetical parsing logic (Regex)
        // Ementa: usually starts with "EMENTA" or is the first paragraph in upper case
        if (preg_match('/EMENTA\s*[:\-\.]?\s*(.*?)(?=\n\s*[A-Z]|$)/s', $content, $matches)) {
            $updates['ementa'] = trim($matches[1]);
        }

        // Decisao: "Vistos, relatados..." or "ACORDAM..."
        if (preg_match('/(Vistos.*?Relator\.)/s', $content, $matches)) {
            $updates['decisao'] = trim($matches[1]);
        }

        return $updates;
    }

    private function updateRecord(string $id, array $updates): void
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($updates['ementa'])) {
            $fields[] = "ementa = :ementa";
            $params[':ementa'] = $updates['ementa'];
        }

        if (isset($updates['decisao'])) {
            $fields[] = "decisao = :decisao";
            $params[':decisao'] = $updates['decisao'];
        }

        if (!empty($fields)) {
            $sql = "UPDATE records SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute($params);
        }
    }
}
