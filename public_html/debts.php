<?php
/**
 * CODEGA Finans - Borç Kontrolü
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';

$user = auth_require_active_subscription();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $creditor    = s($_POST['creditor'] ?? '', 160);
        $total       = money_in($_POST['total_amount'] ?? 0);
        $paid        = money_in($_POST['paid_amount'] ?? 0);
        $due         = s($_POST['due_date'] ?? '', 10) ?: null;
        $installments= intval_safe($_POST['installments'] ?? 1, 1, 360);
        $interest    = (float) str_replace(',', '.', (string)($_POST['interest_pct'] ?? 0));
        $note        = s($_POST['note'] ?? '', 500);

        if ($creditor === '' || $total <= 0) {
            flash('danger', 'Alacaklı ve toplam tutar zorunludur.');
            redirect('/debts.php?new=1');
        }
        $id = db_insert(
            'INSERT INTO ' . t('debts') . '
                (user_id,creditor,total_amount,paid_amount,due_date,installments,interest_pct,note,created_at)
             VALUES (:u,:cr,:tt,:pa,:du,:in,:ip,:nt,NOW())',
            [':u'=>$user['id'],':cr'=>$creditor,':tt'=>$total,':pa'=>$paid,':du'=>$due,
             ':in'=>$installments,':ip'=>$interest,':nt'=>$note ?: null]
        );
        audit('debt.create', (int)$user['id'], null, "id={$id} total={$total}");
        flash('success', 'Borç kaydedildi.');
        redirect('/debts.php');
    }

    if ($action === 'pay') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $amt = money_in($_POST['amount'] ?? 0);
        if ($amt <= 0) { flash('danger', 'Tutar pozitif olmalı.'); redirect('/debts.php'); }

        $d = db_one('SELECT * FROM ' . t('debts') . ' WHERE id = :id AND user_id = :u',
                    [':id' => $id, ':u' => $user['id']]);
        if (!$d) { flash('danger','Kayıt yok.'); redirect('/debts.php'); }

        db()->beginTransaction();
        try {
            db_exec(
                'UPDATE ' . t('debts') . '
                    SET paid_amount = LEAST(total_amount, paid_amount + :a),
                        is_closed = CASE WHEN (paid_amount + :a) >= total_amount THEN 1 ELSE 0 END
                  WHERE id = :id',
                [':a' => $amt, ':id' => $id]
            );
            db_exec(
                'INSERT INTO ' . t('debt_payments') . ' (debt_id,user_id,amount,paid_at,created_at)
                 VALUES (:d,:u,:a,CURDATE(),NOW())',
                [':d' => $id, ':u' => $user['id'], ':a' => $amt]
            );
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            flash('danger', 'Kayıt sırasında hata oluştu.');
            redirect('/debts.php');
        }

        audit('debt.pay', (int)$user['id'], null, "id={$id} +{$amt}");
        flash('success', money($amt) . ' ödeme işlendi.');
        redirect('/debts.php');
    }

    if ($action === 'delete') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec('DELETE FROM ' . t('debt_payments') . ' WHERE debt_id = :id AND user_id = :u',
                [':id' => $id, ':u' => $user['id']]);
        db_exec('DELETE FROM ' . t('debts') . ' WHERE id = :id AND user_id = :u',
                [':id' => $id, ':u' => $user['id']]);
        audit('debt.delete', (int)$user['id'], null, "id={$id}");
        flash('success', 'Borç silindi.');
        redirect('/debts.php');
    }
}

$debts = db_all(
    'SELECT * FROM ' . t('debts') . ' WHERE user_id = :u ORDER BY is_closed, due_date IS NULL, due_date',
    [':u' => $user['id']]
);

$pageTitle  = 'Borç Kontrolü';
$pageHeader = 'Borç Kontrolü';

require __DIR__ . '/../inc/header.php';

$totalOpen = 0; $totalPaid = 0;
foreach ($debts as $d) {
    if (!$d['is_closed']) { $totalOpen += (float)$d['total_amount'] - (float)$d['paid_amount']; }
    $totalPaid += (float)$d['paid_amount'];
}
?>

<div class="cf-grid cf-grid-2" style="margin-bottom:18px;">
    <div class="cf-stat expense">
        <div class="label">Açık Borç Toplamı</div>
        <div class="value"><?= money($totalOpen) ?></div>
        <div class="sub">Henüz ödenmemiş tutar</div>
    </div>
    <div class="cf-stat income">
        <div class="label">Toplam Ödenen</div>
        <div class="value"><?= money($totalPaid) ?></div>
        <div class="sub">Tüm ödemeler dahil</div>
    </div>
</div>

<div class="cf-page-head">
    <h2>Borçlar</h2>
    <div class="actions">
        <a href="/debts.php?new=1" class="btn btn-danger">+ Yeni Borç</a>
    </div>
</div>

<?php if (isset($_GET['new'])): ?>
<div class="cf-card" style="margin-bottom:18px;">
    <h3>Yeni Borç Kaydı</h3>
    <form method="post" class="cf-form" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="row">
            <div>
                <label>Alacaklı / Kurum</label>
                <input type="text" name="creditor" required maxlength="160" placeholder="Örn: ABC Bankası">
            </div>
            <div>
                <label>Vade Tarihi</label>
                <input type="date" name="due_date">
            </div>
        </div>
        <div class="row">
            <div>
                <label>Toplam Tutar</label>
                <input type="text" name="total_amount" data-money required placeholder="0,00">
            </div>
            <div>
                <label>Ödenmiş Tutar</label>
                <input type="text" name="paid_amount" data-money placeholder="0,00">
            </div>
        </div>
        <div class="row">
            <div>
                <label>Taksit Sayısı</label>
                <input type="number" name="installments" value="1" min="1" max="360">
            </div>
            <div>
                <label>Faiz Oranı (%)</label>
                <input type="text" name="interest_pct" value="0" placeholder="0,00">
            </div>
        </div>
        <div>
            <label>Not</label>
            <input type="text" name="note" maxlength="500" placeholder="Ek açıklama…">
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button class="btn btn-danger">Kaydet</button>
            <a class="btn btn-ghost" href="/debts.php">Vazgeç</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (empty($debts)): ?>
    <div class="cf-card cf-empty">
        <div class="icon">🔓</div>
        Aktif borcunuz yok. Tebrikler!
    </div>
<?php else: ?>
<div class="cf-grid" style="grid-template-columns:1fr;gap:14px;">
<?php foreach ($debts as $d):
    $remaining = max(0, (float)$d['total_amount'] - (float)$d['paid_amount']);
    $pct = $d['total_amount'] > 0 ? min(100, round((float)$d['paid_amount']/(float)$d['total_amount']*100)) : 0;
    $overdue = $d['due_date'] && !$d['is_closed'] && $d['due_date'] < date('Y-m-d');
?>
    <div class="cf-card" style="<?= $d['is_closed'] ? 'opacity:.7' : '' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
            <div>
                <h3 style="margin:0 0 4px;">
                    <?= e($d['creditor']) ?>
                    <?php if ($d['is_closed']): ?>
                        <span class="cf-pill success" style="font-size:11px;">✓ Kapandı</span>
                    <?php elseif ($overdue): ?>
                        <span class="cf-pill danger" style="font-size:11px;">⚠ Vadesi geçti</span>
                    <?php endif; ?>
                </h3>
                <div style="font-size:13px;color:var(--cf-text-soft);">
                    Toplam <strong><?= money($d['total_amount']) ?></strong> ·
                    Ödenen <strong style="color:#047857;"><?= money($d['paid_amount']) ?></strong> ·
                    Kalan <strong style="color:#b91c1c;"><?= money($remaining) ?></strong>
                    <?php if ($d['due_date']): ?>· Vade <?= tr_date($d['due_date']) ?><?php endif; ?>
                    <?php if ($d['installments'] > 1): ?>· <?= (int)$d['installments'] ?> taksit<?php endif; ?>
                </div>
                <?php if ($d['note']): ?>
                    <div style="font-size:12px;color:var(--cf-muted);margin-top:4px;"><?= e($d['note']) ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align:right;min-width:160px;">
                <div style="font-size:22px;font-weight:800;">%<?= $pct ?></div>
                <small style="color:var(--cf-text-soft);">ödeme oranı</small>
            </div>
        </div>

        <div class="cf-progress <?= $pct >= 100 ? '' : ($overdue ? 'danger' : '') ?>" style="margin-top:10px;">
            <span style="width:<?= $pct ?>%"></span>
        </div>

        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
            <?php if (!$d['is_closed']): ?>
            <form method="post" style="display:flex;gap:8px;flex:1;" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <input type="text" name="amount" data-money placeholder="Ödeme tutarı" style="flex:1;padding:9px 12px;border:1px solid var(--cf-border);border-radius:8px;font-size:14px;">
                <button class="btn btn-success btn-sm">Ödeme Kaydet</button>
            </form>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Bu borç ve tüm ödeme kayıtları silinsin mi?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <button class="btn btn-ghost btn-sm">Sil</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
