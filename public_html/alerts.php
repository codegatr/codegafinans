<?php
/**
 * CODEGA Finans - Akıllı Uyarılar
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/finance.php';

$user = auth_require_active_subscription();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'regen') {
        fin_generate_alerts((int)$user['id'], true);
        flash('success', 'Uyarılar yeniden hesaplandı.');
        redirect('/alerts.php');
    }

    if ($action === 'read_all') {
        db_exec('UPDATE ' . t('alerts') . ' SET is_read = 1 WHERE user_id = :u', [':u' => $user['id']]);
        flash('success', 'Tüm uyarılar okundu olarak işaretlendi.');
        redirect('/alerts.php');
    }

    if ($action === 'read') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec('UPDATE ' . t('alerts') . ' SET is_read = 1 WHERE id = :id AND user_id = :u',
                [':id' => $id, ':u' => $user['id']]);
        redirect('/alerts.php');
    }

    if ($action === 'delete') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec('DELETE FROM ' . t('alerts') . ' WHERE id = :id AND user_id = :u',
                [':id' => $id, ':u' => $user['id']]);
        flash('success', 'Uyarı silindi.');
        redirect('/alerts.php');
    }
}

$alerts = db_all(
    'SELECT * FROM ' . t('alerts') . ' WHERE user_id = :u ORDER BY is_read, id DESC LIMIT 200',
    [':u' => $user['id']]
);

$pageTitle  = 'Akıllı Uyarılar';
$pageHeader = 'Akıllı Uyarılar';

require __DIR__ . '/../inc/header.php';
?>

<div class="cf-page-head">
    <h2>Uyarılar <small style="color:var(--cf-text-soft);font-size:13px;font-weight:500;">(<?= count($alerts) ?>)</small></h2>
    <div class="actions">
        <form method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="regen">
            <button class="btn btn-ghost btn-sm">⟳ Yeniden Hesapla</button>
        </form>
        <form method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="read_all">
            <button class="btn btn-primary btn-sm">Tümünü Okudum</button>
        </form>
    </div>
</div>

<?php if (empty($alerts)): ?>
    <div class="cf-card cf-empty"><div class="icon">🔔</div>Şu an uyarı yok.</div>
<?php else: ?>
<div class="cf-grid" style="grid-template-columns:1fr;gap:10px;">
<?php foreach ($alerts as $a):
    $clr = $a['level']==='danger'?'#ef4444':($a['level']==='warning'?'#f59e0b':($a['level']==='success'?'#10b981':'#0ea5e9'));
?>
    <div class="cf-card" style="border-left:4px solid <?= $clr ?>;padding:14px 16px;<?= $a['is_read']?'opacity:.7;':'' ?>">
        <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;">
            <div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <strong style="font-size:15px;"><?= e($a['title']) ?></strong>
                    <?php if (!$a['is_read']): ?><span class="cf-pill info" style="font-size:11px;">Yeni</span><?php endif; ?>
                </div>
                <div style="font-size:13px;color:var(--cf-text-soft);margin-top:4px;"><?= e($a['message']) ?></div>
                <div style="font-size:11px;color:var(--cf-muted);margin-top:6px;">
                    <?= tr_datetime($a['created_at']) ?>
                    <?php if ($a['link']): ?> · <a href="<?= e($a['link']) ?>">Detay →</a><?php endif; ?>
                </div>
            </div>
            <div style="display:flex;gap:4px;flex-shrink:0;">
                <?php if (!$a['is_read']): ?>
                <form method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="read">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-ghost btn-sm">Okundu</button>
                </form>
                <?php endif; ?>
                <form method="post" style="display:inline;" onsubmit="return confirm('Uyarı silinsin mi?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-ghost btn-sm">×</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
