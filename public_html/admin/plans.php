<?php
/**
 * CODEGA Finans - Yönetici: Planlar
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

$admin = admin_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id     = intval_safe($_POST['id'] ?? 0, 0);
        $code   = s($_POST['code'] ?? '', 40);
        $name   = s($_POST['name'] ?? '', 120);
        $price  = money_in($_POST['price'] ?? 0);
        $cur    = s($_POST['currency'] ?? 'TRY', 8);
        $period = in_array($_POST['period'] ?? '', ['trial','monthly','yearly','lifetime'], true) ? $_POST['period'] : 'monthly';
        $active = !empty($_POST['is_active']) ? 1 : 0;
        $sort   = intval_safe($_POST['sort'] ?? 0, 0, 9999);

        if ($code === '' || $name === '') {
            flash('danger', 'Kod ve ad zorunlu.');
            redirect('/admin/plans.php');
        }

        if ($id) {
            db_exec(
                'UPDATE ' . t('plans') . '
                    SET code=:c, name=:n, price=:p, currency=:cu, period=:pe, is_active=:a, sort=:s
                  WHERE id=:id',
                [':c'=>$code,':n'=>$name,':p'=>$price,':cu'=>$cur,':pe'=>$period,':a'=>$active,':s'=>$sort,':id'=>$id]
            );
            audit('admin.plan.update', null, (int)$admin['id'], "id={$id}");
            flash('success', 'Plan güncellendi.');
        } else {
            $id = db_insert(
                'INSERT INTO ' . t('plans') . ' (code,name,price,currency,period,is_active,sort,created_at)
                 VALUES (:c,:n,:p,:cu,:pe,:a,:s,NOW())',
                [':c'=>$code,':n'=>$name,':p'=>$price,':cu'=>$cur,':pe'=>$period,':a'=>$active,':s'=>$sort]
            );
            audit('admin.plan.create', null, (int)$admin['id'], "id={$id}");
            flash('success', 'Plan oluşturuldu.');
        }
        redirect('/admin/plans.php');
    }

    if ($action === 'delete') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        // Trial planını silme
        $p = db_one('SELECT code FROM ' . t('plans') . ' WHERE id = :id', [':id' => $id]);
        if ($p && $p['code'] === 'trial') {
            flash('danger', 'Deneme planı silinemez.');
            redirect('/admin/plans.php');
        }
        // Kullanılıyorsa pasif et
        $used = db_one('SELECT COUNT(*) c FROM ' . t('subscriptions') . ' WHERE plan_id = :id', [':id' => $id]);
        if ($used && (int)$used['c'] > 0) {
            db_exec('UPDATE ' . t('plans') . ' SET is_active = 0 WHERE id = :id', [':id' => $id]);
            flash('warning', 'Plan kullanıldığı için pasifleştirildi (silinmedi).');
        } else {
            db_exec('DELETE FROM ' . t('plans') . ' WHERE id = :id', [':id' => $id]);
            flash('success', 'Plan silindi.');
        }
        audit('admin.plan.delete', null, (int)$admin['id'], "id={$id}");
        redirect('/admin/plans.php');
    }
}

$plans = db_all('SELECT * FROM ' . t('plans') . ' ORDER BY sort, id');
$edit = null;
if (isset($_GET['edit'])) {
    $edit = db_one('SELECT * FROM ' . t('plans') . ' WHERE id = :id', [':id' => (int)$_GET['edit']]);
}

$pageTitle  = 'Planlar';
$pageHeader = 'Abonelik Planları';
require __DIR__ . '/../../inc/admin_header.php';
?>

<div class="cf-grid" style="grid-template-columns:1fr 360px;gap:18px;">

<div class="cf-card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;">Mevcut Planlar</h3>
        <a href="/admin/plans.php" class="btn btn-ghost btn-sm">+ Yeni</a>
    </div>
    <div class="cf-table-wrap">
        <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
            <thead>
                <tr><th>Kod</th><th>İsim</th><th>Periyot</th><th class="amount">Fiyat</th><th>Durum</th><th>Sıra</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td><code><?= e($p['code']) ?></code></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['period']) ?></td>
                    <td class="amount"><?= money($p['price'], $p['currency']) ?></td>
                    <td>
                        <span class="cf-pill <?= $p['is_active']?'success':'danger' ?>">
                            <?= $p['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </td>
                    <td><?= (int)$p['sort'] ?></td>
                    <td style="text-align:right;">
                        <a href="?edit=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-sm">Düzenle</a>
                        <?php if ($p['code'] !== 'trial'): ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Silinsin mi?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button class="btn btn-ghost btn-sm">×</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="cf-card">
    <h3><?= $edit ? 'Planı Düzenle' : 'Yeni Plan' ?></h3>
    <form method="post" class="cf-form" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div>
            <label>Kod (örn: monthly, yearly)</label>
            <input type="text" name="code" required value="<?= e($edit['code'] ?? '') ?>" maxlength="40">
        </div>
        <div>
            <label>Görünür İsim</label>
            <input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>" maxlength="120">
        </div>
        <div class="row">
            <div>
                <label>Fiyat</label>
                <input type="text" name="price" data-money value="<?= e(money_raw($edit['price'] ?? 0)) ?>">
            </div>
            <div>
                <label>Para Birimi</label>
                <input type="text" name="currency" value="<?= e($edit['currency'] ?? 'TRY') ?>" maxlength="8">
            </div>
        </div>
        <div class="row">
            <div>
                <label>Periyot</label>
                <select name="period">
                    <?php foreach (['trial','monthly','yearly','lifetime'] as $pr): ?>
                        <option value="<?= $pr ?>" <?= ($edit['period'] ?? '')===$pr?'selected':'' ?>><?= $pr ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Sıra</label>
                <input type="number" name="sort" value="<?= (int)($edit['sort'] ?? 10) ?>">
            </div>
        </div>
        <div>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="is_active" value="1" <?= ($edit['is_active'] ?? 1)?'checked':'' ?>>
                Aktif
            </label>
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button class="btn btn-primary"><?= $edit?'Güncelle':'Oluştur' ?></button>
            <?php if ($edit): ?><a class="btn btn-ghost" href="/admin/plans.php">İptal</a><?php endif; ?>
        </div>
    </form>
</div>

</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
