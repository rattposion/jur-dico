<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class Notification
{
    public static function activeForUser(int $userId): array
    {
        $sql = 'SELECT n.id, n.title, n.message FROM notifications n
                LEFT JOIN notification_reads r ON r.notification_id = n.id AND r.user_id = :uid
                WHERE n.active = 1 AND r.notification_id IS NULL
                ORDER BY n.created_at DESC';
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function markRead(int $userId, int $notificationId): void
    {
        $stmt = DB::pdo()->prepare('INSERT INTO notification_reads (user_id, notification_id, read_at) VALUES (:uid, :nid, :read_at)');
        $stmt->execute([':uid' => $userId, ':nid' => $notificationId, ':read_at' => date('c')]);
    }

    public static function create(string $title, string $message, bool $active = true): void
    {
        $stmt = DB::pdo()->prepare('INSERT INTO notifications (title, message, active, created_at) VALUES (:title, :message, :active, :created_at)');
        $stmt->execute([':title' => $title, ':message' => $message, ':active' => $active ? 1 : 0, ':created_at' => date('c')]);
    }
}

