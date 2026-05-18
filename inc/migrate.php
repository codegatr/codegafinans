<?php
/**
 * CODEGA Finans - Migration Runner
 *
 * migrations/ klasöründeki .sql dosyalarını sıralı çalıştırır,
 * uygulanmış olanları cf_migrations tablosunda izler.
 *
 * Bütün migration dosyaları idempotent yazılmalıdır:
 *   - CREATE TABLE IF NOT EXISTS
 *   - INSERT IGNORE
 *   - ALTER TABLE öncesi INFORMATION_SCHEMA ile koruma
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function cf_migrate_all(): array
{
    $log = [];
    try {
        // İzleme tablosunu garantile
        db()->exec("
            CREATE TABLE IF NOT EXISTS `" . t('migrations') . "` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `file` VARCHAR(200) NOT NULL,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_migrations_file` (`file`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $files = glob(CF_MIGRATIONS . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);

        foreach ($files as $path) {
            $name = basename($path);

            $exists = db_one(
                'SELECT id FROM ' . t('migrations') . ' WHERE file = :f',
                [':f' => $name]
            );
            if ($exists) {
                $log[] = "skip  : {$name}";
                continue;
            }

            $sql = (string) file_get_contents($path);
            if ($sql === '') {
                $log[] = "empty : {$name}";
                continue;
            }

            // SQL'i ; ile böl - basit parser (string içinde ; varsa bilinçli kaçınılmalı)
            $statements = cf_split_sql($sql);

            db()->beginTransaction();
            try {
                foreach ($statements as $stmt) {
                    $trim = trim($stmt);
                    if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '/*')) {
                        continue;
                    }
                    db()->exec($trim);
                }
                db_exec(
                    'INSERT INTO ' . t('migrations') . ' (file, applied_at) VALUES (:f, NOW())',
                    [':f' => $name]
                );
                db()->commit();
                $log[] = "ok    : {$name}";
            } catch (Throwable $e) {
                db()->rollBack();
                $log[] = "FAIL  : {$name} - " . $e->getMessage();
                throw $e;
            }
        }
    } catch (Throwable $e) {
        $log[] = 'HATA: ' . $e->getMessage();
    }
    return $log;
}

/**
 * SQL dosyasını basit biçimde ; ile böl, '/* ... *​/' bloklarını koruyarak.
 */
function cf_split_sql(string $sql): array
{
    // Yorumları çıkar (block ve satır) - güvenli sadeleştirme
    $sql = preg_replace('!/\*.*?\*/!s', '', $sql) ?? $sql;
    $lines = preg_split("/\r?\n/", $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $t = ltrim($line);
        if (str_starts_with($t, '--')) { continue; }
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);

    $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    return array_values(array_filter(array_map('trim', $parts), fn($s) => $s !== ''));
}

function cf_current_db_version(): string
{
    try {
        $row = db_one('SELECT file FROM ' . t('migrations') . ' ORDER BY id DESC LIMIT 1');
        return $row['file'] ?? '-';
    } catch (Throwable) {
        return '-';
    }
}
