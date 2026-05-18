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
<meta name="theme-color" content="#078679">
<title>Giriş Yap · CODEGA Finans</title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="cf-auth cf-auth-login">
<div class="cf-auth-shell">
    <section class="cf-auth-panel">
        <a class="cf-auth-brand cf-auth-home-brand" href="/" aria-label="CODEGA Finans">
            <span class="logo">CF</span>
            <span class="name">
                CODEGA Finans
                <small>Bütçe, cari ve nakit akışı</small>
            </span>
        </a>

        <div class="cf-auth-copy">
            <span class="cf-auth-kicker">GÜVENLİ ÜYE GİRİŞİ</span>
            <h1>Finans paneline devam et</h1>
            <p>Bütçeni, carilerini, borçlarını ve hedeflerini tek yerden yönetmek için hesabına giriş yap.</p>
        </div>

        <div class="cf-auth-points">
            <span>Banka seviyesinde gizlilik</span>
            <span>Ödeme bilgileri yalnızca üyelere görünür</span>
            <span>Mobil uyumlu finans paneli</span>
        </div>
    </section>

    <main class="cf-auth-card cf-auth-login-card">
        <h1>Giriş Yap</h1>
        <p>Hoş geldin. E-posta adresin ve şifrenle devam et.</p>

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
            <a href="/">← Anasayfaya dön</a>
        </small>
    </div>
    </main>
</div>
</body>
</html>
