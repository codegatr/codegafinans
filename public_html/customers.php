<?php
/**
 * CODEGA Finans - Cari Hesaplar
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';

$user = auth_require_active_subscription();
$uid = (int)$user['id'];

function cari_report_rows(int $userId, ?string $from = null, ?string $to = null): array
{
    $dateWhere = ' WHERE user_id = :mu';
    $params = [':u' => $userId, ':mu' => $userId];
    if ($from) {
        $dateWhere .= ' AND tx_date >= :from';
        $params[':from'] = $from;
    }
    if ($to) {
        $dateWhere .= ' AND tx_date <= :to';
        $params[':to'] = $to;
    }

    return db_all(
        'SELECT c.id, c.name, c.type, c.phone, c.email,
                COALESCE(ms.debit_total,0) AS debit_total,
                COALESCE(ms.credit_total,0) AS credit_total,
                COALESCE(ms.balance,0) AS balance,
                ms.last_tx_date
           FROM ' . t('customers') . ' c
           LEFT JOIN (
                SELECT customer_id,
                       SUM(IF(direction="debit", amount, 0)) AS debit_total,
                       SUM(IF(direction="credit", amount, 0)) AS credit_total,
                       SUM(IF(direction="debit", amount, -amount)) AS balance,
                       MAX(tx_date) AS last_tx_date
                  FROM ' . t('customer_movements') . $dateWhere . '
                 GROUP BY customer_id
           ) ms ON ms.customer_id = c.id
          WHERE c.user_id = :u AND c.is_active = 1
          ORDER BY c.name',
        $params
    );
}

function cari_redirect(?int $id = null): never
{
    redirect('/customers.php' . ($id ? '?id=' . $id : ''));
}

$reportFrom = isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['from']) ? (string)$_GET['from'] : null;
$reportTo = isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['to']) ? (string)$_GET['to'] : null;
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = cari_report_rows($uid, $reportFrom, $reportTo);
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cari-rapor-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Cari', 'Tur', 'Telefon', 'E-posta', 'Borc', 'Alacak', 'Bakiye', 'Durum', 'Son Hareket'], ';');
    foreach ($rows as $r) {
        $balance = (float)$r['balance'];
        fputcsv($out, [
            $r['name'],
            $r['type'],
            $r['phone'],
            $r['email'],
            number_format((float)$r['debit_total'], 2, ',', '.'),
            number_format((float)$r['credit_total'], 2, ',', '.'),
            number_format(abs($balance), 2, ',', '.'),
            $balance >= 0 ? 'Alacak' : 'Borc',
            $r['last_tx_date'] ?: '',
        ], ';');
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_customer') {
        $name = s($_POST['name'] ?? '', 160);
        $type = (string)($_POST['type'] ?? 'customer');
        if (!in_array($type, ['customer','supplier','both'], true)) { $type = 'customer'; }
        if ($name === '') {
            flash('danger', 'Cari adi zorunludur.');
            redirect('/customers.php?new=1');
        }

        $id = db_insert(
            'INSERT INTO ' . t('customers') . '
                (user_id,type,name,phone,email,tax_no,address,note,created_at)
             VALUES (:u,:ty,:n,:p,:e,:tax,:a,:nt,NOW())',
            [
                ':u' => $uid,
                ':ty' => $type,
                ':n' => $name,
                ':p' => s($_POST['phone'] ?? '', 40) ?: null,
                ':e' => s($_POST['email'] ?? '', 160) ?: null,
                ':tax' => s($_POST['tax_no'] ?? '', 40) ?: null,
                ':a' => s($_POST['address'] ?? '', 500) ?: null,
                ':nt' => s($_POST['note'] ?? '', 500) ?: null,
            ]
        );
        audit('customer.create', $uid, null, "id={$id}");
        flash('success', 'Cari karti olusturuldu.');
        cari_redirect($id);
    }

    if ($action === 'update_customer') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $name = s($_POST['name'] ?? '', 160);
        $type = (string)($_POST['type'] ?? 'customer');
        if (!in_array($type, ['customer','supplier','both'], true)) { $type = 'customer'; }
        if ($name === '') {
            flash('danger', 'Cari adi zorunludur.');
            cari_redirect($id);
        }

        db_exec(
            'UPDATE ' . t('customers') . '
                SET type=:ty, name=:n, phone=:p, email=:e, tax_no=:tax, address=:a, note=:nt
              WHERE id=:id AND user_id=:u',
            [
                ':ty' => $type,
                ':n' => $name,
                ':p' => s($_POST['phone'] ?? '', 40) ?: null,
                ':e' => s($_POST['email'] ?? '', 160) ?: null,
                ':tax' => s($_POST['tax_no'] ?? '', 40) ?: null,
                ':a' => s($_POST['address'] ?? '', 500) ?: null,
                ':nt' => s($_POST['note'] ?? '', 500) ?: null,
                ':id' => $id,
                ':u' => $uid,
            ]
        );
        audit('customer.update', $uid, null, "id={$id}");
        flash('success', 'Cari bilgileri guncellendi.');
        cari_redirect($id);
    }

    if ($action === 'add_movement') {
        $customerId = intval_safe($_POST['customer_id'] ?? 0, 1);
        $customer = db_one('SELECT id FROM ' . t('customers') . ' WHERE id=:id AND user_id=:u AND is_active=1',
                           [':id' => $customerId, ':u' => $uid]);
        if (!$customer) {
            flash('danger', 'Cari bulunamadi.');
            cari_redirect();
        }

        $direction = (string)($_POST['direction'] ?? 'debit');
        if (!in_array($direction, ['debit','credit'], true)) { $direction = 'debit'; }
        $amount = money_in($_POST['amount'] ?? 0);
        $title = s($_POST['title'] ?? '', 160);
        $date = s($_POST['tx_date'] ?? '', 10) ?: date('Y-m-d');

        if ($amount <= 0 || $title === '') {
            flash('danger', 'Hareket basligi ve pozitif tutar zorunludur.');
            cari_redirect($customerId);
        }

        $mid = db_insert(
            'INSERT INTO ' . t('customer_movements') . '
                (user_id,customer_id,direction,amount,tx_date,title,note,created_at)
             VALUES (:u,:c,:d,:a,:dt,:t,:n,NOW())',
            [
                ':u' => $uid,
                ':c' => $customerId,
                ':d' => $direction,
                ':a' => $amount,
                ':dt' => $date,
                ':t' => $title,
                ':n' => s($_POST['note'] ?? '', 500) ?: null,
            ]
        );
        audit('customer.movement.create', $uid, null, "id={$mid} customer={$customerId}");
        flash('success', 'Cari hareket islendi.');
        cari_redirect($customerId);
    }

    if ($action === 'delete_movement') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $customerId = intval_safe($_POST['customer_id'] ?? 0, 1);
        db_exec('DELETE FROM ' . t('customer_movements') . ' WHERE id=:id AND user_id=:u AND customer_id=:c',
                [':id' => $id, ':u' => $uid, ':c' => $customerId]);
        audit('customer.movement.delete', $uid, null, "id={$id} customer={$customerId}");
        flash('success', 'Cari hareket silindi.');
        cari_redirect($customerId);
    }

    if ($action === 'archive_customer') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec('UPDATE ' . t('customers') . ' SET is_active=0 WHERE id=:id AND user_id=:u',
                [':id' => $id, ':u' => $uid]);
        audit('customer.archive', $uid, null, "id={$id}");
        flash('success', 'Cari arsive alindi.');
        cari_redirect();
    }
}

$customers = db_all(
    'SELECT c.*,
            COALESCE(ms.debit_total,0) AS debit_total,
            COALESCE(ms.credit_total,0) AS credit_total,
            COALESCE(ms.balance,0) AS balance
       FROM ' . t('customers') . ' c
       LEFT JOIN (
            SELECT customer_id,
                   SUM(IF(direction="debit", amount, 0)) AS debit_total,
                   SUM(IF(direction="credit", amount, 0)) AS credit_total,
                   SUM(IF(direction="debit", amount, -amount)) AS balance
              FROM ' . t('customer_movements') . '
             WHERE user_id = :mu
             GROUP BY customer_id
       ) ms ON ms.customer_id = c.id
      WHERE c.user_id = :u AND c.is_active = 1
      ORDER BY c.name',
    [':u' => $uid, ':mu' => $uid]
);

$selectedId = isset($_GET['id']) ? intval_safe($_GET['id'], 1) : 0;
if (!$selectedId && $customers) { $selectedId = (int)$customers[0]['id']; }

$selected = null;
foreach ($customers as $c) {
    if ((int)$c['id'] === $selectedId) { $selected = $c; break; }
}

$movements = [];
if ($selected) {
    $movements = db_all(
        'SELECT * FROM ' . t('customer_movements') . '
          WHERE user_id=:u AND customer_id=:c
          ORDER BY tx_date DESC, id DESC',
        [':u' => $uid, ':c' => (int)$selected['id']]
    );
}

$totalDebit = 0.0; $totalCredit = 0.0;
foreach ($customers as $c) {
    $totalDebit += (float)$c['debit_total'];
    $totalCredit += (float)$c['credit_total'];
}
$netBalance = $totalDebit - $totalCredit;
$reportRows = cari_report_rows($uid, $reportFrom, $reportTo);
$reportDebit = 0.0; $reportCredit = 0.0;
foreach ($reportRows as $r) {
    $reportDebit += (float)$r['debit_total'];
    $reportCredit += (float)$r['credit_total'];
}
$reportNet = $reportDebit - $reportCredit;

$pageTitle = 'Cariler';
$pageHeader = 'Cari Hesaplar';
require __DIR__ . '/../inc/header.php';
?>

<div class="cf-grid cf-grid-3" style="margin-bottom:18px;">
    <div class="cf-stat balance">
        <div class="label">Toplam Cari</div>
        <div class="value"><?= count($customers) ?></div>
        <div class="sub">Aktif musteri / tedarikci karti</div>
    </div>
    <div class="cf-stat income">
        <div class="label">Toplam Borclandirma</div>
        <div class="value"><?= money($totalDebit) ?></div>
        <div class="sub">Carilerin size borclandigi tutar</div>
    </div>
    <div class="cf-stat <?= $netBalance >= 0 ? 'gold' : 'expense' ?>">
        <div class="label">Net Cari Bakiye</div>
        <div class="value"><?= money(abs($netBalance)) ?></div>
        <div class="sub"><?= $netBalance >= 0 ? 'Tahsil edilecek net bakiye' : 'Odenecek net bakiye' ?></div>
    </div>
</div>

<div class="cf-page-head">
    <h2>Cariler</h2>
    <div class="actions">
        <a href="#cari-rapor" class="btn btn-ghost">Cari Rapor</a>
        <a href="/customers.php?new=1" class="btn btn-primary">+ Yeni Cari</a>
    </div>
</div>

<div id="cari-rapor" class="cf-card" style="margin-bottom:18px;">
    <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h3 style="margin-bottom:4px;">Cari Rapor</h3>
            <div class="muted">Secili tarih araligina gore borc, alacak ve net bakiye ozeti.</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="btn btn-ghost" onclick="window.print()">Yazdir</button>
            <a class="btn btn-outline" href="/customers.php?export=csv<?= $reportFrom ? '&from=' . e($reportFrom) : '' ?><?= $reportTo ? '&to=' . e($reportTo) : '' ?>">CSV indir</a>
        </div>
    </div>
    <form method="get" class="cf-form" style="margin-top:14px;">
        <div class="row">
            <div>
                <label>Baslangic</label>
                <input type="date" name="from" value="<?= e($reportFrom ?? '') ?>">
            </div>
            <div>
                <label>Bitis</label>
                <input type="date" name="to" value="<?= e($reportTo ?? '') ?>">
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn btn-primary">Raporu Getir</button>
            <a class="btn btn-ghost" href="/customers.php#cari-rapor">Sifirla</a>
        </div>
    </form>
    <div class="cf-grid cf-grid-3" style="margin:16px 0;">
        <div class="cf-stat income">
            <div class="label">Rapor Borc</div>
            <div class="value"><?= money($reportDebit) ?></div>
            <div class="sub">Carilere islenen borc</div>
        </div>
        <div class="cf-stat expense">
            <div class="label">Rapor Alacak</div>
            <div class="value"><?= money($reportCredit) ?></div>
            <div class="sub">Odemeler / alacak kayitlari</div>
        </div>
        <div class="cf-stat <?= $reportNet >= 0 ? 'gold' : 'expense' ?>">
            <div class="label">Rapor Net</div>
            <div class="value"><?= money(abs($reportNet)) ?></div>
            <div class="sub"><?= $reportNet >= 0 ? 'Tahsil edilecek' : 'Odenecek' ?></div>
        </div>
    </div>
    <div class="cf-table-wrap">
        <table class="cf-table" style="box-shadow:none;border-radius:10px;">
            <thead><tr><th>Cari</th><th>Tur</th><th class="amount">Borc</th><th class="amount">Alacak</th><th class="amount">Bakiye</th><th>Son Hareket</th></tr></thead>
            <tbody>
            <?php foreach ($reportRows as $r):
                $bal = (float)$r['balance'];
            ?>
                <tr>
                    <td><strong><?= e($r['name']) ?></strong><div style="font-size:12px;color:var(--cf-muted);"><?= e($r['phone'] ?: '') ?> <?= e($r['email'] ?: '') ?></div></td>
                    <td><?= e($r['type']) ?></td>
                    <td class="amount income"><?= money($r['debit_total']) ?></td>
                    <td class="amount expense"><?= money($r['credit_total']) ?></td>
                    <td class="amount <?= $bal >= 0 ? 'income' : 'expense' ?>"><?= money(abs($bal)) ?> <?= $bal >= 0 ? 'Alacak' : 'Borc' ?></td>
                    <td><?= $r['last_tx_date'] ? tr_date($r['last_tx_date']) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$reportRows): ?><tr><td colspan="6" class="muted">Raporlanacak cari bulunamadi.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($_GET['new'])): ?>
<div class="cf-card" style="margin-bottom:18px;">
    <h3>Yeni Cari Karti</h3>
    <form method="post" class="cf-form" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_customer">
        <div class="row">
            <div>
                <label>Cari Adi</label>
                <input type="text" name="name" required maxlength="160" placeholder="Orn: ABC Ltd. Sti.">
            </div>
            <div>
                <label>Tur</label>
                <select name="type">
                    <option value="customer">Musteri</option>
                    <option value="supplier">Tedarikci</option>
                    <option value="both">Musteri + Tedarikci</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div><label>Telefon</label><input type="text" name="phone" maxlength="40"></div>
            <div><label>E-posta</label><input type="email" name="email" maxlength="160"></div>
        </div>
        <div class="row">
            <div><label>Vergi / TCKN No</label><input type="text" name="tax_no" maxlength="40"></div>
            <div><label>Not</label><input type="text" name="note" maxlength="500"></div>
        </div>
        <div>
            <label>Adres</label>
            <input type="text" name="address" maxlength="500">
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button class="btn btn-primary">Cari Olustur</button>
            <a class="btn btn-ghost" href="/customers.php">Vazgec</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (!$customers): ?>
    <div class="cf-card cf-empty">
        <div class="icon">□</div>
        Henuz cari karti yok. Ilk musteriyi veya tedarikciyi ekleyin.
    </div>
<?php else: ?>
<div class="cf-grid cf-cari-layout">
    <div class="cf-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;">
            <h3 style="margin:0;">Cari Listesi</h3>
        </div>
        <div style="display:grid;">
            <?php foreach ($customers as $c):
                $bal = (float)$c['balance'];
                $active = $selected && (int)$selected['id'] === (int)$c['id'];
            ?>
                <a href="/customers.php?id=<?= (int)$c['id'] ?>" style="display:flex;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:1px solid #eef0f4;background:<?= $active ? '#f8fafc' : '#fff' ?>;">
                    <span>
                        <strong style="display:block;color:var(--cf-text);"><?= e($c['name']) ?></strong>
                        <small style="color:var(--cf-text-soft);"><?= e($c['type']) ?></small>
                    </span>
                    <span style="text-align:right;">
                        <strong style="color:<?= $bal >= 0 ? '#047857' : '#b91c1c' ?>;"><?= money(abs($bal)) ?></strong>
                        <small style="display:block;color:var(--cf-muted);"><?= $bal >= 0 ? 'Alacak' : 'Borc' ?></small>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($selected):
        $balance = (float)$selected['balance'];
    ?>
    <div style="display:grid;gap:14px;">
        <div class="cf-card">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <h3 style="margin-bottom:4px;"><?= e($selected['name']) ?></h3>
                    <div style="font-size:13px;color:var(--cf-text-soft);">
                        <?= e($selected['phone'] ?: '-') ?> · <?= e($selected['email'] ?: '-') ?>
                        <?php if ($selected['tax_no']): ?> · Vergi/TCKN: <?= e($selected['tax_no']) ?><?php endif; ?>
                    </div>
                    <?php if ($selected['address']): ?><div style="font-size:12px;color:var(--cf-muted);margin-top:4px;"><?= e($selected['address']) ?></div><?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:13px;color:var(--cf-text-soft);">Cari Bakiye</div>
                    <div style="font-size:26px;font-weight:800;color:<?= $balance >= 0 ? '#047857' : '#b91c1c' ?>;">
                        <?= money(abs($balance)) ?>
                    </div>
                    <span class="cf-pill <?= $balance >= 0 ? 'success' : 'danger' ?>"><?= $balance >= 0 ? 'Alacaklisiniz' : 'Borclusunuz' ?></span>
                </div>
            </div>
        </div>

        <div class="cf-card">
            <h3>Yeni Hareket</h3>
            <form method="post" class="cf-form" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_movement">
                <input type="hidden" name="customer_id" value="<?= (int)$selected['id'] ?>">
                <div class="row">
                    <div>
                        <label>Islem Turu</label>
                        <select name="direction">
                            <option value="debit">Borclandir - cari size borclu</option>
                            <option value="credit">Alacaklandir / odeme - siz borclusunuz veya tahsilat</option>
                        </select>
                    </div>
                    <div>
                        <label>Tarih</label>
                        <input type="date" name="tx_date" value="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div><label>Baslik</label><input type="text" name="title" required maxlength="160" placeholder="Orn: Fatura, odeme, tahsilat"></div>
                    <div><label>Tutar</label><input type="text" name="amount" data-money required placeholder="0,00"></div>
                </div>
                <div><label>Not</label><input type="text" name="note" maxlength="500"></div>
                <button class="btn btn-primary" style="justify-self:start;">Hareket Kaydet</button>
            </form>
        </div>

        <div class="cf-card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;">Cari Hareketleri</h3>
                <form method="post" onsubmit="return confirm('Bu cari arsive alinsin mi?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="archive_customer">
                    <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
                    <button class="btn btn-ghost btn-sm">Arsive Al</button>
                </form>
            </div>
            <?php if (!$movements): ?>
                <div class="cf-empty" style="padding:28px;">Bu cariye ait hareket yok.</div>
            <?php else: ?>
            <div class="cf-table-wrap">
                <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
                    <thead><tr><th>Tarih</th><th>Baslik</th><th>Tur</th><th class="amount">Tutar</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><?= tr_date($m['tx_date']) ?></td>
                            <td><strong><?= e($m['title']) ?></strong><?php if ($m['note']): ?><div style="font-size:12px;color:var(--cf-muted);"><?= e($m['note']) ?></div><?php endif; ?></td>
                            <td><span class="cf-pill <?= $m['direction'] === 'debit' ? 'income' : 'expense' ?>"><?= $m['direction'] === 'debit' ? 'Borc' : 'Alacak' ?></span></td>
                            <td class="amount <?= $m['direction'] === 'debit' ? 'income' : 'expense' ?>"><?= money($m['amount']) ?></td>
                            <td style="text-align:right;">
                                <form method="post" onsubmit="return confirm('Hareket silinsin mi?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_movement">
                                    <input type="hidden" name="customer_id" value="<?= (int)$selected['id'] ?>">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
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
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
