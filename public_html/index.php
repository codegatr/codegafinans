<?php
/**
 * CODEGA Finans - Açılış sayfası (Landing)
 * Giriş yapan kullanıcı dashboard'a yönlendirilir.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/subscription.php';

if (auth_user()) {
    redirect('/dashboard.php');
}

// Acik fiyatlandirma icin planlari DB'den oku (defter.net mantigi)
$plansPublic = plans_active();
// IBAN bilgisi olabilir, varsa gosterelim
$ibanRow   = db_one("SELECT value FROM " . t('settings') . " WHERE key_name='iban'");
$ibanNmRow = db_one("SELECT value FROM " . t('settings') . " WHERE key_name='iban_name'");

$pageTitle = 'CODEGA Finans · Bütçe ve Tasarruf';
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="CODEGA Finans · Aylık bütçe yönetimi, gelir-gider takibi, tasarruf hedefleri, borç kontrolü, güncel kurlar ve akıllı uyarılar.">
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="cf-landing">
    <nav class="cf-landing-nav">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="width:36px;height:36px;border-radius:10px;background:var(--cf-grad-blue);display:grid;place-items:center;color:#fff;font-weight:800;">CF</span>
            <strong>CODEGA Finans</strong>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="#fiyatlandirma" class="cf-landing-link">Fiyatlandirma</a>
            <a href="/login.php" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.2);">Giris Yap</a>
            <a href="/register.php" class="btn btn-primary">Ucretsiz Dene</a>
        </div>
    </nav>

    <section class="cf-landing-hero">
        <div>
            <span class="cf-coming">⭐ ÇOK YAKINDA PLAY STORE'DA</span>
            <h1 style="margin-top:18px;">CODEGA <span>Finans</span><br>Bütçe ve Tasarruf</h1>
            <p class="lead">
                Aylık bütçenizi planlayın, gelir &amp; giderinizi takip edin, tasarruf hedeflerinize ulaşın.
                Türkiye Cumhuriyet Merkez Bankası kurları, akıllı uyarılar ve detaylı analizlerle finansal hayatınızı tek noktadan yönetin.
            </p>

            <ul class="features">
                <li>Aylık Bütçe Yönetimi</li>
                <li>Gelir-Gider Takibi</li>
                <li>Tasarruf Hedefleri</li>
                <li>Borç Kontrolü</li>
                <li>Güncel Döviz Kurları (TCMB)</li>
                <li>Akıllı Uyarılar &amp; Analizler</li>
            </ul>

            <div class="cta">
                <a href="/register.php" class="btn btn-primary">7 Gün Ücretsiz Dene →</a>
                <a href="/login.php" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.2);">Zaten üyeyim</a>
            </div>

            <div style="margin-top:24px;font-size:13px;color:#94a3b8;">
                Kayıtla birlikte <strong style="color:#fff;"><?= e(CF_TRIAL_DAYS) ?> günlük deneme</strong> · Kredi kartı gerektirmez · İstediğiniz zaman iptal edebilirsiniz.
            </div>
        </div>

        <div class="cf-phone-frame">
            <div class="pf-h">
                <span class="logo">CF</span>
                <div>
                    <strong>CODEGA FİNANS</strong>
                    <div style="font-size:11px;color:#94a3b8;">Aylık Bütçeniz</div>
                </div>
            </div>
            <div class="pf-stat">
                <small>Bu Ay</small>
                <strong>12.450 ₺ / 8.700 ₺ Harcanan</strong>
            </div>

            <div style="display:flex;justify-content:center;margin:20px 0;">
                <div class="cf-donut" style="
                    --c1:#10b981; --c2:#ef4444; --c3:#f59e0b;
                    width:180px;height:180px;">
                    <div class="center" style="color:#0f172a;">
                        <b>%70</b>
                        <small>Bütçe Kullanımı</small>
                    </div>
                </div>
            </div>

            <div class="pf-stat">
                <small>Tasarruf Hedefi: Tatil Planı</small>
                <div style="margin-top:8px;">
                    <div style="height:6px;background:rgba(255,255,255,.1);border-radius:4px;overflow:hidden;">
                        <span style="display:block;height:100%;width:35%;background:var(--cf-grad-green);"></span>
                    </div>
                    <small style="display:block;margin-top:6px;color:#94a3b8;">%35 Tamamlandı</small>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== SEFFAF FIYATLANDIRMA (defter.net mantigi) ========== -->
    <section id="fiyatlandirma" class="cf-pricing">
        <div class="cf-pricing-head">
            <span class="cf-coming" style="background:rgba(96,165,250,.18);color:#bfdbfe;border-color:rgba(96,165,250,.35);">FIYATLANDIRMA</span>
            <h2>Seffaf fiyatlandirma, gizli ucret yok</h2>
            <p>Tum islemler ayni paket icinde. Once <strong style="color:#fff;"><?= e(CF_TRIAL_DAYS) ?> gun ucretsiz</strong> deneyin, sonra istediginiz plana gecin.</p>
        </div>

        <div class="cf-pricing-grid">
<?php foreach ($plansPublic as $p):
    $isYearly = $p['period'] === 'yearly';
    $period   = $p['period'] === 'yearly' ? 'yil' : ($p['period'] === 'monthly' ? 'ay' : '');
    // Aylik karsiliklarini hesapla (yillik plan icin avantaj gostergesi)
    $monthly  = $isYearly ? round((float)$p['price'] / 12, 0) : null;
?>
            <div class="cf-price-card<?= $isYearly ? ' featured' : '' ?>">
                <?php if ($isYearly): ?><span class="cf-price-ribbon">En cok tercih edilen</span><?php endif; ?>
                <div class="cf-price-name"><?= e($p['name']) ?></div>
                <div class="cf-price-amount">
                    <span class="num"><?= number_format((float)$p['price'], 0, ',', '.') ?></span>
                    <span class="cur"><?= e($p['currency'] ?: 'TRY') ?></span>
                    <?php if ($period): ?><small>/ <?= e($period) ?></small><?php endif; ?>
                </div>
                <?php if ($monthly !== null): ?>
                    <div class="cf-price-sub">Yaklasik <strong><?= number_format((float)$monthly, 0, ',', '.') ?> <?= e($p['currency'] ?: 'TRY') ?></strong> / ay &middot; tek seferde odeme</div>
                <?php else: ?>
                    <div class="cf-price-sub">Her ay otomatik yenilenir, istediginiz zaman iptal edebilirsiniz.</div>
                <?php endif; ?>

                <ul class="cf-price-features">
                    <li>Sinirsiz gelir / gider kaydi</li>
                    <li>Aylik butce ve kategori limiti</li>
                    <li>Tasarruf hedefi &amp; borc kontrolu</li>
                    <li>Cari hesap takibi, borc / alacak girisi</li>
                    <li>Cari ekstre (PDF / mail) &amp; cari rapor</li>
                    <li>Guncel TCMB doviz kurlari</li>
                    <li>Akilli uyarilar &amp; aylik analizler</li>
                    <?php if ($isYearly): ?>
                        <li><strong>Yillik plana ~%20 indirim</strong></li>
                    <?php endif; ?>
                </ul>

                <a class="btn btn-primary btn-block" href="/register.php"><?= e(CF_TRIAL_DAYS) ?> gun ucretsiz dene</a>
                <small class="cf-price-foot">Kredi karti gerektirmez &middot; istediginizde iptal</small>
            </div>
<?php endforeach; ?>
        </div>

        <?php if (!empty($ibanRow['value'])): ?>
        <div class="cf-pricing-iban">
            <h4>Havale / EFT ile odeme</h4>
            <p>Banka havalesi ile odeme yapabilirsiniz. Dekontunuzu mail ile gonderdiginizde aboneliginiz 24 saat icinde aktive edilir.</p>
            <div class="cf-iban-box">
                <small>Hesap Sahibi</small>
                <strong><?= e($ibanNmRow['value'] ?? 'CODEGA') ?></strong>
                <small style="margin-top:10px;">IBAN</small>
                <code><?= e($ibanRow['value']) ?></code>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <!-- ========== /FIYATLANDIRMA ========== -->

    <section style="background:rgba(255,255,255,.02);border-top:1px solid rgba(255,255,255,.05);padding:40px 28px;">
        <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
            <div>
                <h3 style="color:#fff;font-size:16px;margin:0 0 8px;">Detayli analiz</h3>
                <p style="color:#94a3b8;font-size:14px;margin:0;line-height:1.6;">
                    Kategoriye gore harcama dagilimi, aylik trend, gelir-gider farki.
                </p>
            </div>
            <div>
                <h3 style="color:#fff;font-size:16px;margin:0 0 8px;">Akilli uyarilar</h3>
                <p style="color:#94a3b8;font-size:14px;margin:0;line-height:1.6;">
                    Butce siniri, vadesi yaklasan borc, tamamlanan hedef bildirimleri.
                </p>
            </div>
            <div>
                <h3 style="color:#fff;font-size:16px;margin:0 0 8px;">Guvenli &amp; ozel</h3>
                <p style="color:#94a3b8;font-size:14px;margin:0;line-height:1.6;">
                    Veriler sifreli, hesabiniz sadece sizin. Ucuncu taraflarla paylasilmaz.
                </p>
            </div>
        </div>
        <div style="text-align:center;margin-top:40px;font-size:12px;color:#64748b;">
            &copy; <?= date('Y') ?> CODEGA &middot; <a href="/privacy.php" style="color:#94a3b8;">Gizlilik</a>
            &middot; <a href="/terms.php" style="color:#94a3b8;">Kullanim Sartlari</a>
            &middot; v<?= e(CF_VERSION) ?>
        </div>
    </section>
</div>
</body>
</html>
