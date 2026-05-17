<section class="page-head">
  <div>
    <h1>Borc Kontrolu</h1>
    <p>Borc bakiyelerini ve odeme ilerlemesini takip edin.</p>
  </div>
</section>

<section class="grid">
  <form class="card form span-4" method="post" action="/debts">
    <h2>Yeni Borc</h2>
    <?= csrf_field() ?>
    <div class="field"><label>Alacakli / Kurum</label><input name="creditor" required></div>
    <div class="field"><label>Toplam Tutar</label><input name="total_amount" data-money inputmode="decimal" required></div>
    <div class="field"><label>Odenen</label><input name="paid_amount" data-money inputmode="decimal" value="0"></div>
    <div class="field"><label>Vade</label><input type="date" name="due_date"></div>
    <button type="submit">Borc Kaydet</button>
  </form>
  <div class="span-8 grid">
    <?php foreach ($debts as $debt): $pct = min(100, round(((float) $debt['paid_amount'] / max(1, (float) $debt['total_amount'])) * 100)); ?>
      <article class="card span-6">
        <h3><?= e($debt['creditor']) ?></h3>
        <p class="muted">Vade: <?= e($debt['due_date'] ?: 'Belirtilmedi') ?></p>
        <div class="metric"><span><?= money($debt['paid_amount']) ?> / <?= money($debt['total_amount']) ?></span><strong>%<?= e($pct) ?></strong></div>
        <div class="progress"><span style="width:<?= e($pct) ?>%"></span></div>
        <form class="form" method="post" action="/debts/pay" style="margin-top:14px">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($debt['id']) ?>">
          <div class="field"><label>Odeme Ekle</label><input name="amount" data-money inputmode="decimal"></div>
          <button type="submit">Ode</button>
        </form>
      </article>
    <?php endforeach; ?>
    <?php if (!$debts): ?><div class="card span-12 muted">Henuz borc kaydi yok.</div><?php endif; ?>
  </div>
</section>
