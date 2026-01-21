<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data);
        $base = dirname(__DIR__, 2);
        $layout = $base . '/app/Views/layout.php';
        $file = $base . '/app/Views/' . $template . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            echo 'view';
            return;
        }
        ob_start();
        include $file;
        $content = ob_get_clean();
        include $layout;
    }

    public static function e(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

