<h1>Hesap Olustur</h1>
<p class="muted">Codega Finans panelinizi hemen hazirlayin.</p>
<form class="form" method="post" action="/register">
  <?= csrf_field() ?>
  <div class="field">
    <label>Ad Soyad</label>
    <input name="name" value="<?= e(old('name')) ?>" required autocomplete="name">
  </div>
  <div class="field">
    <label>E-posta</label>
    <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
  </div>
  <div class="field">
    <label>Aylik Gelir</label>
    <input name="monthly_income" data-money inputmode="decimal" value="<?= e(old('monthly_income', '0')) ?>">
  </div>
  <div class="field">
    <label>Sifre</label>
    <input type="password" name="password" minlength="6" required autocomplete="new-password">
  </div>
  <button type="submit">Basla</button>
  <a class="button secondary" href="/login">Zaten hesabim var</a>
</form>
