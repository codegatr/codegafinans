<?php
/**
 * CODEGA Finans - Sürüm Bilgisi
 * Tek Hakikat Kaynağı (Single Source of Truth)
 *
 * manifest.json ile birlikte güncellenir.
 * Bu dosyadaki CF_VERSION asla manuel olarak başka bir yerde tutulmamalıdır.
 */

declare(strict_types=1);

define('CF_VERSION',       '1.0.13');
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
 * Toplam modül sayısı - CodeGa ERP'deki TOPLAM_MODUL_SAYISI patterninin aynısı.
 * manifest.json ile birebir uyuşmalı.
 */
define('CF_TOTAL_MODULES', 12);
