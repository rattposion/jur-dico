<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->get('/api/status', [\App\Controllers\ApiStatusController::class, 'check']);
    }

    public function get(string $path, $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $path = $this->normalize($uri);

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            foreach ($this->routes[$method] ?? [] as $route => $h) {
                $params = $this->match($route, $path);
                if ($params !== null) {
                    $handler = $h;
                    $_GET = array_merge($_GET, $params);
                    break;
                }
            }
        }

        if (!$handler) {
            http_response_code(404);
            echo '404';
            return;
        }

        if (is_array($handler)) {
            $controller = new $handler[0]($this->config);
            $action = $handler[1];
            $controller->$action();
            return;
        }

        if (is_callable($handler)) {
            $handler();
        }
    }

    private function normalize(string $path): string
    {
        return rtrim($path, '/') ?: '/';
    }

    private function match(string $route, string $path): ?array
    {
        $rParts = explode('/', trim($route, '/'));
        $pParts = explode('/', trim($path, '/'));
        if (count($rParts) !== count($pParts)) return null;
        $params = [];
        foreach ($rParts as $i => $part) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $m)) {
                $params[$m[1]] = $pParts[$i];
                continue;
            }
            if ($part !== $pParts[$i]) return null;
        }
        return $params;
    }
}

