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
$pageTitle = 'CODEGA Finans - Bütçe, Cari ve Nakit Akışı';
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#073b3a">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="CODEGA Finans; bütçe, gelir-gider, cari hesap, borç, hedef ve kur takibini tek operasyon panelinde birleştirir.">
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="cf-ops-landing">
    <nav class="cf-ops-nav">
        <a class="cf-ops-brand" href="/" aria-label="CODEGA Finans">
            <span class="cf-ops-mark">CF</span>
            <span>
                <strong>CODEGA Finans</strong>
                <small>Finans operasyon paneli</small>
            </span>
        </a>
        <div class="cf-ops-menu">
            <a href="#moduller">Modüller</a>
            <a href="#guvenlik">Güvenlik</a>
            <a href="#fiyatlandirma">Fiyatlandırma</a>
            <a class="cf-ops-login" href="/login.php">Giriş</a>
        </div>
    </nav>

    <main>
        <section class="cf-ops-hero">
            <div class="cf-ops-copy">
                <span class="cf-ops-kicker">WEB TABANLI FİNANS YÖNETİMİ</span>
                <h1>Bütçe, cari ve nakit akışını tek merkezden yönetin.</h1>
                <p>
                    CODEGA Finans, küçük ekiplerin ve işletme sahiplerinin günlük finans işlerini
                    düzenli, ölçülebilir ve takip edilebilir hale getiren sade bir kontrol panelidir.
                </p>
                <div class="cf-ops-actions">
                    <a href="/register.php" class="cf-ops-primary"><?= e(CF_TRIAL_DAYS) ?> gün ücretsiz başla</a>
                    <a href="/login.php" class="cf-ops-secondary">Üye girişi</a>
                </div>
                <div class="cf-ops-metrics" aria-label="CODEGA Finans öne çıkanlar">
                    <span><strong>12</strong> modül</span>
                    <span><strong>TCMB</strong> kur takibi</span>
                    <span><strong>PDF</strong> cari ekstre</span>
                </div>
            </div>

            <div class="cf-ops-console" aria-label="CODEGA Finans panel önizlemesi">
                <div class="cf-console-top">
                    <span>Operasyon Özeti</span>
                    <small>Mayıs 2026</small>
                </div>
                <div class="cf-console-grid">
                    <div class="cf-console-card cash">
                        <small>Net Nakit Akışı</small>
                        <strong>26.125,13 TL</strong>
                        <span>Pozitif seyir</span>
                    </div>
                    <div class="cf-console-card warn">
                        <small>Bütçe Kullanımı</small>
                        <strong>%79</strong>
                        <span>31.275 TL limit kaldı</span>
                    </div>
                    <div class="cf-console-card receivable">
                        <small>Cari Alacak</small>
                        <strong>124.357,90 TL</strong>
                        <span>8 açık kayıt</span>
                    </div>
                </div>
                <div class="cf-ledger-panel">
                    <div class="cf-ledger-head">
                        <strong>Son Finans Hareketleri</strong>
                        <span>Canlı görünüm</span>
                    </div>
                    <div class="cf-ledger-row"><span>ABC Ltd. tahsilat</span><strong class="pos">+18.400 TL</strong></div>
                    <div class="cf-ledger-row"><span>Market / ofis gideri</span><strong class="neg">-4.260 TL</strong></div>
                    <div class="cf-ledger-row"><span>Kredi kartı ödemesi</span><strong class="neg">-12.900 TL</strong></div>
                    <div class="cf-ledger-row"><span>Tasarruf hedefi</span><strong class="pos">+3.100 TL</strong></div>
                </div>
                <div class="cf-flow-strip">
                    <span style="height:42%"></span>
                    <span style="height:68%"></span>
                    <span style="height:54%"></span>
                    <span style="height:86%"></span>
                    <span style="height:72%"></span>
                    <span style="height:92%"></span>
                </div>
            </div>
        </section>

        <section id="moduller" class="cf-ops-section">
            <div class="cf-ops-section-head">
                <span>MODÜLLER</span>
                <h2>Günlük finans işleri için net çalışma alanları</h2>
                <p>Her modül tek bir işi iyi yapar; birlikte kullanıldığında işletmenin finans resmini açık hale getirir.</p>
            </div>
            <div class="cf-ops-module-grid">
                <article><b>01</b><h3>Gelir / Gider</h3><p>Kategori, tarih ve açıklama bazlı kayıt tutun; ay sonu dağılımı anında görün.</p></article>
                <article><b>02</b><h3>Cari Hesap</h3><p>Müşteri ve tedarikçi borç-alacak hareketlerini, tahsilat ve ödemeleri izleyin.</p></article>
                <article><b>03</b><h3>Bütçe</h3><p>Aylık limitler belirleyin; kritik seviyelerde erken uyarı alın.</p></article>
                <article><b>04</b><h3>Hedefler</h3><p>Tasarruf hedeflerini tutar, vade ve ilerleme yüzdesiyle takip edin.</p></article>
                <article><b>05</b><h3>Borç Kontrolü</h3><p>Yaklaşan ödeme tarihlerini ve kapanan borçları düzenli görün.</p></article>
                <article><b>06</b><h3>Kur Takibi</h3><p>TCMB kurlarıyla dövizli takipleri güncel tutun.</p></article>
            </div>
        </section>

        <section id="guvenlik" class="cf-ops-split">
            <div>
                <span class="cf-ops-kicker">GİZLİLİK VE KONTROL</span>
                <h2>Ödeme bilgileri herkese açık değildir.</h2>
                <p>
                    Ana sayfa yalnızca ürün bilgisini gösterir. Ödeme ve hesap bilgileri giriş yapmış üyelerin
                    abonelik ekranında yönetilir; ziyaretçilere finansal hesap bilgisi sunulmaz.
                </p>
            </div>
            <div class="cf-ops-policy">
                <div><strong>Üye girişi</strong><span>Hesap bilgileri oturumdan sonra görünür.</span></div>
                <div><strong>Rol ayrımı</strong><span>Yönetim ayarları admin panelinde kalır.</span></div>
                <div><strong>Yedekli güncelleme</strong><span>Smart Update önce yedek alır, sonra uygular.</span></div>
            </div>
        </section>

        <section id="fiyatlandirma" class="cf-pricing cf-ops-pricing">
            <div class="cf-pricing-head">
                <span class="cf-coming">FİYATLANDIRMA</span>
                <h2>Basit planlar, net kapsam</h2>
                <p>Tüm temel özellikler aynı panelde. Önce <strong><?= e(CF_TRIAL_DAYS) ?> gün ücretsiz</strong> deneyin, sonra size uygun plana geçin.</p>
            </div>

            <div class="cf-pricing-grid">
<?php foreach ($plansPublic as $p):
    $isYearly = $p['period'] === 'yearly';
    $period   = $p['period'] === 'yearly' ? 'yıl' : ($p['period'] === 'monthly' ? 'ay' : '');
    $monthly  = $isYearly ? round((float)$p['price'] / 12, 0) : null;
?>
                <div class="cf-price-card<?= $isYearly ? ' featured' : '' ?>">
                    <?php if ($isYearly): ?><span class="cf-price-ribbon">Avantajlı plan</span><?php endif; ?>
                    <div class="cf-price-name"><?= e($p['name']) ?></div>
                    <div class="cf-price-amount">
                        <span class="num"><?= number_format((float)$p['price'], 0, ',', '.') ?></span>
                        <span class="cur"><?= e($p['currency'] ?: 'TRY') ?></span>
                        <?php if ($period): ?><small>/ <?= e($period) ?></small><?php endif; ?>
                    </div>
                    <?php if ($monthly !== null): ?>
                        <div class="cf-price-sub">Yaklaşık <strong><?= number_format((float)$monthly, 0, ',', '.') ?> <?= e($p['currency'] ?: 'TRY') ?></strong> / ay</div>
                    <?php else: ?>
                        <div class="cf-price-sub">Her ay yenilenir, istediğiniz zaman iptal edebilirsiniz.</div>
                    <?php endif; ?>
                    <ul class="cf-price-features">
                        <li>Sınırsız gelir / gider kaydı</li>
                        <li>Aylık bütçe ve kategori limiti</li>
                        <li>Cari hesap takibi ve ekstre</li>
                        <li>Borç, hedef ve kur takibi</li>
                        <li>Akıllı uyarılar ve aylık analizler</li>
                    </ul>
                    <a class="btn btn-primary btn-block" href="/register.php"><?= e(CF_TRIAL_DAYS) ?> gün ücretsiz dene</a>
                    <small class="cf-price-foot">Kredi kartı gerektirmez</small>
                </div>
<?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="cf-ops-footer">
        <span>&copy; <?= date('Y') ?> CODEGA</span>
        <a href="/privacy.php">Gizlilik</a>
        <a href="/terms.php">Kullanım Şartları</a>
        <span>v<?= e(CF_VERSION) ?></span>
    </footer>
</div>
</body>
</html>
