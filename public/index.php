<?php
declare(strict_types=1);

session_start();

$basePath = dirname(__DIR__);

spl_autoload_register(function ($class) use ($basePath) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = str_replace('App\\', '', $class);
    $file = $basePath . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

require $basePath . '/config/config.php';

App\Core\DB::init($config);

$router = new App\Core\Router($config);

$router->get('/', [App\Controllers\HomeController::class, 'index']);

$router->get('/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->get('/register', [App\Controllers\AuthController::class, 'showRegister']);
$router->post('/register', [App\Controllers\AuthController::class, 'register']);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);

$router->get('/admin', [App\Controllers\AdminController::class, 'index']);
$router->post('/admin/import', [App\Controllers\AdminController::class, 'import']);
$router->post('/admin/upload', [App\Controllers\AdminController::class, 'upload']);
$router->post('/admin/category/create', [App\Controllers\AdminController::class, 'createCategory']);
$router->post('/admin/category/assign', [App\Controllers\AdminController::class, 'assignCategory']);
$router->get('/admin/dashboard/data', [App\Controllers\AdminController::class, 'dashboardData']);
$router->get('/admin/export/csv', [App\Controllers\AdminController::class, 'exportCsv']);
$router->get('/admin/export/pdf', [App\Controllers\AdminController::class, 'exportPdf']);
$router->post('/admin/ai/classify', [App\Controllers\AdminController::class, 'classifyAI']);

// AI Analysis Routes
$router->get('/admin/ai/analysis', [App\Controllers\AIAnalysisController::class, 'index']);
$router->get('/admin/ai/analysis/report', [App\Controllers\AIAnalysisController::class, 'report']);
$router->get('/admin/ai/analysis/details', [App\Controllers\AIAnalysisController::class, 'details']);

// Bulk Operations
$router->get('/admin/bulk-ops', [App\Controllers\BulkOpsController::class, 'index']);
$router->post('/admin/bulk-ops/preview', [App\Controllers\BulkOpsController::class, 'preview']);
$router->post('/admin/bulk-ops/execute', [App\Controllers\BulkOpsController::class, 'execute']);

$router->get('/api/records', [App\Controllers\ApiController::class, 'records']);
$router->get('/api/categories', [App\Controllers\ApiController::class, 'categories']);

$router->get('/records', [App\Controllers\RecordsController::class, 'index']);
$router->get('/records/view/{id}', [App\Controllers\RecordsController::class, 'show']);
$router->get('/records/advanced', [App\Controllers\RecordsController::class, 'advanced']);
$router->get('/records/autocomplete', [App\Controllers\RecordsController::class, 'autocomplete']);
$router->post('/records/save-search', [App\Controllers\RecordsController::class, 'saveSearch']);
$router->get('/records/saved-searches', [App\Controllers\RecordsController::class, 'getSavedSearches']);
$router->post('/records/delete-search', [App\Controllers\RecordsController::class, 'deleteSavedSearch']);
$router->get('/records/history', [App\Controllers\RecordsController::class, 'getHistory']);
$router->get('/records/export/csv', [App\Controllers\RecordsController::class, 'exportCsv']);
$router->get('/records/export/pdf', [App\Controllers\RecordsController::class, 'exportPdf']);
$router->post('/records/update-category', [App\Controllers\RecordsController::class, 'updateCategory']);
$router->post('/records/delete', [App\Controllers\RecordsController::class, 'delete']);

$router->get('/notifications/active', [App\Controllers\NotificationsController::class, 'active']);
$router->post('/notifications/dismiss', [App\Controllers\NotificationsController::class, 'dismiss']);

$router->get('/chat', [App\Controllers\ChatController::class, 'index']);
$router->get('/chat/messages', [App\Controllers\ChatController::class, 'messages']);
$router->post('/chat/send', [App\Controllers\ChatController::class, 'send']);
$router->post('/chat/upload', [App\Controllers\ChatController::class, 'upload']);
$router->post('/chat/typing', [App\Controllers\ChatController::class, 'typing']);
$router->post('/chat/clear', [App\Controllers\ChatController::class, 'clear']);
$router->get('/chat/new', [App\Controllers\ChatController::class, 'create']);
$router->post('/chat/analyze', [App\Controllers\ChatController::class, 'analyzeAI']);
$router->post('/chat/clear', [App\Controllers\ChatController::class, 'clear']);
$router->post('/chat/delete-conversation', [App\Controllers\ChatController::class, 'deleteConversation']);

$router->get('/settings/api-keys', [App\Controllers\ApiKeysController::class, 'index']);
$router->post('/settings/api-keys/save', [App\Controllers\ApiKeysController::class, 'save']);
$router->post('/settings/api-keys/test', [App\Controllers\ApiKeysController::class, 'test']);
$router->post('/settings/api-keys/delete', [App\Controllers\ApiKeysController::class, 'delete']);
$router->post('/settings/api-keys/activate', [App\Controllers\ApiKeysController::class, 'activate']);

$router->get('/ADVOGADOS', [App\Controllers\HomeController::class, 'index']);
$router->get('/ADVOGADOS/records', [App\Controllers\RecordsController::class, 'index']);
$router->get('/ADVOGADOS/admin', [App\Controllers\AdminController::class, 'index']);
$router->get('/ADVOGADOS/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->get('/ADVOGADOS/register', [App\Controllers\AuthController::class, 'showRegister']);
$router->get('/ADVOGADOS/chat', [App\Controllers\ChatController::class, 'index']);
$router->get('/ADVOGADOS/chat/messages', [App\Controllers\ChatController::class, 'messages']);
$router->post('/ADVOGADOS/chat/send', [App\Controllers\ChatController::class, 'send']);
$router->post('/ADVOGADOS/chat/upload', [App\Controllers\ChatController::class, 'upload']);
$router->get('/ADVOGADOS/chat/typing', [App\Controllers\ChatController::class, 'typing']);
$router->post('/ADVOGADOS/chat/typing', [App\Controllers\ChatController::class, 'typing']);
$router->post('/ADVOGADOS/chat/analyze', [App\Controllers\ChatController::class, 'analyzeAI']);
$router->post('/ADVOGADOS/chat/clear', [App\Controllers\ChatController::class, 'clear']);
$router->post('/ADVOGADOS/chat/delete-conversation', [App\Controllers\ChatController::class, 'deleteConversation']);

$router->dispatch();
