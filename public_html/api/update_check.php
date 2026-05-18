<?php
/**
 * CODEGA Finans - API: Android Uygulaması Güncelleme Kontrolü
 *
 * Android WebView uygulaması bunu çağırıp gerekirse Play Store'a yönlendirme yapar.
 * GET parametresi:
 *   v=<mevcut Android sürümü>  (örn: 1.0.0)
 *
 * Yanıt:
 *   {
 *     "ok": true,
 *     "android_version": "1.2.0",
 *     "min_supported": "1.0.0",
 *     "force_update": false,
 *     "store_url": "https://play.google.com/store/apps/details?id=tr.com.codega.finans",
 *     "web_url": "https://finans.codega.com.tr",
 *     "release_notes": "…"
 *   }
 *
 * Android sürüm değerleri cf_settings tablosundan okunur:
 *   android_latest_version   (örn: 1.2.0)
 *   android_min_version      (zorunlu güncelleme alt sınırı)
 *   android_store_url        (Play Store url'i)
 *   android_release_notes
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$clientVer = s($_GET['v'] ?? '1.0.0', 20);

$get = function (string $key, string $default = '') {
    $r = db_one('SELECT value FROM ' . t('settings') . ' WHERE key_name = :k', [':k' => $key]);
    return $r['value'] ?? $default;
};

$latest    = $get('android_latest_version', CF_VERSION);
$min       = $get('android_min_version', '1.0.0');
$storeUrl  = $get('android_store_url', 'https://play.google.com/store/apps/details?id=tr.com.codega.finans');
$notes     = $get('android_release_notes', 'Yeni sürüm yayınlandı.');

$hasUpdate = version_compare($clientVer, $latest, '<');
$force     = version_compare($clientVer, $min, '<');

json_out([
    'ok'              => true,
    'client_version'  => $clientVer,
    'android_version' => $latest,
    'min_supported'   => $min,
    'has_update'      => $hasUpdate,
    'force_update'    => $force,
    'store_url'       => $storeUrl,
    'web_url'         => CF_APP_URL,
    'release_notes'   => $notes,
    'checked_at'      => date('c'),
]);
