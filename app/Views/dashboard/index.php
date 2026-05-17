<section class="page-head">
  <div>
    <h1>Finans Paneli</h1>
    <p><?= e($summary['month']) ?> donemi icin genel durumunuz.</p>
  </div>
  <a class="button" href="/transactions">Yeni Hareket</a>
</section>

<section class="grid">
  <div class="card span-3 stat"><span class="muted">Gelir</span><b><?= money($summary['income']) ?></b></div>
  <div class="card span-3 stat"><span class="muted">Gider</span><b><?= money($summary['expense']) ?></b></div>
  <div class="card span-3 stat"><span class="muted">Bakiye</span><b><?= money($summary['balance']) ?></b></div>
  <div class="card span-3 stat"><span class="muted">Borc</span><b><?= money($summary['debt']) ?></b></div>

  <div class="card span-5">
    <h2>Aylik Butce</h2>
    <div class="metric"><span><?= money($summary['budget']) ?> limit</span><strong>%<?= e($summary['budget_usage']) ?></strong></div>
    <div class="progress"><span style="width:<?= e($summary['budget_usage']) ?>%"></span></div>
  </div>
  <div class="card span-7">
    <h2>Akilli Uyarilar</h2>
    <div class="list">
      <?php foreach ($alerts as $alert): ?>
        <div class="list-row"><div><b><?= e($alert['title']) ?></b><br><span class="muted"><?= e($alert['message']) ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card span-7">
    <h2>Son Hareketler</h2>
    <table class="table">
      <thead><tr><th>Baslik</th><th>Kategori</th><th>Tarih</th><th>Tutar</th></tr></thead>
      <tbody>
      <?php foreach ($transactions as $transaction): ?>
        <tr>
          <td><?= e($transaction['title']) ?></td>
          <td><?= e($transaction['category']) ?></td>
          <td><?= e($transaction['transaction_date']) ?></td>
          <td class="amount <?= $transaction['type'] === 'income' ? 'plus' : 'minus' ?>"><?= $transaction['type'] === 'income' ? '+' : '-' ?><?= money($transaction['amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$transactions): ?><tr><td colspan="4" class="muted">Henuz hareket yok.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card span-5">
    <h2>Guncel Kurlar</h2>
    <div class="list">
      <?php foreach ($rates as $rate): ?>
        <div class="list-row"><b><?= e($rate['code']) ?></b><span><?= money($rate['sell_rate']) ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
