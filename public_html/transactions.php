<?php
/**
 * CODEGA Finans - Gelir & Gider yönetimi
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/finance.php';

$user = auth_require_active_subscription();
$cats = fin_categories_for((int)$user['id']);

// ----------- POST: ekle / sil ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec(
            'DELETE FROM ' . t('transactions') . ' WHERE id = :id AND user_id = :u',
            [':id' => $id, ':u' => $user['id']]
        );
        audit('tx.delete', (int)$user['id'], null, "id={$id}");
        flash('success', 'İşlem silindi.');
        redirect('/transactions.php');
    }

    if ($action === 'create') {
        $type   = ($_POST['type'] ?? 'expense') === 'income' ? 'income' : 'expense';
        $title  = s($_POST['title'] ?? '', 160);
        $amount = money_in($_POST['amount'] ?? 0);
        $catId  = intval_safe($_POST['category_id'] ?? 0, 0);
        $date   = s($_POST['tx_date'] ?? date('Y-m-d'), 10);
        $note   = s($_POST['note'] ?? '', 500);

        if ($title === '' || $amount <= 0) {
            flash('danger', 'Başlık ve tutar zorunludur.');
            redirect('/transactions.php?new=1');
        }

        $id = db_insert(
            'INSERT INTO ' . t('transactions') . '
                (user_id, category_id, type, title, amount, currency, tx_date, note, created_at)
             VALUES (:u, :c, :t, :ti, :a, :cu, :d, :n, NOW())',
            [
                ':u'  => $user['id'],
                ':c'  => $catId ?: null,
                ':t'  => $type,
                ':ti' => $title,
                ':a'  => $amount,
                ':cu' => $user['currency'] ?: 'TRY',
                ':d'  => $date,
                ':n'  => $note ?: null,
            ]
        );
        audit('tx.create', (int)$user['id'], null, "id={$id} {$type} {$amount}");
        flash('success', 'İşlem kaydedildi.');
        redirect('/transactions.php');
    }
}

// ----------- Filtreler ----------
$page  = max(1, (int)($_GET['p'] ?? 1));
$per   = 25;
$off   = ($page - 1) * $per;
$type  = in_array($_GET['type'] ?? '', ['income','expense'], true) ? $_GET['type'] : '';
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['m'] ?? '') ? $_GET['m'] : '';
$q     = s($_GET['q'] ?? '', 80);

$where  = 't.user_id = :u';
$params = [':u' => $user['id']];

if ($type !== '')  { $where .= ' AND t.type = :ty';   $params[':ty'] = $type; }
if ($month !== '') { $where .= ' AND DATE_FORMAT(t.tx_date,"%Y-%m") = :m'; $params[':m'] = $month; }
if ($q !== '')     { $where .= ' AND (t.title LIKE :q OR t.note LIKE :q)';  $params[':q'] = '%' . $q . '%'; }

$total = (int)(db_one("SELECT COUNT(*) c FROM " . t('transactions') . " t WHERE $where", $params)['c'] ?? 0);
$rows = db_all(
    "SELECT t.*, c.name AS category_name, c.color AS category_color
       FROM " . t('transactions') . " t
       LEFT JOIN " . t('categories') . " c ON c.id = t.category_id
      WHERE $where
      ORDER BY t.tx_date DESC, t.id DESC
      LIMIT $per OFFSET $off",
    $params
);
$pages = max(1, (int)ceil($total / $per));

$pageTitle  = 'Gelir &amp; Gider';
$pageHeader = 'Gelir &amp; Gider';

require __DIR__ . '/../inc/header.php';
?>

<div class="cf-page-head">
    <h2>İşlemler <small style="color:var(--cf-text-soft);font-size:13px;font-weight:500;">(toplam <?= (int)$total ?>)</small></h2>
    <div class="actions">
        <a href="/transactions.php?new=1" class="btn btn-primary">+ Yeni İşlem</a>
    </div>
</div>

<?php if (isset($_GET['new'])): ?>
<div class="cf-card" style="margin-bottom:18px;">
    <h3>Yeni Gelir / Gider Kaydı</h3>
    <form method="post" class="cf-form" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="row">
            <div>
                <label>Tür</label>
                <select name="type" required>
                    <option value="expense">Gider</option>
                    <option value="income">Gelir</option>
                </select>
            </div>
            <div>
                <label>Tarih</label>
                <input type="date" name="tx_date" value="<?= e(date('Y-m-d')) ?>" required>
            </div>
        </div>
        <div class="row">
            <div>
                <label>Başlık</label>
                <input type="text" name="title" required maxlength="160" placeholder="Örn: Market alışverişi">
            </div>
            <div>
                <label>Tutar (<?= e($user['currency'] ?: 'TRY') ?>)</label>
                <input type="text" name="amount" data-money inputmode="decimal" required placeholder="0,00">
            </div>
        </div>
        <div class="row">
            <div>
                <label>Kategori</label>
                <select name="category_id">
                    <option value="">— Seçiniz —</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c['id'] ?>">
                            <?= e($c['name']) ?> · <?= $c['type']==='income'?'Gelir':($c['type']==='expense'?'Gider':'Her İkisi') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Not (opsiyonel)</label>
                <input type="text" name="note" maxlength="500" placeholder="Ek açıklama…">
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button type="submit" class="btn btn-primary">Kaydet</button>
            <a href="/transactions.php" class="btn btn-ghost">Vazgeç</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Filtre formu -->
<form method="get" class="cf-card cf-filter-form" style="margin-bottom:18px;display:grid;gap:10px;grid-template-columns:1fr 1fr 1fr auto;align-items:end;">
    <div>
        <label style="font-size:12px;color:var(--cf-text-soft);">Arama</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Baslik / not...">
    </div>
    <div>
        <label style="font-size:12px;color:var(--cf-text-soft);">Tur</label>
        <select name="type">
            <option value="">Tumu</option>
            <option value="income"  <?= $type==='income' ?'selected':'' ?>>Gelir</option>
            <option value="expense" <?= $type==='expense'?'selected':'' ?>>Gider</option>
        </select>
    </div>
    <div>
        <label style="font-size:12px;color:var(--cf-text-soft);">Ay</label>
        <input type="month" name="m" value="<?= e($month) ?>">
    </div>
    <div class="filter-actions" style="display:flex;gap:8px;">
        <button class="btn btn-ghost">Filtrele</button>
        <a class="btn btn-outline" href="/transactions.php">Sifirla</a>
    </div>
</form>

<div class="cf-card" style="padding:0;overflow:hidden;">
    <?php if (empty($rows)): ?>
        <div class="cf-empty" style="padding:36px;">
            <div class="icon">📒</div>
            Filtreye uygun kayıt bulunamadı.
        </div>
    <?php else: ?>
        <div class="cf-table-wrap">
            <table class="cf-table cf-mobile-cards" style="box-shadow:none;border:0;border-radius:0;">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Baslik</th>
                        <th>Kategori</th>
                        <th>Tur</th>
                        <th class="amount">Tutar</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="Tarih"><?= tr_date($r['tx_date']) ?></td>
                        <td data-label="Baslik">
                            <strong><?= e($r['title']) ?></strong>
                            <?php if ($r['note']): ?>
                                <div style="font-size:12px;color:var(--cf-text-soft);"><?= e($r['note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Kategori">
                            <?php if ($r['category_name']): ?>
                                <span class="cf-pill" style="background:<?= e($r['category_color']) ?>22;color:<?= e($r['category_color']) ?>;">
                                    <?= e($r['category_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="cf-pill">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Tur">
                            <span class="cf-pill <?= $r['type']==='income'?'income':'expense' ?>">
                                <?= $r['type']==='income' ? 'Gelir' : 'Gider' ?>
                            </span>
                        </td>
                        <td data-label="Tutar" class="amount <?= $r['type']==='income'?'income':'expense' ?>">
                            <?= ($r['type']==='income'?'+':'-') ?> <?= money($r['amount'], $r['currency']) ?>
                        </td>
                        <td style="text-align:right;">
                            <form method="post" style="display:inline;" onsubmit="return confirm('Bu islem silinsin mi?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-ghost btn-sm" type="submit">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div style="display:flex;justify-content:center;gap:6px;padding:14px;">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?p=<?= $i ?>&type=<?= e($type) ?>&m=<?= e($month) ?>&q=<?= e($q) ?>"
                       class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
