<?php
/**
 * CODEGA Finans - Kayıt
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

start_session();

if (auth_user()) {
    redirect('/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $r = auth_register($_POST);
    if (!$r['ok']) {
        $error = $r['message'];
        remember_old($_POST);
    } else {
        // Otomatik giriş
        auth_login((string)$_POST['email'], (string)$_POST['password']);
        clear_old();
        flash('success', CF_TRIAL_DAYS . ' günlük denemeniz başladı. Hoş geldiniz!');
        redirect('/dashboard.php');
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<title>Kayıt Ol · CODEGA Finans</title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="cf-auth">
<div class="cf-auth-card">
    <div class="cf-auth-brand">
        <span class="logo">CF</span>
        <div class="name">
            CODEGA Finans
            <div style="font-size:11px;color:#94a3b8;font-weight:500;margin-top:2px;">Bütçe &amp; Tasarruf</div>
        </div>
    </div>
    <h1>Aramıza katıl 🚀</h1>
    <p><?= e(CF_TRIAL_DAYS) ?> günlük ücretsiz deneme, kredi kartı gerektirmez.</p>

    <?php if ($error): ?>
        <div class="cf-flash danger" style="margin-bottom:14px;"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" data-once>
        <?= csrf_field() ?>
        <label>Ad Soyad</label>
        <input type="text" name="name" required autofocus value="<?= e(old('name')) ?>" placeholder="Adınız Soyadınız">

        <label>E-posta</label>
        <input type="email" name="email" required value="<?= e(old('email')) ?>" placeholder="ornek@firma.com">

        <label>Telefon (opsiyonel)</label>
        <input type="tel" name="phone" value="<?= e(old('phone')) ?>" placeholder="+90 5XX XXX XX XX">

        <label>Şifre (en az 8 karakter)</label>
        <input type="password" name="password" required placeholder="••••••••">

        <label>Şifre (tekrar)</label>
        <input type="password" name="password_confirmation" required placeholder="••••••••">

        <button type="submit" class="btn-primary">Ücretsiz Hesap Oluştur</button>
    </form>

    <div class="meta">
        Zaten üye misin? <a href="/login.php">Giriş yap</a><br>
        <small style="color:#64748b;display:block;margin-top:14px;">
            Kayıt olarak <a href="/terms.php" style="color:#93c5fd;">Kullanım Şartları</a> ve
            <a href="/privacy.php" style="color:#93c5fd;">Gizlilik Politikası</a>'nı kabul etmiş sayılırsınız.
        </small>
    </div>
</div>
</body>
</html>
