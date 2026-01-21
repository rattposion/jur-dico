<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data + ['config' => $this->config]);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}

