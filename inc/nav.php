<?php
/**
 * CODEGA Finans - Kullanıcı yan menüsü
 */
if (!function_exists('active')) {
    require_once __DIR__ . '/functions.php';
}
$__sub = function_exists('subscription_latest_for') && !empty($_SESSION['user_id'])
    ? subscription_latest_for((int)$_SESSION['user_id']) : null;
?>
<aside class="cf-sidebar">
    <div class="cf-brand">
        <span class="logo">CF</span>
        <span class="name">
            CODEGA Finans
            <small>Bütçe &amp; Tasarruf</small>
        </span>
    </div>

    <ul class="cf-nav">
        <li class="cf-nav-section">Ana Menü</li>
        <li><a href="/dashboard.php" class="<?= trim(active('dashboard.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/></svg>
            Anasayfa
        </a></li>
        <li><a href="/transactions.php" class="<?= trim(active('transactions.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
            Gelir &amp; Gider
        </a></li>
        <li><a href="/budgets.php" class="<?= trim(active('budgets.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M21 12c.6 0 1-.4 1-1V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6c0-.6-.4-1-1-1H17a2 2 0 0 1 0-4z"/></svg>
            Aylık Bütçe
        </a></li>
        <li><a href="/goals.php" class="<?= trim(active('goals.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            Tasarruf Hedefleri
        </a></li>
        <li><a href="/debts.php" class="<?= trim(active('debts.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 12h20"/></svg>
            Borç Kontrolü
        </a></li>

        <li class="cf-nav-section">Araçlar</li>
        <li><a href="/rates.php" class="<?= trim(active('rates.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M23 6l-9.5 9.5-5-5L1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            Döviz Kurları
        </a></li>
        <li><a href="/alerts.php" class="<?= trim(active('alerts.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Akıllı Uyarılar
        </a></li>

        <li class="cf-nav-section">Hesap</li>
        <li><a href="/subscription.php" class="<?= trim(active('subscription.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 16.9l-6.2 4.5 2.4-7.4L2 9.4h7.6z"/></svg>
            Abonelik
            <?php if ($__sub): ?>
                <span style="margin-left:auto;font-size:11px;color:#fbbf24"><?= e($__sub['status']) ?></span>
            <?php endif; ?>
        </a></li>
        <li><a href="/settings.php" class="<?= trim(active('settings.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
            Ayarlar
        </a></li>
        <li><a href="/logout.php">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Çıkış
        </a></li>
    </ul>

    <div style="margin-top:30px;padding:12px;background:rgba(255,255,255,.04);border-radius:10px;font-size:11px;color:#94a3b8;text-align:center;">
        v<?= e(CF_VERSION) ?> · <?= e(CF_TOTAL_MODULES) ?> modül<br>
        <span style="color:#fbbf24;">© <?= date('Y') ?> CODEGA</span>
    </div>
</aside>
