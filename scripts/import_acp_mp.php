<?php

require_once dirname(__DIR__) . '/config/config.php';

// Simple Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Services\ProcessSyncService;
use App\Core\DB;

// Inicializa conexão com banco de dados
DB::init($config);

echo "=== Iniciando importação massiva do DataJud ===\n";
echo "Query: ação civil pública AND ministério público\n";

$service = new ProcessSyncService($config);

// Query específica solicitada (sem aspas nas frases para flexibilidade testada)
$query = 'ação civil pública AND ministério público';

try {
    // Importar sem limite (0)
    $stats = $service->importByQuery($query, 0);
    
    echo "\n=== Importação Concluída ===\n";
    echo "Total Baixado: " . $stats['fetched'] . "\n";
    echo "Total Salvo/Atualizado: " . $stats['upserted'] . "\n";
    echo "Erros: " . $stats['errors'] . "\n";
    echo "Duração: " . round($stats['duration'], 2) . " segundos\n";
    
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
