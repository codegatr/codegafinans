<?php
/**
 * CODEGA Finans - Abonelik
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/subscription.php';

$user = auth_require();
$sub  = subscription_latest_for((int)$user['id']);
$plans = plans_active();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'cancel') {
        $r = subscription_cancel((int)$user['id']);
        flash($r['ok'] ? 'success' : 'danger',
              $r['ok'] ? 'Aboneliğiniz iptal edildi. Dönem sonuna kadar kullanmaya devam edebilirsiniz.'
                       : ($r['message'] ?? 'İptal edilemedi.'));
        redirect('/subscription.php');
    }
}

$iban    = db_one("SELECT value FROM " . t('settings') . " WHERE key_name='iban'");
$ibanNm  = db_one("SELECT value FROM " . t('settings') . " WHERE key_name='iban_name'");
$contact = db_one("SELECT value FROM " . t('settings') . " WHERE key_name='contact_email'");

$pageTitle  = 'Abonelik';
$pageHeader = 'Abonelik Yönetimi';

require __DIR__ . '/../inc/header.php';
?>

<?php if (!empty($_GET['reason']) && $_GET['reason']==='expired'): ?>
    <div class="cf-flash danger">
        ⚠️ Bu sayfaya erişim için aktif bir aboneliğinizin olması gerekiyor.
    </div>
<?php endif; ?>

<!-- Mevcut durum -->
<div class="cf-card" style="margin-bottom:18px;background:var(--cf-grad-night);color:#fff;border:0;">
    <h3 style="color:#fff;">Mevcut Aboneliğiniz</h3>
    <?php if (!$sub): ?>
        <p style="color:#cbd5e1;">Henüz bir abonelik kaydınız yok.</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:12px;">
            <div>
                <small style="color:#94a3b8;">Plan</small>
                <div style="font-size:18px;font-weight:700;"><?= e($sub['plan_name']) ?></div>
            </div>
            <div>
                <small style="color:#94a3b8;">Durum</small>
                <div style="font-size:18px;font-weight:700;color:<?php
                    echo $sub['status']==='active'?'#34d399':($sub['status']==='trial'?'#fbbf24':'#f87171');
                ?>;">
                    <?= e($sub['status']) ?>
                </div>
            </div>
            <div>
                <small style="color:#94a3b8;">Dönem Bitişi</small>
                <div style="font-size:18px;font-weight:700;"><?= tr_date($sub['current_period_end']) ?></div>
            </div>
            <div>
                <small style="color:#94a3b8;">Yenileme</small>
                <div style="font-size:18px;font-weight:700;"><?= $sub['auto_renew'] ? 'Açık' : 'Kapalı' ?></div>
            </div>
        </div>

        <?php if (in_array($sub['status'], ['active','trial'], true)): ?>
        <form method="post" style="margin-top:16px;" onsubmit="return confirm('Aboneliğinizi iptal etmek istediğinize emin misiniz? Dönem sonuna kadar kullanmaya devam edebilirsiniz.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel">
            <button class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.25);">Aboneliği İptal Et</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Planlar -->
<h2 style="margin:18px 0 12px;font-size:20px;">Planlar</h2>
<div class="cf-grid cf-grid-2">
<?php foreach ($plans as $p):
    $isCurrent = $sub && (int)$sub['plan_id'] === (int)$p['id'] && in_array($sub['status'], ['active','trial'], true);
?>
    <div class="cf-card" style="<?= $p['code']==='yearly' ? 'border:2px solid var(--cf-primary);' : '' ?>">
        <?php if ($p['code']==='yearly'): ?>
            <div style="margin-bottom:10px;"><span class="cf-pill info">★ En Avantajlı</span></div>
        <?php endif; ?>
        <h3 style="margin:0 0 4px;font-size:18px;"><?= e($p['name']) ?></h3>
        <div style="font-size:32px;font-weight:800;color:var(--cf-primary);margin:6px 0;">
            <?= money($p['price']) ?>
            <small style="font-size:14px;color:var(--cf-text-soft);font-weight:500;">
                / <?= $p['period']==='yearly'?'yıl':($p['period']==='monthly'?'ay':'') ?>
            </small>
        </div>
        <ul style="list-style:none;padding:0;margin:14px 0;font-size:14px;color:var(--cf-text-soft);line-height:2;">
            <li>✓ Sınırsız gelir/gider kaydı</li>
            <li>✓ Aylık bütçe ve kategori limiti</li>
            <li>✓ Tasarruf hedefleri ve borç takibi</li>
            <li>✓ Güncel TCMB döviz kurları</li>
            <li>✓ Akıllı uyarılar &amp; analizler</li>
            <?php if ($p['period']==='yearly'): ?>
                <li>✓ <strong>~%20 indirim</strong></li>
            <?php endif; ?>
        </ul>
        <?php if ($isCurrent): ?>
            <button class="btn btn-success btn-block" disabled>Mevcut Planınız</button>
        <?php else: ?>
            <a class="btn btn-primary btn-block" href="#nasil">Aboneliği Başlat</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<!-- Ödeme talimatı -->
<div class="cf-card" id="nasil" style="margin-top:18px;">
    <h3>Nasıl Abone Olunur?</h3>
    <p class="muted">Şu an için ödemeler havale/EFT yoluyla manuel olarak alınmaktadır. Ödemenizi yaptıktan sonra aboneliğiniz 24 saat içinde aktive edilir.</p>

    <div class="cf-grid cf-grid-2" style="margin-top:14px;">
        <div style="background:#f8fafc;padding:18px;border-radius:12px;">
            <h4 style="margin:0 0 10px;font-size:14px;">📌 Adımlar</h4>
            <ol style="margin:0;padding-left:18px;color:var(--cf-text-soft);line-height:1.9;font-size:14px;">
                <li>Yandaki IBAN'a istediğiniz plana karşılık gelen tutarı havale edin.</li>
                <li>Açıklamaya <strong><?= e($user['email']) ?></strong> yazın.</li>
                <li>Dekontu <strong><?= e($contact['value'] ?? CF_ADMIN_MAIL) ?></strong> adresine gönderin.</li>
                <li>24 saat içinde aboneliğiniz aktive edilir, e-posta ile bilgi verilir.</li>
            </ol>
        </div>
        <div style="background:#f8fafc;padding:18px;border-radius:12px;">
            <h4 style="margin:0 0 10px;font-size:14px;">💳 IBAN Bilgileri</h4>
            <div style="font-size:13px;color:var(--cf-text-soft);">Hesap Sahibi</div>
            <div style="font-size:16px;font-weight:700;margin-bottom:8px;"><?= e($ibanNm['value'] ?? 'CODEGA') ?></div>
            <div style="font-size:13px;color:var(--cf-text-soft);">IBAN</div>
            <div style="font-family:'Menlo','Monaco','Consolas',monospace;font-size:14px;font-weight:700;background:#fff;padding:10px 12px;border-radius:8px;border:1px dashed #cbd5e1;margin-top:4px;word-break:break-all;">
                <?= e($iban['value'] ?? '—') ?>
            </div>
            <div style="font-size:12px;color:var(--cf-muted);margin-top:8px;">
                Sorularınız için: <a href="mailto:<?= e($contact['value'] ?? CF_ADMIN_MAIL) ?>"><?= e($contact['value'] ?? CF_ADMIN_MAIL) ?></a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
