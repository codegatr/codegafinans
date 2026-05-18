<?php
/**
 * CODEGA Finans - Döviz Kurları
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/rates.php';

$user = auth_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'refresh') {
        $r = rates_refresh_from_tcmb();
        flash($r['ok'] ? 'success' : 'danger', $r['ok']
            ? "Güncellendi · {$r['updated']} kayıt."
            : ($r['message'] ?? 'Güncelleme başarısız.')
        );
        redirect('/rates.php');
    }
}

$rates = rates_all(true);
$last  = db_one("SELECT value FROM " . t('settings') . " WHERE key_name='rates_last_at'");

$pageTitle  = 'Döviz Kurları';
$pageHeader = 'Güncel Döviz Kurları';

require __DIR__ . '/../inc/header.php';
?>

<div class="cf-page-head">
    <h2>TCMB Kurları</h2>
    <div class="actions">
        <form method="post" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="refresh">
            <button class="btn btn-primary btn-sm">⟳ Şimdi Güncelle</button>
        </form>
    </div>
</div>

<div class="cf-card" style="margin-bottom:14px;">
    <small style="color:var(--cf-text-soft);">
        Kaynak: <strong>Türkiye Cumhuriyet Merkez Bankası</strong> · Son güncelleme:
        <strong><?= e($last['value'] ?? '—') ?></strong> ·
        Otomatik yenileme: her <?= (int)CF_TCMB_REFRESH_MIN ?> dakikada bir.
    </small>
</div>

<?php if (empty($rates)): ?>
    <div class="cf-card cf-empty"><div class="icon">💱</div>Henüz kur verisi yok.</div>
<?php else: ?>
<div class="cf-grid cf-grid-4">
    <?php foreach ($rates as $r): ?>
        <div class="cf-card" style="text-align:center;">
            <div style="font-size:28px;font-weight:800;color:var(--cf-primary);"><?= e($r['code']) ?></div>
            <div style="color:var(--cf-text-soft);font-size:13px;margin-bottom:10px;"><?= e($r['name']) ?></div>
            <div style="display:flex;justify-content:space-between;font-size:14px;border-top:1px solid #eef0f4;padding-top:10px;">
                <span>Alış</span>
                <strong><?= number_format((float)$r['buy_rate'], 4, ',', '.') ?> ₺</strong>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:14px;margin-top:4px;">
                <span>Satış</span>
                <strong style="color:#0ea5e9;"><?= number_format((float)$r['sell_rate'], 4, ',', '.') ?> ₺</strong>
            </div>
            <small style="display:block;margin-top:8px;color:var(--cf-muted);font-size:11px;">
                <?= tr_datetime($r['updated_at']) ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
