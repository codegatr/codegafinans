<?php
/**
 * CODEGA Finans - Kullanım Şartları
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kullanım Şartları · CODEGA Finans</title>
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
        <h1 style="font-size:24px;margin:0 0 6px;">Kullanım Şartları</h1>
        <p style="color:var(--cf-text-soft);margin:0 0 22px;">Yürürlük tarihi: <?= e(CF_RELEASED_AT) ?></p>

        <h3>1. Hizmet</h3>
        <p>CODEGA Finans (bundan sonra "Uygulama"), kullanıcılara kişisel finans yönetimi, bütçe takibi, gelir-gider kaydı, tasarruf hedefleri, borç kontrolü ve döviz kurları bilgisi sunan bir SaaS hizmettir. Uygulama hiçbir koşulda profesyonel finansal danışmanlık niteliğinde değildir.</p>

        <h3>2. Hesap</h3>
        <p>Hizmeti kullanmak için 18 yaş ve üzeri olmanız gerekir. Hesabınızın güvenliği, şifrenizin gizliliği ve hesabınızdan yapılan tüm işlemler size aittir.</p>

        <h3>3. Abonelik ve Ücretlendirme</h3>
        <p>Kayıt olunduğunda <?= (int)CF_TRIAL_DAYS ?> günlük ücretsiz deneme sunulur. Deneme süresi sonunda hizmetin kesintisiz kullanımı için abonelik gereklidir. Aboneliğinizi istediğiniz zaman iptal edebilirsiniz; mevcut dönemin sonuna kadar kullanmaya devam edebilirsiniz. İptal sonrası iade söz konusu değildir.</p>

        <h3>4. Yasaklanan Kullanım</h3>
        <ul>
            <li>Yasalara aykırı, müstehcen veya zarar verici içerik girilmesi,</li>
            <li>Başkasının hesabına izinsiz erişim,</li>
            <li>Hizmete otomatik araç (bot) ile aşırı yük bindirilmesi,</li>
            <li>Tersine mühendislik veya kaynak kodun izinsiz dağıtımı.</li>
        </ul>

        <h3>5. Veri ve İçerik</h3>
        <p>Kullanıcının girdiği finansal veriler kullanıcıya aittir. CODEGA, hizmetin işleyişi için gerekli ölçüde işleme yetkisine sahiptir. Detaylar için <a href="/privacy.php">Gizlilik Politikası</a>'na bakınız.</p>

        <h3>6. Hizmet Kesintileri</h3>
        <p>CODEGA, bakım veya teknik nedenlerle hizmette geçici kesintiler yapma hakkını saklı tutar; mümkün olduğunda önceden duyurur. Bu tür kesintiler iade hakkı doğurmaz.</p>

        <h3>7. Sorumluluk Sınırı</h3>
        <p>Uygulama, finansal kararlarınız için danışmanlık değil yalnızca takip aracı sunar. Verilerinizin doğruluğu sizin sorumluluğunuzdadır. Yanlış girdi sonucu uğrayacağınız zararlardan CODEGA sorumlu tutulamaz.</p>

        <h3>8. Fikri Mülkiyet</h3>
        <p>Uygulamanın yazılımı, tasarımı, logoları ve içerikleri CODEGA'ya aittir. Yazılı izin olmaksızın kopyalanamaz veya dağıtılamaz.</p>

        <h3>9. Hesap Sonlandırma</h3>
        <p>Bu şartların ihlali halinde CODEGA, önceden bildirimsiz olarak hesabı askıya alma veya kapatma hakkına sahiptir.</p>

        <h3>10. Uygulanacak Hukuk</h3>
        <p>Bu sözleşmenin uygulanmasında Türkiye Cumhuriyeti yasaları geçerlidir. Anlaşmazlıkların çözümünde Konya mahkemeleri ve icra daireleri yetkilidir.</p>

        <h3>11. Değişiklikler</h3>
        <p>CODEGA bu şartları güncelleme hakkını saklı tutar; önemli değişiklikler bu sayfada ve e-posta ile duyurulur.</p>

        <hr style="margin:24px 0;border:0;border-top:1px solid #eef0f4;">
        <p style="text-align:center;color:var(--cf-muted);font-size:12px;">
            © <?= date('Y') ?> CODEGA · <?= e(CF_APP_NAME) ?> v<?= e(CF_VERSION) ?> · <a href="/">Anasayfa</a> · <a href="/privacy.php">Gizlilik</a>
        </p>
    </div>
</div>
</body>
</html>
