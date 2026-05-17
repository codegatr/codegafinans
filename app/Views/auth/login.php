<h1>Giris Yap</h1>
<p class="muted">Butcenizi, borclarinizi ve hedeflerinizi tek panelden izleyin.</p>
<form class="form" method="post" action="/login">
  <?= csrf_field() ?>
  <div class="field">
    <label>E-posta</label>
    <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
  </div>
  <div class="field">
    <label>Sifre</label>
    <input type="password" name="password" required autocomplete="current-password">
  </div>
  <button type="submit">Giris Yap</button>
  <a class="button secondary" href="/register">Yeni hesap olustur</a>
</form>
