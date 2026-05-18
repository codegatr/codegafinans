<?php
/**
 * CODEGA Finans - Yönetici yan menüsü
 */
if (!function_exists('active')) {
    require_once __DIR__ . '/functions.php';
}
?>
<aside class="cf-sidebar">
    <div class="cf-brand">
        <span class="logo">CF</span>
        <span class="name">
            CODEGA Finans
            <small>Yönetim Paneli</small>
        </span>
    </div>

    <ul class="cf-nav">
        <li class="cf-nav-section">Genel</li>
        <li><a href="/admin/index.php" class="<?= trim(active('admin/index.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Genel Bakış
        </a></li>

        <li class="cf-nav-section">Müşteriler</li>
        <li><a href="/admin/users.php" class="<?= trim(active('admin/users.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Kullanıcılar
        </a></li>
        <li><a href="/admin/subscriptions.php" class="<?= trim(active('admin/subscriptions.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 16.9l-6.2 4.5 2.4-7.4L2 9.4h7.6z"/></svg>
            Abonelikler
        </a></li>
        <li><a href="/admin/plans.php" class="<?= trim(active('admin/plans.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Planlar
        </a></li>
        <li><a href="/admin/payments.php" class="<?= trim(active('admin/payments.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Ödemeler
        </a></li>

        <li class="cf-nav-section">Veriler</li>
        <li><a href="/admin/transactions.php" class="<?= trim(active('admin/transactions.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/></svg>
            Tüm İşlemler
        </a></li>

        <li class="cf-nav-section">Sistem</li>
        <li><a href="/admin/updates.php" class="<?= trim(active('admin/updates.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            Güncellemeler
        </a></li>
        <li><a href="/admin/logs.php" class="<?= trim(active('admin/logs.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Denetim Kayıtları
        </a></li>
        <li><a href="/admin/settings.php" class="<?= trim(active('admin/settings.php')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5z"/></svg>
            Ayarlar
        </a></li>

        <li class="cf-nav-section">Hesap</li>
        <li><a href="/admin/logout.php">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Çıkış
        </a></li>
    </ul>

    <div style="margin-top:30px;padding:12px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;font-size:11px;color:#fbbf24;text-align:center;">
        v<?= e(CF_VERSION) ?> · Yönetim<br>
        <span style="color:#94a3b8;">© <?= date('Y') ?> CODEGA</span>
    </div>
</aside>
