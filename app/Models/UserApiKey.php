<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class UserApiKey
{
    public static function getActive(int $userId): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT provider, enc_key, model, created_at, updated_at FROM user_api_keys WHERE user_id = :uid AND active = 1 LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getByProvider(int $userId, string $provider): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT provider, enc_key, model, created_at, updated_at FROM user_api_keys WHERE user_id = :uid AND provider = :p LIMIT 1');
        $stmt->execute([':uid' => $userId, ':p' => $provider]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function save(int $userId, string $provider, string $encKey, ?string $model = null): void
    {
        $stmt = DB::pdo()->prepare('REPLACE INTO user_api_keys (user_id, provider, enc_key, model, created_at, updated_at, active) VALUES (:uid, :p, :k, :m, :c, :u, 1)');
        $stmt->execute([
            ':uid' => $userId,
            ':p' => $provider,
            ':k' => $encKey,
            ':m' => $model,
            ':c' => date('c'),
            ':u' => date('c')
        ]);
    }

    public static function delete(int $userId, string $provider): void
    {
        DB::pdo()->prepare('DELETE FROM user_api_keys WHERE user_id = :uid AND provider = :p')->execute([':uid' => $userId, ':p' => $provider]);
    }

    public static function setActive(int $userId, string $provider): void
    {
        DB::pdo()->prepare('UPDATE user_api_keys SET active = 0 WHERE user_id = :uid')->execute([':uid' => $userId]);
        DB::pdo()->prepare('UPDATE user_api_keys SET active = 1 WHERE user_id = :uid AND provider = :p')->execute([':uid' => $userId, ':p' => $provider]);
    }

    public static function allByUser(int $userId): array
    {
        $stmt = DB::pdo()->prepare('SELECT provider, enc_key, model, created_at, updated_at, active FROM user_api_keys WHERE user_id = :uid ORDER BY provider');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mask(string $key): string
    {
        $len = strlen($key);
        if ($len <= 8) return $key;
        return substr($key,0,4) . str_repeat('*', max(0,$len-8)) . substr($key,-4);
    }

    public static function encrypt(string $plain): string
    {
        $k = getenv('FILE_ENC_KEY') ?: '';
        $iv = substr(hash('sha256',$k,true),0,16);
        $c = $k ? openssl_encrypt($plain, 'aes-256-cbc', $k, OPENSSL_RAW_DATA, $iv) : $plain;
        return base64_encode($c);
    }

    public static function decrypt(string $enc): string
    {
        $k = getenv('FILE_ENC_KEY') ?: '';
        $iv = substr(hash('sha256',$k,true),0,16);
        $raw = base64_decode($enc, true) ?: '';
        return $k ? (openssl_decrypt($raw, 'aes-256-cbc', $k, OPENSSL_RAW_DATA, $iv) ?: '') : $raw;
    }
}

