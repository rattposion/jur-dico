<?php
declare(strict_types=1);

namespace App\Core;

class CSRF
{
    public static function generate(array $config): string
    {
        $key = 'csrf_token';
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$key];
    }

    public static function verify(array $config, ?string $token): bool
    {
        $key = 'csrf_token';
        if (empty($_SESSION[$key]) || empty($token)) {
            return false;
        }
        
        $valid = hash_equals($_SESSION[$key], $token);
        
        // Previously we might have unset the token here for one-time use,
        // but for better UX in async apps (like chat), we keep it per session.
        // if ($valid) {
        //    unset($_SESSION[$key]);
        // }
        
        return $valid;
    }
}
