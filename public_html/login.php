<?php
/**
 * CODEGA Finans - Kullanıcı Girişi
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

start_session();

if (auth_user()) {
    redirect('/dashboard.php');
}

$error = null;
$next  = (string)($_GET['next'] ?? '/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = s($_POST['email'] ?? '', 160);
    $pass  = (string)($_POST['password'] ?? '');

    $r = auth_login($email, $pass);
    if (!$r['ok']) {
        $error = $r['message'];
        remember_old(['email' => $email]);
    } else {
        clear_old();
        flash('success', 'Hoş geldiniz, ' . $r['user']['name'] . '.');
        // next güvenli mi?
        $clean = parse_url($next, PHP_URL_PATH) ?: '/dashboard.php';
        redirect($clean);
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<title>Giriş Yap · CODEGA Finans</title>
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
    <h1>Tekrar hoş geldin 👋</h1>
    <p>Finanslarını yönetmeye devam etmek için giriş yap.</p>

    <?php if ($error): ?>
        <div class="cf-flash danger" style="margin-bottom:14px;"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" data-once>
        <?= csrf_field() ?>
        <label>E-posta</label>
        <input type="email" name="email" required autofocus value="<?= e(old('email')) ?>" placeholder="ornek@firma.com">

        <label>Şifre</label>
        <input type="password" name="password" required placeholder="••••••••">

        <button type="submit" class="btn-primary">Giriş Yap</button>
    </form>

    <div class="meta">
        Hesabın yok mu? <a href="/register.php">Hemen kayıt ol</a><br>
        <small style="color:#64748b;display:block;margin-top:14px;">
            <a href="/" style="color:#94a3b8;">← Anasayfaya dön</a>
        </small>
    </div>
</div>
</body>
</html>
