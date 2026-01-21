<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class SearchHistory
{
    public static function log(int $userId, ?string $term, array $criteria): void
    {
        // Avoid duplicates if recent (optional optimization)
        $stmt = DB::pdo()->prepare('INSERT INTO search_history (user_id, term, criteria, searched_at) VALUES (:uid, :term, :criteria, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':term' => $term,
            ':criteria' => json_encode($criteria, JSON_UNESCAPED_UNICODE)
        ]);
    }

    public static function getRecent(int $userId, int $limit = 10): array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM search_history WHERE user_id = :uid ORDER BY searched_at DESC LIMIT :limit');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['criteria'] = json_decode($r['criteria'], true);
        }
        return $rows;
    }
}
