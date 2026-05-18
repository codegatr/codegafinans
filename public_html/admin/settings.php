<?php
/**
 * CODEGA Finans - Yönetici: Sistem Ayarları
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';
require_once __DIR__ . '/../../inc/mail.php';

$admin = admin_require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['_action'] ?? '') === 'test_mail') {
        $to = s($_POST['test_to'] ?? '', 160);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Test icin gecerli bir e-posta adresi yazin.');
            redirect('/admin/settings.php');
        }
        try {
            cf_send_mail(
                $to,
                'CODEGA Finans test maili',
                '<p>CODEGA Finans mail ayarlariniz calisiyor.</p><p>Test zamani: ' . e(date('d.m.Y H:i:s')) . '</p>',
                'CODEGA Finans mail ayarlariniz calisiyor. Test zamani: ' . date('d.m.Y H:i:s')
            );
            audit('admin.settings.mail_test', null, (int)$admin['id'], 'to=' . $to);
            flash('success', 'Test maili gonderildi: ' . $to);
        } catch (Throwable $e) {
            flash('danger', 'Test maili gonderilemedi: ' . $e->getMessage());
        }
        redirect('/admin/settings.php');
    }

    foreach ($_POST as $key => $val) {
        if ($key === '_token' || str_starts_with($key, '_')) continue;
        if (!preg_match('/^[a-z0-9_]+$/i', $key)) continue;
        $val = is_string($val) ? trim($val) : '';
        if ($key === 'mail_pass' && $val === '') continue;
        if (cf_str_len($val) > 4000) $val = cf_str_sub($val, 0, 4000);
        db_exec(
            'INSERT INTO ' . t('settings') . ' (key_name, value)
             VALUES (:k, :v_insert) ON DUPLICATE KEY UPDATE value = :v_update',
            [':k' => $key, ':v_insert' => $val, ':v_update' => $val]
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

$mailKnown = [
    'mail_host'      => ['label' => 'SMTP Sunucu',       'type' => 'text',     'placeholder' => 'smtp.gmail.com'],
    'mail_port'      => ['label' => 'SMTP Port',         'type' => 'number',   'placeholder' => '587'],
    'mail_secure'    => ['label' => 'Guvenlik',          'type' => 'text',     'placeholder' => 'tls'],
    'mail_timeout'   => ['label' => 'Zaman Asimi Sn.',   'type' => 'number',   'placeholder' => '15'],
    'mail_user'      => ['label' => 'SMTP Kullanici',    'type' => 'email',    'placeholder' => 'donusyapmayin@gmail.com'],
    'mail_pass'      => ['label' => 'Uygulama Sifresi',  'type' => 'password', 'placeholder' => 'Bos birakirsaniz mevcut sifre korunur'],
    'mail_from'      => ['label' => 'Gonderen E-posta',  'type' => 'email',    'placeholder' => 'donusyapmayin@gmail.com'],
    'mail_from_name' => ['label' => 'Gonderen Adi',      'type' => 'text',     'placeholder' => 'CODEGA Finans'],
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
        <h3>Mail / SMTP Ayarları</h3>
        <p class="muted" style="font-size:12px;margin-top:-4px;">
            Gmail icin once <code>smtp.gmail.com</code> + <code>587</code> + <code>tls</code> deneyin. Zaman asimi alirsaniz <code>465</code> + <code>ssl</code> deneyin veya hosting firmanizdan dis SMTP cikis izni isteyin.
        </p>
        <form method="post" class="cf-form" data-once>
            <?= csrf_field() ?>
            <?php foreach ($mailKnown as $k => $meta): ?>
                <div>
                    <label><?= e($meta['label']) ?> <small style="color:var(--cf-muted);"><code><?= e($k) ?></code></small></label>
                    <input
                        type="<?= e($meta['type']) ?>"
                        name="<?= e($k) ?>"
                        value="<?= $k === 'mail_pass' ? '' : e($kv[$k] ?? '') ?>"
                        placeholder="<?= e($meta['placeholder'] ?? '') ?>"
                        maxlength="500">
                    <?php if ($k === 'mail_pass' && !empty($kv[$k])): ?>
                        <small class="muted">Kayitli uygulama sifresi korunuyor. Degistirmek icin yeni sifre yazin.</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button class="btn btn-primary" style="justify-self:start;margin-top:6px;">Mail Ayarlarını Kaydet</button>
        </form>

        <form method="post" class="cf-form" data-once style="margin-top:18px;border-top:1px solid #eef0f4;padding-top:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="test_mail">
            <div>
                <label>Test Maili Gonder</label>
                <input type="email" name="test_to" value="<?= e($kv['mail_user'] ?? $kv['contact_email'] ?? '') ?>" placeholder="ornek@alan.com" required>
            </div>
            <button class="btn btn-outline" style="justify-self:start;">Test Maili Gonder</button>
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
                        <?php $displayValue = $r['key_name'] === 'mail_pass' ? '••••••••' : $r['value']; ?>
                        <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($displayValue) ?>"><?= e($displayValue) ?></td>
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
