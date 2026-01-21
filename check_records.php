<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/DB.php';

use App\Core\DB;

DB::init($config);

// Contar total
$stmt = DB::pdo()->query("SELECT COUNT(*) FROM records");
$total = $stmt->fetchColumn();

echo "Total de registros no banco: " . $total . "\n";

// Pegar os 5 mais recentes
$stmt = DB::pdo()->query("SELECT id, numeroProcesso, dataDecisao, siglaClasse FROM records ORDER BY dataDecisao DESC LIMIT 5");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Top 5 mais recentes:\n";
foreach ($items as $r) {
    echo "ID: {$r['id']} | Proc: {$r['numeroProcesso']} | Data: {$r['dataDecisao']} | Classe: {$r['siglaClasse']}\n";
}
