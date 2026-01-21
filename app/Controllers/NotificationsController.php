<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Models\Notification;
use App\Models\Audit;

class NotificationsController extends Controller
{
    public function active(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        $items = Notification::activeForUser((int)$u['id']);
        header('Content-Type: application/json');
        echo json_encode(['items' => $items]);
    }

    public function dismiss(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'csrf']); return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        $u = Auth::user();
        Notification::markRead((int)$u['id'], $id);
        Audit::log((int)$u['id'], 'dismiss_notification', null, (string)$id);
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true]);
    }
}

