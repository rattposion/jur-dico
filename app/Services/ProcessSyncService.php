<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DataJudClient;
use App\Models\Record;
use App\Models\Audit;
use Exception;

class ProcessSyncService
{
    private DataJudClient $client;
    private int $batchSize = 100;
    private int $maxRetries = 3;

    public function __construct(array $config)
    {
        $this->client = new DataJudClient($config);
    }

    /**
     * Synchronizes all processes from the API to the local database.
     * 
     * @param int $limit Maximum number of records to fetch (0 for unlimited).
     * @return array Statistics of the operation.
     */
    public function syncAll(int $limit = 0): array
    {
        $stats = [
            'fetched' => 0,
            'upserted' => 0,
            'errors' => 0,
            'start_time' => microtime(true)
        ];

        $searchAfter = null;
        $keepRunning = true;

        while ($keepRunning) {
            try {
                $batch = $this->fetchBatch($searchAfter);
                
                if (empty($batch)) {
                    break;
                }

                foreach ($batch as $hit) {
                    if ($limit > 0 && $stats['fetched'] >= $limit) {
                        $keepRunning = false;
                        break;
                    }

                    $this->processHit($hit, $stats);
                    
                    // Update searchAfter cursor (using sort values)
                    $searchAfter = $hit['sort'] ?? null;
                    $stats['fetched']++;
                }

                if ($searchAfter === null) {
                    break; // Should not happen if we have results and sort
                }

                // Politeness delay
                usleep(100000); // 100ms

            } catch (Exception $e) {
                $stats['errors']++;
                error_log("Sync Error: " . $e->getMessage());
                // In a real scenario, we might want to stop or retry the batch.
                // For now, we abort to avoid infinite error loops.
                break;
            }
        }

        $stats['duration'] = microtime(true) - $stats['start_time'];
        return $stats;
    }

    /**
     * Imports processes based on a specific ElasticSearch query string.
     * 
     * @param string $queryString The query string (e.g., '"term1" AND "term2"').
     * @param int $limit Maximum number of records to fetch (0 for unlimited).
     * @return array Statistics of the operation.
     */
    public function importByQuery(string $queryString, int $limit = 0): array
    {
        $stats = [
            'fetched' => 0,
            'upserted' => 0,
            'errors' => 0,
            'start_time' => microtime(true)
        ];

        $searchAfter = null;
        $keepRunning = true;

        // Query structure for query_string
        $queryBody = [
            "query_string" => [
                "query" => $queryString
            ]
        ];

        while ($keepRunning) {
            try {
                $batch = $this->fetchBatch($searchAfter, $queryBody);
                
                if (empty($batch)) {
                    break;
                }

                foreach ($batch as $hit) {
                    if ($limit > 0 && $stats['fetched'] >= $limit) {
                        $keepRunning = false;
                        break;
                    }

                    $this->processHit($hit, $stats);
                    
                    // Update searchAfter cursor
                    $searchAfter = $hit['sort'] ?? null;
                    $stats['fetched']++;
                }

                if ($searchAfter === null) {
                    break;
                }

                usleep(100000); // 100ms delay

            } catch (Exception $e) {
                $stats['errors']++;
                error_log("Import Query Error: " . $e->getMessage());
                break;
            }
        }

        $stats['duration'] = microtime(true) - $stats['start_time'];
        return $stats;
    }

    private function fetchBatch(?array $searchAfter, ?array $queryOverride = null): array
    {
        $body = [
            "size" => $this->batchSize,
            "query" => $queryOverride ?? [
                "match_all" => (object)[]
            ],
            "sort" => [
                ["dataHoraUltimaAtualizacao" => "asc"]
            ]
        ];

        if ($searchAfter) {
            $body["search_after"] = $searchAfter;
        }

        // Retry logic
        $attempts = 0;
        while ($attempts < $this->maxRetries) {
            try {
                $hits = $this->client->search($body);
                return $hits;
            } catch (Exception $e) {
                $attempts++;
                sleep(pow(2, $attempts)); // Exponential backoff
            }
        }

        throw new Exception("Failed to fetch batch after {$this->maxRetries} attempts.");
    }

    private function processHit(array $hit, array &$stats): void
    {
        try {
            $source = $hit['_source'] ?? [];
            if (empty($source)) return;

            $recordData = $this->mapToRecord($hit);
            Record::upsert($recordData);
            $stats['upserted']++;

        } catch (Exception $e) {
            $stats['errors']++;
            error_log("Error processing hit {$hit['_id']}: " . $e->getMessage());
        }
    }

    private function mapToRecord(array $hit): array
    {
        $src = $hit['_source'];
        
        $data = [
            'id' => $hit['_id'] ?? uniqid(),
            'numeroProcesso' => $src['numeroProcesso'] ?? null,
            'numeroRegistro' => null, 
            'siglaClasse' => null, 
            'descricaoClasse' => $src['classe']['nome'] ?? null,
            'nomeOrgaoJulgador' => $src['orgaoJulgador']['nome'] ?? null,
            'ministroRelator' => null,
            'dataPublicacao' => null,
            'ementa' => null,
            'tipoDeDecisao' => null,
            'dataDecisao' => null,
            'decisao' => null,
        ];

        // 1. Órgão Julgador Validation
        if ($data['nomeOrgaoJulgador'] === null || stripos($data['nomeOrgaoJulgador'], 'PRESIDÊNCIA') !== false) {
            // Check if it's explicitly 'PRESIDÊNCIA' or similar
            if (isset($src['orgaoJulgador']['nome']) && stripos($src['orgaoJulgador']['nome'], 'PRESID') !== false) {
                $data['nomeOrgaoJulgador'] = 'PRESIDÊNCIA';
            }
        }

        // 2. Extract Data from Movements
        if (!empty($src['movimentos'])) {
            // Sort movements by date descending to get latest relevant info
            usort($src['movimentos'], function ($a, $b) {
                return strcmp($b['dataHora'], $a['dataHora']);
            });

            foreach ($src['movimentos'] as $mov) {
                $nomeMov = $mov['nome'] ?? '';
                $dataMov = $mov['dataHora'] ?? null;
                
                // Relator extraction
                if (empty($data['ministroRelator']) && stripos($nomeMov, 'Distribui') !== false) {
                    // Try to find relator in complements or description
                    // Often in DataJud it's not explicit in 'nome', but maybe in complements?
                    // STJ specific: "Conclusão ao(à) Ministro(a) Relator(a)"
                }
                
                // Try to find Relator in "Conclusão" or similar movements
                if (empty($data['ministroRelator']) && stripos($nomeMov, 'Conclusão') !== false) {
                     // Check complements for "Ministro X"
                }

                // Decision Date & Type
                if (empty($data['dataDecisao']) && (stripos($nomeMov, 'Julgamento') !== false || stripos($nomeMov, 'Decisão') !== false)) {
                    $data['dataDecisao'] = $dataMov;
                    $data['tipoDeDecisao'] = $nomeMov;
                }

                // Publication Date
                if (empty($data['dataPublicacao']) && stripos($nomeMov, 'Publicação') !== false) {
                    $data['dataPublicacao'] = $dataMov;
                }
            }
        }

        // 3. Fallback/Inference for Relator (Simulated for now as it's complex without specific text)
        // If we have "Relator" in any field
        
        // 4. Ementa & Decisão (Often in document content which is not in standard search result)
        // If 'decisao' field exists in source (some custom indexers add it)
        if (isset($src['decisao'])) {
            $data['decisao'] = $src['decisao'];
        }

        return $data;
    }
}
