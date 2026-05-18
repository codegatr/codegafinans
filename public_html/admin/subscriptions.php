<?php
/**
 * CODEGA Finans - Yönetici: Abonelikler
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

$admin = admin_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'extend') {
        $id   = intval_safe($_POST['id'] ?? 0, 1);
        $days = intval_safe($_POST['days'] ?? 0, 1, 3650);
        db_exec(
            'UPDATE ' . t('subscriptions') . '
                SET current_period_end = DATE_ADD(GREATEST(current_period_end, CURDATE()), INTERVAL :d DAY),
                    status = CASE WHEN status IN ("expired","past_due") THEN "active" ELSE status END
              WHERE id = :id',
            [':d' => $days, ':id' => $id]
        );
        audit('admin.sub.extend', null, (int)$admin['id'], "id={$id} +{$days}d");
        flash('success', "Abonelik {$days} gün uzatıldı.");
        redirect('/admin/subscriptions.php');
    }

    if ($action === 'cancel') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec('UPDATE ' . t('subscriptions') . ' SET status = "cancelled", cancelled_at = NOW(), auto_renew = 0 WHERE id = :id',
                [':id' => $id]);
        audit('admin.sub.cancel', null, (int)$admin['id'], "id={$id}");
        flash('success', 'Abonelik iptal edildi.');
        redirect('/admin/subscriptions.php');
    }
}

// Filtre
$st = in_array($_GET['st'] ?? '', ['trial','active','past_due','cancelled','expired'], true) ? $_GET['st'] : '';
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 30;
$off = ($page - 1) * $per;

$where = '1=1';
$params = [];
if ($st) { $where .= ' AND s.status = :st'; $params[':st'] = $st; }

$total = (int)(db_one("SELECT COUNT(*) c FROM " . t('subscriptions') . " s WHERE $where", $params)['c'] ?? 0);
$rows = db_all(
    "SELECT s.*, u.name AS user_name, u.email AS user_email, p.name AS plan_name, p.price, p.currency
       FROM " . t('subscriptions') . " s
       JOIN " . t('users') . " u ON u.id = s.user_id
       JOIN " . t('plans') . " p ON p.id = s.plan_id
      WHERE $where
      ORDER BY s.id DESC
      LIMIT $per OFFSET $off",
    $params
);
$pages = max(1, (int)ceil($total / $per));

$pageTitle  = 'Abonelikler';
$pageHeader = 'Abonelikler';
require __DIR__ . '/../../inc/admin_header.php';
?>

<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
    <a href="/admin/subscriptions.php" class="btn btn-sm <?= $st===''?'btn-primary':'btn-ghost' ?>">Tümü</a>
    <a href="?st=active"    class="btn btn-sm <?= $st==='active'?'btn-primary':'btn-ghost' ?>">Aktif</a>
    <a href="?st=trial"     class="btn btn-sm <?= $st==='trial'?'btn-primary':'btn-ghost' ?>">Deneme</a>
    <a href="?st=expired"   class="btn btn-sm <?= $st==='expired'?'btn-primary':'btn-ghost' ?>">Süresi dolmuş</a>
    <a href="?st=cancelled" class="btn btn-sm <?= $st==='cancelled'?'btn-primary':'btn-ghost' ?>">İptal</a>
    <a href="?st=past_due"  class="btn btn-sm <?= $st==='past_due'?'btn-primary':'btn-ghost' ?>">Vadesi geçti</a>
</div>

<div class="cf-card" style="padding:0;overflow:hidden;">
    <div class="cf-table-wrap">
        <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>Plan</th>
                    <th>Durum</th>
                    <th>Başlangıç</th>
                    <th>Bitiş</th>
                    <th>Kaynak</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $s): ?>
                <tr>
                    <td>#<?= (int)$s['id'] ?></td>
                    <td>
                        <strong><?= e($s['user_name']) ?></strong>
                        <div style="font-size:12px;color:var(--cf-text-soft);"><?= e($s['user_email']) ?></div>
                    </td>
                    <td><?= e($s['plan_name']) ?> <small style="color:var(--cf-text-soft);">(<?= money($s['price'], $s['currency']) ?>)</small></td>
                    <td><span class="cf-pill <?php
                        echo $s['status']==='active'?'success':
                            ($s['status']==='trial'?'warn':
                            ($s['status']==='expired'||$s['status']==='cancelled'?'danger':'info'));
                    ?>"><?= e($s['status']) ?></span></td>
                    <td><?= tr_date($s['started_at']) ?></td>
                    <td><?= tr_date($s['current_period_end']) ?></td>
                    <td><?= e($s['source']) ?></td>
                    <td style="text-align:right;">
                        <form method="post" style="display:inline-flex;gap:4px;align-items:center;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="extend">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <input type="number" name="days" value="30" min="1" max="3650" style="width:60px;padding:4px 6px;border:1px solid var(--cf-border);border-radius:6px;font-size:13px;">
                            <button class="btn btn-success btn-sm" type="submit">+ gün</button>
                        </form>
                        <?php if (in_array($s['status'], ['active','trial'], true)): ?>
                        <form method="post" style="display:inline-block;margin-left:4px;" onsubmit="return confirm('İptal edilsin mi?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <button class="btn btn-ghost btn-sm">İptal</button>
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
