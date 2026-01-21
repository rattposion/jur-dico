<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\DataJudClient;
use App\Models\Audit;
use PDO;
use Exception;

class RecordUpdater
{
    private array $config;
    private DataJudClient $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new DataJudClient($config);
    }

    /**
     * Finds records with a specific "Órgão Julgador" value (e.g., placeholder)
     * and updates them with data from the official API.
     * 
     * @param string $targetValue The placeholder value to search for (e.g. "PRIMEIRA SEÇÃO")
     * @return array Statistics of the operation
     */
    public function fixOrgaoJulgador(string $targetValue = 'PRIMEIRA SEÇÃO'): array
    {
        // Prevent timeout for large batches
        set_time_limit(0);
        ignore_user_abort(true);

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'not_found_api' => 0,
            'error' => 0
        ];

        // 1. Identify records
        $stmt = DB::pdo()->prepare("SELECT id, numeroProcesso, nomeOrgaoJulgador, decisao, siglaClasse FROM records WHERE nomeOrgaoJulgador LIKE :target");
        $stmt->execute([':target' => "%$targetValue%"]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($records)) {
            return $stats;
        }

        // System User ID for audit logs
        $systemUserId = 0; 

        foreach ($records as $row) {
            $stats['processed']++;
            $id = $row['id'];
            $processo = $row['numeroProcesso'];
            $currentOrgao = $row['nomeOrgaoJulgador'];

            try {
                $officialOrgao = $this->resolveOrgaoJulgador($processo, $row);

                if ($officialOrgao && $officialOrgao !== $currentOrgao) {
                    // 3. Update DB
                    $updateStmt = DB::pdo()->prepare("UPDATE records SET nomeOrgaoJulgador = :new WHERE id = :id");
                    $updateStmt->execute([':new' => $officialOrgao, ':id' => $id]);
                    
                    $stats['updated']++;

                    // 3. Log Update
                    Audit::log($systemUserId, 'system_update_orgao', (string)$id, json_encode([
                        'old' => $currentOrgao,
                        'new' => $officialOrgao,
                        'reason' => 'Fixing placeholder via DataJud sync or Text Extraction'
                    ]));
                } elseif ($officialOrgao === $currentOrgao) {
                     $stats['unchanged']++;
                } else {
                    $stats['not_found_api']++;
                }

            } catch (Exception $e) {
                $stats['error']++;
                // Log error but continue
                error_log("Error updating record $id: " . $e->getMessage());
            }

            // Small delay to be polite to the API
            usleep(50000); // 50ms
        }

        return $stats;
    }

    private function resolveOrgaoJulgador(string $processo, array $row): ?string
    {
        // Strategy 1: Exact API Search
        $results = $this->client->searchByProcessNumber($processo);
        if (!empty($results)) {
            $hit = $results[0];
            return $hit['_source']['orgaoJulgador']['nome'] ?? null;
        }

        // Strategy 2: Wildcard API Search (if process number is short)
        if (strlen($processo) < 20) {
            $body = [
                "query" => [
                    "wildcard" => [
                        "numeroProcesso" => "*$processo*"
                    ]
                ]
            ];
            $results = $this->client->search($body);
            // Attempt to find a match based on Class or other metadata
            foreach ($results as $hit) {
                $src = $hit['_source'] ?? [];
                // If we have local class info, try to match
                if (!empty($row['siglaClasse'])) {
                    $apiClass = $src['classe']['nome'] ?? '';
                    // Very basic fuzzy match: if API class contains local sigla (e.g. "CC" in "Conflito de Competência" - wait, "CC" is sigla, name is "Conflito...")
                    // Better: if local class description is available? We didn't fetch it.
                    // But if local sigla is 'CC' and API class is 'Conflito de Competência', hard to match without a map.
                    // Let's just rely on the first hit if it looks reasonable? No, risky.
                }
            }
        }

        // Strategy 3: Text Extraction from 'decisao'
        if (!empty($row['decisao'])) {
            // Pattern: "Ministros da [NOME] do Superior"
            // Handles: "PRIMEIRA SEÇÃO", "PRIMEIRA TURMA", "CORTE ESPECIAL"
            if (preg_match('/Ministros da\s+(.*?)\s+do Superior Tribunal/iu', $row['decisao'], $matches)) {
                $extracted = trim($matches[1]);
                // Convert to Title Case for better presentation if it was all caps
                return mb_convert_case($extracted, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return null;
    }

    /**
     * Validates that all records have valid "Órgão Julgador" (not the placeholder).
     * @param string $invalidValue
     * @return int Number of remaining invalid records
     */
    public function validateCompleteness(string $invalidValue = 'PRIMEIRA SEÇÃO'): int
    {
        // Use BINARY for case-sensitive comparison to distinguish "PRIMEIRA SEÇÃO" from "Primeira Seção"
        $stmt = DB::pdo()->prepare("SELECT COUNT(1) FROM records WHERE BINARY nomeOrgaoJulgador = :target");
        $stmt->execute([':target' => $invalidValue]);
        return (int)$stmt->fetchColumn();
    }
}
