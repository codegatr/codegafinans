<?php
/**
 * CODEGA Finans - Aylık Bütçe Yönetimi
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/finance.php';

$user = auth_require_active_subscription();
$cats = fin_categories_for((int)$user['id'], 'expense');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $month  = preg_match('/^\d{4}-\d{2}$/', $_POST['month'] ?? '') ? $_POST['month'] : date('Y-m');
        $catId  = (int)($_POST['category_id'] ?? 0) ?: null;
        $limit  = money_in($_POST['limit_amount'] ?? 0);

        if ($limit <= 0) {
            flash('danger', 'Limit pozitif olmalı.');
            redirect('/budgets.php?m=' . $month);
        }

        $exists = db_one(
            'SELECT id FROM ' . t('budgets') . '
              WHERE user_id = :u AND month = :m AND ((category_id IS NULL AND :c IS NULL) OR category_id = :c)',
            [':u' => $user['id'], ':m' => $month, ':c' => $catId]
        );
        if ($exists) {
            db_exec(
                'UPDATE ' . t('budgets') . ' SET limit_amount = :l WHERE id = :id',
                [':l' => $limit, ':id' => $exists['id']]
            );
        } else {
            db_insert(
                'INSERT INTO ' . t('budgets') . ' (user_id, category_id, month, limit_amount, created_at)
                 VALUES (:u, :c, :m, :l, NOW())',
                [':u' => $user['id'], ':c' => $catId, ':m' => $month, ':l' => $limit]
            );
        }
        audit('budget.save', (int)$user['id'], null, "month={$month} cat={$catId} limit={$limit}");
        flash('success', 'Bütçe kaydedildi.');
        redirect('/budgets.php?m=' . $month);
    }

    if ($action === 'delete') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec(
            'DELETE FROM ' . t('budgets') . ' WHERE id = :id AND user_id = :u',
            [':id' => $id, ':u' => $user['id']]
        );
        audit('budget.delete', (int)$user['id'], null, "id={$id}");
        flash('success', 'Bütçe silindi.');
        redirect('/budgets.php?m=' . ($_POST['month'] ?? date('Y-m')));
    }
}

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['m'] ?? '') ? $_GET['m'] : date('Y-m');

// Bu ayın bütçeleri + kategori bazlı gerçekleşen gider
$budgets = db_all(
    'SELECT b.*, c.name AS category_name, c.color AS category_color,
            (SELECT COALESCE(SUM(t.amount),0)
               FROM ' . t('transactions') . ' t
              WHERE t.user_id = :u AND t.type="expense"
                AND DATE_FORMAT(t.tx_date,"%Y-%m") = b.month
                AND ((b.category_id IS NULL) OR t.category_id = b.category_id)
            ) AS spent
       FROM ' . t('budgets') . ' b
       LEFT JOIN ' . t('categories') . ' c ON c.id = b.category_id
      WHERE b.user_id = :u AND b.month = :m
      ORDER BY b.category_id IS NULL DESC, c.sort',
    [':u' => $user['id'], ':m' => $month]
);

$pageTitle  = 'Aylık Bütçe';
$pageHeader = 'Aylık Bütçe · ' . tr_month($month);

require __DIR__ . '/../inc/header.php';
?>

<div class="cf-page-head">
    <h2>Bütçeler</h2>
    <form method="get" style="display:flex;gap:8px;align-items:center;">
        <label style="font-size:13px;color:var(--cf-text-soft);">Ay:</label>
        <input type="month" name="m" value="<?= e($month) ?>" onchange="this.form.submit()">
    </form>
</div>

<div class="cf-grid cf-grid-2" style="margin-bottom:18px;">
    <div class="cf-card">
        <h3>Yeni Bütçe Tanımla</h3>
        <p class="muted">Bir kategori için aylık limit veya tüm ay için genel limit belirleyebilirsiniz.</p>
        <form method="post" class="cf-form" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="month" value="<?= e($month) ?>">
            <div class="row">
                <div>
                    <label>Kategori</label>
                    <select name="category_id">
                        <option value="">— Genel ay bütçesi —</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Limit (<?= e($user['currency']) ?>)</label>
                    <input type="text" name="limit_amount" data-money required placeholder="0,00">
                </div>
            </div>
            <button class="btn btn-primary" style="justify-self:start;">Kaydet</button>
        </form>
    </div>

    <div class="cf-card">
        <h3>Bu Ay Özet</h3>
        <?php
        $sumLim = 0; $sumSpent = 0;
        foreach ($budgets as $b) { $sumLim += (float)$b['limit_amount']; $sumSpent += (float)$b['spent']; }
        $pct = $sumLim > 0 ? min(100, round($sumSpent / $sumLim * 100)) : 0;
        ?>
        <div style="display:flex;justify-content:space-between;font-size:14px;">
            <span>Toplam limit</span>
            <strong><?= money($sumLim) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:14px;margin-top:6px;">
            <span>Toplam harcama</span>
            <strong style="color:#b91c1c;"><?= money($sumSpent) ?></strong>
        </div>
        <div class="cf-progress <?= $pct >= 100 ? 'danger' : ($pct >= 85 ? 'warn' : '') ?>" style="margin-top:12px;">
            <span style="width:<?= $pct ?>%"></span>
        </div>
        <div style="font-size:12px;color:var(--cf-text-soft);margin-top:6px;">
            %<?= $pct ?> kullanım · kalan <strong><?= money(max(0, $sumLim - $sumSpent)) ?></strong>
        </div>
    </div>
</div>

<div class="cf-card" style="padding:0;overflow:hidden;">
    <?php if (empty($budgets)): ?>
        <div class="cf-empty" style="padding:36px;">
            <div class="icon">📊</div>
            <?= tr_month($month) ?> için bütçe tanımlanmamış.
        </div>
    <?php else: ?>
        <div class="cf-table-wrap">
            <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="amount">Limit</th>
                        <th class="amount">Harcanan</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($budgets as $b):
                    $lim = (float)$b['limit_amount'];
                    $sp  = (float)$b['spent'];
                    $p   = $lim > 0 ? min(100, round($sp / $lim * 100)) : 0;
                ?>
                    <tr>
                        <td>
                            <?php if ($b['category_name']): ?>
                                <span class="cf-pill" style="background:<?= e($b['category_color']) ?>22;color:<?= e($b['category_color']) ?>;">
                                    <?= e($b['category_name']) ?>
                                </span>
                            <?php else: ?>
                                <strong>Genel ay bütçesi</strong>
                            <?php endif; ?>
                        </td>
                        <td class="amount"><?= money($lim) ?></td>
                        <td class="amount" style="color:#b91c1c;"><?= money($sp) ?></td>
                        <td style="min-width:200px;">
                            <div class="cf-progress <?= $p >= 100 ? 'danger' : ($p >= 85 ? 'warn' : '') ?>" style="margin-top:0;">
                                <span style="width:<?= $p ?>%"></span>
                            </div>
                            <small style="color:var(--cf-text-soft);">%<?= $p ?></small>
                        </td>
                        <td style="text-align:right;">
                            <form method="post" style="display:inline;" onsubmit="return confirm('Bu bütçe silinsin mi?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                <input type="hidden" name="month" value="<?= e($month) ?>">
                                <button class="btn btn-ghost btn-sm">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
