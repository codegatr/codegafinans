<?php
/**
 * CODEGA Finans - Gizlilik Politikası
 * Play Store yayını için gereklidir.
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gizlilik Politikası · CODEGA Finans</title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background:#f7f8fb;">
<div style="max-width:820px;margin:30px auto;padding:0 20px;">
    <div style="margin-bottom:20px;">
        <a href="/" style="display:inline-flex;align-items:center;gap:8px;">
            <span style="width:34px;height:34px;border-radius:10px;background:var(--cf-grad-blue);display:grid;place-items:center;color:#fff;font-weight:800;">CF</span>
            <strong>CODEGA Finans</strong>
        </a>
    </div>

    <div class="cf-card">
        <h1 style="font-size:24px;margin:0 0 6px;">Gizlilik Politikası</h1>
        <p style="color:var(--cf-text-soft);margin:0 0 22px;">Yürürlük tarihi: <?= e(CF_RELEASED_AT) ?></p>

        <h3>1. Veri Sorumlusu</h3>
        <p>Bu uygulamayı sunan: <strong>CODEGA — Yunus AKSOY</strong>, Konya / Türkiye. İletişim: <a href="mailto:<?= e(CF_ADMIN_MAIL) ?>"><?= e(CF_ADMIN_MAIL) ?></a></p>

        <h3>2. Toplanan Bilgiler</h3>
        <ul>
            <li><strong>Hesap bilgileri:</strong> ad, e-posta, telefon (opsiyonel), şifre (geri döndürülemez şekilde hash'lenir).</li>
            <li><strong>Finansal kayıtlarınız:</strong> gelir/gider, bütçe, tasarruf hedefleri, borçlar – yalnızca uygulamanın işlevi için.</li>
            <li><strong>Teknik veriler:</strong> IP adresi, tarayıcı/cihaz bilgisi, son giriş zamanı.</li>
        </ul>

        <h3>3. Verinin Kullanım Amaçları</h3>
        <ul>
            <li>Hizmeti sunmak, hesabınızı işletmek ve güvenliğini sağlamak.</li>
            <li>Abonelik, faturalama ve müşteri desteği.</li>
            <li>Yasal yükümlülüklere uyum sağlamak.</li>
        </ul>

        <h3>4. Üçüncü Taraflarla Paylaşım</h3>
        <p>Finansal verileriniz reklam, pazarlama veya analiz amacıyla üçüncü taraflarla paylaşılmaz. Yalnızca yasal zorunluluk halinde, yetkili mercilerle yasanın gerektirdiği ölçüde paylaşılabilir.</p>

        <h3>5. Veri Saklama Süresi</h3>
        <p>Hesabınızı silmediğiniz sürece verileriniz saklanır. Silme talebiniz olursa <?= e(CF_ADMIN_MAIL) ?> adresinden iletişime geçebilirsiniz; tüm verileriniz 30 gün içinde silinir (yasal saklama gereklilikleri hariç).</p>

        <h3>6. Güvenlik</h3>
        <p>Şifreler bcrypt ile hash'lenir. Tüm iletişim HTTPS üzerinden şifreli olarak gerçekleşir. Sunuculara yalnızca yetkili personel erişebilir.</p>

        <h3>7. Kullanıcı Hakları (KVKK Madde 11)</h3>
        <p>Kişisel verilerinize ilişkin bilgi alma, düzeltme, silme, işlemeyi sınırlama, taşınabilirlik ve itiraz haklarınız vardır. Talepleriniz için <?= e(CF_ADMIN_MAIL) ?> adresine yazabilirsiniz.</p>

        <h3>8. Çerezler</h3>
        <p>Yalnızca oturum yönetimi için zorunlu çerezler kullanılır. Üçüncü taraf izleme çerezi kullanılmaz.</p>

        <h3>9. Çocukların Gizliliği</h3>
        <p>Hizmet, 13 yaş altındaki kullanıcılara yönelik değildir.</p>

        <h3>10. Değişiklikler</h3>
        <p>Politika güncellenirse bu sayfada yayınlanır; önemli değişiklikler e-posta ile bildirilir.</p>

        <hr style="margin:24px 0;border:0;border-top:1px solid #eef0f4;">
        <p style="text-align:center;color:var(--cf-muted);font-size:12px;">
            © <?= date('Y') ?> CODEGA · <?= e(CF_APP_NAME) ?> v<?= e(CF_VERSION) ?> · <a href="/">Anasayfa</a> · <a href="/terms.php">Kullanım Şartları</a>
        </p>
    </div>
</div>
</body>
</html>
