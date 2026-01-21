<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Audit
{
    public static function log(int $userId, string $action, ?string $recordId, ?string $details): void
    {
        $stmt = DB::pdo()->prepare('INSERT INTO audit_logs (user_id, action, record_id, details, created_at) VALUES (:user_id, :action, :record_id, :details, :created_at)');
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':record_id' => $recordId,
            ':details' => $details,
            ':created_at' => date('c'),
        ]);
    }
}

