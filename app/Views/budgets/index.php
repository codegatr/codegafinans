<section class="page-head">
  <div>
    <h1>Aylik Butce</h1>
    <p>Kategori bazli limitler belirleyin.</p>
  </div>
</section>

<section class="grid">
  <form class="card form span-4" method="post" action="/budgets">
    <h2>Butce Limiti</h2>
    <?= csrf_field() ?>
    <div class="field"><label>Ay</label><input type="month" name="month" value="<?= e(date('Y-m')) ?>" required></div>
    <div class="field"><label>Kategori</label><input name="category" placeholder="Mutfak" required></div>
    <div class="field"><label>Limit</label><input name="limit_amount" data-money inputmode="decimal" required></div>
    <button type="submit">Limiti Kaydet</button>
  </form>
  <div class="card span-8">
    <h2>Butceler</h2>
    <table class="table">
      <thead><tr><th>Ay</th><th>Kategori</th><th>Limit</th></tr></thead>
      <tbody>
      <?php foreach ($budgets as $budget): ?>
        <tr><td><?= e($budget['month']) ?></td><td><?= e($budget['category']) ?></td><td><?= money($budget['limit_amount']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$budgets): ?><tr><td colspan="3" class="muted">Henuz butce limiti yok.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
