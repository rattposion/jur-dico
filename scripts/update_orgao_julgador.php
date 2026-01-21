<?php
/**
 * Script to update "nomeOrgaoJulgador" for records where it is currently "PRIMEIRA SEÇÃO".
 * Usage: php scripts/update_orgao_julgador.php
 */

declare(strict_types=1);

// 1. Bootstrap (Autoloader & Config)
$basePath = dirname(__DIR__);

spl_autoload_register(function ($class) use ($basePath) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = str_replace('App\\', '', $class);
    $file = $basePath . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

require $basePath . '/config/config.php';

use App\Core\DB;
use App\Services\RecordUpdater;

// 2. Initialize DB
echo "[INFO] Connecting to database...\n";
DB::init($config);

echo "[INFO] Starting auto-correction via RecordUpdater service...\n";

$updater = new RecordUpdater($config);
$stats = $updater->fixOrgaoJulgador('PRIMEIRA SEÇÃO');
$remaining = $updater->validateCompleteness('PRIMEIRA SEÇÃO');

// 5. Report
echo "\n--------------------------------------------------\n";
echo "UPDATE SUMMARY REPORT\n";
echo "--------------------------------------------------\n";
echo "Total Records Checked: " . $stats['processed'] . "\n";
echo "Updated:               " . $stats['updated'] . "\n";
echo "Unchanged:             " . $stats['unchanged'] . "\n";
echo "Not Found in API:      " . $stats['not_found_api'] . "\n";
echo "Errors:                " . $stats['error'] . "\n";
echo "Remaining Invalid:     " . $remaining . "\n";
echo "--------------------------------------------------\n";
echo "Done.\n";
