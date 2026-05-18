<?php
/**
 * CODEGA Finans - Yönetici: Sistem Ayarları
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';

$admin = admin_require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($_POST as $key => $val) {
        if ($key === '_token' || str_starts_with($key, '_')) continue;
        if (!preg_match('/^[a-z0-9_]+$/i', $key)) continue;
        $val = is_string($val) ? trim($val) : '';
        if (cf_str_len($val) > 4000) $val = cf_str_sub($val, 0, 4000);
        db_exec(
            'INSERT INTO ' . t('settings') . ' (key_name, value)
             VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v',
            [':k' => $key, ':v' => $val]
        );
    }
    audit('admin.settings.update', null, (int)$admin['id']);
    flash('success', 'Ayarlar kaydedildi.');
    redirect('/admin/settings.php');
}

$rows = db_all('SELECT * FROM ' . t('settings') . ' ORDER BY key_name');
$kv = [];
foreach ($rows as $r) { $kv[$r['key_name']] = $r['value']; }

// Tanımlı ayar anahtarları
$known = [
    'site_title'    => ['label' => 'Site Başlığı',      'type' => 'text'],
    'contact_email' => ['label' => 'İletişim E-posta',  'type' => 'email'],
    'iban'          => ['label' => 'IBAN',               'type' => 'text'],
    'iban_name'     => ['label' => 'IBAN Hesap Sahibi', 'type' => 'text'],
    'maintenance'   => ['label' => 'Bakım Modu (0/1)',  'type' => 'text'],
];

$pageTitle  = 'Sistem Ayarları';
$pageHeader = 'Sistem Ayarları';
require __DIR__ . '/../../inc/admin_header.php';
?>

<div class="cf-grid cf-grid-2">

    <div class="cf-card">
        <h3>Genel Ayarlar</h3>
        <form method="post" class="cf-form" data-once>
            <?= csrf_field() ?>
            <?php foreach ($known as $k => $meta): ?>
                <div>
                    <label><?= e($meta['label']) ?> <small style="color:var(--cf-muted);"><code><?= e($k) ?></code></small></label>
                    <input type="<?= e($meta['type']) ?>" name="<?= e($k) ?>" value="<?= e($kv[$k] ?? '') ?>" maxlength="500">
                </div>
            <?php endforeach; ?>
            <button class="btn btn-primary" style="justify-self:start;margin-top:6px;">Kaydet</button>
        </form>
    </div>

    <div class="cf-card">
        <h3>Sistem Bilgileri</h3>
        <div style="display:grid;gap:8px;font-size:13px;color:var(--cf-text-soft);">
            <div><strong style="color:var(--cf-text);">Uygulama:</strong> <?= e(CF_APP_NAME) ?> v<?= e(CF_VERSION) ?></div>
            <div><strong style="color:var(--cf-text);">Domain:</strong> <?= e(CF_DOMAIN) ?></div>
            <div><strong style="color:var(--cf-text);">Repo:</strong> <code><?= e(CF_REPO) ?></code></div>
            <div><strong style="color:var(--cf-text);">Modül:</strong> <?= (int)CF_TOTAL_MODULES ?></div>
            <div><strong style="color:var(--cf-text);">PHP:</strong> <?= e(PHP_VERSION) ?></div>
            <div><strong style="color:var(--cf-text);">MySQL:</strong> <?= e(db()->getAttribute(PDO::ATTR_SERVER_VERSION) ?? '?') ?></div>
            <div><strong style="color:var(--cf-text);">DB:</strong> <?= e(CF_DB_NAME) ?> (<?= e(CF_DB_PREFIX) ?>)</div>
            <div><strong style="color:var(--cf-text);">Zaman:</strong> <?= date('d.m.Y H:i:s') ?> (<?= e(date_default_timezone_get()) ?>)</div>
            <div><strong style="color:var(--cf-text);">Trial:</strong> <?= (int)CF_TRIAL_DAYS ?> gün</div>
        </div>
    </div>

    <div class="cf-card">
        <h3>Tüm Ayar Anahtarları</h3>
        <div class="cf-table-wrap">
            <table class="cf-table" style="box-shadow:none;border:0;">
                <thead><tr><th>Anahtar</th><th>Değer</th><th>Güncellenme</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code><?= e($r['key_name']) ?></code></td>
                        <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($r['value']) ?>"><?= e($r['value']) ?></td>
                        <td style="font-size:12px;color:var(--cf-muted);"><?= tr_datetime($r['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="cf-card">
        <h3>Yöneticiler</h3>
        <?php
        $admins = db_all('SELECT id,name,email,role,is_active,last_login_at FROM ' . t('admins') . ' ORDER BY id');
        ?>
        <div class="cf-table-wrap">
            <table class="cf-table" style="box-shadow:none;border:0;">
                <thead><tr><th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Son Giriş</th></tr></thead>
                <tbody>
                <?php foreach ($admins as $a): ?>
                    <tr>
                        <td><?= e($a['name']) ?></td>
                        <td><?= e($a['email']) ?></td>
                        <td><span class="cf-pill"><?= e($a['role']) ?></span></td>
                        <td>
                            <span class="cf-pill <?= $a['is_active']?'success':'danger' ?>">
                                <?= $a['is_active'] ? 'Aktif' : 'Pasif' ?>
                            </span>
                        </td>
                        <td><?= tr_datetime($a['last_login_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted" style="margin-top:10px;font-size:12px;">
            Yönetici hesabı eklemek için <code>migrations/</code> veya doğrudan veritabanı üzerinden tanımlama yapılmalıdır.
            CLI ile: <code>php cli/add_admin.php</code>
        </p>
    </div>

</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
