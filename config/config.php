<?php
declare(strict_types=1);

$config = [
    'env' => 'prod',
    'app_name' => 'DV Plataforma',
    'base_url' => '/',
    'db' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'dv_app',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'security' => [
        'csrf_key' => 'csrf_token',
    ],
    'import' => [
        'json_path' => dirname(__DIR__) . '/20251231 (1).json',
        'batch_size' => 100,
    ],
    'ai' => [
        'provider' => 'openai',
        'openai_model' => 'gpt-4.1-mini',
        'gemini_model' => 'gemini-1.5-flash',
    ],
    'datajud' => [
        'url' => 'https://api-publica.datajud.cnj.jus.br/api_publica_stj/_search',
        'api_key' => 'cDZHYzlZa0JadVREZDJCendQbXY6SkJlTzNjLV9TRENyQk1RdnFKZGRQdw==',
    ],
];
