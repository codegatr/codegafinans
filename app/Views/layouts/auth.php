<?php use App\Core\Session; ?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($title ?? 'Codega Finans') ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <main class="auth-page">
    <section class="card auth-card">
      <a class="brand" href="/">
        <img src="/assets/img/icon.svg" alt="">
        <span>CODEGA<br><small>FINANS</small></span>
      </a>
      <?php foreach (Session::flashes() as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><span><?= e($flash['message']) ?></span><button type="button" data-close>x</button></div>
      <?php endforeach; ?>
      <?= $content ?>
    </section>
  </main>
  <script src="/assets/js/app.js"></script>
</body>
</html>
