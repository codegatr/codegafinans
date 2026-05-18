<?php
/**
 * CODEGA Finans - Veritabanı Bağlantısı
 *
 * Singleton PDO bağlantısı. Tüm sorgular hazırlanmış ifade (prepared statement) ile çalıştırılır.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Tek PDO instance'ı döndürür.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        CF_DB_HOST, CF_DB_PORT, CF_DB_NAME
    );

    try {
        $pdo = new PDO($dsn, CF_DB_USER, CF_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+03:00'",
        ]);
    } catch (Throwable $e) {
        if (CF_DEBUG) {
            die('DB bağlantı hatası: ' . htmlspecialchars($e->getMessage()));
        }
        http_response_code(503);
        die('Servis geçici olarak kullanılamıyor.');
    }

    return $pdo;
}

/**
 * Tablo adına prefix ekler.
 *  t('users')  ->  'cf_users'
 */
function t(string $name): string
{
    return CF_DB_PREFIX . $name;
}

/**
 * Kısa yol: hazırla + çalıştır + tek satır.
 */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/**
 * Kısa yol: hazırla + çalıştır + tüm satırlar.
 */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Kısa yol: hazırla + çalıştır + lastInsertId.
 */
function db_insert(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return (int) db()->lastInsertId();
}

/**
 * Kısa yol: hazırla + çalıştır + etkilenen satır sayısı.
 */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/**
 * Bir tablo var mı? (migration sisteminde kullanılır)
 */
function db_table_exists(string $table): bool
{
    $row = db_one(
        "SELECT COUNT(*) AS c
           FROM information_schema.tables
          WHERE table_schema = DATABASE() AND table_name = :t",
        [':t' => $table]
    );
    return $row && (int)$row['c'] > 0;
}

/**
 * Bir kolon var mı?
 */
function db_column_exists(string $table, string $column): bool
{
    $row = db_one(
        "SELECT COUNT(*) AS c
           FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c",
        [':t' => $table, ':c' => $column]
    );
    return $row && (int)$row['c'] > 0;
}
