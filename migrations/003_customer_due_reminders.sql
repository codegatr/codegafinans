-- =============================================================
-- CODEGA Finans - 003 Customer Movement Due Reminders
-- =============================================================

ALTER TABLE `cf_customer_movements`
    ADD COLUMN `due_date` DATE NULL AFTER `tx_date`,
    ADD COLUMN `reminder_sent_at` DATETIME NULL AFTER `note`,
    ADD KEY `ix_customer_movements_due` (`due_date`, `reminder_sent_at`);
