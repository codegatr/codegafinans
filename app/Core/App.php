<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function view(string $name, array $data = []): string
    {
        $viewFile = BASE_PATH . '/app/Views/' . str_replace('.', '/', $name) . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$name}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        return (string) ob_get_clean();
    }

    public function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $content = $this->view($view, $data);
        echo $this->view($layout, array_merge($data, ['content' => $content]));
    }
}
