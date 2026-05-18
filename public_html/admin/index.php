<?php
/**
 * CODEGA Finans - Yönetici Anasayfa (Dashboard)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

$admin = admin_require();

// KPI'lar
$kpi = [
    'users_total'    => (int)(db_one("SELECT COUNT(*) c FROM " . t('users'))['c'] ?? 0),
    'users_today'    => (int)(db_one("SELECT COUNT(*) c FROM " . t('users') . " WHERE DATE(created_at) = CURDATE()")['c'] ?? 0),
    'users_active'   => (int)(db_one("SELECT COUNT(*) c FROM " . t('users') . " WHERE status = 'active'")['c'] ?? 0),
    'sub_active'     => (int)(db_one("SELECT COUNT(*) c FROM " . t('subscriptions') . " WHERE status IN ('active','trial') AND current_period_end >= CURDATE()")['c'] ?? 0),
    'sub_trial'      => (int)(db_one("SELECT COUNT(*) c FROM " . t('subscriptions') . " WHERE status='trial' AND current_period_end >= CURDATE()")['c'] ?? 0),
    'sub_paid'       => (int)(db_one("SELECT COUNT(*) c FROM " . t('subscriptions') . " WHERE status='active' AND current_period_end >= CURDATE()")['c'] ?? 0),
    'sub_expired'    => (int)(db_one("SELECT COUNT(*) c FROM " . t('subscriptions') . " WHERE status='expired'")['c'] ?? 0),
    'mrr'            => (float)(db_one("
        SELECT COALESCE(SUM(p.price),0) m
          FROM " . t('subscriptions') . " s
          JOIN " . t('plans') . " p ON p.id = s.plan_id
         WHERE s.status='active' AND s.current_period_end >= CURDATE() AND p.period='monthly'
    ")['m'] ?? 0),
    'arr'            => (float)(db_one("
        SELECT COALESCE(SUM(p.price),0) m
          FROM " . t('subscriptions') . " s
          JOIN " . t('plans') . " p ON p.id = s.plan_id
         WHERE s.status='active' AND s.current_period_end >= CURDATE() AND p.period='yearly'
    ")['m'] ?? 0),
    'pay_pending'    => (int)(db_one("SELECT COUNT(*) c FROM " . t('payments') . " WHERE status='pending'")['c'] ?? 0),
    'pay_30days'     => (float)(db_one("
        SELECT COALESCE(SUM(amount),0) s
          FROM " . t('payments') . "
         WHERE status='succeeded' AND paid_at >= (NOW() - INTERVAL 30 DAY)
    ")['s'] ?? 0),
    'tx_total'       => (int)(db_one("SELECT COUNT(*) c FROM " . t('transactions'))['c'] ?? 0),
];

// Son kayıt olan kullanıcılar
$recentUsers = db_all(
    "SELECT u.*, p.name AS plan_name, s.status AS sub_status, s.current_period_end
       FROM " . t('users') . " u
       LEFT JOIN " . t('subscriptions') . " s ON s.id = u.subscription_id
       LEFT JOIN " . t('plans') . " p ON p.id = s.plan_id
      ORDER BY u.id DESC LIMIT 8"
);

// Bekleyen ödemeler
$pendingPays = db_all(
    "SELECT pay.*, u.name AS user_name, u.email AS user_email, pl.name AS plan_name
       FROM " . t('payments') . " pay
       JOIN " . t('users') . " u ON u.id = pay.user_id
       JOIN " . t('subscriptions') . " s ON s.id = pay.subscription_id
       JOIN " . t('plans') . " pl ON pl.id = s.plan_id
      WHERE pay.status = 'pending'
      ORDER BY pay.id DESC LIMIT 8"
);

// Son güncelleme log
$lastUpdate = db_one("SELECT * FROM " . t('update_log') . " ORDER BY id DESC LIMIT 1");

$pageTitle  = 'Genel Bakış';
$pageHeader = 'Genel Bakış';

require __DIR__ . '/../../inc/admin_header.php';
?>

<!-- KPI'lar -->
<div class="cf-kpi-grid" style="margin-bottom:18px;">
    <div class="cf-stat balance">
        <div class="label">Toplam Kullanıcı</div>
        <div class="value"><?= number_format($kpi['users_total'], 0, ',', '.') ?></div>
        <div class="sub">Bugün +<?= (int)$kpi['users_today'] ?> · Aktif <?= (int)$kpi['users_active'] ?></div>
    </div>
    <div class="cf-stat income">
        <div class="label">Aktif Abonelik</div>
        <div class="value"><?= number_format($kpi['sub_active'], 0, ',', '.') ?></div>
        <div class="sub"><?= (int)$kpi['sub_paid'] ?> ücretli · <?= (int)$kpi['sub_trial'] ?> deneme</div>
    </div>
    <div class="cf-stat cf-mrr">
        <div class="label">MRR (Aylık Gelir)</div>
        <div class="value"><?= money($kpi['mrr']) ?></div>
        <div class="sub">Yıllık plan: <?= money($kpi['arr']) ?></div>
    </div>
    <div class="cf-stat gold">
        <div class="label">Son 30 Gün Tahsilat</div>
        <div class="value"><?= money($kpi['pay_30days']) ?></div>
        <div class="sub">Bekleyen ödeme: <?= (int)$kpi['pay_pending'] ?></div>
    </div>
</div>

<div class="cf-grid cf-grid-2" style="margin-bottom:18px;">
    <!-- Son kayıt kullanıcılar -->
    <div class="cf-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">Son Kayıtlar</h3>
            <a href="/admin/users.php" style="font-size:13px;">Tümü →</a>
        </div>
        <div class="cf-table-wrap">
            <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
                <thead>
                    <tr><th>Kullanıcı</th><th>Plan</th><th>Durum</th><th>Kayıt</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <strong><?= e($u['name']) ?></strong>
                            <div style="font-size:12px;color:var(--cf-text-soft);"><?= e($u['email']) ?></div>
                        </td>
                        <td><?= e($u['plan_name'] ?? '—') ?></td>
                        <td>
                            <span class="cf-pill <?php
                                echo $u['sub_status']==='active'?'success':
                                    ($u['sub_status']==='trial'?'warn':
                                    ($u['sub_status']==='expired'?'danger':'info'));
                            ?>"><?= e($u['sub_status'] ?? '—') ?></span>
                        </td>
                        <td><?= tr_date($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bekleyen ödemeler -->
    <div class="cf-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">Bekleyen Ödemeler</h3>
            <a href="/admin/payments.php" style="font-size:13px;">Tümü →</a>
        </div>
        <?php if (empty($pendingPays)): ?>
            <div class="cf-empty" style="padding:30px;">
                <div class="icon">💳</div>Şu an bekleyen ödeme yok.
            </div>
        <?php else: ?>
        <div class="cf-table-wrap">
            <table class="cf-table" style="box-shadow:none;border:0;border-radius:0;">
                <thead>
                    <tr><th>Kullanıcı</th><th>Plan</th><th class="amount">Tutar</th><th>Tarih</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pendingPays as $p): ?>
                    <tr>
                        <td>
                            <strong><?= e($p['user_name']) ?></strong>
                            <div style="font-size:12px;color:var(--cf-text-soft);"><?= e($p['user_email']) ?></div>
                        </td>
                        <td><?= e($p['plan_name']) ?></td>
                        <td class="amount"><?= money($p['amount'], $p['currency']) ?></td>
                        <td><?= tr_date($p['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hızlı bilgi -->
<div class="cf-grid cf-grid-2">
    <div class="cf-card">
        <h3>Sistem Bilgileri</h3>
        <div style="display:grid;gap:8px;font-size:13px;color:var(--cf-text-soft);">
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eef0f4;padding-bottom:6px;">
                <span>Sürüm</span><strong>v<?= e(CF_VERSION) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eef0f4;padding-bottom:6px;">
                <span>Modül sayısı</span><strong><?= (int)CF_TOTAL_MODULES ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eef0f4;padding-bottom:6px;">
                <span>PHP</span><strong><?= e(PHP_VERSION) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eef0f4;padding-bottom:6px;">
                <span>DB</span><strong><?= e(CF_DB_NAME) ?> (prefix: <?= e(CF_DB_PREFIX) ?>)</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eef0f4;padding-bottom:6px;">
                <span>Toplam İşlem</span><strong><?= number_format($kpi['tx_total'], 0, ',', '.') ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>Son güncelleme</span>
                <strong><?= $lastUpdate ? tr_datetime($lastUpdate['created_at']) . ' (' . e($lastUpdate['status']) . ')' : '—' ?></strong>
            </div>
        </div>
        <div style="margin-top:14px;">
            <a href="/admin/updates.php" class="btn btn-primary btn-sm">Smart Update v5</a>
        </div>
    </div>

    <div class="cf-card">
        <h3>Hızlı Erişim</h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <a href="/admin/users.php" class="btn btn-ghost btn-sm">👥 Kullanıcılar</a>
            <a href="/admin/subscriptions.php" class="btn btn-ghost btn-sm">⭐ Abonelikler</a>
            <a href="/admin/payments.php" class="btn btn-ghost btn-sm">💳 Ödemeler</a>
            <a href="/admin/plans.php" class="btn btn-ghost btn-sm">📦 Planlar</a>
            <a href="/admin/transactions.php" class="btn btn-ghost btn-sm">💼 İşlemler</a>
            <a href="/admin/logs.php" class="btn btn-ghost btn-sm">📋 Denetim</a>
            <a href="/admin/settings.php" class="btn btn-ghost btn-sm">⚙️ Ayarlar</a>
            <a href="/admin/updates.php" class="btn btn-ghost btn-sm">⬆️ Güncellemeler</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
