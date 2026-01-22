<?php
require 'vendor/autoload.php';
require 'config/config.php';
use App\Core\DB;
use App\Models\Category;

DB::init($config);

$categories = [
    "Processo Estrutural ACOR",
    "Processo Estrutural DTXT",
    "Ação Civil Pública",
    "Ação Popular",
    "Mandado De Segurança Coletivo"
];

foreach ($categories as $name) {
    $id = Category::findOrCreateByName($name);
    echo "Category '$name' is ID: $id\n";
}
