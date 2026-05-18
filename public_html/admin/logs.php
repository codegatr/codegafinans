<?php
/**
 * CODEGA Finans - Yönetici: Denetim Kayıtları
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

$admin = admin_require();

$tab = in_array($_GET['tab'] ?? '', ['audit','login'], true) ? $_GET['tab'] : 'audit';
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 50;
$off = ($page - 1) * $per;

if ($tab === 'audit') {
    $total = (int)(db_one("SELECT COUNT(*) c FROM " . t('audit_log'))['c'] ?? 0);
    $rows  = db_all(
        "SELECT al.*, u.email AS user_email, a.email AS admin_email
           FROM " . t('audit_log') . " al
           LEFT JOIN " . t('users')  . " u ON u.id = al.user_id
           LEFT JOIN " . t('admins') . " a ON a.id = al.admin_id
          ORDER BY al.id DESC LIMIT $per OFFSET $off"
    );
} else {
    $total = (int)(db_one("SELECT COUNT(*) c FROM " . t('login_attempts'))['c'] ?? 0);
    $rows  = db_all("SELECT * FROM " . t('login_attempts') . " ORDER BY id DESC LIMIT $per OFFSET $off");
}

$pages = max(1, (int)ceil($total / $per));

$pageTitle  = 'Denetim Kayıtları';
$pageHeader = 'Denetim Kayıtları';
require __DIR__ . '/../../inc/admin_header.php';
?>

<div style="display:flex;gap:8px;margin-bottom:14px;">
    <a href="?tab=audit" class="btn btn-sm <?= $tab==='audit'?'btn-primary':'btn-ghost' ?>">Eylem Günlüğü</a>
    <a href="?tab=login" class="btn btn-sm <?= $tab==='login'?'btn-primary':'btn-ghost' ?>">Giriş Denemeleri</a>
</div>

<div class="cf-card" style="padding:0;overflow:hidden;">
    <div class="cf-table-wrap">
        <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
            <?php if ($tab === 'audit'): ?>
                <thead>
                    <tr><th>Zaman</th><th>Eylem</th><th>Kullanıcı</th><th>Yönetici</th><th>IP</th><th>Meta</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td style="font-size:12px;color:var(--cf-text-soft);"><?= tr_datetime($r['created_at']) ?></td>
                        <td><code style="font-size:12px;"><?= e($r['action']) ?></code></td>
                        <td><?= e($r['user_email'] ?? '—') ?></td>
                        <td><?= e($r['admin_email'] ?? '—') ?></td>
                        <td style="font-size:12px;font-family:monospace;"><?= e($r['ip']) ?></td>
                        <td style="font-size:12px;color:var(--cf-text-soft);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($r['meta']) ?>"><?= e($r['meta']) ?></td>
                    </tr>
                <?php endforeach; if (!$rows): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--cf-text-soft);padding:30px;">Kayıt yok.</td></tr>
                <?php endif; ?>
                </tbody>
            <?php else: ?>
                <thead>
                    <tr><th>Zaman</th><th>E-posta</th><th>IP</th><th>Bölge</th><th>Sonuç</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td style="font-size:12px;color:var(--cf-text-soft);"><?= tr_datetime($r['created_at']) ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td style="font-size:12px;font-family:monospace;"><?= e($r['ip']) ?></td>
                        <td><span class="cf-pill"><?= e($r['area']) ?></span></td>
                        <td>
                            <span class="cf-pill <?= $r['ok'] ? 'success' : 'danger' ?>">
                                <?= $r['ok'] ? '✓ Başarılı' : '✗ Başarısız' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; if (!$rows): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--cf-text-soft);padding:30px;">Kayıt yok.</td></tr>
                <?php endif; ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;padding:14px;flex-wrap:wrap;">
            <?php for ($i = max(1,$page-5); $i <= min($pages,$page+5); $i++): ?>
                <a href="?tab=<?= $tab ?>&p=<?= $i ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <small style="color:var(--cf-muted);align-self:center;margin-left:10px;">Toplam <?= number_format($total, 0, ',', '.') ?> kayıt</small>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
