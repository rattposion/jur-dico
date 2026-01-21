<?php
declare(strict_types=1);

require 'config/config.php';
require 'app/Core/DataJudClient.php';
require 'app/Services/JsonUpdateService.php';

use App\Services\JsonUpdateService;

// CLI Arguments
$options = getopt("i:o:", ["input:", "output:"]);
$input = $options['input'] ?? $options['i'] ?? null;
$output = $options['output'] ?? $options['o'] ?? null;

if (!$input) {
    die("Usage: php scripts/update_json_file.php --input <file.json> [--output <file_updated.json>]\n");
}

if (!$output) {
    $info = pathinfo($input);
    $output = $info['dirname'] . '/' . $info['filename'] . '_updated.' . $info['extension'];
}

echo "Starting JSON Update...\n";
echo "Input: $input\n";
echo "Output: $output\n";

try {
    $service = new JsonUpdateService($config);
    $stats = $service->updateJsonFile($input, $output);

    echo "\n--------------------------------------------------\n";
    echo "UPDATE REPORT\n";
    echo "--------------------------------------------------\n";
    echo "Total Items: " . $stats['total'] . "\n";
    echo "Processed:   " . $stats['processed'] . "\n";
    echo "Updated:     " . $stats['updated'] . "\n";
    echo "Unchanged:   " . $stats['unchanged'] . "\n";
    echo "Failed:      " . $stats['failed'] . "\n";
    echo "Duration:    " . round($stats['duration'], 2) . "s\n";
    echo "--------------------------------------------------\n";
    
    if (!empty($stats['logs'])) {
        echo "Errors/Warnings:\n";
        foreach (array_slice($stats['logs'], 0, 10) as $log) {
            echo " - $log\n";
        }
        if (count($stats['logs']) > 10) {
            echo " ... and " . (count($stats['logs']) - 10) . " more.\n";
        }
    }
    
    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
