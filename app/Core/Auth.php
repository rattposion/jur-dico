<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user) return false;
        if (!password_verify($password, $user['password'])) return false;
        $_SESSION['user'] = ['id' => (int)$user['id'], 'email' => $user['email'], 'name' => $user['name'], 'role' => $user['role']];
        return true;
    }

    public static function register(string $name, string $email, string $password): bool
    {
        if (User::findByEmail($email)) return false;
        $hash = password_hash($password, PASSWORD_DEFAULT);
        User::create($name, $email, $hash);
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function requireAuth(): void
    {
        if (!self::user()) {
            header('Location: /login');
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        $u = self::user();
        if (!$u || ($u['role'] ?? '') !== $role) {
            http_response_code(403);
            echo '403';
            exit;
        }
    }
}
