<?php
/**
 * CODEGA Finans - Genel Konfigürasyon
 *
 * Bu dosya repo içindedir ve genel ayarları içerir.
 * Sunucuya özel ayarlar (DB şifresi, GitHub tokeni vb.) için:
 *   inc/config.local.php  oluşturun, .gitignore tarafından korunur.
 *
 * NOT: config.local.php config.php'den ÖNCE require edilir, böylece içerdeki
 *      define() çağrıları varsayılanları "override" edebilir
 *      (defined() koruması sayesinde çift tanım çakışması olmaz).
 */

declare(strict_types=1);

require_once __DIR__ . '/version.php';

/* --------------------------------------------------------------------------
 * Yerel override - varsa burada yüklenir, aşağıdaki define()'ları baskılar.
 * -------------------------------------------------------------------------- */
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

/* --------------------------------------------------------------------------
 * Hata gösterimi (production'da kapat)
 * -------------------------------------------------------------------------- */
if (!defined('CF_DEBUG')) {
    define('CF_DEBUG', false);
}

if (CF_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

/* --------------------------------------------------------------------------
 * Saat dilimi & dil
 * -------------------------------------------------------------------------- */
date_default_timezone_set('Europe/Istanbul');
@setlocale(LC_ALL, 'tr_TR.UTF-8', 'tr_TR', 'tr');

/* --------------------------------------------------------------------------
 * Yol sabitleri
 * -------------------------------------------------------------------------- */
if (!defined('CF_ROOT'))       define('CF_ROOT',       dirname(__DIR__));
if (!defined('CF_INC'))        define('CF_INC',        CF_ROOT . '/inc');
if (!defined('CF_PUBLIC'))     define('CF_PUBLIC',     CF_ROOT . '/public_html');
if (!defined('CF_STORAGE'))    define('CF_STORAGE',    CF_ROOT . '/storage');
if (!defined('CF_BACKUPS'))    define('CF_BACKUPS',    CF_ROOT . '/backups');
if (!defined('CF_UPDATES'))    define('CF_UPDATES',    CF_ROOT . '/updates');
if (!defined('CF_MIGRATIONS')) define('CF_MIGRATIONS', CF_ROOT . '/migrations');

/* --------------------------------------------------------------------------
 * Varsayılan veritabanı ayarları
 * -------------------------------------------------------------------------- */
if (!defined('CF_DB_HOST'))   define('CF_DB_HOST',   'localhost');
if (!defined('CF_DB_PORT'))   define('CF_DB_PORT',   '3306');
if (!defined('CF_DB_NAME'))   define('CF_DB_NAME',   'codegaco_finans');
if (!defined('CF_DB_USER'))   define('CF_DB_USER',   'codegaco_finans');
if (!defined('CF_DB_PASS'))   define('CF_DB_PASS',   'changeme');
if (!defined('CF_DB_PREFIX')) define('CF_DB_PREFIX', 'cf_');

/* --------------------------------------------------------------------------
 * Uygulama URL'i (CLI ve cron için)
 * -------------------------------------------------------------------------- */
if (!defined('CF_APP_URL'))   define('CF_APP_URL',   'https://finans.codega.com.tr');

/* --------------------------------------------------------------------------
 * Güvenlik
 * -------------------------------------------------------------------------- */
if (!defined('CF_SESSION_NAME'))     define('CF_SESSION_NAME',     'CFSESSION');
if (!defined('CF_SESSION_LIFETIME')) define('CF_SESSION_LIFETIME', 60 * 60 * 12);
if (!defined('CF_PASSWORD_ALGO'))    define('CF_PASSWORD_ALGO',    PASSWORD_BCRYPT);
if (!defined('CF_LOGIN_MAX_TRY'))    define('CF_LOGIN_MAX_TRY',    5);
if (!defined('CF_LOGIN_LOCK_MIN'))   define('CF_LOGIN_LOCK_MIN',   15);

/* --------------------------------------------------------------------------
 * Smart Update v5 (CMiner / Mizan paterni)
 * -------------------------------------------------------------------------- */
if (!defined('CF_UPDATE_ENABLED'))      define('CF_UPDATE_ENABLED',   true);
if (!defined('CF_UPDATE_GH_TOKEN'))     define('CF_UPDATE_GH_TOKEN',  '');
if (!defined('CF_UPDATE_ZIP_LIMIT'))    define('CF_UPDATE_ZIP_LIMIT', 100 * 1024 * 1024);
if (!defined('CF_UPDATE_KEEP_BACKUPS')) define('CF_UPDATE_KEEP_BACKUPS', 10);

/* --------------------------------------------------------------------------
 * Abonelik / paket
 * -------------------------------------------------------------------------- */
if (!defined('CF_TRIAL_DAYS'))       define('CF_TRIAL_DAYS',       7);
if (!defined('CF_DEFAULT_CURRENCY')) define('CF_DEFAULT_CURRENCY', 'TRY');
if (!defined('CF_GRACE_DAYS'))       define('CF_GRACE_DAYS',       3);

/* --------------------------------------------------------------------------
 * Bildirim / e-posta
 * -------------------------------------------------------------------------- */
if (!defined('CF_MAIL_FROM'))      define('CF_MAIL_FROM',      'finans@codega.com.tr');
if (!defined('CF_MAIL_FROM_NAME')) define('CF_MAIL_FROM_NAME', 'CODEGA Finans');
if (!defined('CF_MAIL_HOST'))      define('CF_MAIL_HOST',      '');
if (!defined('CF_MAIL_PORT'))      define('CF_MAIL_PORT',      587);
if (!defined('CF_MAIL_USER'))      define('CF_MAIL_USER',      '');
if (!defined('CF_MAIL_PASS'))      define('CF_MAIL_PASS',      '');
if (!defined('CF_MAIL_SECURE'))    define('CF_MAIL_SECURE',    'tls');
if (!defined('CF_MAIL_TIMEOUT'))   define('CF_MAIL_TIMEOUT',   15);
if (!defined('CF_ADMIN_MAIL'))     define('CF_ADMIN_MAIL',     'yunus@codega.com.tr');

/* --------------------------------------------------------------------------
 * TCMB döviz kurları
 * -------------------------------------------------------------------------- */
if (!defined('CF_TCMB_URL'))         define('CF_TCMB_URL',         'https://www.tcmb.gov.tr/kurlar/today.xml');
if (!defined('CF_TCMB_REFRESH_MIN')) define('CF_TCMB_REFRESH_MIN', 60);
