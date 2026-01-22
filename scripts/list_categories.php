<?php
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
require $basePath . '/config/config.php';

use App\Core\DB;
use App\Models\Category;

DB::init($config);

$cats = Category::all();
if (empty($cats)) {
    echo "No categories found.\n";
} else {
    foreach ($cats as $c) {
        echo "ID: {$c['id']} | Name: {$c['name']}\n";
    }
}
