-- =============================================================
-- CODEGA Finans - 002 Customer Accounts / Cari Takip
-- =============================================================

CREATE TABLE IF NOT EXISTS `cf_customers` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `type`       ENUM('customer','supplier','both') NOT NULL DEFAULT 'customer',
    `name`       VARCHAR(160) NOT NULL,
    `phone`      VARCHAR(40) NULL,
    `email`      VARCHAR(160) NULL,
    `tax_no`     VARCHAR(40) NULL,
    `address`    VARCHAR(500) NULL,
    `note`       VARCHAR(500) NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_customers_user` (`user_id`, `is_active`),
    KEY `ix_customers_name` (`user_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cf_customer_movements` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `customer_id` INT UNSIGNED NOT NULL,
    `direction`   ENUM('debit','credit') NOT NULL,
    `amount`      DECIMAL(14,2) NOT NULL DEFAULT 0,
    `tx_date`     DATE NOT NULL,
    `title`       VARCHAR(160) NOT NULL,
    `note`        VARCHAR(500) NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_customer_movements_user` (`user_id`, `tx_date`),
    KEY `ix_customer_movements_customer` (`customer_id`, `tx_date`),
    KEY `ix_customer_movements_direction` (`direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
