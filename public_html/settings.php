<?php
/**
 * CODEGA Finans - Kullanıcı Ayarları
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';

$user = auth_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name     = s($_POST['name'] ?? '', 120);
        $phone    = s($_POST['phone'] ?? '', 40);
        $currency = in_array($_POST['currency'] ?? '', ['TRY','USD','EUR','GBP'], true) ? $_POST['currency'] : 'TRY';
        $monthly  = money_in($_POST['monthly_budget'] ?? 0);

        if ($name === '') { flash('danger', 'İsim boş olamaz.'); redirect('/settings.php'); }
        db_exec(
            'UPDATE ' . t('users') . '
                SET name = :n, phone = :p, currency = :c, monthly_budget = :b
              WHERE id = :id',
            [':n' => $name, ':p' => $phone ?: null, ':c' => $currency, ':b' => $monthly, ':id' => $user['id']]
        );
        audit('user.profile_update', (int)$user['id']);
        flash('success', 'Profil güncellendi.');
        redirect('/settings.php');
    }

    if ($action === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $new2    = (string)($_POST['new_password_confirmation'] ?? '');
        if (!password_verify($current, $user['password'])) {
            flash('danger', 'Mevcut şifre hatalı.'); redirect('/settings.php');
        }
        if (cf_str_len($new) < 8 || $new !== $new2) {
            flash('danger', 'Yeni şifre en az 8 karakter olmalı ve doğrulama ile eşleşmeli.');
            redirect('/settings.php');
        }
        db_exec(
            'UPDATE ' . t('users') . ' SET password = :p WHERE id = :id',
            [':p' => password_hash($new, CF_PASSWORD_ALGO), ':id' => $user['id']]
        );
        audit('user.password_change', (int)$user['id']);
        flash('success', 'Şifre güncellendi.');
        redirect('/settings.php');
    }
}

// Yenile (cache'i bypass)
$user = db_one('SELECT * FROM ' . t('users') . ' WHERE id = :id', [':id' => $user['id']]);

$pageTitle  = 'Ayarlar';
$pageHeader = 'Hesap Ayarları';

require __DIR__ . '/../inc/header.php';
?>

<div class="cf-grid cf-grid-2">
    <div class="cf-card">
        <h3>Profil Bilgileri</h3>
        <form method="post" class="cf-form" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <div class="row">
                <div>
                    <label>Ad Soyad</label>
                    <input type="text" name="name" value="<?= e($user['name']) ?>" required>
                </div>
                <div>
                    <label>Telefon</label>
                    <input type="tel" name="phone" value="<?= e($user['phone']) ?>" placeholder="+90 …">
                </div>
            </div>
            <div class="row">
                <div>
                    <label>E-posta (değiştirilemez)</label>
                    <input type="email" value="<?= e($user['email']) ?>" disabled style="opacity:.7">
                </div>
                <div>
                    <label>Para Birimi</label>
                    <select name="currency">
                        <?php foreach (['TRY' => 'Türk Lirası','USD' => 'ABD Doları','EUR' => 'Euro','GBP' => 'İngiliz Sterlini'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $user['currency']===$k?'selected':'' ?>><?= e($v) ?> (<?= $k ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label>Aylık Bütçe (genel, opsiyonel)</label>
                <input type="text" name="monthly_budget" data-money value="<?= e(money_raw($user['monthly_budget'])) ?>">
            </div>
            <button class="btn btn-primary" style="justify-self:start;">Kaydet</button>
        </form>
    </div>

    <div class="cf-card">
        <h3>Şifre Değiştir</h3>
        <form method="post" class="cf-form" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <div>
                <label>Mevcut Şifre</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="row">
                <div>
                    <label>Yeni Şifre (en az 8)</label>
                    <input type="password" name="new_password" required>
                </div>
                <div>
                    <label>Yeni Şifre (tekrar)</label>
                    <input type="password" name="new_password_confirmation" required>
                </div>
            </div>
            <button class="btn btn-primary" style="justify-self:start;">Şifreyi Güncelle</button>
        </form>

        <hr style="margin:20px 0;border:0;border-top:1px solid #eef0f4;">

        <h3>Hesap Bilgileri</h3>
        <div style="font-size:13px;color:var(--cf-text-soft);line-height:1.8;">
            Kayıt tarihi: <strong><?= tr_datetime($user['created_at']) ?></strong><br>
            Son giriş: <strong><?= tr_datetime($user['last_login_at']) ?></strong><br>
            Son IP: <strong><?= e($user['last_login_ip'] ?: '-') ?></strong><br>
            Durum: <span class="cf-pill <?= $user['status']==='active'?'success':'warn' ?>"><?= e($user['status']) ?></span>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
