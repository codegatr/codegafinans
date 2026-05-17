<?php

declare(strict_types=1);

namespace App\Models;

final class Updater
{
    public static function status(): array
    {
        $enabled = env_value('UPDATE_ENABLED', 'false') === 'true';
        $branch = env_value('GITHUB_BRANCH', 'main') ?: 'main';
        $repo = env_value('GITHUB_REPO', 'codegatr/codegafinans') ?: 'codegatr/codegafinans';
        $isGit = is_dir(BASE_PATH . '/.git');

        $status = [
            'enabled' => $enabled,
            'repo' => $repo,
            'branch' => $branch,
            'is_git' => $isGit,
            'current' => self::run('rev-parse --short HEAD')['output'] ?? null,
            'remote' => null,
            'behind' => null,
            'dirty' => false,
            'message' => '',
            'log' => self::latestLog(),
        ];

        if (!$enabled) {
            $status['message'] = 'Guncelleme sistemi kapali. UPDATE_ENABLED=true yapin.';
            return $status;
        }
        if (!$isGit) {
            $status['message'] = 'Bu kurulum Git deposu degil. DirectAdmin Git ile klonlama onerilir.';
            return $status;
        }

        $dirty = self::run('status --porcelain');
        $status['dirty'] = trim($dirty['output'] ?? '') !== '';

        self::run('fetch origin ' . escapeshellarg($branch));
        $remote = self::run('rev-parse --short origin/' . $branch);
        $behind = self::run('rev-list --count HEAD..origin/' . $branch);
        $status['remote'] = trim($remote['output'] ?? '') ?: null;
        $status['behind'] = is_numeric(trim($behind['output'] ?? '')) ? (int) trim($behind['output']) : null;
        $status['message'] = (($status['behind'] ?? 0) > 0) ? 'Yeni surum hazir.' : 'Sistem guncel gorunuyor.';

        return $status;
    }

    public static function apply(string $token): array
    {
        $expected = env_value('UPDATE_TOKEN', '');
        if (env_value('UPDATE_ENABLED', 'false') !== 'true') {
            return self::result(false, 'Guncelleme sistemi kapali.');
        }
        if (!$expected || !hash_equals($expected, $token)) {
            return self::result(false, 'Guncelleme token bilgisi hatali.');
        }
        if (!is_dir(BASE_PATH . '/.git')) {
            return self::result(false, 'Bu kurulum Git deposu degil.');
        }

        self::ensureStorage();
        $lockFile = BASE_PATH . '/storage/update.lock';
        if (is_file($lockFile) && filemtime($lockFile) > time() - 600) {
            return self::result(false, 'Baska bir guncelleme islemi devam ediyor.');
        }

        file_put_contents($lockFile, (string) time());
        try {
            $branch = env_value('GITHUB_BRANCH', 'main') ?: 'main';
            $before = trim(self::run('rev-parse --short HEAD')['output'] ?? '');
            $dirty = trim(self::run('status --porcelain')['output'] ?? '');
            if ($dirty !== '') {
                return self::result(false, 'Calisma alaninda commitlenmemis degisiklik var. Once temizleyin.');
            }

            $fetch = self::run('fetch origin ' . escapeshellarg($branch));
            if (!$fetch['ok']) {
                return self::result(false, 'GitHub fetch basarisiz: ' . $fetch['output']);
            }

            $pull = self::run('pull --ff-only origin ' . escapeshellarg($branch));
            if (!$pull['ok']) {
                return self::result(false, 'Git pull basarisiz: ' . $pull['output']);
            }

            \App\Core\Database::migrate();
            $after = trim(self::run('rev-parse --short HEAD')['output'] ?? '');
            $message = "Guncelleme tamamlandi. {$before} -> {$after}";
            self::writeLog($message . PHP_EOL . $pull['output']);
            return self::result(true, $message);
        } finally {
            @unlink($lockFile);
        }
    }

    public static function directAdminCommands(): array
    {
        $repo = env_value('GITHUB_REPO', 'codegatr/codegafinans') ?: 'codegatr/codegafinans';
        $branch = env_value('GITHUB_BRANCH', 'main') ?: 'main';

        return [
            "cd ~/domains/finans.codega.com.tr",
            "git clone -b {$branch} https://github.com/{$repo}.git codegafinans",
            "cd codegafinans",
            "cp .env.example .env",
            "mkdir -p storage",
            "chmod 775 storage",
            "cd ..",
            "mv public_html public_html_backup_$(date +%Y%m%d%H%M%S)",
            "ln -s codegafinans/public_html public_html",
        ];
    }

    private static function run(string $arguments): array
    {
        if (!function_exists('exec')) {
            return ['ok' => false, 'output' => 'exec fonksiyonu kapali.'];
        }

        $binary = env_value('GIT_BINARY', 'git') ?: 'git';
        $command = $binary . ' -C ' . escapeshellarg(BASE_PATH) . ' ' . $arguments . ' 2>&1';
        $lines = [];
        $code = 0;
        exec($command, $lines, $code);

        return ['ok' => $code === 0, 'output' => trim(implode(PHP_EOL, $lines)), 'code' => $code];
    }

    private static function result(bool $ok, string $message): array
    {
        self::writeLog(($ok ? 'OK: ' : 'HATA: ') . $message);
        return ['ok' => $ok, 'message' => $message];
    }

    private static function writeLog(string $message): void
    {
        self::ensureStorage();
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents(BASE_PATH . '/storage/update.log', $line, FILE_APPEND);
    }

    private static function ensureStorage(): void
    {
        if (!is_dir(BASE_PATH . '/storage')) {
            mkdir(BASE_PATH . '/storage', 0775, true);
        }
    }

    private static function latestLog(): string
    {
        $file = BASE_PATH . '/storage/update.log';
        if (!is_file($file)) {
            return '';
        }
        $content = file_get_contents($file);
        return substr((string) $content, -3000);
    }
}
