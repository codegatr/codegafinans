<?php
/**
 * CODEGA Finans - Landing
 * Giris yapan kullanici dashboard'a yonlendirilir.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/subscription.php';

if (auth_user()) {
    redirect('/dashboard.php');
}

$plansPublic = plans_active();
$pageTitle = 'CODEGA Finans - Butce ve Cari Takip';
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#04776f">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="CODEGA Finans ile butce, gelir-gider, cari hesap, borc, hedef ve kur takibini tek panelden yonetin.">
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="cf-landing cf-home">
    <nav class="cf-landing-nav cf-home-nav">
        <a class="cf-home-brand" href="/" aria-label="CODEGA Finans">
            <span class="cf-home-logo">CF</span>
            <span>
                <strong>CODEGA Finans</strong>
                <small>Para yonetimi</small>
            </span>
        </a>
        <div class="cf-home-menu">
            <a href="#ozellikler" class="cf-landing-link">Ozellikler</a>
            <a href="#onizleme" class="cf-landing-link">Ekran Goruntuleri</a>
            <a href="#fiyatlandirma" class="cf-landing-link">Fiyatlandirma</a>
            <a href="/login.php" class="btn cf-home-login">Giris</a>
        </div>
    </nav>

    <section class="cf-home-hero">
        <div class="cf-home-copy">
            <div class="cf-home-product">
                <span class="cf-home-logo big">CF</span>
                <span>
                    <strong>CODEGA Finans</strong>
                    <small>Butce, cari ve nakit akisi</small>
                </span>
            </div>
            <h1>Kisisel ve isletme finansinizi yonetin</h1>
            <p class="lead">
                Gelir-gider, aylik butce, cari hesap, borc, tasarruf hedefi ve guncel kur takibini
                sade bir panelde birlestirin. Verilerinize webden guvenle erisin, ekibinizle daha net karar alin.
            </p>
            <div class="cf-home-actions">
                <a href="/register.php" class="btn cf-home-primary">
                    <span aria-hidden="true">&rarr;</span>
                    <?= e(CF_TRIAL_DAYS) ?> Gun Ucretsiz Dene
                </a>
                <a href="/login.php" class="btn cf-home-secondary">Hesabina Giris Yap</a>
            </div>
            <div class="cf-home-trust">
                <span>Banka seviyesinde gizlilik</span>
                <span>Kredi karti gerekmez</span>
                <span>Akilli uyarilar</span>
            </div>
        </div>

        <div class="cf-home-preview" aria-label="CODEGA Finans uygulama onizlemesi">
            <div class="cf-home-float balance">
                <small>Toplam Bakiye</small>
                <strong>174.850,00 TL</strong>
            </div>
            <div class="cf-home-phone main-phone">
                <div class="phone-top">
                    <span>20:52</span>
                    <span>&bull; &bull; &bull;</span>
                </div>
                <div class="phone-title">
                    <span class="mini-logo">CF</span>
                    <div>
                        <strong>Finansim</strong>
                        <small>Mayis ozeti</small>
                    </div>
                </div>
                <div class="phone-card green">
                    <small>Nakit + Banka</small>
                    <strong>58.790,25 TL</strong>
                </div>
                <div class="phone-split">
                    <div><small>Alacaklar</small><strong>124.357,90</strong></div>
                    <div><small>Borclar</small><strong>-98.724,87</strong></div>
                </div>
                <div class="phone-list">
                    <span><b>ABC Bank</b><em>769.750,00 TL</em></span>
                    <span><b>Kredi Karti</b><em class="danger">-20.624,00 TL</em></span>
                    <span><b>Cari Hesap</b><em>46.257,03 TL</em></span>
                    <span><b>Tasarruf</b><em>31.100,00 TL</em></span>
                </div>
            </div>
            <div class="cf-home-phone side-phone">
                <div class="phone-top">
                    <span>13:23</span>
                    <span>&bull; &bull; &bull;</span>
                </div>
                <div class="cf-mini-donut">
                    <span>Mayis<br>Butce</span>
                </div>
                <div class="phone-list compact">
                    <span><b>Elektrik</b><em class="danger">-678,20</em></span>
                    <span><b>Market</b><em class="danger">-4.260,25</em></span>
                    <span><b>Kira</b><em class="danger">-32.000,00</em></span>
                </div>
            </div>
            <div class="cf-home-float report">
                <small>Aylik Rapor</small>
                <strong>+12.5%</strong>
                <span>gecen aya gore</span>
            </div>
        </div>
    </section>

    <section id="ozellikler" class="cf-home-section">
        <div class="cf-section-head">
            <h2>Paranizi yonetmek icin ihtiyaciniz olan her sey</h2>
            <p>Tek kisilik kullanimdan ekip calismasina kadar, finansal kayitlarinizi okunabilir ve takip edilebilir hale getirin.</p>
        </div>
        <div class="cf-feature-grid">
            <article>
                <span>01</span>
                <h3>Tum hesap tipleri</h3>
                <p>Nakit, banka, kredi karti, cari, borc, hedef ve gelir-gider kayitlarini ayni yerde izleyin.</p>
            </article>
            <article>
                <span>02</span>
                <h3>Cari hesap takibi</h3>
                <p>Musteri ve tedarikci hareketlerini, borc-alacak dengesini ve ekstresini hizla yonetin.</p>
            </article>
            <article>
                <span>03</span>
                <h3>Akilli uyarilar</h3>
                <p>Butce limiti, vadesi gelen borc ve hedef ilerlemesi icin zamaninda bildirim alin.</p>
            </article>
            <article>
                <span>04</span>
                <h3>Kur ve raporlar</h3>
                <p>TCMB kurlari, aylik analizler ve kategori bazli dagilimlarla nakit akisinizi netlestirin.</p>
            </article>
        </div>
    </section>

    <section id="onizleme" class="cf-home-section cf-home-showcase">
        <div class="cf-section-head">
            <span class="cf-coming">UYGULAMA ONIZLEMESI</span>
            <h2>Temel finans gorunumleri, tek akista</h2>
            <p>Dashboard, butce, cari hesap ve rapor ekranlari mobil hissiyle sade ve hizli okunur.</p>
        </div>
        <div class="cf-showcase-grid">
            <div class="cf-shot tall">
                <strong>Genel Durum</strong>
                <div class="cf-shot-balance">82.410 TL</div>
                <div class="cf-shot-bars"><i></i><i></i><i></i><i></i></div>
            </div>
            <div class="cf-shot">
                <strong>Cari Hesap</strong>
                <span>Alacak: 124.357 TL</span>
                <span>Borc: 98.724 TL</span>
            </div>
            <div class="cf-shot">
                <strong>Butce</strong>
                <div class="cf-mini-donut small"><span>%70</span></div>
            </div>
        </div>
    </section>

    <section id="fiyatlandirma" class="cf-pricing cf-home-pricing">
        <div class="cf-pricing-head">
            <span class="cf-coming">FIYATLANDIRMA</span>
            <h2>Seffaf fiyatlandirma, gizli ucret yok</h2>
            <p>Tum temel ozellikler ayni pakette. Once <strong><?= e(CF_TRIAL_DAYS) ?> gun ucretsiz</strong> deneyin, sonra size uygun plana gecin.</p>
        </div>

        <div class="cf-pricing-grid">
<?php foreach ($plansPublic as $p):
    $isYearly = $p['period'] === 'yearly';
    $period   = $p['period'] === 'yearly' ? 'yil' : ($p['period'] === 'monthly' ? 'ay' : '');
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
                    <div class="cf-price-sub">Yaklasik <strong><?= number_format((float)$monthly, 0, ',', '.') ?> <?= e($p['currency'] ?: 'TRY') ?></strong> / ay</div>
                <?php else: ?>
                    <div class="cf-price-sub">Her ay yenilenir, istediginiz zaman iptal edebilirsiniz.</div>
                <?php endif; ?>

                <ul class="cf-price-features">
                    <li>Sinirsiz gelir / gider kaydi</li>
                    <li>Aylik butce ve kategori limiti</li>
                    <li>Tasarruf hedefi ve borc kontrolu</li>
                    <li>Cari hesap takibi ve ekstre</li>
                    <li>Guncel TCMB doviz kurlari</li>
                    <li>Akilli uyarilar ve aylik analizler</li>
                    <?php if ($isYearly): ?>
                        <li><strong>Yillik planda avantajli fiyat</strong></li>
                    <?php endif; ?>
                </ul>

                <a class="btn btn-primary btn-block" href="/register.php"><?= e(CF_TRIAL_DAYS) ?> gun ucretsiz dene</a>
                <small class="cf-price-foot">Kredi karti gerektirmez</small>
            </div>
<?php endforeach; ?>
        </div>

    </section>

    <section class="cf-home-final">
        <h2>Finans kayitlarinizi bugun duzene alin</h2>
        <p>CODEGA Finans ile butcenizi, carilerinizi ve hedeflerinizi tek yerden takip etmeye baslayin.</p>
        <a href="/register.php" class="btn cf-home-primary"><?= e(CF_TRIAL_DAYS) ?> Gun Ucretsiz Basla</a>
    </section>

    <footer class="cf-home-footer">
        <span>&copy; <?= date('Y') ?> CODEGA</span>
        <a href="/privacy.php">Gizlilik</a>
        <a href="/terms.php">Kullanim Sartlari</a>
        <span>v<?= e(CF_VERSION) ?></span>
    </footer>
</div>
</body>
</html>
