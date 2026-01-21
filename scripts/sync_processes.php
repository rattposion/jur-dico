<?php
declare(strict_types=1);

require 'config/config.php';
require 'app/Core/DB.php';
require 'app/Core/DataJudClient.php';
require 'app/Services/ProcessSyncService.php';
require 'app/Models/Record.php';

use App\Core\DB;
use App\Services\ProcessSyncService;

// Initialize Database
DB::init($config);

// CLI Arguments
$options = getopt("l:", ["limit:"]);
$limit = (int)($options['limit'] ?? $options['l'] ?? 0);

echo "Starting Process Sync...\n";
if ($limit > 0) {
    echo "Limit: $limit records\n";
} else {
    echo "Mode: Full Sync (Unlimited)\n";
}

$service = new ProcessSyncService($config);
$stats = $service->syncAll($limit);

echo "\n--------------------------------------------------\n";
echo "SYNC REPORT\n";
echo "--------------------------------------------------\n";
echo "Fetched:   " . $stats['fetched'] . "\n";
echo "Upserted:  " . $stats['upserted'] . "\n";
echo "Errors:    " . $stats['errors'] . "\n";
echo "Duration:  " . round($stats['duration'], 2) . "s\n";
echo "--------------------------------------------------\n";
echo "Done.\n";
