<?php
/**
 * CODEGA Finans - CLI: Migration çalıştırıcı
 *
 * Kullanım:
 *   php cli/migrate.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only.');
}

require_once __DIR__ . '/../inc/migrate.php';

echo "[" . date('Y-m-d H:i:s') . "] Migration başlatılıyor...\n\n";
$log = cf_migrate_all();
foreach ($log as $line) {
    echo "  " . $line . "\n";
}
echo "\n[" . date('Y-m-d H:i:s') . "] Tamam. " . count($log) . " satır işlendi.\n";
