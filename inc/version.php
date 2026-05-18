<?php
/**
 * CODEGA Finans - SÃ¼rÃ¼m Bilgisi
 * Tek Hakikat KaynaÄŸÄ± (Single Source of Truth)
 *
 * manifest.json ile birlikte gÃ¼ncellenir.
 * Bu dosyadaki CF_VERSION asla manuel olarak baÅŸka bir yerde tutulmamalÄ±dÄ±r.
 */

declare(strict_types=1);

define('CF_VERSION',       '1.0.12');
define('CF_RELEASED_AT',   '2026-05-18');
define('CF_REPO',          'codegatr/codegafinans');
define('CF_BRANCH',        'main');
define('CF_APP_NAME',      'CODEGA Finans');
define('CF_APP_SLUG',      'codegafinans');
define('CF_AUTHOR',        'CODEGA - Yunus Aksoy');
define('CF_AUTHOR_URL',    'https://codega.com.tr');
define('CF_DOMAIN',        'finans.codega.com.tr');
define('CF_MIN_PHP',       '8.3.0');

/**
 * Toplam modÃ¼l sayÄ±sÄ± - CodeGa ERP'deki TOPLAM_MODUL_SAYISI patterninin aynÄ±sÄ±.
 * manifest.json ile birebir uyuÅŸmalÄ±.
 */
define('CF_TOTAL_MODULES', 12);

