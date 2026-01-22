<?php
require 'vendor/autoload.php';
require 'config/config.php';
use App\Core\DB;

DB::init($config);

$sql = "SELECT DISTINCT siglaClasse, descricaoClasse FROM records";
$stmt = DB::pdo()->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
