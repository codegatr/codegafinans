<?php
/**
 * CODEGA Finans - Anasayfa / Dashboard
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/finance.php';
require_once __DIR__ . '/../inc/subscription.php';

$user = auth_require();
$sub  = subscription_latest_for((int)$user['id']);

$month   = $_GET['m'] ?? date('Y-m');
$summary = fin_monthly_summary((int)$user['id'], $month);
$recent  = fin_recent_transactions((int)$user['id'], 8);
$cats    = fin_category_breakdown((int)$user['id'], $month);
$series  = fin_monthly_series((int)$user['id'], 6);
$alerts  = fin_generate_alerts((int)$user['id'], true);

$goals = db_all(
    'SELECT * FROM ' . t('goals') . ' WHERE user_id = :u AND is_completed = 0 ORDER BY id DESC LIMIT 3',
    [':u' => $user['id']]
);

$donutData = [
    'labels' => array_map(fn($c) => $c['name'], $cats),
    'values' => array_map(fn($c) => (float)$c['total'], $cats),
    'colors' => array_map(fn($c) => $c['color'], $cats),
];
$sparkValues = array_map(fn($r) => (float)$r['income'] - (float)$r['expense'], $series);

$pageTitle  = 'Anasayfa';
$pageHeader = 'Merhaba, ' . explode(' ', $user['name'])[0];

require __DIR__ . '/../inc/header.php';
?>

<?php if ($sub && $sub['status'] === 'trial'): ?>
    <div class="cf-flash warning">
        <strong>Deneme süreniz devam ediyor.</strong>
        <?= tr_date($sub['current_period_end']) ?> tarihinde sona erecek.
        <a href="/subscription.php" style="color:#92400e;text-decoration:underline;">Aboneliği başlat</a>
    </div>
<?php elseif ($sub && in_array($sub['status'], ['expired','past_due'], true)): ?>
    <div class="cf-flash danger">
        Aboneliğiniz sona erdi.
        <a href="/subscription.php" style="color:#991b1b;text-decoration:underline;">Yenilemek için tıklayın</a>
    </div>
<?php endif; ?>

<div class="cf-dashboard">
<!-- KPI'lar -->
<div class="cf-grid cf-grid-4 cf-dashboard-kpis">
    <div class="cf-stat income">
        <div class="label">Gelir + Cari Alacak</div>
        <div class="value"><?= money($summary['income']) ?></div>
        <div class="sub"><?= tr_month($summary['month']) ?> &middot; Cari <?= money($summary['cariMonthDebit']) ?></div>
    </div>
    <div class="cf-stat expense">
        <div class="label">Gider + Cari Ödeme</div>
        <div class="value"><?= money($summary['expense']) ?></div>
        <div class="sub">Cari ödeme: <?= money($summary['cariMonthCredit']) ?></div>
    </div>
    <div class="cf-stat balance">
        <div class="label">Net Durum</div>
        <div class="value"><?= money($summary['balance']) ?></div>
        <div class="sub"><?= $summary['balance'] >= 0 ? 'Pozitif' : 'Negatif' ?> aylık sonuç</div>
    </div>
    <div class="cf-stat gold">
        <div class="label">Cari Net</div>
        <div class="value"><?= money(abs($summary['cariBalance'])) ?></div>
        <div class="sub"><?= $summary['cariBalance'] >= 0 ? 'Tahsil edilecek' : 'Ödenecek' ?> bakiye</div>
    </div>
</div>

<div class="cf-grid cf-grid-2 cf-dashboard-grid cf-cari-position-row">
    <div class="cf-card">
        <div class="cf-card-head">
            <h3 style="margin:0;">Borç / Alacak Durumu</h3>
            <a href="/customers.php" style="font-size:13px;">Cariler &rarr;</a>
        </div>
        <div class="cf-grid cf-grid-2">
            <div class="cf-mini-metric"><span>Alacağım</span><strong><?= money($summary['cariReceivable']) ?></strong></div>
            <div class="cf-mini-metric"><span>Vereceğim</span><strong><?= money($summary['cariPayable']) ?></strong></div>
        </div>
    </div>
    <div class="cf-card">
        <div class="cf-card-head">
            <h3 style="margin:0;">Birikim Toplamı</h3>
            <a href="/goals.php" style="font-size:13px;">Hedefler &rarr;</a>
        </div>
        <div class="cf-mini-metric"><span>Aktif tasarruf hedefleri</span><strong><?= money($summary['saved']) ?></strong></div>
    </div>
</div>

<!-- Bütçe kullanım progress -->
<?php if ($summary['budget'] > 0): ?>
<div class="cf-card cf-budget-card">
    <div class="cf-card-head">
        <h3 style="margin:0;">Aylık Bütçe Kullanımı</h3>
        <span class="cf-pill <?= $summary['usage'] >= 100 ? 'danger' : ($summary['usage'] >= 85 ? 'warn' : 'success') ?>">
            <?= money($summary['expense']) ?> / <?= money($summary['budget']) ?>
        </span>
    </div>
    <div class="cf-progress <?= $summary['usage'] >= 100 ? 'danger' : ($summary['usage'] >= 85 ? 'warn' : '') ?>">
        <span style="width:<?= min(100, (int)$summary['usage']) ?>%"></span>
    </div>
    <div style="font-size:13px;color:var(--cf-text-soft);margin-top:8px;">
        %<?= (int)$summary['usage'] ?> kullanım &middot; kalan
        <strong><?= money(max(0, $summary['budget'] - $summary['expense'])) ?></strong>
    </div>
</div>
<?php endif; ?>

<!-- Dağılım + Trend -->
<div class="cf-grid cf-grid-2 cf-dashboard-grid">
    <div class="cf-card">
        <h3>Kategori Bazlı Harcama</h3>
        <?php if (empty($cats)): ?>
            <div class="cf-empty">
                <div class="icon">TL</div>
                Bu ay için kayıtlı bir gider yok.<br>
                <a class="btn btn-primary btn-sm" href="/transactions.php?new=1" style="margin-top:12px;">İlk Gideri Ekle</a>
            </div>
        <?php else: ?>
            <div data-donut='<?= e(json_encode($donutData, JSON_UNESCAPED_UNICODE)) ?>' style="min-height:280px;"></div>
        <?php endif; ?>
    </div>

    <div class="cf-card">
        <h3>Son 6 Ayın Net Akışı</h3>
        <?php if (count($sparkValues) >= 2): ?>
            <div data-spark='<?= e(json_encode(['series'=>$sparkValues,'color'=>'#2563eb'])) ?>' style="height:120px;"></div>
            <div class="cf-table-wrap" style="margin-top:14px;">
                <table class="cf-table cf-mobile-cards cf-dashboard-table" style="box-shadow:none;border:0;">
                    <thead>
                        <tr><th>Ay</th><th class="amount">Gelir</th><th class="amount">Gider</th><th class="amount">Net</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($series as $r):
                            $net = (float)$r['income'] - (float)$r['expense']; ?>
                            <tr>
                                <td data-label="Ay"><?= tr_month($r['ym']) ?></td>
                                <td data-label="Gelir" class="amount income"><?= money($r['income']) ?></td>
                                <td data-label="Gider" class="amount expense"><?= money($r['expense']) ?></td>
                                <td data-label="Net" class="amount" style="color:<?= $net >= 0 ? '#047857' : '#b91c1c' ?>"><?= money($net) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="cf-empty"><div class="icon">%</div>Trend için en az 2 aylık veri gerekli.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Tasarruf hedefleri + Uyarılar + Son işlemler -->
<div class="cf-grid cf-grid-2 cf-dashboard-grid">
    <div class="cf-card">
        <div class="cf-card-head">
            <h3 style="margin:0;">Aktif Tasarruf Hedefleri</h3>
            <a href="/goals.php" style="font-size:13px;">Tümü &rarr;</a>
        </div>
        <?php if (empty($goals)): ?>
            <div class="cf-empty">
                <div class="icon">H</div>
                Hedef tanımlanmamış.<br>
                <a class="btn btn-success btn-sm" href="/goals.php?new=1" style="margin-top:10px;">Hedef Oluştur</a>
            </div>
        <?php else: foreach ($goals as $g):
            $pct = $g['target_amount'] > 0 ? min(100, round((float)$g['current_amount']/(float)$g['target_amount']*100)) : 0; ?>
            <div style="margin-bottom:14px;">
                <div class="cf-goal-row">
                    <span><?= e($g['title']) ?></span>
                    <span><?= money($g['current_amount']) ?> / <?= money($g['target_amount']) ?></span>
                </div>
                <div class="cf-progress" style="margin-top:6px;">
                    <span style="width:<?= $pct ?>%;background:<?= e($g['color']) ?>;"></span>
                </div>
                <small style="color:var(--cf-text-soft);">%<?= $pct ?> tamamlandı<?php if ($g['deadline']): ?> &middot; vade <?= tr_date($g['deadline']) ?><?php endif; ?></small>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="cf-card">
        <div class="cf-card-head">
            <h3 style="margin:0;">Akıllı Uyarılar</h3>
            <a href="/alerts.php" style="font-size:13px;">Tümü &rarr;</a>
        </div>
        <?php foreach (array_slice($alerts, 0, 5) as $a): ?>
            <div style="padding:10px 12px;margin-bottom:8px;border-radius:10px;background:#f8fafc;border-left:3px solid <?php
                echo $a['level']==='danger'?'#ef4444':($a['level']==='warning'?'#f59e0b':($a['level']==='success'?'#10b981':'#0ea5e9'));
            ?>;">
                <strong style="font-size:14px;"><?= e($a['title']) ?></strong>
                <div style="font-size:13px;color:var(--cf-text-soft);margin-top:2px;"><?= e($a['message']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="cf-card">
    <div class="cf-card-head">
        <h3 style="margin:0;">Son İşlemler</h3>
        <a href="/transactions.php" style="font-size:13px;">Tümü &rarr;</a>
    </div>
    <?php if (empty($recent)): ?>
        <div class="cf-empty">
            <div class="icon">İ</div>
            Henüz bir işlem kaydı yok.<br>
            <a class="btn btn-primary btn-sm" href="/transactions.php?new=1" style="margin-top:10px;">İlk İşlemi Ekle</a>
        </div>
    <?php else: ?>
        <div class="cf-table-wrap">
            <table class="cf-table cf-mobile-cards cf-dashboard-table" style="box-shadow:none;border:0;">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Açıklama</th>
                        <th>Kategori</th>
                        <th>Tür</th>
                        <th class="amount">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td data-label="Tarih"><?= tr_date($r['tx_date']) ?></td>
                            <td data-label="Açıklama"><strong><?= e($r['title']) ?></strong></td>
                            <td data-label="Kategori">
                                <?php if ($r['category_name']): ?>
                                    <span class="cf-pill" style="background: <?= e($r['category_color']) ?>22;color:<?= e($r['category_color']) ?>;">
                                        <?= e($r['category_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="cf-pill">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tür">
                                <span class="cf-pill <?= $r['type']==='income'?'income':'expense' ?>">
                                    <?= $r['type']==='income' ? 'Gelir' : 'Gider' ?>
                                </span>
                            </td>
                            <td data-label="Tutar" class="amount <?= $r['type']==='income'?'income':'expense' ?>">
                                <?= ($r['type']==='income'?'+':'&minus;') ?> <?= money($r['amount']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
