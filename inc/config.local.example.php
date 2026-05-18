<?php
/**
 * CODEGA Finans - Yerel Konfigürasyon ÖRNEĞİ
 *
 * Bu dosyayı sunucuda  inc/config.local.php  adıyla kopyalayın
 * ve aşağıdaki sabitleri kendi sunucunuza göre yeniden tanımlayın.
 *
 * NOT: config.local.php repoda yer almaz, .gitignore tarafından korunur.
 */

declare(strict_types=1);

// Tüm define()'lar override edilebilir, redefine hatasını önlemek için defined() koruması:

if (!defined('CF_DB_HOST'))    define('CF_DB_HOST',   'localhost');
if (!defined('CF_DB_PORT'))    define('CF_DB_PORT',   '3306');
if (!defined('CF_DB_NAME'))    define('CF_DB_NAME',   'codegaco_finans');
if (!defined('CF_DB_USER'))    define('CF_DB_USER',   'codegaco_finans');
if (!defined('CF_DB_PASS'))    define('CF_DB_PASS',   'BURAYA-GERCEK-SIFRE');

if (!defined('CF_DEBUG'))      define('CF_DEBUG',     false);

// GitHub Personal Access Token (Smart Update sırasında release indirme için)
if (!defined('CF_UPDATE_GH_TOKEN')) define('CF_UPDATE_GH_TOKEN', 'ghp_xxx_buraya');

// SMTP / mail ayarları
if (!defined('CF_MAIL_HOST'))  define('CF_MAIL_HOST', 'mail.codega.com.tr');
if (!defined('CF_MAIL_PORT'))  define('CF_MAIL_PORT', 465);
if (!defined('CF_MAIL_USER'))  define('CF_MAIL_USER', 'finans@codega.com.tr');
if (!defined('CF_MAIL_PASS'))  define('CF_MAIL_PASS', 'mail-sifresi');
if (!defined('CF_MAIL_SECURE')) define('CF_MAIL_SECURE', 'ssl');
