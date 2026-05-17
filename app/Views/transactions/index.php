<section class="page-head">
  <div>
    <h1>Gelir-Gider Takibi</h1>
    <p>Tum finansal hareketlerinizi kaydedin.</p>
  </div>
</section>

<section class="grid">
  <form class="card form span-4" method="post" action="/transactions">
    <h2>Yeni Hareket</h2>
    <?= csrf_field() ?>
    <div class="field"><label>Tip</label><select name="type"><option value="expense">Gider</option><option value="income">Gelir</option></select></div>
    <div class="field"><label>Baslik</label><input name="title" required></div>
    <div class="field"><label>Kategori</label><input name="category" placeholder="Kira, Mutfak, Maas" required></div>
    <div class="field"><label>Tutar</label><input name="amount" data-money inputmode="decimal" required></div>
    <div class="field"><label>Tarih</label><input type="date" name="transaction_date" value="<?= e(date('Y-m-d')) ?>" required></div>
    <div class="field"><label>Not</label><textarea name="note"></textarea></div>
    <button type="submit">Kaydet</button>
  </form>

  <div class="card span-8">
    <h2>Hareketler</h2>
    <table class="table">
      <thead><tr><th>Tip</th><th>Baslik</th><th>Kategori</th><th>Tarih</th><th>Tutar</th></tr></thead>
      <tbody>
      <?php foreach ($transactions as $transaction): ?>
        <tr>
          <td><span class="badge <?= e($transaction['type']) ?>"><?= $transaction['type'] === 'income' ? 'Gelir' : 'Gider' ?></span></td>
          <td><?= e($transaction['title']) ?></td>
          <td><?= e($transaction['category']) ?></td>
          <td><?= e($transaction['transaction_date']) ?></td>
          <td class="amount <?= $transaction['type'] === 'income' ? 'plus' : 'minus' ?>"><?= money($transaction['amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$transactions): ?><tr><td colspan="5" class="muted">Ilk hareketinizi soldaki formdan ekleyin.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
