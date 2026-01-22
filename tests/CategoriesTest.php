<?php
// Simple Integration Test for Category Model

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
require $basePath . '/config/config.php';

use App\Core\DB;
use App\Models\Category;

// Init DB
try {
    DB::init($config);
} catch (Exception $e) {
    die("DB Init Failed: " . $e->getMessage());
}

echo "Starting Categories Test...\n";

// 1. Create
echo "1. Testing Create...\n";
$testName = "Test Category " . uniqid();
Category::create($testName, "Description for test");

// Verify
$cats = Category::all();
$found = null;
foreach ($cats as $c) {
    if ($c['name'] === $testName) {
        $found = $c;
        break;
    }
}

if ($found) {
    echo "PASS: Category created (ID: {$found['id']})\n";
} else {
    echo "FAIL: Category not found\n";
    exit(1);
}

// 2. Update
echo "2. Testing Update...\n";
$newName = $testName . " Updated";
Category::update((int)$found['id'], $newName, "New Description");

$check = Category::find((int)$found['id']);
if ($check && $check['name'] === $newName) {
    echo "PASS: Category updated\n";
} else {
    echo "FAIL: Update failed\n";
    var_dump($check);
    exit(1);
}

// 3. Delete
echo "3. Testing Delete...\n";
Category::delete((int)$found['id']);
$check2 = Category::find((int)$found['id']);

if (!$check2) {
    echo "PASS: Category deleted\n";
} else {
    echo "FAIL: Delete failed\n";
    exit(1);
}

echo "All tests passed!\n";
