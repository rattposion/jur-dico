<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class Category
{
    public static function create(string $name): void
    {
        $stmt = DB::pdo()->prepare('INSERT INTO categories (name, created_at) VALUES (:name, :created_at)');
        $stmt->execute([':name' => $name, ':created_at' => date('c')]);
    }

    public static function all(): array
    {
        $stmt = DB::pdo()->query('SELECT id, name FROM categories ORDER BY name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT id, name FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findOrCreateByName(string $name): int
    {
        $stmt = DB::pdo()->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['id'])) return (int)$row['id'];
        self::create($name);
        $id = (int)DB::pdo()->lastInsertId();
        return $id;
    }
}
