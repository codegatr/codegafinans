<?php
/**
 * CODEGA Finans - Yönetici: Cron Ayarları
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';
require_once __DIR__ . '/../../inc/cari_reminders.php';

$admin = admin_require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['_action'] ?? '') === 'run_due_reminders') {
        try {
            $result = cari_send_due_reminders(date('Y-m-d'), 200);
            flash('success', 'Cari vade hatırlatma görevi çalıştı. Gönderilen: ' . $result['sent'] . ', atlanan: ' . $result['skipped'] . ', hata: ' . $result['failed'] . '.');
        } catch (Throwable $e) {
            flash('danger', 'Cron görevi çalıştırılamadı: ' . $e->getMessage());
        }
        redirect('/admin/cron.php');
    }
}

$phpBinary = '/usr/local/php83/bin/php';
$root = dirname(__DIR__, 2);
$cronCommand = '0 4 * * * ' . $phpBinary . ' ' . $root . '/cli/cron.php >> ' . $root . '/storage/cron.log 2>&1';
$dueToday = (int)(db_one(
    'SELECT COUNT(*) c
       FROM ' . t('customer_movements') . ' m
       JOIN ' . t('customers') . ' cst ON cst.id = m.customer_id AND cst.user_id = m.user_id
      WHERE m.due_date <= CURDATE()
        AND m.reminder_sent_at IS NULL
        AND cst.is_active = 1
        AND cst.email IS NOT NULL
        AND cst.email <> ""'
)['c'] ?? 0);

$pageTitle = 'Cron Ayarları';
$pageHeader = 'Cron Ayarları';
require __DIR__ . '/../../inc/admin_header.php';
?>

<div class="cf-grid cf-grid-2">
    <div class="cf-card">
        <h3>Günlük Cron Komutu</h3>
        <p class="muted">DirectAdmin Cron Manager içine aşağıdaki komutu ekleyin. Günlük çalışması cari vade hatırlatmaları, döviz kurları, abonelik ve temizlik görevlerini yürütür.</p>
        <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:12px;font-size:13px;line-height:1.5;"><?= e($cronCommand) ?></pre>
        <div style="display:grid;gap:8px;font-size:13px;color:var(--cf-text-soft);">
            <div><strong style="color:var(--cf-text);">Önerilen saat:</strong> Her gün 04:00</div>
            <div><strong style="color:var(--cf-text);">Çalışan dosya:</strong> <code>cli/cron.php</code></div>
            <div><strong style="color:var(--cf-text);">Log:</strong> <code>storage/cron.log</code></div>
        </div>
    </div>

    <div class="cf-card">
        <h3>Cari Vade Hatırlatmaları</h3>
        <p class="muted">Bugün veya geçmiş vadesi gelmiş, henüz mail gönderilmemiş cari hareketler için müşteriye hatırlatma gönderilir.</p>
        <div class="cf-mini-metric" style="margin-bottom:14px;">
            <span>Bekleyen hatırlatma</span>
            <strong><?= (int)$dueToday ?></strong>
        </div>
        <form method="post" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="run_due_reminders">
            <button class="btn btn-primary">Şimdi Çalıştır</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
