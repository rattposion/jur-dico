<?php
declare(strict_types=1);

require 'config/config.php';
require 'app/Core/DB.php';
require 'app/Core/DataJudClient.php';
require 'app/Services/DocumentEnricherService.php';

use App\Core\DB;
use App\Services\DocumentEnricherService;

// Initialize DB
DB::init($config);

// CLI Arguments
$options = getopt("l:", ["limit:"]);
$limit = (int)($options['limit'] ?? $options['l'] ?? 50);

echo "Starting Document Enrichment...\n";
echo "Limit: $limit records per batch\n";

$service = new DocumentEnricherService($config);
$stats = $service->enrichBatch($limit);

echo "\n--------------------------------------------------\n";
echo "ENRICHMENT REPORT\n";
echo "--------------------------------------------------\n";
echo "Processed: " . $stats['processed'] . "\n";
echo "Enriched:  " . $stats['enriched'] . "\n";
echo "Cached:    " . $stats['cached'] . "\n";
echo "Failed:    " . $stats['failed'] . "\n";
echo "Duration:  " . round($stats['duration'], 2) . "s\n";
echo "--------------------------------------------------\n";
echo "Done.\n";
