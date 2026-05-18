<?php
/**
 * CODEGA Finans - First admin setup
 *
 * Shared hosting helper for installations without SSH access.
 * This page only creates the first superadmin account. After an admin exists,
 * it becomes read-only and refuses further changes.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/migrate.php';
require_once __DIR__ . '/../inc/functions.php';

start_session();

$error = null;
$success = null;

try {
    cf_migrate_all();
    $row = db_one('SELECT COUNT(*) AS c FROM ' . t('admins'));
    $adminCount = (int)($row['c'] ?? 0);
} catch (Throwable $e) {
    $adminCount = -1;
    $error = CF_DEBUG ? $e->getMessage() : 'Veritabani baglantisi veya migration sirasinda hata olustu.';
}

if ($adminCount === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name = s($_POST['name'] ?? '', 120);
    $email = cf_str_lower(s($_POST['email'] ?? '', 160));
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password_confirmation'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ad soyad ve gecerli e-posta zorunludur.';
    } elseif (cf_str_len($pass) < 10) {
        $error = 'Sifre en az 10 karakter olmalidir.';
    } elseif ($pass !== $pass2) {
        $error = 'Sifre dogrulamasi eslesmiyor.';
    } else {
        db_insert(
            'INSERT INTO ' . t('admins') . ' (name, email, password, role, is_active, created_at)
             VALUES (:n, :e, :p, "superadmin", 1, NOW())',
            [
                ':n' => $name,
                ':e' => $email,
                ':p' => password_hash($pass, CF_PASSWORD_ALGO),
            ]
        );

        $adminCount = 1;
        $success = 'Superadmin hesabi olusturuldu. Artik yonetim paneline giris yapabilirsiniz.';
        clear_old();
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Ilk Yonetici Kurulumu - CODEGA Finans</title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.cf-auth::before { background: #2563eb !important; }
.cf-auth::after { background: #16a34a !important; }
.setup-note { color:#94a3b8;font-size:13px;line-height:1.55;margin-top:14px; }
.setup-note code { color:#e2e8f0; }
</style>
</head>
<body class="cf-auth">
<div class="cf-auth-card">
    <div class="cf-auth-brand">
        <span class="logo">CF</span>
        <div class="name">
            Ilk Yonetici Kurulumu
            <div style="font-size:11px;color:#94a3b8;font-weight:500;margin-top:2px;">CODEGA Finans</div>
        </div>
    </div>

    <?php if ($adminCount > 0): ?>
        <h1>Kurulum tamam</h1>
        <?php if ($success): ?>
            <div class="cf-flash success" style="margin-bottom:14px;"><?= e($success) ?></div>
        <?php else: ?>
            <div class="cf-flash info" style="margin-bottom:14px;">Sistemde zaten yonetici hesabi var. Bu sayfa yeni hesap olusturamaz.</div>
        <?php endif; ?>
        <a class="btn-primary" href="/admin/login.php" style="display:block;text-align:center;text-decoration:none;">Yonetim paneline git</a>
        <p class="setup-note">Guvenlik icin kurulumdan sonra <code>setup_admin.php</code> dosyasini silebilirsiniz.</p>
    <?php else: ?>
        <h1>Superadmin olustur</h1>
        <p>Bu sayfa yalnizca ilk yonetici hesabi icin calisir.</p>

        <?php if ($error): ?>
            <div class="cf-flash danger" style="margin-bottom:14px;"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($adminCount === 0): ?>
            <form method="post" data-once>
                <?= csrf_field() ?>

                <label>Ad Soyad</label>
                <input type="text" name="name" required autofocus placeholder="Yunus AKSOY">

                <label>E-posta</label>
                <input type="email" name="email" required placeholder="admin@alan-adiniz.com">

                <label>Sifre</label>
                <input type="password" name="password" required minlength="10" placeholder="En az 10 karakter">

                <label>Sifre Tekrar</label>
                <input type="password" name="password_confirmation" required minlength="10" placeholder="Tekrar yazin">

                <button type="submit" class="btn-primary">Superadmin olustur</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
