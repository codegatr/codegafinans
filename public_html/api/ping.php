<?php
/**
 * CODEGA Finans - API: Sağlık Kontrolü
 * Mobil app ve harici izleyiciler için temel ping endpoint'i.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dbOk = false;
try {
    db()->query('SELECT 1');
    $dbOk = true;
} catch (Throwable) {
    $dbOk = false;
}

json_out([
    'ok'        => $dbOk,
    'app'       => CF_APP_NAME,
    'version'   => CF_VERSION,
    'modules'   => CF_TOTAL_MODULES,
    'released'  => CF_RELEASED_AT,
    'server_at' => date('c'),
    'tz'        => date_default_timezone_get(),
    'db'        => $dbOk ? 'up' : 'down',
]);
