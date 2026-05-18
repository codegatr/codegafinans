-- =============================================================
-- CODEGA Finans - 001 Initial Schema
-- =============================================================
-- Tüm tablolar IF NOT EXISTS ile, kolonlar INFORMATION_SCHEMA ile
-- korunarak idempotent biçimde oluşturulur. Tekcanmetal kuralı:
--   Önce ALTER bloğu garanti edilmeden hiçbir UPDATE eklenmez.
-- =============================================================

/* ------------------------------------------------------------- *
 * 1) Kullanıcılar
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_users` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(120) NOT NULL,
    `email`           VARCHAR(160) NOT NULL,
    `phone`           VARCHAR(40)  NULL,
    `password`        VARCHAR(255) NOT NULL,
    `currency`        VARCHAR(8)   NOT NULL DEFAULT 'TRY',
    `monthly_budget`  DECIMAL(14,2) NOT NULL DEFAULT 0,
    `status`          ENUM('active','suspended','banned') NOT NULL DEFAULT 'active',
    `trial_ends_at`   DATE NULL,
    `subscription_id` INT UNSIGNED NULL,
    `email_verified_at` DATETIME NULL,
    `last_login_at`   DATETIME NULL,
    `last_login_ip`   VARCHAR(45) NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `ix_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 2) Yöneticiler
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_admins` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(120) NOT NULL,
    `email`        VARCHAR(160) NOT NULL,
    `password`     VARCHAR(255) NOT NULL,
    `role`         ENUM('superadmin','admin','viewer') NOT NULL DEFAULT 'admin',
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 3) Kategoriler (Hem global hem kullanıcıya özel)
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_categories` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`   INT UNSIGNED NULL,                    -- NULL ise sistemsel
    `name`      VARCHAR(80) NOT NULL,
    `type`      ENUM('income','expense','both') NOT NULL DEFAULT 'expense',
    `icon`      VARCHAR(40)  NOT NULL DEFAULT 'circle',
    `color`     VARCHAR(20)  NOT NULL DEFAULT '#5b6cff',
    `sort`      INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_cat_user` (`user_id`),
    KEY `ix_cat_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 4) Gelir / Gider hareketleri
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_transactions` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `category_id`  INT UNSIGNED NULL,
    `type`         ENUM('income','expense') NOT NULL,
    `title`        VARCHAR(160) NOT NULL,
    `amount`       DECIMAL(14,2) NOT NULL DEFAULT 0,
    `currency`     VARCHAR(8) NOT NULL DEFAULT 'TRY',
    `tx_date`      DATE NOT NULL,
    `note`         VARCHAR(500) NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_tx_user_date` (`user_id`, `tx_date`),
    KEY `ix_tx_user_type` (`user_id`, `type`),
    KEY `ix_tx_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 5) Bütçeler (aya ve kategoriye özel limit)
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_budgets` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `category_id`  INT UNSIGNED NULL,                 -- NULL ise genel ay bütçesi
    `month`        CHAR(7) NOT NULL,                  -- 'YYYY-MM'
    `limit_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_budget_user_cat_month` (`user_id`, `category_id`, `month`),
    KEY `ix_budget_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 6) Tasarruf hedefleri
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_goals` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED NOT NULL,
    `title`          VARCHAR(160) NOT NULL,
    `target_amount`  DECIMAL(14,2) NOT NULL DEFAULT 0,
    `current_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `deadline`       DATE NULL,
    `color`          VARCHAR(20) NOT NULL DEFAULT '#22c55e',
    `is_completed`   TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_goals_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 7) Borçlar
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_debts` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `creditor`     VARCHAR(160) NOT NULL,
    `total_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `paid_amount`  DECIMAL(14,2) NOT NULL DEFAULT 0,
    `due_date`     DATE NULL,
    `installments` INT NOT NULL DEFAULT 1,
    `interest_pct` DECIMAL(6,2) NOT NULL DEFAULT 0,
    `note`         VARCHAR(500) NULL,
    `is_closed`    TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_debts_user` (`user_id`),
    KEY `ix_debts_due` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 8) Borç ödemeleri
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_debt_payments` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `debt_id`    INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `amount`     DECIMAL(14,2) NOT NULL DEFAULT 0,
    `paid_at`    DATE NOT NULL,
    `note`       VARCHAR(300) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_debtpay_debt` (`debt_id`),
    KEY `ix_debtpay_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 9) Akıllı uyarılar
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_alerts` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `level`      ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    `title`      VARCHAR(160) NOT NULL,
    `message`    VARCHAR(800) NOT NULL,
    `link`       VARCHAR(200) NULL,
    `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_alerts_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 10) Döviz kurları (TCMB cache)
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_rates` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(8) NOT NULL,
    `name`       VARCHAR(80) NOT NULL,
    `buy_rate`   DECIMAL(14,6) NOT NULL DEFAULT 0,
    `sell_rate`  DECIMAL(14,6) NOT NULL DEFAULT 0,
    `source`     VARCHAR(40) NOT NULL DEFAULT 'TCMB',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rates_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 11) Abonelik planları
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_plans` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`          VARCHAR(40) NOT NULL,            -- 'free', 'monthly', 'yearly'
    `name`          VARCHAR(120) NOT NULL,
    `price`         DECIMAL(10,2) NOT NULL DEFAULT 0,
    `currency`      VARCHAR(8) NOT NULL DEFAULT 'TRY',
    `period`        ENUM('trial','monthly','yearly','lifetime') NOT NULL DEFAULT 'monthly',
    `features_json` JSON NULL,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `sort`          INT NOT NULL DEFAULT 0,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plans_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 12) Abonelikler
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_subscriptions` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED NOT NULL,
    `plan_id`       INT UNSIGNED NOT NULL,
    `status`        ENUM('trial','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trial',
    `started_at`    DATE NOT NULL,
    `current_period_end` DATE NOT NULL,
    `cancelled_at`  DATETIME NULL,
    `source`        ENUM('web','playstore','manual') NOT NULL DEFAULT 'web',
    `external_ref`  VARCHAR(200) NULL,               -- Google Play purchase token vb.
    `auto_renew`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_sub_user` (`user_id`),
    KEY `ix_sub_status` (`status`),
    KEY `ix_sub_end` (`current_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 13) Ödemeler
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_payments` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subscription_id` INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `amount`          DECIMAL(10,2) NOT NULL DEFAULT 0,
    `currency`        VARCHAR(8) NOT NULL DEFAULT 'TRY',
    `status`          ENUM('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
    `method`          VARCHAR(40) NOT NULL DEFAULT 'manual', -- 'iyzico','playstore','iban','manual'
    `provider_ref`    VARCHAR(200) NULL,
    `note`            VARCHAR(500) NULL,
    `paid_at`         DATETIME NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_pay_user` (`user_id`),
    KEY `ix_pay_sub`  (`subscription_id`),
    KEY `ix_pay_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 14) Giriş denemeleri (rate limit)
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_login_attempts` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(160) NOT NULL,
    `ip`         VARCHAR(45) NOT NULL,
    `ok`         TINYINT(1) NOT NULL DEFAULT 0,
    `area`       ENUM('user','admin') NOT NULL DEFAULT 'user',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_la_email` (`email`),
    KEY `ix_la_ip`    (`ip`),
    KEY `ix_la_when`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 15) Audit log
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_audit_log` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NULL,
    `admin_id`   INT UNSIGNED NULL,
    `action`     VARCHAR(80)  NOT NULL,
    `ip`         VARCHAR(45)  NULL,
    `ua`         VARCHAR(500) NULL,
    `meta`       TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_audit_user`  (`user_id`),
    KEY `ix_audit_admin` (`admin_id`),
    KEY `ix_audit_when`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 16) Sistem ayarları (key/value)
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_settings` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key_name`   VARCHAR(80) NOT NULL,
    `value`      TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 17) Migration izleme
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_migrations` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `file`       VARCHAR(200) NOT NULL,
    `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_migrations_file` (`file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * 18) Güncelleme log (Smart Update v5)
 * ------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `cf_update_log` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_ver`   VARCHAR(20) NULL,
    `to_ver`     VARCHAR(20) NULL,
    `status`     ENUM('started','success','failed','rolled_back') NOT NULL DEFAULT 'started',
    `admin_id`   INT UNSIGNED NULL,
    `message`    TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_upd_when` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------- *
 * Varsayılan veriler: kategoriler & planlar
 * ------------------------------------------------------------- */

INSERT IGNORE INTO `cf_categories` (`user_id`,`name`,`type`,`icon`,`color`,`sort`) VALUES
    (NULL,'Maaş','income','wallet','#10b981',1),
    (NULL,'Ek Gelir','income','plus-circle','#22c55e',2),
    (NULL,'Yatırım Getirisi','income','trending-up','#06b6d4',3),
    (NULL,'Kira','expense','home','#ef4444',10),
    (NULL,'Faturalar','expense','file-text','#f59e0b',11),
    (NULL,'Market','expense','shopping-cart','#f97316',12),
    (NULL,'Mutfak','expense','coffee','#ec4899',13),
    (NULL,'Ulaşım','expense','truck','#3b82f6',14),
    (NULL,'Sağlık','expense','heart','#dc2626',15),
    (NULL,'Eğitim','expense','book','#8b5cf6',16),
    (NULL,'Eğlence','expense','music','#a855f7',17),
    (NULL,'Tatil','expense','sun','#0ea5e9',18),
    (NULL,'Kredi / Borç','expense','credit-card','#64748b',19),
    (NULL,'Diğer','both','more-horizontal','#94a3b8',99);

INSERT IGNORE INTO `cf_plans` (`code`,`name`,`price`,`currency`,`period`,`is_active`,`sort`) VALUES
    ('trial',   'Deneme',         0.00, 'TRY', 'trial',    1, 1),
    ('monthly', 'Aylık Üyelik',  49.00, 'TRY', 'monthly',  1, 2),
    ('yearly',  'Yıllık Üyelik',469.00, 'TRY', 'yearly',   1, 3);

INSERT IGNORE INTO `cf_settings` (`key_name`,`value`) VALUES
    ('site_title',    'CODEGA Finans'),
    ('contact_email', 'finans@codega.com.tr'),
    ('iban',          'TR00 0000 0000 0000 0000 0000 00'),
    ('iban_name',     'CODEGA - Yunus AKSOY'),
    ('rates_last_at', ''),
    ('maintenance',   '0');
