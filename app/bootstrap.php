<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Database;
use App\Core\Session;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

function env_value(string $key, ?string $default = null): ?string
{
    static $env = null;
    if ($env === null) {
        $env = [];
        $file = BASE_PATH . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $env[trim($name)] = trim(trim($value), "\"'");
            }
        }
    }

    return $_ENV[$key] ?? getenv($key) ?: ($env[$key] ?? $default);
}

function app(): App
{
    return App::instance();
}

function db(): PDO
{
    return Database::connection();
}

function view(string $name, array $data = []): string
{
    return app()->view($name, $data);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(mixed $value, string $currency = 'TL'): string
{
    return number_format((float) $value, 2, ',', '.') . ' ' . $currency;
}

function csrf_token(): string
{
    return Session::csrfToken();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::pull('old', []);
    Session::put('old', $old);
    return $old[$key] ?? $default;
}

function auth_user(): ?array
{
    return Session::get('user');
}

Session::start();
Database::migrate();
