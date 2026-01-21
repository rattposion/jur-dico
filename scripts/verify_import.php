<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Core/DB.php';

use App\Core\DB;

DB::init($config);

echo "Verificando registros importados...\n";

// Query to find records matching terms
$sql = "SELECT id, numeroProcesso, descricaoClasse, ementa FROM records 
        WHERE ementa LIKE :term1 OR ementa LIKE :term2 
        OR descricaoClasse LIKE :term1 OR descricaoClasse LIKE :term2
        LIMIT 5";

$stmt = DB::pdo()->prepare($sql);
$stmt->execute([
    ':term1' => '%ação civil pública%',
    ':term2' => '%ministério público%'
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Encontrados " . count($results) . " registros correspondentes no banco:\n";
foreach ($results as $r) {
    echo "ID: {$r['id']} | Proc: {$r['numeroProcesso']} | Classe: {$r['descricaoClasse']}\n";
    // echo "Ementa: " . substr($r['ementa'], 0, 100) . "...\n";
}
