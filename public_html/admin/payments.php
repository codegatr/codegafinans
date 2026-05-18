<?php
/**
 * CODEGA Finans - Yönetici: Ödemeler
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

$admin = admin_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = intval_safe($_POST['id'] ?? 0, 1);

    if ($action === 'confirm') {
        db_exec(
            'UPDATE ' . t('payments') . ' SET status = "succeeded", paid_at = NOW() WHERE id = :id',
            [':id' => $id]
        );
        audit('admin.pay.confirm', null, (int)$admin['id'], "id={$id}");
        flash('success', 'Ödeme onaylandı.');
    }
    if ($action === 'reject') {
        db_exec('UPDATE ' . t('payments') . ' SET status = "failed" WHERE id = :id', [':id' => $id]);
        audit('admin.pay.reject', null, (int)$admin['id'], "id={$id}");
        flash('warning', 'Ödeme reddedildi.');
    }
    if ($action === 'refund') {
        db_exec('UPDATE ' . t('payments') . ' SET status = "refunded" WHERE id = :id', [':id' => $id]);
        audit('admin.pay.refund', null, (int)$admin['id'], "id={$id}");
        flash('warning', 'İade işaretlendi.');
    }
    redirect('/admin/payments.php?st=' . ($_POST['back_st'] ?? ''));
}

$st = in_array($_GET['st'] ?? '', ['pending','succeeded','failed','refunded'], true) ? $_GET['st'] : '';
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 30;
$off = ($page - 1) * $per;

$where = '1=1'; $params = [];
if ($st) { $where .= ' AND pay.status = :st'; $params[':st'] = $st; }

$total = (int)(db_one("SELECT COUNT(*) c FROM " . t('payments') . " pay WHERE $where", $params)['c'] ?? 0);
$rows = db_all(
    "SELECT pay.*, u.name AS user_name, u.email AS user_email, pl.name AS plan_name
       FROM " . t('payments') . " pay
       JOIN " . t('users') . " u ON u.id = pay.user_id
       JOIN " . t('subscriptions') . " s ON s.id = pay.subscription_id
       JOIN " . t('plans') . " pl ON pl.id = s.plan_id
      WHERE $where
      ORDER BY pay.id DESC
      LIMIT $per OFFSET $off",
    $params
);
$pages = max(1, (int)ceil($total / $per));

$sumOk = (float)(db_one("SELECT COALESCE(SUM(amount),0) s FROM " . t('payments') . " WHERE status='succeeded'")['s'] ?? 0);
$sumPending = (float)(db_one("SELECT COALESCE(SUM(amount),0) s FROM " . t('payments') . " WHERE status='pending'")['s'] ?? 0);

$pageTitle  = 'Ödemeler';
$pageHeader = 'Ödemeler';
require __DIR__ . '/../../inc/admin_header.php';
?>

<div class="cf-grid cf-grid-2" style="margin-bottom:18px;">
    <div class="cf-stat income">
        <div class="label">Toplam Onaylanan</div>
        <div class="value"><?= money($sumOk) ?></div>
    </div>
    <div class="cf-stat gold">
        <div class="label">Bekleyen Tutar</div>
        <div class="value"><?= money($sumPending) ?></div>
    </div>
</div>

<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
    <a href="/admin/payments.php" class="btn btn-sm <?= $st===''?'btn-primary':'btn-ghost' ?>">Tümü</a>
    <a href="?st=pending"   class="btn btn-sm <?= $st==='pending'?'btn-primary':'btn-ghost' ?>">Bekleyen</a>
    <a href="?st=succeeded" class="btn btn-sm <?= $st==='succeeded'?'btn-primary':'btn-ghost' ?>">Onaylanmış</a>
    <a href="?st=failed"    class="btn btn-sm <?= $st==='failed'?'btn-primary':'btn-ghost' ?>">Reddedilmiş</a>
    <a href="?st=refunded"  class="btn btn-sm <?= $st==='refunded'?'btn-primary':'btn-ghost' ?>">İade</a>
</div>

<div class="cf-card" style="padding:0;overflow:hidden;">
    <div class="cf-table-wrap">
        <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>Plan</th>
                    <th class="amount">Tutar</th>
                    <th>Yöntem</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>Aksiyon</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td>#<?= (int)$p['id'] ?></td>
                    <td>
                        <strong><?= e($p['user_name']) ?></strong>
                        <div style="font-size:12px;color:var(--cf-text-soft);"><?= e($p['user_email']) ?></div>
                    </td>
                    <td><?= e($p['plan_name']) ?></td>
                    <td class="amount"><?= money($p['amount'], $p['currency']) ?></td>
                    <td><?= e($p['method']) ?></td>
                    <td>
                        <span class="cf-pill <?php
                            echo $p['status']==='succeeded'?'success':
                                ($p['status']==='pending'?'warn':
                                ($p['status']==='refunded'?'info':'danger'));
                        ?>"><?= e($p['status']) ?></span>
                    </td>
                    <td><?= tr_datetime($p['paid_at'] ?? $p['created_at']) ?></td>
                    <td style="text-align:right;">
                        <?php if ($p['status'] === 'pending'): ?>
                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="back_st" value="<?= e($st) ?>">
                                <button class="btn btn-success btn-sm">Onayla</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="back_st" value="<?= e($st) ?>">
                                <button class="btn btn-ghost btn-sm">Reddet</button>
                            </form>
                        <?php elseif ($p['status'] === 'succeeded'): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('İade olarak işaretlensin mi?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="refund">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="back_st" value="<?= e($st) ?>">
                                <button class="btn btn-ghost btn-sm">İade</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; if (!$rows): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--cf-text-soft);padding:30px;">Kayıt yok.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;padding:14px;">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?p=<?= $i ?>&st=<?= e($st) ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
