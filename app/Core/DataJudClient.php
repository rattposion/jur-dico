<?php
declare(strict_types=1);

namespace App\Core;

class DataJudClient
{
    private array $config;
    private string $apiKey;
    private string $url;
    private string $cacheDir;

    public function __construct(array $config)
    {
        $this->config = $config;
        // Prioritize provided key if config is empty, or use config if available.
        // User provided key: cDZHYzlZa0JadVREZDJCendQbXY6SkJlTzNjLV9TRENyQk1RdnFKZGRQdw==
        $this->apiKey = $config['datajud']['api_key'] ?? 'cDZHYzlZa0JadVREZDJCendQbXY6SkJlTzNjLV9TRENyQk1RdnFKZGRQdw==';
        $this->url = $config['datajud']['url'] ?? 'https://api-publica.datajud.cnj.jus.br/api_publica_stj/_search';
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache/datajud';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Search for a process by its unique number (CNJ standard).
     * 
     * @param string $processNumber The 20-digit process number (only digits).
     * @return array The search results (hits).
     */
    public function searchByProcessNumber(string $processNumber): array
    {
        // Remove non-numeric characters
        $cleanNumber = preg_replace('/\D/', '', $processNumber);
        
        // ElasticSearch query for exact match on numeroProcesso
        $body = [
            "query" => [
                "match" => [
                    "numeroProcesso" => $cleanNumber
                ]
            ]
        ];

        return $this->search($body, 'proc_' . $cleanNumber);
    }

    /**
     * Search for processes by a general query (parties, keywords).
     * 
     * @param string $query Text to search for.
     * @return array The search results (hits).
     */
    public function searchByQuery(string $query): array
    {
        // Simple query string search across all fields
        $body = [
            "query" => [
                "query_string" => [
                    "query" => $query
                ]
            ],
            "size" => 10
        ];

        return $this->search($body, 'query_' . md5($query));
    }

    /**
     * Executes multiple search requests in parallel using curl_multi.
     * 
     * @param array $processNumbers Array of process numbers to search.
     * @return array Associative array [processNumber => hit|null]
     */
    public function fetchMulti(array $processNumbers): array
    {
        if (empty($this->apiKey) || empty($this->url) || empty($processNumbers)) {
            return [];
        }

        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        // Prepare handles
        foreach ($processNumbers as $proc) {
            $cleanNumber = preg_replace('/\D/', '', $proc);
            $body = [
                "query" => [
                    "match" => [
                        "numeroProcesso" => $cleanNumber
                    ]
                ]
            ];
            $payload = json_encode($body);

            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: APIKey ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            curl_multi_add_handle($mh, $ch);
            $handles[$proc] = $ch;
        }

        // Execute handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // Collect results
        foreach ($handles as $proc => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($httpCode >= 200 && $httpCode < 300 && !$error) {
                $data = json_decode($response, true);
                $hits = $data['hits']['hits'] ?? [];
                $results[$proc] = !empty($hits) ? $hits[0] : null;
            } else {
                // On error, we return null. Service can retry if needed.
                $results[$proc] = null;
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        return $results;
    }

    /**
     * Execute a raw search query against the DataJud API with caching.
     * 
     * @param array $body The ElasticSearch query body.
     * @param string|null $cacheKey Optional cache key. If provided, result will be cached for 1 hour.
     * @return array The 'hits' array from the response.
     */
    public function search(array $body, ?string $cacheKey = null): array
    {
        if (empty($this->apiKey) || empty($this->url)) {
            return [];
        }

        // Check Cache
        if ($cacheKey) {
            $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if (is_array($cached)) return $cached;
            }
        }

        $ch = curl_init($this->url);
        $payload = json_encode($body);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: APIKey ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Increased timeout for complex queries
        // Disable SSL verification for local dev if needed, though not recommended for prod
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            // Log error if needed, for now just return empty
            curl_close($ch);
            return [];
        }
        
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            $hits = $data['hits']['hits'] ?? [];
            
            // Save to Cache
            if ($cacheKey && !empty($hits)) {
                file_put_contents($cacheFile, json_encode($hits));
            }
            
            return $hits;
        }

        return [];
    }

    /**
     * Fetches the full document content (Simulated/Placeholder).
     * In a real scenario, this would call a specific endpoint like /documents/{id}/content
     * 
     * @param string $processNumber The process number to fetch documents for.
     * @return string|null The raw document content or null on failure.
     */
    public function fetchDocumentContent(string $processNumber): ?string
    {
        // TODO: Replace with actual endpoint when available.
        // Current implementation simulates a fetch by checking if we can find 'decisao' in a deep search
        // or returns a placeholder if it requires a separate restricted API.
        
        // Hypothetical separate endpoint call:
        // $url = str_replace('_search', 'download', $this->url) . '/' . $processNumber;
        // ... curl execution ...
        
        return null; // Placeholder: Real implementation requires specific endpoint documentation
    }
}
