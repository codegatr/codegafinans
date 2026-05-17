<section class="page-head">
  <div>
    <h1>Akilli Uyarilar</h1>
    <p>Butce, borc ve nakit akisi sinyalleri.</p>
  </div>
</section>

<section class="grid">
  <?php foreach ($alerts as $alert): ?>
    <article class="card span-6">
      <h2><?= e($alert['title']) ?></h2>
      <p class="muted"><?= e($alert['message']) ?></p>
    </article>
  <?php endforeach; ?>
</section>
