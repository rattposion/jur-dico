<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class Deadline
{
    public static function create(int $userId, string $description, ?string $date, ?int $convId = null, ?string $procNum = null): int
    {
        $sql = "INSERT INTO deadlines (user_id, conversation_id, process_number, description, due_date, created_at) 
                VALUES (:uid, :cid, :pnum, :desc, :date, NOW())";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $convId,
            ':pnum' => $procNum,
            ':desc' => $description,
            ':date' => $date // Format 'Y-m-d H:i:s' expected
        ]);
        return (int)DB::pdo()->lastInsertId();
    }

    public static function getActive(int $userId): array
    {
        $sql = "SELECT * FROM deadlines WHERE user_id = :uid AND status = 'pending' ORDER BY due_date ASC";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function dismiss(int $id, int $userId): bool
    {
        $sql = "UPDATE deadlines SET status = 'completed' WHERE id = :id AND user_id = :uid";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
