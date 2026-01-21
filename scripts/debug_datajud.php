<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Core/DataJudClient.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\DataJudClient;

$client = new DataJudClient($config);

echo "Testando conexão com DataJud...\n";

// Test 1: Simple term search
$term = "direito";
echo "Buscando por '$term'...\n";
$results = $client->searchByQuery($term);
echo "Encontrados: " . count($results) . "\n";

if (empty($results)) {
    echo "Nenhum resultado. Verifique a chave de API ou a URL.\n";
    // Tentar imprimir erro do curl se possível (precisaria alterar a classe para expor erro, mas vamos assumir que logou vazio)
} else {
    echo "Primeiro resultado: " . ($results[0]['_source']['numeroProcesso'] ?? 'N/A') . "\n";
}

// Test 2: Phrase search without AND
$query2 = '"ação civil pública"';
echo "\nBuscando por '$query2'...\n";
$results2 = $client->searchByQuery($query2);
echo "Encontrados: " . count($results2) . "\n";

// Test 3: AND without quotes
$query3 = 'ação civil pública AND ministério público';
echo "\nBuscando por '$query3'...\n";
$results3 = $client->searchByQuery($query3);
echo "Encontrados: " . count($results3) . "\n";

// Test 4: OR query
$query4 = 'ação civil pública OR ministério público';
echo "\nBuscando por '$query4'...\n";
$results4 = $client->searchByQuery($query4);
echo "Encontrados: " . count($results4) . "\n";
