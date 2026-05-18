<?php
/**
 * CODEGA Finans - Tasarruf Hedefleri
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';

$user = auth_require_active_subscription();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title    = s($_POST['title'] ?? '', 160);
        $target   = money_in($_POST['target_amount'] ?? 0);
        $current  = money_in($_POST['current_amount'] ?? 0);
        $deadline = s($_POST['deadline'] ?? '', 10) ?: null;
        $color    = s($_POST['color'] ?? '#22c55e', 20);

        if ($title === '' || $target <= 0) {
            flash('danger', 'Başlık ve hedef tutar zorunludur.');
            redirect('/goals.php?new=1');
        }
        $id = db_insert(
            'INSERT INTO ' . t('goals') . '
                (user_id,title,target_amount,current_amount,deadline,color,created_at)
             VALUES (:u,:t,:ta,:ca,:d,:co,NOW())',
            [':u'=>$user['id'],':t'=>$title,':ta'=>$target,':ca'=>$current,':d'=>$deadline,':co'=>$color]
        );
        audit('goal.create', (int)$user['id'], null, "id={$id} target={$target}");
        flash('success', 'Tasarruf hedefi oluşturuldu.');
        redirect('/goals.php');
    }

    if ($action === 'deposit') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $amount = money_in($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            flash('danger', 'Tutar pozitif olmalı.');
            redirect('/goals.php');
        }
        db_exec(
            'UPDATE ' . t('goals') . '
                SET current_amount = current_amount + :a,
                    is_completed = CASE WHEN current_amount + :a >= target_amount AND target_amount > 0 THEN 1 ELSE is_completed END
              WHERE id = :id AND user_id = :u',
            [':a' => $amount, ':id' => $id, ':u' => $user['id']]
        );
        audit('goal.deposit', (int)$user['id'], null, "id={$id} +{$amount}");
        flash('success', money($amount) . ' eklendi.');
        redirect('/goals.php');
    }

    if ($action === 'delete') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec('DELETE FROM ' . t('goals') . ' WHERE id = :id AND user_id = :u',
                [':id' => $id, ':u' => $user['id']]);
        audit('goal.delete', (int)$user['id'], null, "id={$id}");
        flash('success', 'Hedef silindi.');
        redirect('/goals.php');
    }
}

$goals = db_all(
    'SELECT * FROM ' . t('goals') . ' WHERE user_id = :u ORDER BY is_completed, id DESC',
    [':u' => $user['id']]
);

$pageTitle  = 'Tasarruf Hedefleri';
$pageHeader = 'Tasarruf Hedefleri';

require __DIR__ . '/../inc/header.php';
?>

<div class="cf-page-head">
    <h2>Hedefler <small style="color:var(--cf-text-soft);font-size:13px;font-weight:500;">(<?= count($goals) ?>)</small></h2>
    <div class="actions">
        <a href="/goals.php?new=1" class="btn btn-success">+ Yeni Hedef</a>
    </div>
</div>

<?php if (isset($_GET['new'])): ?>
<div class="cf-card" style="margin-bottom:18px;">
    <h3>Yeni Tasarruf Hedefi</h3>
    <form method="post" class="cf-form" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="row">
            <div>
                <label>Başlık</label>
                <input type="text" name="title" required maxlength="160" placeholder="Örn: Tatil Planı">
            </div>
            <div>
                <label>Vade (opsiyonel)</label>
                <input type="date" name="deadline">
            </div>
        </div>
        <div class="row">
            <div>
                <label>Hedef Tutar</label>
                <input type="text" name="target_amount" data-money required placeholder="0,00">
            </div>
            <div>
                <label>Başlangıç Birikimi</label>
                <input type="text" name="current_amount" data-money placeholder="0,00">
            </div>
        </div>
        <div>
            <label>Renk</label>
            <input type="color" name="color" value="#22c55e" style="height:42px;">
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button class="btn btn-success" type="submit">Hedef Oluştur</button>
            <a class="btn btn-ghost" href="/goals.php">Vazgeç</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (empty($goals)): ?>
    <div class="cf-card cf-empty">
        <div class="icon">🎯</div>
        Henüz bir tasarruf hedefiniz yok. İlk hedefinizi oluşturarak başlayın.
    </div>
<?php else: ?>
<div class="cf-grid cf-grid-2">
    <?php foreach ($goals as $g):
        $pct = $g['target_amount'] > 0 ? min(100, round((float)$g['current_amount']/(float)$g['target_amount']*100)) : 0; ?>
        <div class="cf-card" style="<?= $g['is_completed'] ? 'background:linear-gradient(135deg,rgba(16,185,129,.06),#fff);border-color:rgba(16,185,129,.3);' : '' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <h3 style="margin:0 0 4px;"><?= e($g['title']) ?>
                        <?php if ($g['is_completed']): ?>
                            <span class="cf-pill success" style="font-size:11px;">✓ Tamamlandı</span>
                        <?php endif; ?>
                    </h3>
                    <small style="color:var(--cf-text-soft);">
                        <?= money($g['current_amount']) ?> / <?= money($g['target_amount']) ?>
                        <?php if ($g['deadline']): ?> · vade <?= tr_date($g['deadline']) ?><?php endif; ?>
                    </small>
                </div>
                <div style="font-size:22px;font-weight:800;color:<?= e($g['color']) ?>;">%<?= $pct ?></div>
            </div>
            <div class="cf-progress" style="margin-top:12px;">
                <span style="width:<?= $pct ?>%;background:<?= e($g['color']) ?>;"></span>
            </div>

            <form method="post" style="margin-top:14px;display:flex;gap:8px;" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="deposit">
                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                <input type="text" name="amount" data-money placeholder="Birikim ekle…" style="flex:1;padding:9px 12px;border:1px solid var(--cf-border);border-radius:8px;font-size:14px;">
                <button class="btn btn-success btn-sm">+ Ekle</button>
            </form>

            <form method="post" style="margin-top:8px;" onsubmit="return confirm('Bu hedef silinsin mi?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                <button class="btn btn-ghost btn-sm">Sil</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
