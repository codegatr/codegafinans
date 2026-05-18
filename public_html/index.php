<?php
/**
 * CODEGA Finans - Landing
 * Giriş yapan kullanıcı dashboard'a yönlendirilir.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/subscription.php';

if (auth_user()) {
    redirect('/dashboard.php');
}

$plansPublic = plans_active();
$pageTitle = 'CODEGA Finans - Bütçe ve Cari Takip';
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#04776f">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="CODEGA Finans ile bütçe, gelir-gider, cari hesap, borç, hedef ve kur takibini tek panelden yönetin.">
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
                <small>Para yönetimi</small>
            </span>
        </a>
        <div class="cf-home-menu">
            <a href="#ozellikler" class="cf-landing-link">Özellikler</a>
            <a href="#onizleme" class="cf-landing-link">Ekran Görüntüleri</a>
            <a href="#fiyatlandirma" class="cf-landing-link">Fiyatlandırma</a>
            <a href="/login.php" class="btn cf-home-login">Giriş</a>
        </div>
    </nav>

    <section class="cf-home-hero">
        <div class="cf-home-copy">
            <div class="cf-home-product">
                <span class="cf-home-logo big">CF</span>
                <span>
                    <strong>CODEGA Finans</strong>
                    <small>Bütçe, cari ve nakit akışı</small>
                </span>
            </div>
            <h1>Kişisel ve işletme finansınızı yönetin</h1>
            <p class="lead">
                Gelir-gider, aylık bütçe, cari hesap, borç, tasarruf hedefi ve güncel kur takibini
                sade bir panelde birleştirin. Verilerinize webden güvenle erişin, ekibinizle daha net karar alın.
            </p>
            <div class="cf-home-actions">
                <a href="/register.php" class="btn cf-home-primary">
                    <span aria-hidden="true">&rarr;</span>
                    <?= e(CF_TRIAL_DAYS) ?> G?n ?cretsiz Dene
                </a>
                <a href="/login.php" class="btn cf-home-secondary">Hesabına Giriş Yap</a>
            </div>
            <div class="cf-home-trust">
                <span>Banka seviyesinde gizlilik</span>
                <span>Kredi kartı gerekmez</span>
                <span>Akıllı uyarılar</span>
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
                        <small>Mayıs özeti</small>
                    </div>
                </div>
                <div class="phone-card green">
                    <small>Nakit + Banka</small>
                    <strong>58.790,25 TL</strong>
                </div>
                <div class="phone-split">
                    <div><small>Alacaklar</small><strong>124.357,90</strong></div>
                    <div><small>Borçlar</small><strong>-98.724,87</strong></div>
                </div>
                <div class="phone-list">
                    <span><b>ABC Bank</b><em>769.750,00 TL</em></span>
                    <span><b>Kredi Kartı</b><em class="danger">-20.624,00 TL</em></span>
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
                    <span>Mayıs<br>Bütçe</span>
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
                <span>geçen aya göre</span>
            </div>
        </div>
    </section>

    <section id="ozellikler" class="cf-home-section">
        <div class="cf-section-head">
            <h2>Paranızı yönetmek için ihtiyacınız olan her şey</h2>
            <p>Tek ki?ilik kullan?mdan ekip ?al??mas?na kadar, finansal kay?tlar?n?z? okunabilir ve takip edilebilir hale getirin.</p>
        </div>
        <div class="cf-feature-grid">
            <article>
                <span>01</span>
                <h3>Tüm hesap tipleri</h3>
                <p>Nakit, banka, kredi kartı, cari, borç, hedef ve gelir-gider kayıtlarını aynı yerde izleyin.</p>
            </article>
            <article>
                <span>02</span>
                <h3>Cari hesap takibi</h3>
                <p>Müşteri ve tedarikçi hareketlerini, borç-alacak dengesini ve ekstresini hızla yönetin.</p>
            </article>
            <article>
                <span>03</span>
                <h3>Akıllı uyarılar</h3>
                <p>Bütçe limiti, vadesi gelen borç ve hedef ilerlemesi için zamanında bildirim alın.</p>
            </article>
            <article>
                <span>04</span>
                <h3>Kur ve raporlar</h3>
                <p>TCMB kurlar?, ayl?k analizler ve kategori bazl? da??l?mlarla nakit ak???n?z? netle?tirin.</p>
            </article>
        </div>
    </section>

    <section id="onizleme" class="cf-home-section cf-home-showcase">
        <div class="cf-section-head">
            <span class="cf-coming">UYGULAMA ÖNİZLEMESİ</span>
            <h2>Temel finans g?r?n?mleri, tek ak??ta</h2>
            <p>Dashboard, bütçe, cari hesap ve rapor ekranları mobil hissiyle sade ve hızlı okunur.</p>
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
                <strong>Bütçe</strong>
                <div class="cf-mini-donut small"><span>%70</span></div>
            </div>
        </div>
    </section>

    <section id="fiyatlandirma" class="cf-pricing cf-home-pricing">
        <div class="cf-pricing-head">
            <span class="cf-coming">FİYATLANDIRMA</span>
            <h2>Şeffaf fiyatlandırma, gizli ücret yok</h2>
            <p>T?m temel Özellikler ayn? pakette. ?nce <strong><?= e(CF_TRIAL_DAYS) ?> g?n ?cretsiz</strong> deneyin, sonra size uygun plana ge?in.</p>
        </div>

        <div class="cf-pricing-grid">
<?php foreach ($plansPublic as $p):
    $isYearly = $p['period'] === 'yearly';
    $period   = $p['period'] === 'yearly' ? 'yıl' : ($p['period'] === 'monthly' ? 'ay' : '');
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
                    <div class="cf-price-sub">Yakla??k <strong><?= number_format((float)$monthly, 0, ',', '.') ?> <?= e($p['currency'] ?: 'TRY') ?></strong> / ay</div>
                <?php else: ?>
                    <div class="cf-price-sub">Her ay yenilenir, istediğiniz zaman iptal edebilirsiniz.</div>
                <?php endif; ?>

                <ul class="cf-price-features">
                    <li>Sınırsız gelir / gider kaydı</li>
                    <li>Aylık bütçe ve kategori limiti</li>
                    <li>Tasarruf hedefi ve borç kontrolü</li>
                    <li>Cari hesap takibi ve ekstre</li>
                    <li>Güncel TCMB döviz kurları</li>
                    <li>Akıllı uyarılar ve aylık analizler</li>
                    <?php if ($isYearly): ?>
                        <li><strong>Yıllık planda avantajlı fiyat</strong></li>
                    <?php endif; ?>
                </ul>

                <a class="btn btn-primary btn-block" href="/register.php"><?= e(CF_TRIAL_DAYS) ?> g?n ?cretsiz dene</a>
                <small class="cf-price-foot">Kredi kartı gerektirmez</small>
            </div>
<?php endforeach; ?>
        </div>

    </section>

    <section class="cf-home-final">
        <h2>Finans kayıtlarınızı bugün düzene alın</h2>
        <p>CODEGA Finans ile bütçenizi, carilerinizi ve hedeflerinizi tek yerden takip etmeye başlayın.</p>
        <a href="/register.php" class="btn cf-home-primary"><?= e(CF_TRIAL_DAYS) ?> G?n ?cretsiz Ba?la</a>
    </section>

    <footer class="cf-home-footer">
        <span>&copy; <?= date('Y') ?> CODEGA</span>
        <a href="/privacy.php">Gizlilik</a>
        <a href="/terms.php">Kullanım Şartları</a>
        <span>v<?= e(CF_VERSION) ?></span>
    </footer>
</div>
</body>
</html>
