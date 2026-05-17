<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $driver = env_value('DB_CONNECTION', 'sqlite');
        if ($driver === 'mysql') {
            $host = env_value('DB_HOST', '127.0.0.1');
            $port = env_value('DB_PORT', '3306');
            $database = env_value('DB_DATABASE', 'codega_finans');
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            self::$pdo = new PDO($dsn, env_value('DB_USERNAME', 'root'), env_value('DB_PASSWORD', ''), self::options());
            return self::$pdo;
        }

        $path = env_value('DB_DATABASE', 'storage/codega_finans.sqlite') ?: 'storage/codega_finans.sqlite';
        $fullPath = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path)
            ? $path
            : BASE_PATH . '/' . $path;

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0775, true);
        }
        self::$pdo = new PDO('sqlite:' . $fullPath, null, null, self::options());
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        return self::$pdo;
    }

    public static function migrate(): void
    {
        $pdo = self::connection();
        $schemaFile = self::driver() === 'mysql' ? '/database/schema_mysql.sql' : '/database/schema.sql';
        $schema = file_get_contents(BASE_PATH . $schemaFile);
        if ($schema === false) {
            throw new \RuntimeException('Schema file could not be read.');
        }
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [])) as $statement) {
            $pdo->exec($statement);
        }
    }

    public static function driver(): string
    {
        return env_value('DB_CONNECTION', 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
    }

    private static function options(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
