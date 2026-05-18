<?php
/**
 * CODEGA Finans - Yönetici Girişi
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

start_session();
if (admin_user()) {
    redirect('/admin/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = s($_POST['email'] ?? '', 160);
    $pass  = (string)($_POST['password'] ?? '');

    $r = admin_login($email, $pass);
    if (!$r['ok']) {
        $error = $r['message'];
        remember_old(['email' => $email]);
    } else {
        clear_old();
        redirect('/admin/index.php');
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Yönetici Girişi · CODEGA Finans</title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.cf-auth::before { background: #f59e0b !important; }
.cf-auth::after  { background: #b45309 !important; }
</style>
</head>
<body class="cf-auth">
<div class="cf-auth-card">
    <div class="cf-auth-brand">
        <span class="logo" style="background:linear-gradient(135deg,#b45309,#f59e0b 55%,#fde68a);color:#422006;">CF</span>
        <div class="name">
            Yönetim Paneli
            <div style="font-size:11px;color:#94a3b8;font-weight:500;margin-top:2px;">CODEGA Finans</div>
        </div>
    </div>
    <h1>Yönetici Girişi 🔐</h1>
    <p>Bu alan yalnızca yetkili personel içindir.</p>

    <?php if ($error): ?>
        <div class="cf-flash danger" style="margin-bottom:14px;"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" data-once>
        <?= csrf_field() ?>
        <label>E-posta</label>
        <input type="email" name="email" required autofocus value="<?= e(old('email')) ?>" placeholder="admin@codega.com.tr">

        <label>Şifre</label>
        <input type="password" name="password" required placeholder="••••••••">

        <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#b45309,#f59e0b);">Giriş</button>
    </form>

    <div class="meta">
        <small style="color:#94a3b8;">
            <a href="/" style="color:#93c5fd;">← Siteye dön</a>
        </small>
    </div>
</div>
</body>
</html>
