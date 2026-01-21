<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(string $name, string $email, string $password): void
    {
        $stmt = DB::pdo()->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, :created_at)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password,
            ':role' => 'admin',
            ':created_at' => date('c'),
        ]);
    }
}

