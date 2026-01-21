<?php
/**
 * Script to clean (truncate) all database tables.
 * Usage: php scripts/clean_db.php
 */

declare(strict_types=1);

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
use PDO;

echo "[INFO] Connecting to database...\n";
DB::init($config);
$pdo = DB::pdo();

echo "[INFO] Cleaning database...\n";

try {
    // Disable FK checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $tables = [
        'users',
        'records',
        'categories',
        'audit_logs',
        'notifications',
        'notification_reads',
        'conversations',
        'conversation_users',
        'messages',
        'attachments',
        'typing',
        'user_api_keys'
    ];

    foreach ($tables as $table) {
        try {
            // Check if table exists before truncating
            $check = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($check->rowCount() > 0) {
                $pdo->exec("TRUNCATE TABLE `$table`");
                echo " - Truncated: $table\n";
            } else {
                echo " - Skipped (not found): $table\n";
            }
        } catch (PDOException $e) {
            echo " - Error truncating $table: " . $e->getMessage() . "\n";
        }
    }

    // Enable FK checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "\n[SUCCESS] Database cleaned successfully.\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Database error: " . $e->getMessage() . "\n";
}
