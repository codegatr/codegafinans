<?php
/**
 * CODEGA Finans - Yönetici: Sistem Geneli İşlem Gözlemi (READ-ONLY)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

$admin = admin_require();

$page = max(1, (int)($_GET['p'] ?? 1));
$per = 40;
$off = ($page - 1) * $per;
$type = in_array($_GET['type'] ?? '', ['income','expense'], true) ? $_GET['type'] : '';
$userQ = s($_GET['user'] ?? '', 120);

$where = '1=1'; $params = [];
if ($type) { $where .= ' AND t.type = :ty'; $params[':ty'] = $type; }
if ($userQ !== '') {
    $where .= ' AND (u.name LIKE :uq OR u.email LIKE :uq)';
    $params[':uq'] = '%' . $userQ . '%';
}

$total = (int)(db_one(
    "SELECT COUNT(*) c FROM " . t('transactions') . " t JOIN " . t('users') . " u ON u.id = t.user_id WHERE $where",
    $params
)['c'] ?? 0);

$rows = db_all(
    "SELECT t.*, u.name AS user_name, u.email AS user_email, c.name AS category_name, c.color AS category_color
       FROM " . t('transactions') . " t
       JOIN " . t('users') . " u ON u.id = t.user_id
       LEFT JOIN " . t('categories') . " c ON c.id = t.category_id
      WHERE $where
      ORDER BY t.id DESC
      LIMIT $per OFFSET $off",
    $params
);
$pages = max(1, (int)ceil($total / $per));

$pageTitle  = 'Tüm İşlemler';
$pageHeader = 'Sistem Geneli İşlemler';
require __DIR__ . '/../../inc/admin_header.php';
?>

<form method="get" class="cf-card" style="margin-bottom:14px;display:grid;gap:10px;grid-template-columns:1fr 1fr auto;align-items:end;">
    <div>
        <label style="font-size:12px;color:var(--cf-text-soft);">Kullanıcı (ad/e-posta)</label>
        <input type="text" name="user" value="<?= e($userQ) ?>" placeholder="ör. Yunus">
    </div>
    <div>
        <label style="font-size:12px;color:var(--cf-text-soft);">Tür</label>
        <select name="type">
            <option value="">Tümü</option>
            <option value="income"  <?= $type==='income' ?'selected':'' ?>>Gelir</option>
            <option value="expense" <?= $type==='expense'?'selected':'' ?>>Gider</option>
        </select>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary">Filtrele</button>
        <a class="btn btn-ghost" href="/admin/transactions.php">Sıfırla</a>
    </div>
</form>

<div class="cf-card" style="padding:0;overflow:hidden;">
    <div class="cf-table-wrap">
        <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kullanıcı</th>
                    <th>Başlık</th>
                    <th>Kategori</th>
                    <th>Tür</th>
                    <th class="amount">Tutar</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= tr_date($r['tx_date']) ?></td>
                    <td>
                        <strong><?= e($r['user_name']) ?></strong>
                        <div style="font-size:12px;color:var(--cf-text-soft);"><?= e($r['user_email']) ?></div>
                    </td>
                    <td><?= e($r['title']) ?></td>
                    <td>
                        <?php if ($r['category_name']): ?>
                            <span class="cf-pill" style="background:<?= e($r['category_color']) ?>22;color:<?= e($r['category_color']) ?>;">
                                <?= e($r['category_name']) ?>
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <span class="cf-pill <?= $r['type']==='income'?'income':'expense' ?>">
                            <?= $r['type']==='income'?'Gelir':'Gider' ?>
                        </span>
                    </td>
                    <td class="amount <?= $r['type']==='income'?'income':'expense' ?>">
                        <?= ($r['type']==='income'?'+':'−') ?> <?= money($r['amount'], $r['currency']) ?>
                    </td>
                </tr>
            <?php endforeach; if (!$rows): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--cf-text-soft);padding:30px;">Kayıt yok.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;padding:14px;flex-wrap:wrap;">
            <?php for ($i = max(1,$page-5); $i <= min($pages,$page+5); $i++): ?>
                <a href="?p=<?= $i ?>&type=<?= e($type) ?>&user=<?= e($userQ) ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <small style="color:var(--cf-muted);align-self:center;margin-left:10px;">Toplam <?= number_format($total, 0, ',', '.') ?> kayıt</small>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
