<section class="page-head">
  <div>
    <h1>Ayarlar</h1>
    <p>Profil ve aylik gelir bilginizi guncelleyin.</p>
  </div>
</section>

<form class="card form" method="post" action="/settings" style="max-width:520px">
  <?= csrf_field() ?>
  <div class="field"><label>Ad Soyad</label><input name="name" value="<?= e($user['name']) ?>" required></div>
  <div class="field"><label>E-posta</label><input value="<?= e($user['email']) ?>" disabled></div>
  <div class="field"><label>Aylik Gelir</label><input name="monthly_income" data-money inputmode="decimal" value="<?= e($user['monthly_income']) ?>"></div>
  <button type="submit">Kaydet</button>
</form>
