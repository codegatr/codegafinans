<?php
/**
 * CODEGA Finans - API: Sürüm Bilgisi
 * Android WebView veya başka istemcilerin sürüm sorgulaması için.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

json_out([
    'ok'         => true,
    'app'        => CF_APP_NAME,
    'slug'       => CF_APP_SLUG,
    'version'    => CF_VERSION,
    'released'   => CF_RELEASED_AT,
    'min_php'    => CF_MIN_PHP,
    'modules'    => CF_TOTAL_MODULES,
    'repo'       => CF_REPO,
    'domain'     => CF_DOMAIN,
    'urls'       => [
        'home'      => url('/'),
        'login'     => url('/login.php'),
        'register'  => url('/register.php'),
        'dashboard' => url('/dashboard.php'),
        'privacy'   => url('/privacy.php'),
        'terms'     => url('/terms.php'),
    ],
]);
