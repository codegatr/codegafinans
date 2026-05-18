<?php
/**
 * CODEGA Finans - Yönetici: Kullanıcılar
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';
require_once __DIR__ . '/../../inc/subscription.php';

$admin = admin_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'status') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $st = in_array($_POST['status'] ?? '', ['active','suspended','banned'], true) ? $_POST['status'] : 'active';
        db_exec('UPDATE ' . t('users') . ' SET status = :s WHERE id = :id', [':s' => $st, ':id' => $id]);
        audit('admin.user.status', null, (int)$admin['id'], "id={$id} → {$st}");
        flash('success', 'Kullanıcı durumu güncellendi.');
        redirect('/admin/users.php?detail=' . $id);
    }

    if ($action === 'activate_subscription') {
        $userId = intval_safe($_POST['user_id'] ?? 0, 1);
        $planId = intval_safe($_POST['plan_id'] ?? 0, 1);
        $note   = s($_POST['note'] ?? '', 500);
        $r = subscription_activate_manual($userId, $planId, $note, (int)$admin['id']);
        flash($r['ok'] ? 'success' : 'danger',
              $r['ok'] ? 'Abonelik etkinleştirildi ve ödeme kaydı oluşturuldu.' : ($r['message'] ?? 'Başarısız.'));
        redirect('/admin/users.php?detail=' . $userId);
    }

    if ($action === 'reset_password') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $newPass = bin2hex(random_bytes(5));
        db_exec(
            'UPDATE ' . t('users') . ' SET password = :p WHERE id = :id',
            [':p' => password_hash($newPass, CF_PASSWORD_ALGO), ':id' => $id]
        );
        audit('admin.user.password_reset', null, (int)$admin['id'], "id={$id}");
        flash('success', "Şifre sıfırlandı. Yeni şifre: <code style=\"background:#fff;padding:2px 6px;border-radius:4px;\">{$newPass}</code> (kullanıcıya iletin)");
        redirect('/admin/users.php?detail=' . $id);
    }
}

// Detay görünümü
$detailId = (int)($_GET['detail'] ?? 0);
$plans = plans_active();

if ($detailId) {
    $u = db_one('SELECT * FROM ' . t('users') . ' WHERE id = :id', [':id' => $detailId]);
    if (!$u) {
        flash('danger', 'Kullanıcı bulunamadı.');
        redirect('/admin/users.php');
    }
    $sub  = subscription_latest_for($detailId);
    $stats = [
        'tx'    => (int)(db_one("SELECT COUNT(*) c FROM " . t('transactions') . " WHERE user_id = :u", [':u' => $detailId])['c'] ?? 0),
        'goals' => (int)(db_one("SELECT COUNT(*) c FROM " . t('goals')       . " WHERE user_id = :u", [':u' => $detailId])['c'] ?? 0),
        'debts' => (int)(db_one("SELECT COUNT(*) c FROM " . t('debts')       . " WHERE user_id = :u", [':u' => $detailId])['c'] ?? 0),
    ];
    $subHistory = db_all(
        'SELECT s.*, p.name AS plan_name, p.code AS plan_code
           FROM ' . t('subscriptions') . ' s JOIN ' . t('plans') . ' p ON p.id = s.plan_id
          WHERE s.user_id = :u ORDER BY s.id DESC LIMIT 12',
        [':u' => $detailId]
    );
    $payHistory = db_all(
        'SELECT * FROM ' . t('payments') . ' WHERE user_id = :u ORDER BY id DESC LIMIT 12',
        [':u' => $detailId]
    );

    $pageTitle  = $u['name'] . ' · Detay';
    $pageHeader = 'Kullanıcı: ' . $u['name'];
    require __DIR__ . '/../../inc/admin_header.php';
?>
    <div style="margin-bottom:14px;">
        <a href="/admin/users.php" class="btn btn-ghost btn-sm">← Listeye Dön</a>
    </div>

    <div class="cf-grid cf-grid-3" style="margin-bottom:18px;">
        <div class="cf-stat balance"><div class="label">İşlem</div><div class="value"><?= (int)$stats['tx'] ?></div></div>
        <div class="cf-stat income"><div class="label">Hedef</div><div class="value"><?= (int)$stats['goals'] ?></div></div>
        <div class="cf-stat expense"><div class="label">Borç</div><div class="value"><?= (int)$stats['debts'] ?></div></div>
    </div>

    <div class="cf-grid cf-grid-2">
        <div class="cf-card">
            <h3>Profil</h3>
            <div style="font-size:13px;color:var(--cf-text-soft);line-height:1.9;">
                <strong style="color:var(--cf-text);font-size:16px;"><?= e($u['name']) ?></strong><br>
                E-posta: <strong><?= e($u['email']) ?></strong><br>
                Telefon: <?= e($u['phone'] ?: '—') ?><br>
                Para birimi: <?= e($u['currency']) ?><br>
                Genel ay bütçesi: <?= money($u['monthly_budget']) ?><br>
                Kayıt: <?= tr_datetime($u['created_at']) ?><br>
                Son giriş: <?= tr_datetime($u['last_login_at']) ?><br>
                Son IP: <?= e($u['last_login_ip'] ?: '—') ?><br>
                Durum: <span class="cf-pill <?= $u['status']==='active'?'success':($u['status']==='suspended'?'warn':'danger') ?>"><?= e($u['status']) ?></span>
            </div>

            <hr style="margin:14px 0;border:0;border-top:1px solid #eef0f4;">
            <form method="post" style="display:flex;gap:8px;align-items:end;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <div style="flex:1;">
                    <label style="font-size:12px;">Hesap durumu</label>
                    <select name="status">
                        <option value="active"    <?= $u['status']==='active'?'selected':'' ?>>Aktif</option>
                        <option value="suspended" <?= $u['status']==='suspended'?'selected':'' ?>>Askıya alınmış</option>
                        <option value="banned"    <?= $u['status']==='banned'?'selected':'' ?>>Yasaklı</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm">Güncelle</button>
            </form>

            <form method="post" style="margin-top:8px;" onsubmit="return confirm('Şifre sıfırlansın mı? Yeni şifre size gösterilecektir.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="btn btn-ghost btn-sm">🔑 Şifreyi sıfırla</button>
            </form>
        </div>

        <div class="cf-card">
            <h3>Abonelik Aksiyonları</h3>
            <?php if ($sub): ?>
                <div style="font-size:13px;color:var(--cf-text-soft);margin-bottom:10px;">
                    Mevcut: <strong><?= e($sub['plan_name']) ?></strong>
                    · <span class="cf-pill <?= $sub['status']==='active'?'success':($sub['status']==='trial'?'warn':'danger') ?>"><?= e($sub['status']) ?></span>
                    · vade <?= tr_date($sub['current_period_end']) ?>
                </div>
            <?php else: ?>
                <p class="muted">Henüz bir abonelik kaydı yok.</p>
            <?php endif; ?>

            <form method="post" class="cf-form" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="activate_subscription">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <label>Plan seç</label>
                <select name="plan_id" required>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> · <?= money($p['price']) ?> / <?= e($p['period']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Not (opsiyonel)</label>
                <input type="text" name="note" maxlength="500" placeholder="ör. Havale - dekont #1234">
                <button class="btn btn-success" style="justify-self:start;">Aboneliği Etkinleştir</button>
            </form>
        </div>
    </div>

    <h3 style="margin:20px 0 8px;">Abonelik Geçmişi</h3>
    <div class="cf-table-wrap">
        <table class="cf-table">
            <thead><tr><th>Plan</th><th>Durum</th><th>Başlangıç</th><th>Bitiş</th><th>Kaynak</th></tr></thead>
            <tbody>
            <?php foreach ($subHistory as $s): ?>
                <tr>
                    <td><?= e($s['plan_name']) ?></td>
                    <td><span class="cf-pill"><?= e($s['status']) ?></span></td>
                    <td><?= tr_date($s['started_at']) ?></td>
                    <td><?= tr_date($s['current_period_end']) ?></td>
                    <td><?= e($s['source']) ?></td>
                </tr>
            <?php endforeach; if (!$subHistory): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--cf-text-soft);">Kayıt yok</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h3 style="margin:20px 0 8px;">Ödeme Geçmişi</h3>
    <div class="cf-table-wrap">
        <table class="cf-table">
            <thead><tr><th>Tarih</th><th>Yöntem</th><th>Durum</th><th class="amount">Tutar</th><th>Not</th></tr></thead>
            <tbody>
            <?php foreach ($payHistory as $p): ?>
                <tr>
                    <td><?= tr_datetime($p['paid_at'] ?? $p['created_at']) ?></td>
                    <td><?= e($p['method']) ?></td>
                    <td><span class="cf-pill <?php
                        echo $p['status']==='succeeded'?'success':($p['status']==='pending'?'warn':'danger');
                    ?>"><?= e($p['status']) ?></span></td>
                    <td class="amount"><?= money($p['amount'], $p['currency']) ?></td>
                    <td><?= e($p['note'] ?? '') ?></td>
                </tr>
            <?php endforeach; if (!$payHistory): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--cf-text-soft);">Kayıt yok</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php
    require __DIR__ . '/../../inc/admin_footer.php';
    exit;
}

/* ----- LİSTE GÖRÜNÜMÜ ----- */
$q     = s($_GET['q'] ?? '', 80);
$page  = max(1, (int)($_GET['p'] ?? 1));
$per   = 30;
$off   = ($page - 1) * $per;

$where  = '1=1';
$params = [];
if ($q !== '') {
    $where .= ' AND (u.name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$total = (int)(db_one("SELECT COUNT(*) c FROM " . t('users') . " u WHERE $where", $params)['c'] ?? 0);
$rows  = db_all(
    "SELECT u.*, s.status AS sub_status, s.current_period_end, p.name AS plan_name
       FROM " . t('users') . " u
       LEFT JOIN " . t('subscriptions') . " s ON s.id = u.subscription_id
       LEFT JOIN " . t('plans') . " p ON p.id = s.plan_id
      WHERE $where
      ORDER BY u.id DESC
      LIMIT $per OFFSET $off",
    $params
);
$pages = max(1, (int)ceil($total / $per));

$pageTitle  = 'Kullanıcılar';
$pageHeader = 'Kullanıcılar';
require __DIR__ . '/../../inc/admin_header.php';
?>

<form method="get" class="cf-users-search">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Ad, e-posta veya telefon ile ara…">
    <button class="btn btn-primary">Ara</button>
    <?php if ($q): ?><a class="btn btn-ghost" href="/admin/users.php">Sıfırla</a><?php endif; ?>
</form>

<div class="cf-card" style="padding:0;overflow:hidden;">
    <div class="cf-table-wrap">
        <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>Plan / Durum</th>
                    <th>Vade</th>
                    <th>Son Giriş</th>
                    <th>Kayıt</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $u): ?>
                <tr>
                    <td>#<?= (int)$u['id'] ?></td>
                    <td>
                        <strong><?= e($u['name']) ?></strong>
                        <div style="font-size:12px;color:var(--cf-text-soft);"><?= e($u['email']) ?></div>
                    </td>
                    <td>
                        <?= e($u['plan_name'] ?? '—') ?><br>
                        <?php if ($u['sub_status']): ?>
                            <span class="cf-pill <?php
                                echo $u['sub_status']==='active'?'success':
                                    ($u['sub_status']==='trial'?'warn':
                                    ($u['sub_status']==='expired'?'danger':'info'));
                            ?>" style="font-size:11px;"><?= e($u['sub_status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['current_period_end'] ? tr_date($u['current_period_end']) : '—' ?></td>
                    <td><?= tr_datetime($u['last_login_at']) ?></td>
                    <td><?= tr_date($u['created_at']) ?></td>
                    <td>
                        <a href="/admin/users.php?detail=<?= (int)$u['id'] ?>" class="btn btn-ghost btn-sm">Detay →</a>
                    </td>
                </tr>
            <?php endforeach; if (!$rows): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--cf-text-soft);padding:30px;">Kayıt bulunamadı.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;padding:14px;">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?p=<?= $i ?>&q=<?= e($q) ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
