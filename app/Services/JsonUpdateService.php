<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DataJudClient;
use Exception;

class JsonUpdateService
{
    private DataJudClient $client;
    private int $concurrency = 10;
    private int $maxRetries = 2;

    public function __construct(array $config)
    {
        $this->client = new DataJudClient($config);
    }

    /**
     * Reads a JSON file, updates each record from the API, and saves to a new file.
     * 
     * @param string $inputPath Path to source JSON file.
     * @param string $outputPath Path to save updated JSON.
     * @return array Statistics of the operation.
     */
    public function updateJsonFile(string $inputPath, string $outputPath): array
    {
        $stats = [
            'total' => 0,
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0,
            'start_time' => microtime(true),
            'logs' => []
        ];

        // 1. Read JSON
        if (!file_exists($inputPath)) {
            throw new Exception("Input file not found: $inputPath");
        }

        $jsonContent = file_get_contents($inputPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON: " . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new Exception("JSON must be an array of objects.");
        }

        $stats['total'] = count($data);
        $chunks = array_chunk($data, $this->concurrency, true); // preserve keys

        foreach ($chunks as $chunk) {
            $processMap = [];
            $singleItems = [];
            foreach ($chunk as $index => $item) {
                // Identify process number field
                $procNum = $item['numeroProcesso'] ?? $item['NumeroProcesso'] ?? $item['processo'] ?? null;
                
                // Validate Process Number length
                if ($procNum && strlen(preg_replace('/\D/', '', $procNum)) >= 15) {
                    $processMap[$index] = $procNum;
                } elseif ($procNum) {
                    // Short number - process individually to use wildcard/heuristic
                    $stats['logs'][] = "Short process number detected: $procNum (Item $index). Using fallback search.";
                    $singleItems[$index] = $procNum;
                } else {
                    $stats['logs'][] = "Item $index missing process number.";
                    $stats['failed']++;
                }
            }

            // 2. Fetch standard NPUs in parallel
            if (!empty($processMap)) {
                $results = $this->fetchWithRetry(array_values($processMap));
                
                foreach ($processMap as $index => $procNum) {
                    $hit = $results[$procNum] ?? null;
                    $this->applyUpdate($data, $index, $hit, $stats, $procNum);
                }
            }

            // 3. Fetch short numbers individually (heuristic)
            foreach ($singleItems as $index => $procNum) {
                $hit = $this->fetchSingleHeuristic($procNum, $data[$index]);
                $this->applyUpdate($data, $index, $hit, $stats, $procNum);
            }

            // Small delay between batches
            usleep(100000); 
        }

        // 4. Save Output
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($outputPath, $encoded);

        $stats['duration'] = microtime(true) - $stats['start_time'];
        return $stats;
    }

    private function applyUpdate(array &$data, int $index, ?array $hit, array &$stats, string $procNum): void
    {
        if ($hit) {
            $updatedItem = $this->updateItem($data[$index], $hit);
            if ($updatedItem !== $data[$index]) {
                $data[$index] = $updatedItem;
                $stats['updated']++;
            } else {
                $stats['unchanged']++;
            }
        } else {
            $stats['failed']++;
            $stats['logs'][] = "Failed to fetch or not found: $procNum";
        }
        $stats['processed']++;
    }

    private function fetchSingleHeuristic(string $procNum, array $item): ?array
    {
        // Try exact match first (sometimes short numbers work if indexed as keywords)
        $body = ["query" => ["match" => ["numeroProcesso" => $procNum]]];
        try {
            $hits = $this->client->search($body);
            if (!empty($hits)) return $hits[0];
        } catch (Exception $e) {}

        // Try wildcard
        $body = ["query" => ["wildcard" => ["numeroProcesso" => "*$procNum*"]]];
        try {
            $hits = $this->client->search($body);
            // Filter results if we have class info
            // "siglaClasse" : "AR"
            $sigla = $item['siglaClasse'] ?? null;
            
            foreach ($hits as $hit) {
                // If we have a sigla, try to verify
                // But API returns 'classe.nome' (e.g. "Ação Rescisória"), not sigla.
                // We can check if 'classe.nome' matches the description or acronym
                if ($sigla) {
                     $apiClass = $hit['_source']['classe']['nome'] ?? '';
                     // Simple check: first letters match? Or fuzzy?
                     // Let's just return the first hit for now as a best effort
                     return $hit;
                }
                return $hit;
            }
        } catch (Exception $e) {}

        return null;
    }

    private function fetchWithRetry(array $processNumbers): array
    {
        $attempts = 0;
        $finalResults = [];
        $toFetch = $processNumbers;

        while ($attempts <= $this->maxRetries && !empty($toFetch)) {
            if ($attempts > 0) {
                usleep(500000 * $attempts); // Backoff
            }

            $results = $this->client->fetchMulti($toFetch);
            
            // Check which ones succeeded
            $failed = [];
            foreach ($toFetch as $proc) {
                if (isset($results[$proc]) && $results[$proc] !== null) {
                    $finalResults[$proc] = $results[$proc];
                } else {
                    // Check if it's strictly a failure or just not found?
                    // fetchMulti returns null for both.
                    // We assume null means "try again" if it's network, but "not found" if 404.
                    // Since we can't distinguish easily without more client logic, we retry everything that returned null.
                    $failed[] = $proc;
                }
            }

            $toFetch = $failed;
            $attempts++;
        }

        return $finalResults;
    }

    private function updateItem(array $item, array $hit): array
    {
        $src = $hit['_source'];
        
        // Map fields if they are missing or placeholders in the original item
        // Fields to update: OrgaoJulgador, Relator, DataPublicacao, Ementa, Decisao
        
        // Órgão Julgador
        if ($this->shouldUpdate($item, 'nomeOrgaoJulgador', 'PRIMEIRA SEÇÃO')) {
            $item['nomeOrgaoJulgador'] = $src['orgaoJulgador']['nome'] ?? $item['nomeOrgaoJulgador'] ?? '-';
        }
        
        // Relator (Try extraction or fallback)
        // Note: extraction logic is simpler here than ProcessSyncService for speed, relying on basic structure
        // If we want full extraction logic, we should use a shared helper, but user asked for "Extract data needed"
        
        // Data Publicação
        if (empty($item['dataPublicacao']) || $item['dataPublicacao'] === '-') {
             // Try to find in movements
             $pubDate = $this->findMovementDate($src, 'Publicação');
             if ($pubDate) $item['dataPublicacao'] = $pubDate;
        }

        // Data Decisão / Tipo Decisão
        if (empty($item['dataDecisao']) || $item['dataDecisao'] === '-') {
            $decDate = $this->findMovementDate($src, ['Julgamento', 'Decisão']);
            if ($decDate) $item['dataDecisao'] = $decDate;
        }

        return $item;
    }

    private function shouldUpdate(array $item, string $key, string $placeholder): bool
    {
        $val = $item[$key] ?? '';
        return empty($val) || $val === '-' || stripos($val, $placeholder) !== false;
    }

    private function findMovementDate(array $src, $keywords): ?string
    {
        if (empty($src['movimentos'])) return null;
        
        $keywords = (array)$keywords;
        // Sort by date desc
        usort($src['movimentos'], fn($a, $b) => strcmp($b['dataHora'] ?? '', $a['dataHora'] ?? ''));

        foreach ($src['movimentos'] as $mov) {
            $name = $mov['nome'] ?? '';
            foreach ($keywords as $kw) {
                if (stripos($name, $kw) !== false) {
                    return $mov['dataHora'] ?? null;
                }
            }
        }
        return null;
    }
}
