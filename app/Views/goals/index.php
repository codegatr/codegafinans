<section class="page-head">
  <div>
    <h1>Tasarruf Hedefleri</h1>
    <p>Tatil, acil durum fonu veya yatirim hedeflerinizi izleyin.</p>
  </div>
</section>

<section class="grid">
  <form class="card form span-4" method="post" action="/goals">
    <h2>Yeni Hedef</h2>
    <?= csrf_field() ?>
    <div class="field"><label>Hedef Adi</label><input name="title" required></div>
    <div class="field"><label>Hedef Tutar</label><input name="target_amount" data-money inputmode="decimal" required></div>
    <div class="field"><label>Mevcut Birikim</label><input name="current_amount" data-money inputmode="decimal" value="0"></div>
    <div class="field"><label>Son Tarih</label><input type="date" name="deadline"></div>
    <button type="submit">Hedefi Olustur</button>
  </form>
  <div class="span-8 grid">
    <?php foreach ($goals as $goal): $pct = min(100, round(((float) $goal['current_amount'] / max(1, (float) $goal['target_amount'])) * 100)); ?>
      <article class="card span-6">
        <h3><?= e($goal['title']) ?></h3>
        <div class="metric"><span><?= money($goal['current_amount']) ?> / <?= money($goal['target_amount']) ?></span><strong>%<?= e($pct) ?></strong></div>
        <div class="progress"><span style="width:<?= e($pct) ?>%"></span></div>
        <form class="form" method="post" action="/goals/deposit" style="margin-top:14px">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($goal['id']) ?>">
          <div class="field"><label>Birikim Ekle</label><input name="amount" data-money inputmode="decimal"></div>
          <button type="submit">Ekle</button>
        </form>
      </article>
    <?php endforeach; ?>
    <?php if (!$goals): ?><div class="card span-12 muted">Henuz hedef yok.</div><?php endif; ?>
  </div>
</section>
