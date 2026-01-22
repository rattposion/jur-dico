<?php
require 'vendor/autoload.php';
require 'config/config.php';
use App\Core\DB;

DB::init($config);

echo "--- Buscando Classes Específicas ---\n";
$sql = "SELECT DISTINCT siglaClasse, descricaoClasse FROM records 
        WHERE descricaoClasse LIKE '%Civil Pública%' 
           OR descricaoClasse LIKE '%Popular%' 
           OR descricaoClasse LIKE '%Segurança Coletivo%' 
           OR siglaClasse IN ('ACOR', 'DTXT', 'ACP', 'AP', 'MSC', 'MS')
           OR descricaoClasse LIKE '%Estrutural%'";
$stmt = DB::pdo()->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

echo "\n--- Buscando 'ACOR' e 'DTXT' em qualquer lugar ---\n";
// Verifica se ACOR ou DTXT aparecem como sigla exata
$sql2 = "SELECT DISTINCT siglaClasse FROM records WHERE siglaClasse LIKE '%ACOR%' OR siglaClasse LIKE '%DTXT%'";
$stmt2 = DB::pdo()->query($sql2);
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($rows2);
