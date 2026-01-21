<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class SavedSearch
{
    public static function create(int $userId, string $name, array $criteria): bool
    {
        $stmt = DB::pdo()->prepare('INSERT INTO saved_searches (user_id, name, criteria, created_at) VALUES (:uid, :name, :criteria, NOW())');
        return $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':criteria' => json_encode($criteria, JSON_UNESCAPED_UNICODE)
        ]);
    }

    public static function listByUser(int $userId): array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM saved_searches WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['criteria'] = json_decode($r['criteria'], true);
        }
        return $rows;
    }

    public static function delete(string $id, int $userId): bool
    {
        $stmt = DB::pdo()->prepare('DELETE FROM saved_searches WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
