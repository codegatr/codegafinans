<?php
/**
 * CODEGA Finans - Yönetici: Smart Update v5
 */
declare(strict_types=1);

require_once __DIR__ . '/../../inc/admin_auth.php';
require_once __DIR__ . '/../../inc/updater.php';
require_once __DIR__ . '/../../inc/migrate.php';

$admin = admin_require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'apply') {
        @set_time_limit(300);
        $r = upd_apply((int)$admin['id']);
        flash($r['ok'] ? 'success' : 'danger', $r['message']);
        redirect('/admin/updates.php');
    }

    if ($action === 'migrate_only') {
        @set_time_limit(120);
        $log = cf_migrate_all();
        flash('success', 'Migration tamamlandı. ' . count($log) . ' adım.');
        redirect('/admin/updates.php');
    }
}

$status = upd_status();

$pageTitle  = 'Smart Update v5';
$pageHeader = 'Sistem Güncellemeleri';
require __DIR__ . '/../../inc/admin_header.php';
?>

<div class="cf-update-card">
    <h3 style="margin-top:0;">Smart Update v5</h3>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:14px 0;">
        <div>
            <small style="color:#94a3b8;">Yüklü sürüm</small>
            <div><span class="ver">v<?= e($status['current']) ?></span></div>
        </div>
        <div>
            <small style="color:#94a3b8;">Son sürüm</small>
            <div>
                <?php if ($status['latest']): ?>
                    <span class="ver <?= $status['has_update'] ? 'new' : '' ?>">
                        <?= e($status['latest']['tag']) ?>
                    </span>
                <?php else: ?>
                    <span class="ver">— bilinmiyor —</span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <small style="color:#94a3b8;">Repository</small>
            <div><code style="background:rgba(255,255,255,.1);padding:2px 8px;border-radius:6px;font-size:13px;"><?= e($status['repo']) ?></code></div>
        </div>
        <div>
            <small style="color:#94a3b8;">Son migration</small>
            <div><span class="ver"><?= e($status['db_migration']) ?></span></div>
        </div>
    </div>

    <?php if ($status['has_update']): ?>
        <div style="background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.35);padding:12px 14px;border-radius:10px;color:#bbf7d0;margin-bottom:14px;">
            🎉 <strong>Yeni sürüm mevcut: <?= e($status['latest']['tag']) ?></strong>
            <?php if (!empty($status['latest']['name'])): ?> · <?= e($status['latest']['name']) ?><?php endif; ?>
            <?php if (!empty($status['latest']['published'])): ?> · <?= tr_date($status['latest']['published']) ?><?php endif; ?>
        </div>

        <?php if (!empty($status['latest']['body'])): ?>
            <details style="background:rgba(0,0,0,.2);padding:10px 14px;border-radius:8px;margin-bottom:14px;">
                <summary style="cursor:pointer;color:#cbd5e1;">Sürüm notları</summary>
                <pre style="color:#cbd5e1;font-size:12px;white-space:pre-wrap;margin-top:8px;"><?= e($status['latest']['body']) ?></pre>
            </details>
        <?php endif; ?>

        <form method="post" onsubmit="return confirm('Güncellemeyi uygulamak istediğinizden emin misiniz?\n\nBu işlem:\n• Önce mevcut dosyaların ZIP yedeğini alacak\n• Sonra GitHub Release ZIP\'ini indirip uygulayacak\n• Migration\'ları çalıştıracak\n\nDevam edilsin mi?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="apply">
            <button type="submit" class="btn btn-success">⬆️ Güncellemeyi Uygula</button>
        </form>
    <?php elseif ($status['latest']): ?>
        <div style="background:rgba(14,165,233,.15);border:1px solid rgba(14,165,233,.35);padding:12px 14px;border-radius:10px;color:#bae6fd;margin-bottom:14px;">
            ✓ Sistem güncel. En son sürüm zaten yüklü.
        </div>
    <?php else: ?>
        <div style="background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.35);padding:12px 14px;border-radius:10px;color:#fde68a;margin-bottom:14px;">
            ⚠️ GitHub'a ulaşılamadı veya henüz hiç Release yayınlanmamış. <code>CF_UPDATE_GH_TOKEN</code> ayarını kontrol edin.
        </div>
    <?php endif; ?>

    <form method="post" style="margin-top:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="migrate_only">
        <button class="btn btn-ghost btn-sm" style="background:rgba(255,255,255,.08);color:#fff;">⟳ Yalnızca migration çalıştır</button>
    </form>
</div>

<div class="cf-card">
    <h3>Son Güncelleme Günlüğü</h3>
    <?php if (empty($status['last_log'])): ?>
        <div class="cf-empty"><div class="icon">📜</div>Henüz bir güncelleme yapılmamış.</div>
    <?php else: ?>
        <div class="cf-table-wrap">
            <table class="cf-table" style="box-shadow:none;border:0;">
                <thead>
                    <tr><th>Tarih</th><th>Sürüm</th><th>Durum</th><th>Mesaj</th></tr>
                </thead>
                <tbody>
                <?php foreach ($status['last_log'] as $row): ?>
                    <tr>
                        <td><?= tr_datetime($row['created_at']) ?></td>
                        <td>
                            <code style="font-size:12px;"><?= e($row['from_ver'] ?? '?') ?> → <?= e($row['to_ver'] ?? '?') ?></code>
                        </td>
                        <td>
                            <span class="cf-pill <?php
                                echo $row['status']==='success'?'success':
                                    ($row['status']==='failed'?'danger':
                                    ($row['status']==='rolled_back'?'warn':'info'));
                            ?>"><?= e($row['status']) ?></span>
                        </td>
                        <td>
                            <details>
                                <summary style="cursor:pointer;font-size:13px;">
                                    <?= e(cf_str_sub(strtok($row['message'] ?? '', "\n"), 0, 90)) ?>
                                </summary>
                                <pre class="cf-update-log" style="margin-top:6px;"><?= e($row['message'] ?? '') ?></pre>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="cf-card" style="margin-top:14px;">
    <h3>Nasıl Çalışır?</h3>
    <ol style="margin:0;padding-left:20px;color:var(--cf-text-soft);line-height:1.9;font-size:14px;">
        <li><strong>Durum kontrolü:</strong> <code><?= e('https://api.github.com/repos/' . CF_REPO . '/releases/latest') ?></code> sorgulanır. CF_UPDATE_GH_TOKEN tanımlıysa private repo'ya da erişilir.</li>
        <li><strong>İndirme:</strong> Release'in en üst .zip varlığı varsa kullanılır; yoksa GitHub zipball_url'i kullanılır.</li>
        <li><strong>Yedek:</strong> Tüm kurulu dosyaların ZIP yedeği <code>/backups</code> klasörüne alınır.</li>
        <li><strong>Açma:</strong> Yeni ZIP <code>/updates/_extract_&lt;tag&gt;</code> dizinine açılır.</li>
        <li><strong>Kopyalama:</strong> <code>manifest.json.tracked_paths</code> hedefe kopyalanır; <code>excluded_paths</code> (storage/, backups/, config.local.php) dokunulmaz.</li>
        <li><strong>Migration:</strong> <code>migrations/*.sql</code> idempotent şekilde çalıştırılır.</li>
        <li><strong>Temizlik:</strong> <code>CF_UPDATE_KEEP_BACKUPS=<?= (int)CF_UPDATE_KEEP_BACKUPS ?></code> üstündeki eski yedekler silinir.</li>
    </ol>
</div>

<?php require __DIR__ . '/../../inc/admin_footer.php'; ?>
