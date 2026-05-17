<?php use App\Core\Session; $user = auth_user(); $active = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/'; ?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b2134">
  <title><?= e($title ?? 'Codega Finans') ?></title>
  <link rel="manifest" href="/manifest.json">
  <link rel="icon" href="/assets/img/icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <div class="app-shell">
    <header class="topbar">
      <div class="container topbar-inner">
        <a class="brand" href="<?= $user ? '/dashboard' : '/' ?>">
          <img src="/assets/img/icon.svg" alt="">
          <span>CODEGA<br><small>FINANS</small></span>
        </a>
        <div class="top-actions">
          <?php if ($user): ?>
            <a class="button secondary" href="/settings"><?= e($user['name']) ?></a>
            <form method="post" action="/logout"><?= csrf_field() ?><button type="submit">Cikis</button></form>
          <?php else: ?>
            <a class="button secondary" href="/login">Giris</a>
            <a class="button" href="/register">Basla</a>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <?php foreach (Session::flashes() as $flash): ?>
      <div class="container flash <?= e($flash['type']) ?>">
        <span><?= e($flash['message']) ?></span>
        <button type="button" data-close>x</button>
      </div>
    <?php endforeach; ?>

    <main class="container"><?= $content ?></main>

    <?php if ($user): ?>
      <nav class="bottom-nav" aria-label="Alt menu">
        <div class="bottom-nav-inner">
          <?php
            $items = [
              ['/dashboard', 'Panel', 'M5 12h14M12 5v14'],
              ['/transactions', 'Gelir/Gider', 'M4 17l6-6 4 4 6-8'],
              ['/budgets', 'Butce', 'M4 7h16M7 7v10m10-10v10M5 17h14'],
              ['/goals', 'Hedef', 'M12 21s7-4.35 7-10a7 7 0 1 0-14 0c0 5.65 7 10 7 10z'],
              ['/debts', 'Borc', 'M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L10.6 5.34'],
              ['/alerts', 'Uyarilar', 'M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0'],
            ];
          ?>
          <?php foreach ($items as [$href, $label, $path]): ?>
            <a class="nav-item <?= $active === $href ? 'active' : '' ?>" href="<?= $href ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="<?= e($path) ?>"/></svg>
              <span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </nav>
    <?php endif; ?>
  </div>
  <script src="/assets/js/app.js"></script>
</body>
</html>
