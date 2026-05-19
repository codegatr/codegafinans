<?php
/**
 * CODEGA Finans - Cron / zamanlanmış görevler
 *
 * Günde 1 defa çalıştırın (DirectAdmin Cron Manager):
 *   0 4 * * * /usr/local/php83/bin/php /home/<user>/domains/finans.codega.com.tr/codegafinans/cli/cron.php >> /home/<user>/domains/finans.codega.com.tr/codegafinans/storage/cron.log 2>&1
 *
 * Yapılan işler:
 *   1. Süresi dolmuş abonelikleri "expired" olarak işaretle.
 *   2. TCMB döviz kurlarını yenile.
 *   3. Her kullanıcı için akıllı uyarıları yeniden hesapla.
 *   4. Vadesi gelen cari hareketler için müşteriye hatırlatma maili gönder.
 *   5. 30 günden eski login_attempts kayıtlarını temizle.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only.');
}

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/subscription.php';
require_once __DIR__ . '/../inc/rates.php';
require_once __DIR__ . '/../inc/finance.php';
require_once __DIR__ . '/../inc/cari_reminders.php';

$started = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] CODEGA Finans cron başladı.\n";

// 1) Abonelik sweep
try {
    $n = subscription_sweep_expired();
    echo " - subscription_sweep_expired: {$n} kayıt güncellendi.\n";
} catch (Throwable $e) {
    echo " ! subscription_sweep_expired hata: " . $e->getMessage() . "\n";
}

// 2) TCMB kurları
try {
    $r = rates_refresh_from_tcmb();
    if ($r['ok']) {
        echo " - rates_refresh_from_tcmb: {$r['updated']} kur güncellendi.\n";
    } else {
        echo " - rates_refresh_from_tcmb atlandı: " . ($r['message'] ?? '?') . "\n";
    }
} catch (Throwable $e) {
    echo " ! rates_refresh_from_tcmb hata: " . $e->getMessage() . "\n";
}

// 3) Akıllı uyarılar
try {
    $users = db_all('SELECT id FROM ' . t('users') . ' WHERE status = "active"');
    $cnt = 0;
    foreach ($users as $u) {
        fin_generate_alerts((int)$u['id'], true);
        $cnt++;
    }
    echo " - fin_generate_alerts: {$cnt} kullanıcı için çalıştırıldı.\n";
} catch (Throwable $e) {
    echo " ! fin_generate_alerts hata: " . $e->getMessage() . "\n";
}

// 4) Cari vade hatırlatma mailleri
try {
    $r = cari_send_due_reminders(date('Y-m-d'), 200);
    echo " - cari_send_due_reminders: {$r['sent']} mail gönderildi, {$r['skipped']} atlandı, {$r['failed']} hata.\n";
} catch (Throwable $e) {
    echo " ! cari_send_due_reminders hata: " . $e->getMessage() . "\n";
}

// 5) Eski login_attempts kayıtları
try {
    $n = db_exec('DELETE FROM ' . t('login_attempts') . ' WHERE created_at < (NOW() - INTERVAL 30 DAY)');
    echo " - login_attempts temizlendi: {$n} kayıt.\n";
} catch (Throwable $e) {
    echo " ! login_attempts temizleme hata: " . $e->getMessage() . "\n";
}

$ms = round((microtime(true) - $started) * 1000);
echo "[" . date('Y-m-d H:i:s') . "] cron tamamlandı (~{$ms} ms).\n\n";
