<section class="page-head">
  <div>
    <h1>Akilli Guncelleme</h1>
    <p>GitHub uzerinden yeni surumleri kontrol edip DirectAdmin kurulumunu guncelleyin.</p>
  </div>
</section>

<section class="grid">
  <article class="card span-6">
    <h2>Durum</h2>
    <div class="list">
      <div class="list-row"><span>Repo</span><b><?= e($status['repo']) ?></b></div>
      <div class="list-row"><span>Branch</span><b><?= e($status['branch']) ?></b></div>
      <div class="list-row"><span>Mevcut Commit</span><b><?= e($status['current'] ?: '-') ?></b></div>
      <div class="list-row"><span>GitHub Commit</span><b><?= e($status['remote'] ?: '-') ?></b></div>
      <div class="list-row"><span>Geride</span><b><?= $status['behind'] === null ? '-' : e($status['behind']) . ' commit' ?></b></div>
      <div class="list-row"><span>Calisma Alani</span><b><?= $status['dirty'] ? 'Degisiklik var' : 'Temiz' ?></b></div>
    </div>
    <p class="muted"><?= e($status['message']) ?></p>
  </article>

  <form class="card form span-6" method="post" action="/updates/apply">
    <h2>Guncellemeyi Baslat</h2>
    <?= csrf_field() ?>
    <p class="muted">Guvenlik icin `.env` icindeki `UPDATE_TOKEN` degerini girmeniz gerekir.</p>
    <div class="field">
      <label>Guncelleme Token</label>
      <input name="update_token" type="password" autocomplete="off" required>
    </div>
    <button type="submit">GitHub'dan Guncelle</button>
  </form>

  <article class="card span-6">
    <h2>DirectAdmin Ilk Kurulum</h2>
    <p class="muted">Domain dizininde, `public_html` klasoru web koku olacak sekilde kurulum icin ornek komutlar.</p>
    <pre class="code-block"><?php foreach ($commands as $command): ?><?= e($command) . PHP_EOL ?><?php endforeach; ?></pre>
  </article>

  <article class="card span-6">
    <h2>Son Guncelleme Logu</h2>
    <pre class="code-block"><?= e($status['log'] ?: 'Henuz log yok.') ?></pre>
  </article>
</section>
