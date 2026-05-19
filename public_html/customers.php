<?php
/**
 * CODEGA Finans - Cari Hesaplar
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/mail.php';

$user = auth_require_active_subscription();
$uid = (int)$user['id'];

function cari_report_rows(int $userId, ?string $from = null, ?string $to = null, ?int $customerId = null): array
{
    $dateWhere = ' WHERE user_id = :mu';
    $params = [':u' => $userId, ':mu' => $userId];
    if ($from) {
        $dateWhere .= ' AND tx_date >= :from';
        $params[':from'] = $from;
    }
    if ($to) {
        $dateWhere .= ' AND tx_date <= :to';
        $params[':to'] = $to;
    }
    if ($customerId) {
        $dateWhere .= ' AND customer_id = :customer_filter';
        $params[':customer_filter'] = $customerId;
    }

    $customerWhere = '';
    if ($customerId) {
        $customerWhere = ' AND c.id = :selected_customer';
        $params[':selected_customer'] = $customerId;
    }

    return db_all(
        'SELECT c.id, c.name, c.type, c.phone, c.email,
                COALESCE(ms.debit_total,0) AS debit_total,
                COALESCE(ms.credit_total,0) AS credit_total,
                COALESCE(ms.balance,0) AS balance,
                ms.last_tx_date
           FROM ' . t('customers') . ' c
           LEFT JOIN (
                SELECT customer_id,
                       SUM(IF(direction="debit", amount, 0)) AS debit_total,
                       SUM(IF(direction="credit", amount, 0)) AS credit_total,
                       SUM(IF(direction="debit", amount, -amount)) AS balance,
                       MAX(tx_date) AS last_tx_date
                  FROM ' . t('customer_movements') . $dateWhere . '
                 GROUP BY customer_id
           ) ms ON ms.customer_id = c.id
          WHERE c.user_id = :u AND c.is_active = 1' . $customerWhere . '
          ORDER BY c.name',
        $params
    );
}

function cari_user_name(array $user): string
{
    return trim((string)($user['name'] ?? '')) ?: CF_APP_NAME;
}

function cari_user_contact(array $user): string
{
    $parts = [];
    if (!empty($user['phone'])) { $parts[] = (string)$user['phone']; }
    if (!empty($user['email'])) { $parts[] = (string)$user['email']; }
    return $parts ? implode(' / ', $parts) : '-';
}

function cari_redirect(?int $id = null): never
{
    redirect('/customers.php' . ($id ? '?id=' . $id : ''));
}

function cari_customer_or_fail(int $userId, int $customerId): array
{
    $customer = db_one(
        'SELECT * FROM ' . t('customers') . ' WHERE id=:id AND user_id=:u AND is_active=1',
        [':id' => $customerId, ':u' => $userId]
    );
    if (!$customer) {
        http_response_code(404);
        die('Cari bulunamadı.');
    }
    return $customer;
}

function cari_statement_rows(int $userId, int $customerId, ?string $from = null, ?string $to = null): array
{
    $where = ' WHERE user_id=:u AND customer_id=:c';
    $params = [':u' => $userId, ':c' => $customerId];
    if ($from) {
        $where .= ' AND tx_date >= :from';
        $params[':from'] = $from;
    }
    if ($to) {
        $where .= ' AND tx_date <= :to';
        $params[':to'] = $to;
    }

    return db_all(
        'SELECT * FROM ' . t('customer_movements') . $where . ' ORDER BY tx_date ASC, id ASC',
        $params
    );
}

function cari_statement_totals(array $rows): array
{
    $debit = 0.0;
    $credit = 0.0;
    foreach ($rows as $row) {
        if ($row['direction'] === 'debit') {
            $debit += (float)$row['amount'];
        } else {
            $credit += (float)$row['amount'];
        }
    }
    return [$debit, $credit, $debit - $credit];
}

function cari_doc_period(?string $from, ?string $to): string
{
    return ($from ? tr_date($from) : 'İlk kayıt') . ' - ' . ($to ? tr_date($to) : 'Bugün');
}

function cari_document_styles(): string
{
    return 'body{margin:0;background:#eef2f7;color:#0f172a;font-family:Arial,sans-serif}.bar{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:14px 22px;background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:5}.bar-actions{display:flex;gap:8px;flex-wrap:wrap}.page{max-width:980px;margin:24px auto;background:#fff;padding:32px;box-shadow:0 18px 50px rgba(15,23,42,.12);border:1px solid #e5e7eb}.btn{border:0;background:#2563eb;color:#fff;padding:10px 14px;border-radius:8px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex}.btn.secondary{background:#0f172a}.doc-head{display:flex;justify-content:space-between;gap:20px;border-bottom:3px solid #0f172a;padding-bottom:18px;margin-bottom:18px}.brand{font-weight:800;font-size:22px;letter-spacing:.2px}.doc-kicker{font-size:12px;color:#0f766e;font-weight:800;text-transform:uppercase;letter-spacing:.8px;margin-top:4px}.doc-title{text-align:right}.doc-title h1{font-size:24px;margin:0 0 6px}.muted{color:#64748b}.info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:16px 0}.info{border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fbfdff}.info small{display:block;color:#64748b;margin-bottom:4px;font-size:11px;text-transform:uppercase;letter-spacing:.4px}.info strong{font-size:15px}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:18px 0}.sum{border-radius:12px;padding:15px;color:#fff}.sum.debit{background:#2563eb}.sum.credit{background:#0f766e}.sum.net{background:#111827}.sum small{display:block;opacity:.86;margin-bottom:6px}.sum strong{font-size:21px}table{width:100%;border-collapse:collapse;margin-top:14px}th,td{padding:10px;border-bottom:1px solid #e5e7eb;font-size:13px;vertical-align:top}th{background:#f8fafc;text-transform:uppercase;font-size:11px;letter-spacing:.4px;color:#475569;text-align:left}.amount{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.balance-note{font-size:12px;color:#475569;margin-top:8px}.foot{margin-top:18px;color:#64748b;font-size:12px;border-top:1px solid #e5e7eb;padding-top:12px;line-height:1.5}@media(max-width:700px){.page{margin:0;padding:18px;border:0}.doc-head,.info-grid,.summary{grid-template-columns:1fr;display:grid}.doc-title{text-align:left}.bar{align-items:flex-start;flex-direction:column}.bar-actions{width:100%}.bar-actions .btn{flex:1;justify-content:center}th,td{font-size:12px;padding:8px 6px}.doc-title h1{font-size:21px}}@media print{body{background:#fff}.bar{display:none}.page{max-width:none;margin:0;padding:0;box-shadow:none;border:0}.sum{-webkit-print-color-adjust:exact;print-color-adjust:exact}a{color:inherit;text-decoration:none}}';
}

function cari_document_shell(string $title, string $body, string $backUrl): never
{
    while (ob_get_level() > 0) { ob_end_clean(); }
    ?><!doctype html>
    <html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?></title>
        <style><?= cari_document_styles() ?></style>
    </head>
    <body>
        <div class="bar">
            <strong><?= e($title) ?></strong>
            <div class="bar-actions">
                <button class="btn" onclick="window.print()">PDF / Yazdır</button>
                <a class="btn secondary" href="<?= e($backUrl) ?>">Geri dön</a>
            </div>
        </div>
        <main class="page"><?= $body ?></main>
    </body>
    </html><?php
    exit;
}

function cari_statement_html(array $customer, array $rows, ?string $from, ?string $to, array $user): string
{
    [$debit, $credit, $balance] = cari_statement_totals($rows);
    $period = cari_doc_period($from, $to);
    $running = 0.0;
    $senderName = cari_user_name($user);
    $senderContact = cari_user_contact($user);
    $customerContact = ($customer['email'] ?: '-') . ' / ' . ($customer['phone'] ?: '-');
    $netLabel = $balance >= 0 ? 'Alacak' : 'Borç';
    ob_start();
    ?>
    <div style="margin:0;padding:24px;background:#f3f6fa;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;max-width:980px;margin:0 auto;background:#ffffff;border:1px solid #dbe3ef;">
            <tr>
                <td style="padding:30px 34px 18px 34px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                        <tr>
                            <td valign="top" style="padding:0 16px 20px 0;">
                                <div style="font-size:25px;line-height:1.15;font-weight:800;letter-spacing:.3px;color:#06152e;"><?= e($senderName) ?></div>
                                <div style="margin-top:8px;font-size:13px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#0f766e;">Cari mutabakat belgesi</div>
                                <div style="margin-top:8px;font-size:14px;line-height:1.45;color:#50627d;"><?= e($senderContact) ?></div>
                            </td>
                            <td valign="top" align="right" style="padding:0 0 20px 16px;">
                                <div style="font-size:28px;line-height:1.15;font-weight:800;color:#06152e;">Cari Hesap Ekstresi</div>
                                <div style="margin-top:8px;font-size:15px;color:#50627d;"><?= e($period) ?></div>
                            </td>
                        </tr>
                    </table>
                    <div style="height:3px;background:#111827;margin:0 0 22px 0;"></div>

                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 10px;">
                        <tr>
                            <td width="33.33%" valign="top" style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;">
                                <div style="font-size:12px;text-transform:uppercase;color:#50627d;letter-spacing:.5px;">Cari ünvanı</div>
                                <div style="margin-top:7px;font-size:18px;line-height:1.25;font-weight:800;color:#06152e;"><?= e($customer['name']) ?></div>
                            </td>
                            <td width="33.33%" valign="top" style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;">
                                <div style="font-size:12px;text-transform:uppercase;color:#50627d;letter-spacing:.5px;">E-posta / Telefon</div>
                                <div style="margin-top:7px;font-size:16px;line-height:1.35;font-weight:700;color:#06152e;"><?= e($customerContact) ?></div>
                            </td>
                            <td width="33.33%" valign="top" style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;">
                                <div style="font-size:12px;text-transform:uppercase;color:#50627d;letter-spacing:.5px;">Hazırlayan</div>
                                <div style="margin-top:7px;font-size:16px;line-height:1.35;font-weight:800;color:#06152e;"><?= e($senderName) ?></div>
                                <div style="margin-top:4px;font-size:13px;color:#50627d;"><?= e($senderContact) ?></div>
                            </td>
                        </tr>
                    </table>

                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 12px;margin-top:6px;">
                        <tr>
                            <td width="33.33%" style="padding:18px 18px;border-radius:12px;background:#2563eb;color:#ffffff;">
                                <div style="font-size:13px;font-weight:700;opacity:.9;">Toplam Borç</div>
                                <div style="margin-top:10px;font-size:27px;line-height:1.1;font-weight:800;"><?= e(money($debit)) ?></div>
                            </td>
                            <td width="33.33%" style="padding:18px 18px;border-radius:12px;background:#0f766e;color:#ffffff;">
                                <div style="font-size:13px;font-weight:700;opacity:.9;">Toplam Alacak / Ödeme</div>
                                <div style="margin-top:10px;font-size:27px;line-height:1.1;font-weight:800;"><?= e(money($credit)) ?></div>
                            </td>
                            <td width="33.33%" style="padding:18px 18px;border-radius:12px;background:#111827;color:#ffffff;">
                                <div style="font-size:13px;font-weight:700;opacity:.9;">Net Durum</div>
                                <div style="margin-top:10px;font-size:27px;line-height:1.1;font-weight:800;"><?= e(money(abs($balance))) ?> <?= e($netLabel) ?></div>
                            </td>
                        </tr>
                    </table>

                    <div style="margin:12px 0 14px 0;font-size:13px;color:#334155;">Oluşturma zamanı: <?= e(date('d.m.Y H:i')) ?>. Dönem: <?= e($period) ?>.</div>
                    <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #e2e8f0;font-size:13px;">
                        <thead>
                            <tr>
                                <th align="left" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Tarih</th>
                                <th align="left" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">İşlem / Açıklama</th>
                                <th align="left" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Vade</th>
                                <th align="right" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Borç</th>
                                <th align="right" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Alacak</th>
                                <th align="right" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Ara Bakiye</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row):
                            $amount = (float)$row['amount'];
                            $running += $row['direction'] === 'debit' ? $amount : -$amount;
                        ?>
                            <tr>
                                <td style="padding:12px 10px;border-bottom:1px solid #e2e8f0;color:#0f172a;white-space:nowrap;"><?= e(tr_date($row['tx_date'])) ?></td>
                                <td style="padding:12px 10px;border-bottom:1px solid #e2e8f0;color:#0f172a;"><strong><?= e($row['title']) ?></strong><?php if ($row['note']): ?><br><span style="color:#64748b;"><?= e($row['note']) ?></span><?php endif; ?></td>
                                <td style="padding:12px 10px;border-bottom:1px solid #e2e8f0;color:#0f172a;white-space:nowrap;"><?= !empty($row['due_date']) ? e(tr_date($row['due_date'])) : '-' ?></td>
                                <td align="right" style="padding:12px 10px;border-bottom:1px solid #e2e8f0;color:#0f172a;white-space:nowrap;"><?= $row['direction'] === 'debit' ? e(money($amount)) : '-' ?></td>
                                <td align="right" style="padding:12px 10px;border-bottom:1px solid #e2e8f0;color:#0f172a;white-space:nowrap;"><?= $row['direction'] === 'credit' ? e(money($amount)) : '-' ?></td>
                                <td align="right" style="padding:12px 10px;border-bottom:1px solid #e2e8f0;color:#0f172a;white-space:nowrap;"><?= e(money(abs($running))) ?> <?= $running >= 0 ? 'Alacak' : 'Borç' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                            <tr><td colspan="6" style="padding:18px 10px;color:#64748b;">Bu dönem için hareket yok.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <div style="margin-top:20px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:13px;line-height:1.5;color:#50627d;">Bu ekstre bilgilendirme ve mutabakat amacıyla oluşturulmuştur. Ödeme, tahsilat ve itiraz süreçleri için kendi kayıtlarınızla karşılaştırarak kontrol ediniz.</div>
                </td>
            </tr>
        </table>
    </div>
    <?php
    return (string)ob_get_clean();
}

function cari_report_html(array $rows, ?string $from, ?string $to, array $user, ?array $reportCustomer = null): string
{
    $period = cari_doc_period($from, $to);
    $debit = 0.0; $credit = 0.0;
    foreach ($rows as $row) {
        $debit += (float)$row['debit_total'];
        $credit += (float)$row['credit_total'];
    }
    $net = $debit - $credit;
    $senderName = cari_user_name($user);
    $senderContact = cari_user_contact($user);
    $title = $reportCustomer ? 'Müşteri Cari Raporu' : 'Cari Raporu';
    ob_start();
    ?>
    <div style="margin:0;padding:24px;background:#f3f6fa;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;max-width:980px;margin:0 auto;background:#ffffff;border:1px solid #dbe3ef;">
            <tr><td style="padding:30px 34px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;"><tr>
                    <td valign="top"><div style="font-size:25px;font-weight:800;color:#06152e;"><?= e($senderName) ?></div><div style="margin-top:8px;font-size:13px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#0f766e;">Cari rapor belgesi</div><div style="margin-top:8px;font-size:14px;color:#50627d;"><?= e($senderContact) ?></div></td>
                    <td valign="top" align="right"><div style="font-size:28px;font-weight:800;color:#06152e;"><?= e($title) ?></div><div style="margin-top:8px;font-size:15px;color:#50627d;"><?= e($period) ?></div></td>
                </tr></table>
                <div style="height:3px;background:#111827;margin:20px 0 22px 0;"></div>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 10px;"><tr>
                    <td width="33.33%" style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;"><div style="font-size:12px;text-transform:uppercase;color:#50627d;">Raporu hazırlayan</div><div style="margin-top:7px;font-size:16px;font-weight:800;color:#06152e;"><?= e($senderName) ?></div><div style="margin-top:4px;font-size:13px;color:#50627d;"><?= e($senderContact) ?></div></td>
                    <td width="33.33%" style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;"><div style="font-size:12px;text-transform:uppercase;color:#50627d;">Rapor kapsamı</div><div style="margin-top:7px;font-size:16px;font-weight:800;color:#06152e;"><?= e($reportCustomer['name'] ?? (count($rows) . ' cari')) ?></div></td>
                    <td width="33.33%" style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;"><div style="font-size:12px;text-transform:uppercase;color:#50627d;">Oluşturma zamanı</div><div style="margin-top:7px;font-size:16px;font-weight:800;color:#06152e;"><?= e(date('d.m.Y H:i')) ?></div></td>
                </tr></table>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 12px;margin-top:6px;"><tr>
                    <td width="33.33%" style="padding:18px;border-radius:12px;background:#2563eb;color:#fff;"><div style="font-size:13px;font-weight:700;opacity:.9;">Toplam Borç</div><div style="margin-top:10px;font-size:27px;font-weight:800;"><?= e(money($debit)) ?></div></td>
                    <td width="33.33%" style="padding:18px;border-radius:12px;background:#0f766e;color:#fff;"><div style="font-size:13px;font-weight:700;opacity:.9;">Toplam Alacak / Ödeme</div><div style="margin-top:10px;font-size:27px;font-weight:800;"><?= e(money($credit)) ?></div></td>
                    <td width="33.33%" style="padding:18px;border-radius:12px;background:#111827;color:#fff;"><div style="font-size:13px;font-weight:700;opacity:.9;">Net Portföy</div><div style="margin-top:10px;font-size:27px;font-weight:800;"><?= e(money(abs($net))) ?> <?= $net >= 0 ? 'Alacak' : 'Borç' ?></div></td>
                </tr></table>
                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #e2e8f0;font-size:13px;margin-top:12px;"><thead><tr>
                    <th align="left" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Cari</th><th align="left" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">İletişim</th><th align="right" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Borç</th><th align="right" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Alacak</th><th align="right" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Net Bakiye</th><th align="left" style="padding:11px 10px;background:#f1f5f9;color:#334155;border-bottom:1px solid #e2e8f0;">Son Hareket</th>
                </tr></thead><tbody>
                <?php foreach ($rows as $row): $balance = (float)$row['balance']; ?>
                    <tr><td style="padding:12px 10px;border-bottom:1px solid #e2e8f0;"><strong><?= e($row['name']) ?></strong><br><span style="color:#64748b;"><?= e($row['type']) ?></span></td><td style="padding:12px 10px;border-bottom:1px solid #e2e8f0;"><?= e($row['phone'] ?: '-') ?><br><span style="color:#64748b;"><?= e($row['email'] ?: '-') ?></span></td><td align="right" style="padding:12px 10px;border-bottom:1px solid #e2e8f0;white-space:nowrap;"><?= e(money($row['debit_total'])) ?></td><td align="right" style="padding:12px 10px;border-bottom:1px solid #e2e8f0;white-space:nowrap;"><?= e(money($row['credit_total'])) ?></td><td align="right" style="padding:12px 10px;border-bottom:1px solid #e2e8f0;white-space:nowrap;"><?= e(money(abs($balance))) ?> <?= $balance >= 0 ? 'Alacak' : 'Borç' ?></td><td style="padding:12px 10px;border-bottom:1px solid #e2e8f0;"><?= $row['last_tx_date'] ? e(tr_date($row['last_tx_date'])) : '-' ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="6" style="padding:18px 10px;color:#64748b;">Raporlanacak cari bulunamadı.</td></tr><?php endif; ?>
                </tbody></table>
                <div style="margin-top:20px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:13px;line-height:1.5;color:#50627d;">Cari risk, tahsilat ve ödeme takibini hızlı okumak için hazırlanmıştır.</div>
            </td></tr>
        </table>
    </div>
    <?php
    return (string)ob_get_clean();
}

function cari_pdf_ascii(string $text): string
{
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) { $text = $converted; }
    }
    $text = preg_replace('/[^\x20-\x7E]/', ' ', $text) ?? $text;
    return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
}

function cari_pdf_text(string $text): string
{
    $text = cari_pdf_ascii($text);
    return '(' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text) . ')';
}

function cari_pdf_fit(string $text, int $max = 42): string
{
    $text = cari_pdf_ascii($text);
    return strlen($text) > $max ? substr($text, 0, max(0, $max - 3)) . '...' : $text;
}

function cari_pdf_money($value): string
{
    return cari_pdf_ascii(money($value));
}

function cari_pdf_rect(float $x, float $y, float $w, float $h, string $fill, ?string $stroke = null): string
{
    $cmd = $fill . " rg\n{$x} {$y} {$w} {$h} re f\n";
    if ($stroke) { $cmd .= $stroke . " RG\n0.6 w\n{$x} {$y} {$w} {$h} re S\n"; }
    return $cmd;
}

function cari_pdf_line(float $x1, float $y1, float $x2, float $y2, string $stroke = '0.82 0.86 0.92', float $width = 0.6): string
{
    return $stroke . " RG\n{$width} w\n{$x1} {$y1} m {$x2} {$y2} l S\n";
}

function cari_pdf_label(float $x, float $y, string $text, string $font = 'F1', int $size = 9, string $rgb = '0.29 0.38 0.52'): string
{
    return "BT\n/{$font} {$size} Tf\n{$rgb} rg\n{$x} {$y} Td\n" . cari_pdf_text($text) . " Tj\nET\n";
}

function cari_pdf_build(array $pageContents): string
{
    $objects = [];
    $pageRefs = [];
    $catalogObj = 1;
    $pagesObj = 2;
    $fontObj = 3;
    $fontBoldObj = 4;
    $nextObj = 5;
    foreach ($pageContents as $content) {
        $contentObj = $nextObj++;
        $pageObj = $nextObj++;
        $objects[$contentObj] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $objects[$pageObj] = "<< /Type /Page /Parent {$pagesObj} 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObj} 0 R /F2 {$fontBoldObj} 0 R >> >> /Contents {$contentObj} 0 R >>";
        $pageRefs[] = "{$pageObj} 0 R";
    }
    $objects[$catalogObj] = "<< /Type /Catalog /Pages {$pagesObj} 0 R >>";
    $objects[$pagesObj] = "<< /Type /Pages /Kids [" . implode(' ', $pageRefs) . "] /Count " . count($pageRefs) . " >>";
    $objects[$fontObj] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[$fontBoldObj] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
    ksort($objects);
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) { $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0); }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogObj} 0 R >>\nstartxref\n{$xref}\n%%EOF";
    return $pdf;
}

function cari_pdf_base(string $docTitle, string $period, string $sender, string $contact, int $page, int $pages): string
{
    $c = "0.94 0.97 1 rg\n0 0 595 842 re f\n";
    $c .= cari_pdf_rect(34, 32, 527, 778, '1 1 1', '0.85 0.89 0.94');
    $c .= cari_pdf_label(54, 778, $sender, 'F2', 22, '0.02 0.08 0.18');
    $c .= cari_pdf_label(54, 756, 'CARI MUTABAKAT BELGESI', 'F2', 10, '0.02 0.48 0.42');
    $c .= cari_pdf_label(54, 738, $contact, 'F1', 10, '0.30 0.39 0.52');
    $c .= cari_pdf_label(382, 778, $docTitle, 'F2', 20, '0.02 0.08 0.18');
    $c .= cari_pdf_label(430, 756, $period, 'F1', 11, '0.30 0.39 0.52');
    $c .= cari_pdf_label(492, 738, 'Sayfa ' . $page . '/' . $pages, 'F1', 9, '0.30 0.39 0.52');
    $c .= cari_pdf_line(54, 718, 541, 718, '0.07 0.09 0.16', 2);
    return $c;
}

function cari_pdf_info_box(float $x, float $y, float $w, string $label, string $value, string $sub = ''): string
{
    $c = cari_pdf_rect($x, $y, $w, 56, '0.98 0.99 1', '0.84 0.88 0.94');
    $c .= cari_pdf_label($x + 12, $y + 38, strtoupper($label), 'F1', 8, '0.30 0.39 0.52');
    $c .= cari_pdf_label($x + 12, $y + 20, cari_pdf_fit($value, 31), 'F2', 12, '0.02 0.08 0.18');
    if ($sub !== '') { $c .= cari_pdf_label($x + 12, $y + 8, cari_pdf_fit($sub, 35), 'F1', 8, '0.30 0.39 0.52'); }
    return $c;
}

function cari_pdf_summary_box(float $x, float $y, float $w, string $label, string $value, string $fill): string
{
    $c = cari_pdf_rect($x, $y, $w, 64, $fill);
    $c .= cari_pdf_label($x + 12, $y + 42, $label, 'F2', 10, '1 1 1');
    $c .= cari_pdf_label($x + 12, $y + 18, cari_pdf_fit($value, 17), 'F2', 12, '1 1 1');
    return $c;
}

function cari_statement_pdf(array $customer, array $rows, ?string $from, ?string $to, array $user): string
{
    [$debit, $credit, $balance] = cari_statement_totals($rows);
    $senderName = cari_user_name($user);
    $senderContact = cari_user_contact($user);
    $period = cari_doc_period($from, $to);
    $chunks = array_chunk($rows, 16);
    if (!$chunks) { $chunks = [[]]; }
    $pages = count($chunks);
    $running = 0.0;
    $pageContents = [];
    foreach ($chunks as $pageIndex => $chunk) {
        $c = cari_pdf_base('Cari Hesap Ekstresi', $period, $senderName, $senderContact, $pageIndex + 1, $pages);
        if ($pageIndex === 0) {
            $c .= cari_pdf_info_box(54, 644, 155, 'Cari unvani', (string)$customer['name']);
            $c .= cari_pdf_info_box(220, 644, 155, 'E-posta / Telefon', ($customer['email'] ?: '-') . ' / ' . ($customer['phone'] ?: '-'));
            $c .= cari_pdf_info_box(386, 644, 155, 'Hazirlayan', $senderName, $senderContact);
            $netText = cari_pdf_money(abs($balance)) . ' ' . ($balance >= 0 ? 'Alacak' : 'Borc');
            $c .= cari_pdf_summary_box(54, 560, 155, 'Toplam Borc', cari_pdf_money($debit), '0.15 0.38 0.92');
            $c .= cari_pdf_summary_box(220, 560, 155, 'Toplam Alacak / Odeme', cari_pdf_money($credit), '0.05 0.48 0.42');
            $c .= cari_pdf_summary_box(386, 560, 155, 'Net Durum', $netText, '0.07 0.10 0.17');
            $tableY = 522;
        } else {
            $tableY = 674;
        }
        $c .= cari_pdf_label(54, $tableY + 18, 'Olusturma zamani: ' . date('d.m.Y H:i') . '   Donem: ' . $period, 'F1', 9, '0.30 0.39 0.52');
        $c .= cari_pdf_rect(54, $tableY - 8, 487, 24, '0.95 0.97 0.99');
        $c .= cari_pdf_label(60, $tableY, 'Tarih', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(118, $tableY, 'Islem / Aciklama', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(292, $tableY, 'Vade', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(346, $tableY, 'Borc', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(406, $tableY, 'Alacak', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(466, $tableY, 'Ara Bakiye', 'F2', 8, '0.20 0.27 0.36');
        $y = $tableY - 28;
        foreach ($chunk as $row) {
            $amount = (float)$row['amount'];
            $running += $row['direction'] === 'debit' ? $amount : -$amount;
            $c .= cari_pdf_line(54, $y + 16, 541, $y + 16, '0.89 0.92 0.96', 0.5);
            $c .= cari_pdf_label(60, $y, tr_date($row['tx_date']), 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(118, $y, cari_pdf_fit((string)$row['title'], 31), 'F2', 8, '0.02 0.08 0.18');
            if (!empty($row['note'])) { $c .= cari_pdf_label(118, $y - 11, cari_pdf_fit((string)$row['note'], 36), 'F1', 7, '0.39 0.45 0.55'); }
            $c .= cari_pdf_label(292, $y, !empty($row['due_date']) ? tr_date($row['due_date']) : '-', 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(346, $y, $row['direction'] === 'debit' ? cari_pdf_fit(cari_pdf_money($amount), 11) : '-', 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(406, $y, $row['direction'] === 'credit' ? cari_pdf_fit(cari_pdf_money($amount), 11) : '-', 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(466, $y, cari_pdf_fit(cari_pdf_money(abs($running)) . ' ' . ($running >= 0 ? 'Alacak' : 'Borc'), 15), 'F1', 8, '0.02 0.08 0.18');
            $y -= 30;
        }
        if (!$chunk) { $c .= cari_pdf_label(60, $y, 'Bu donem icin hareket yok.', 'F1', 10, '0.39 0.45 0.55'); }
        $c .= cari_pdf_line(54, 86, 541, 86, '0.89 0.92 0.96', 0.6);
        $c .= cari_pdf_label(54, 66, 'Bu ekstre bilgilendirme ve mutabakat amaciyla olusturulmustur.', 'F1', 9, '0.30 0.39 0.52');
        $pageContents[] = $c;
    }
    return cari_pdf_build($pageContents);
}

function cari_report_pdf(array $rows, ?string $from, ?string $to, array $user, ?array $reportCustomer = null): string
{
    $debit = 0.0; $credit = 0.0;
    foreach ($rows as $row) { $debit += (float)$row['debit_total']; $credit += (float)$row['credit_total']; }
    $net = $debit - $credit;
    $senderName = cari_user_name($user);
    $senderContact = cari_user_contact($user);
    $period = cari_doc_period($from, $to);
    $title = $reportCustomer ? 'Musteri Cari Raporu' : 'Cari Raporu';
    $chunks = array_chunk($rows, 18);
    if (!$chunks) { $chunks = [[]]; }
    $pages = count($chunks);
    $pageContents = [];
    foreach ($chunks as $pageIndex => $chunk) {
        $c = cari_pdf_base($title, $period, $senderName, $senderContact, $pageIndex + 1, $pages);
        if ($pageIndex === 0) {
            $c .= cari_pdf_info_box(54, 644, 155, 'Raporu hazirlayan', $senderName, $senderContact);
            $c .= cari_pdf_info_box(220, 644, 155, 'Rapor kapsami', (string)($reportCustomer['name'] ?? (count($rows) . ' cari')));
            $c .= cari_pdf_info_box(386, 644, 155, 'Olusturma zamani', date('d.m.Y H:i'));
            $c .= cari_pdf_summary_box(54, 560, 155, 'Toplam Borc', cari_pdf_money($debit), '0.15 0.38 0.92');
            $c .= cari_pdf_summary_box(220, 560, 155, 'Toplam Alacak', cari_pdf_money($credit), '0.05 0.48 0.42');
            $c .= cari_pdf_summary_box(386, 560, 155, 'Net Portfoy', cari_pdf_money(abs($net)) . ' ' . ($net >= 0 ? 'Alacak' : 'Borc'), '0.07 0.10 0.17');
            $tableY = 522;
        } else { $tableY = 674; }
        $c .= cari_pdf_rect(54, $tableY - 8, 487, 24, '0.95 0.97 0.99');
        $c .= cari_pdf_label(60, $tableY, 'Cari', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(198, $tableY, 'Iletisim', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(304, $tableY, 'Borc', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(362, $tableY, 'Alacak', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(420, $tableY, 'Net Bakiye', 'F2', 8, '0.20 0.27 0.36');
        $c .= cari_pdf_label(512, $tableY, 'Son', 'F2', 8, '0.20 0.27 0.36');
        $y = $tableY - 28;
        foreach ($chunk as $row) {
            $balance = (float)$row['balance'];
            $c .= cari_pdf_line(54, $y + 16, 541, $y + 16, '0.89 0.92 0.96', 0.5);
            $c .= cari_pdf_label(60, $y, cari_pdf_fit((string)$row['name'], 22), 'F2', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(198, $y, cari_pdf_fit(($row['phone'] ?: '-') . ' / ' . ($row['email'] ?: '-'), 18), 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(304, $y, cari_pdf_fit(cari_pdf_money($row['debit_total']), 11), 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(362, $y, cari_pdf_fit(cari_pdf_money($row['credit_total']), 11), 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(420, $y, cari_pdf_fit(cari_pdf_money(abs($balance)) . ' ' . ($balance >= 0 ? 'Alacak' : 'Borc'), 13), 'F1', 8, '0.02 0.08 0.18');
            $c .= cari_pdf_label(512, $y, $row['last_tx_date'] ? cari_pdf_fit(tr_date($row['last_tx_date']), 11) : '-', 'F1', 8, '0.02 0.08 0.18');
            $y -= 28;
        }
        if (!$chunk) { $c .= cari_pdf_label(60, $y, 'Raporlanacak cari bulunamadi.', 'F1', 10, '0.39 0.45 0.55'); }
        $c .= cari_pdf_line(54, 86, 541, 86, '0.89 0.92 0.96', 0.6);
        $c .= cari_pdf_label(54, 66, 'Cari risk, tahsilat ve odeme takibi icin hazirlanmistir.', 'F1', 9, '0.30 0.39 0.52');
        $pageContents[] = $c;
    }
    return cari_pdf_build($pageContents);
}

$reportFrom = isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['from']) ? (string)$_GET['from'] : null;
$reportTo = isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['to']) ? (string)$_GET['to'] : null;
$reportCustomerId = intval_safe($_GET['customer_id'] ?? 0, 0);
$reportCustomer = $reportCustomerId ? cari_customer_or_fail($uid, $reportCustomerId) : null;
$statementFrom = isset($_GET['statement_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['statement_from']) ? (string)$_GET['statement_from'] : null;
$statementTo = isset($_GET['statement_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['statement_to']) ? (string)$_GET['statement_to'] : null;
if ($reportFrom && $reportTo && $reportFrom > $reportTo) { [$reportFrom, $reportTo] = [$reportTo, $reportFrom]; }
if ($statementFrom && $statementTo && $statementFrom > $statementTo) { [$statementFrom, $statementTo] = [$statementTo, $statementFrom]; }
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = cari_report_rows($uid, $reportFrom, $reportTo, $reportCustomerId ?: null);
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cari-rapor-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Cari', 'Tür', 'Telefon', 'E-posta', 'Borç', 'Alacak', 'Bakiye', 'Durum', 'Son Hareket'], ';');
    foreach ($rows as $r) {
        $balance = (float)$r['balance'];
        fputcsv($out, [
            $r['name'],
            $r['type'],
            $r['phone'],
            $r['email'],
            number_format((float)$r['debit_total'], 2, ',', '.'),
            number_format((float)$r['credit_total'], 2, ',', '.'),
            number_format(abs($balance), 2, ',', '.'),
            $balance >= 0 ? 'Alacak' : 'Borç',
            $r['last_tx_date'] ?: '',
        ], ';');
    }
    fclose($out);
    exit;
}

if (isset($_GET['statement']) && $_GET['statement'] === 'print') {
    $customerId = intval_safe($_GET['id'] ?? 0, 1);
    $customer = cari_customer_or_fail($uid, $customerId);
    $rows = cari_statement_rows($uid, $customerId, $statementFrom, $statementTo);
    $html = cari_statement_html($customer, $rows, $statementFrom, $statementTo, $user);
    cari_document_shell('Cari Ekstre - ' . $customer['name'], $html, '/customers.php?id=' . (int)$customer['id']);
}

if (isset($_GET['report']) && $_GET['report'] === 'print') {
    $rows = cari_report_rows($uid, $reportFrom, $reportTo, $reportCustomerId ?: null);
    $html = cari_report_html($rows, $reportFrom, $reportTo, $user, $reportCustomer);
    cari_document_shell($reportCustomer ? 'Müşteri Cari Raporu' : 'Cari Raporu', $html, '/customers.php#cari-rapor');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_customer') {
        $name = s($_POST['name'] ?? '', 160);
        $type = (string)($_POST['type'] ?? 'customer');
        if (!in_array($type, ['customer','supplier','both'], true)) { $type = 'customer'; }
        if ($name === '') {
            flash('danger', 'Cari adi zorunludur.');
            redirect('/customers.php?new=1');
        }

        $id = db_insert(
            'INSERT INTO ' . t('customers') . '
                (user_id,type,name,phone,email,tax_no,address,note,created_at)
             VALUES (:u,:ty,:n,:p,:e,:tax,:a,:nt,NOW())',
            [
                ':u' => $uid,
                ':ty' => $type,
                ':n' => $name,
                ':p' => s($_POST['phone'] ?? '', 40) ?: null,
                ':e' => s($_POST['email'] ?? '', 160) ?: null,
                ':tax' => s($_POST['tax_no'] ?? '', 40) ?: null,
                ':a' => s($_POST['address'] ?? '', 500) ?: null,
                ':nt' => s($_POST['note'] ?? '', 500) ?: null,
            ]
        );
        audit('customer.create', $uid, null, "id={$id}");
        flash('success', 'Cari karti olusturuldu.');
        cari_redirect($id);
    }

    if ($action === 'update_customer') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $name = s($_POST['name'] ?? '', 160);
        $type = (string)($_POST['type'] ?? 'customer');
        if (!in_array($type, ['customer','supplier','both'], true)) { $type = 'customer'; }
        if ($name === '') {
            flash('danger', 'Cari adi zorunludur.');
            cari_redirect($id);
        }

        db_exec(
            'UPDATE ' . t('customers') . '
                SET type=:ty, name=:n, phone=:p, email=:e, tax_no=:tax, address=:a, note=:nt
              WHERE id=:id AND user_id=:u',
            [
                ':ty' => $type,
                ':n' => $name,
                ':p' => s($_POST['phone'] ?? '', 40) ?: null,
                ':e' => s($_POST['email'] ?? '', 160) ?: null,
                ':tax' => s($_POST['tax_no'] ?? '', 40) ?: null,
                ':a' => s($_POST['address'] ?? '', 500) ?: null,
                ':nt' => s($_POST['note'] ?? '', 500) ?: null,
                ':id' => $id,
                ':u' => $uid,
            ]
        );
        audit('customer.update', $uid, null, "id={$id}");
        flash('success', 'Cari bilgileri guncellendi.');
        cari_redirect($id);
    }

    if ($action === 'add_movement') {
        $customerId = intval_safe($_POST['customer_id'] ?? 0, 1);
        $customer = db_one('SELECT id FROM ' . t('customers') . ' WHERE id=:id AND user_id=:u AND is_active=1',
                           [':id' => $customerId, ':u' => $uid]);
        if (!$customer) {
            flash('danger', 'Cari bulunamadı.');
            cari_redirect();
        }

        $direction = (string)($_POST['direction'] ?? 'debit');
        if (!in_array($direction, ['debit','credit'], true)) { $direction = 'debit'; }
        $amount = money_in($_POST['amount'] ?? 0);
        $title = s($_POST['title'] ?? '', 160);
        $date = s($_POST['tx_date'] ?? '', 10) ?: date('Y-m-d');
        $dueDate = s($_POST['due_date'] ?? '', 10);

        if ($amount <= 0 || $title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            flash('danger', 'Hareket başlığı, pozitif tutar ve vade tarihi zorunludur.');
            cari_redirect($customerId);
        }

        $mid = db_insert(
            'INSERT INTO ' . t('customer_movements') . '
                (user_id,customer_id,direction,amount,tx_date,due_date,title,note,created_at)
             VALUES (:u,:c,:d,:a,:dt,:du,:t,:n,NOW())',
            [
                ':u' => $uid,
                ':c' => $customerId,
                ':d' => $direction,
                ':a' => $amount,
                ':dt' => $date,
                ':du' => $dueDate,
                ':t' => $title,
                ':n' => s($_POST['note'] ?? '', 500) ?: null,
            ]
        );
        audit('customer.movement.create', $uid, null, "id={$mid} customer={$customerId}");
        flash('success', 'Cari hareket işlendi.');
        cari_redirect($customerId);
    }

    if ($action === 'delete_movement') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $customerId = intval_safe($_POST['customer_id'] ?? 0, 1);
        db_exec('DELETE FROM ' . t('customer_movements') . ' WHERE id=:id AND user_id=:u AND customer_id=:c',
                [':id' => $id, ':u' => $uid, ':c' => $customerId]);
        audit('customer.movement.delete', $uid, null, "id={$id} customer={$customerId}");
        flash('success', 'Cari hareket silindi.');
        cari_redirect($customerId);
    }

    if ($action === 'update_movement') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        $customerId = intval_safe($_POST['customer_id'] ?? 0, 1);
        $direction = (string)($_POST['direction'] ?? 'debit');
        if (!in_array($direction, ['debit','credit'], true)) { $direction = 'debit'; }
        $amount = money_in($_POST['amount'] ?? 0);
        $title = s($_POST['title'] ?? '', 160);
        $date = s($_POST['tx_date'] ?? '', 10) ?: date('Y-m-d');
        $dueDate = s($_POST['due_date'] ?? '', 10);
        if ($amount <= 0 || $title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            flash('danger', 'Hareket başlığı, pozitif tutar ve vade tarihi zorunludur.');
            cari_redirect($customerId);
        }
        db_exec(
            'UPDATE ' . t('customer_movements') . '
                SET direction=:d, amount=:a, tx_date=:dt, due_date=:du, title=:t, note=:n, reminder_sent_at=NULL
              WHERE id=:id AND user_id=:u AND customer_id=:c',
            [
                ':d' => $direction,
                ':a' => $amount,
                ':dt' => $date,
                ':du' => $dueDate,
                ':t' => $title,
                ':n' => s($_POST['note'] ?? '', 500) ?: null,
                ':id' => $id,
                ':u' => $uid,
                ':c' => $customerId,
            ]
        );
        audit('customer.movement.update', $uid, null, "id={$id} customer={$customerId}");
        flash('success', 'Cari hareket güncellendi.');
        cari_redirect($customerId);
    }

    if ($action === 'send_statement_email') {
        $customerId = intval_safe($_POST['customer_id'] ?? 0, 1);
        $from = isset($_POST['statement_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_POST['statement_from']) ? (string)$_POST['statement_from'] : null;
        $to = isset($_POST['statement_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_POST['statement_to']) ? (string)$_POST['statement_to'] : null;
        if ($from && $to && $from > $to) { [$from, $to] = [$to, $from]; }
        $customer = cari_customer_or_fail($uid, $customerId);
        $email = trim((string)($customer['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Bu caride geçerli bir e-posta adresi yok.');
            cari_redirect($customerId);
        }

        try {
            $rows = cari_statement_rows($uid, $customerId, $from, $to);
            $html = cari_statement_html($customer, $rows, $from, $to, $user);
            $pdf = cari_statement_pdf($customer, $rows, $from, $to, $user);
            $subject = 'Cari Hesap Ekstresi - ' . $customer['name'];
            cf_send_mail(
                $email,
                $subject,
                $html,
                'Cari hesap ekstreniz PDF olarak ektedir. Detaylar HTML mesaj içeriğinde de yer alır.',
                [[
                    'filename' => 'cari-hesap-ekstresi-' . date('Ymd-His') . '.pdf',
                    'content_type' => 'application/pdf',
                    'content' => $pdf,
                ]]
            );
            audit('customer.statement.email', $uid, null, "customer={$customerId} email={$email}");
            flash('success', 'Cari hesap ekstresi PDF olarak ' . $email . ' adresine gönderildi.');
        } catch (Throwable $e) {
            flash('danger', 'Mail gönderilemedi: ' . $e->getMessage());
        }
        cari_redirect($customerId);
    }

    if ($action === 'send_report_email') {
        $from = isset($_POST['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_POST['from']) ? (string)$_POST['from'] : null;
        $to = isset($_POST['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_POST['to']) ? (string)$_POST['to'] : null;
        $customerId = intval_safe($_POST['customer_id'] ?? 0, 0);
        $customer = $customerId ? cari_customer_or_fail($uid, $customerId) : null;
        if ($from && $to && $from > $to) { [$from, $to] = [$to, $from]; }
        $email = s($_POST['report_email'] ?? ($user['email'] ?? ''), 160);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Cari raporu için geçerli bir alıcı e-posta adresi yazın.');
            redirect('/customers.php#cari-rapor');
        }
        try {
            $rows = cari_report_rows($uid, $from, $to, $customerId ?: null);
            $html = cari_report_html($rows, $from, $to, $user, $customer);
            $pdf = cari_report_pdf($rows, $from, $to, $user, $customer);
            cf_send_mail(
                $email,
                ($customer ? 'Müşteri Cari Raporu - ' . $customer['name'] : 'Cari Raporu') . ' - ' . cari_doc_period($from, $to),
                $html,
                'Cari raporunuz PDF olarak ektedir. Detaylar HTML mesaj içeriğinde de yer alır.',
                [[
                    'filename' => 'cari-raporu-' . date('Ymd-His') . '.pdf',
                    'content_type' => 'application/pdf',
                    'content' => $pdf,
                ]]
            );
            audit('customer.report.email', $uid, null, 'email=' . $email);
            flash('success', 'Cari raporu PDF olarak ' . $email . ' adresine gönderildi.');
        } catch (Throwable $e) {
            flash('danger', 'Cari raporu gönderilemedi: ' . $e->getMessage());
        }
        redirect('/customers.php#cari-rapor');
    }

    if ($action === 'archive_customer') {
        $id = intval_safe($_POST['id'] ?? 0, 1);
        db_exec('UPDATE ' . t('customers') . ' SET is_active=0 WHERE id=:id AND user_id=:u',
                [':id' => $id, ':u' => $uid]);
        audit('customer.archive', $uid, null, "id={$id}");
        flash('success', 'Cari arsive alindi.');
        cari_redirect();
    }
}

$customerSearch = s($_GET['q'] ?? '', 120);
$customerWhere = 'WHERE c.user_id = :u AND c.is_active = 1';
$customerParams = [':u' => $uid, ':mu' => $uid];
if ($customerSearch !== '') {
    $customerWhere .= ' AND (c.name LIKE :q OR c.phone LIKE :q OR c.email LIKE :q OR c.tax_no LIKE :q)';
    $customerParams[':q'] = '%' . $customerSearch . '%';
}
$customerSearchQuery = $customerSearch !== '' ? '&q=' . rawurlencode($customerSearch) : '';

$customers = db_all(
    'SELECT c.*,
            COALESCE(ms.debit_total,0) AS debit_total,
            COALESCE(ms.credit_total,0) AS credit_total,
            COALESCE(ms.balance,0) AS balance
       FROM ' . t('customers') . ' c
       LEFT JOIN (
            SELECT customer_id,
                   SUM(IF(direction="debit", amount, 0)) AS debit_total,
                   SUM(IF(direction="credit", amount, 0)) AS credit_total,
                   SUM(IF(direction="debit", amount, -amount)) AS balance
              FROM ' . t('customer_movements') . '
             WHERE user_id = :mu
             GROUP BY customer_id
       ) ms ON ms.customer_id = c.id
      ' . $customerWhere . '
      ORDER BY c.name',
    $customerParams
);

$selectedId = isset($_GET['id']) ? intval_safe($_GET['id'], 1) : 0;

$selected = null;
foreach ($customers as $c) {
    if ((int)$c['id'] === $selectedId) { $selected = $c; break; }
}

$movements = [];
$editMovementId = isset($_GET['edit_movement']) ? intval_safe($_GET['edit_movement'], 1) : 0;
$editMovement = null;
if ($selected) {
    $movements = db_all(
        'SELECT * FROM ' . t('customer_movements') . '
          WHERE user_id=:u AND customer_id=:c
          ORDER BY tx_date DESC, id DESC',
        [':u' => $uid, ':c' => (int)$selected['id']]
    );
    foreach ($movements as $movement) {
        if ((int)$movement['id'] === $editMovementId) { $editMovement = $movement; break; }
    }
}

$totalDebit = 0.0; $totalCredit = 0.0;
foreach ($customers as $c) {
    $totalDebit += (float)$c['debit_total'];
    $totalCredit += (float)$c['credit_total'];
}
$netBalance = $totalDebit - $totalCredit;

$pageTitle = 'Cariler';
$pageHeader = 'Cari Hesaplar';
require __DIR__ . '/../inc/header.php';
?>

<div class="cf-cari-page">
<div class="cf-grid cf-grid-3 cf-cari-summary">
    <div class="cf-stat balance">
        <div class="label">Toplam Cari</div>
        <div class="value"><?= count($customers) ?></div>
        <div class="sub">Aktif müşteri / tedarikçi kartı</div>
    </div>
    <div class="cf-stat income">
        <div class="label">Toplam Borçlandırma</div>
        <div class="value"><?= money($totalDebit) ?></div>
        <div class="sub">Carilerin size borçlandığı tutar</div>
    </div>
    <div class="cf-stat <?= $netBalance >= 0 ? 'gold' : 'expense' ?>">
        <div class="label">Net Cari Bakiye</div>
        <div class="value"><?= money(abs($netBalance)) ?></div>
        <div class="sub"><?= $netBalance >= 0 ? 'Tahsil edilecek net bakiye' : 'Ödenecek net bakiye' ?></div>
    </div>
</div>

<div class="cf-page-head cf-cari-head">
    <h2>Cariler</h2>
    <div class="actions">
        <a href="/reports.php" class="btn btn-ghost">Raporlar</a>
        <a href="/customers.php?new=1<?= $customerSearchQuery ? '&q=' . e(rawurlencode($customerSearch)) : '' ?>" class="btn btn-primary">+ Yeni Cari</a>
    </div>
</div>

<?php if (isset($_GET['new'])): ?>
<div class="cf-card" style="margin-bottom:18px;">
    <h3>Yeni Cari Kartı</h3>
    <form method="post" class="cf-form" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_customer">
        <div class="row">
            <div>
                <label>Cari Adı</label>
                <input type="text" name="name" required maxlength="160" placeholder="Örn: ABC Ltd. Şti.">
            </div>
            <div>
                <label>Tür</label>
                <select name="type">
                    <option value="customer">Müşteri</option>
                    <option value="supplier">Tedarikçi</option>
                    <option value="both">Müşteri + Tedarikçi</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div><label>Telefon</label><input type="text" name="phone" maxlength="40"></div>
            <div><label>E-posta</label><input type="email" name="email" maxlength="160"></div>
        </div>
        <div class="row">
            <div><label>Vergi / TCKN No</label><input type="text" name="tax_no" maxlength="40"></div>
            <div><label>Not</label><input type="text" name="note" maxlength="500"></div>
        </div>
        <div>
            <label>Adres</label>
            <input type="text" name="address" maxlength="500">
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button class="btn btn-primary">Cari Oluştur</button>
            <a class="btn btn-ghost" href="/customers.php">Vazgeç</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (!$customers): ?>
    <div class="cf-card cf-empty">
        <div class="icon">□</div>
        <?= $customerSearch !== '' ? 'Arama sonucu bulunamadı. Farklı bir ad, telefon veya e-posta deneyin.' : 'Henüz cari kartı yok. İlk müşteriyi veya tedarikçiyi ekleyin.' ?>
        <form method="get" action="/customers.php" class="cf-cari-search cf-cari-search-empty">
            <input type="search" name="q" value="<?= e($customerSearch) ?>" maxlength="120" placeholder="Cari adı, telefon, e-posta veya vergi no ara">
            <button class="btn btn-primary btn-sm" title="Ara" aria-label="Ara">Ara</button>
            <?php if ($customerSearch !== ''): ?><a class="btn btn-ghost btn-sm" href="/customers.php" title="Temizle" aria-label="Temizle">Temizle</a><?php endif; ?>
        </form>
    </div>
<?php else: ?>
<div class="cf-grid cf-cari-layout">
    <div class="cf-card cf-cari-list" style="padding:0;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;">
            <h3 style="margin:0;">Cari Listesi</h3>
            <div class="muted">Kartı açın, hareket ekleyin veya ekstre gönderin.</div>
        </div>
        <form method="get" action="/customers.php" class="cf-cari-search">
            <input type="search" name="q" value="<?= e($customerSearch) ?>" maxlength="120" placeholder="Cari adı, telefon, e-posta veya vergi no ara">
            <button class="btn btn-primary btn-sm">Ara</button>
            <?php if ($customerSearch !== ''): ?><a class="btn btn-ghost btn-sm" href="/customers.php">Temizle</a><?php endif; ?>
        </form>
        <div style="display:grid;">
            <?php foreach ($customers as $c):
                $bal = (float)$c['balance'];
                $active = $selected && (int)$selected['id'] === (int)$c['id'];
            ?>
                <a class="<?= $active ? 'active' : '' ?>" href="/customers.php?id=<?= (int)$c['id'] ?><?= e($customerSearchQuery) ?>" style="display:flex;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:1px solid #eef0f4;background:<?= $active ? '#f8fafc' : '#fff' ?>;">
                    <span>
                        <strong style="display:block;color:var(--cf-text);"><?= e($c['name']) ?></strong>
                        <small style="color:var(--cf-text-soft);"><?= e($c['type']) ?></small>
                    </span>
                    <span style="text-align:right;">
                        <strong style="color:<?= $bal >= 0 ? '#047857' : '#b91c1c' ?>;"><?= money(abs($bal)) ?></strong>
                        <small style="display:block;color:var(--cf-muted);"><?= $bal >= 0 ? 'Alacak' : 'Borç' ?></small>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!$selected): ?>
    <div class="cf-card cf-empty cf-cari-detail cf-cari-pick-note">
        Lütfen bir cari seçin.
    </div>
    <?php endif; ?>

    <?php if ($selected):
        $balance = (float)$selected['balance'];
    ?>
    <div class="cf-cari-detail" style="display:grid;gap:14px;">
        <div class="cf-card">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <h3 style="margin-bottom:4px;"><?= e($selected['name']) ?></h3>
                    <div style="font-size:13px;color:var(--cf-text-soft);">
                        <?= e($selected['phone'] ?: '-') ?> &middot; <?= e($selected['email'] ?: '-') ?>
                        <?php if ($selected['tax_no']): ?> &middot; Vergi/TCKN: <?= e($selected['tax_no']) ?><?php endif; ?>
                    </div>
                    <?php if ($selected['address']): ?><div style="font-size:12px;color:var(--cf-muted);margin-top:4px;"><?= e($selected['address']) ?></div><?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:13px;color:var(--cf-text-soft);">Cari Bakiye</div>
                    <div style="font-size:26px;font-weight:800;color:<?= $balance >= 0 ? '#047857' : '#b91c1c' ?>;">
                        <?= money(abs($balance)) ?>
                    </div>
                    <span class="cf-pill <?= $balance >= 0 ? 'success' : 'danger' ?>"><?= $balance >= 0 ? 'Alacaklısınız' : 'Borçlusunuz' ?></span>
                </div>
            </div>
        </div>

        <div class="cf-card cf-cari-action-card">
            <h3>Hızlı Borç / Alacak</h3>
            <form method="post" class="cf-form" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_movement">
                <input type="hidden" name="customer_id" value="<?= (int)$selected['id'] ?>">
                <div class="row">
                    <div>
                        <label>Tutar</label>
                        <input type="text" name="amount" data-money inputmode="decimal" required placeholder="0,00">
                    </div>
                    <div>
                        <label>Tarih</label>
                        <input type="date" name="tx_date" value="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                </div>
                <div>
                    <label>Vade Tarihi</label>
                    <input type="date" name="due_date" value="<?= e(date('Y-m-d')) ?>" required>
                </div>
                <div><label>Açıklama</label><input type="text" name="title" required maxlength="160" placeholder="Örn: Sefer ücreti, tahsilat, ödeme"></div>
                <input type="hidden" name="note" value="">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button class="btn btn-primary" name="direction" value="debit">Borç Ekle</button>
                    <button class="btn btn-success" name="direction" value="credit">Alacak / Ödeme Ekle</button>
                </div>
            </form>
        </div>

        <div class="cf-card cf-cari-statement-card">
            <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:12px;">
                <div>
                    <h3 style="margin-bottom:4px;">Cari Hesap Ekstresi</h3>
                    <div class="muted">Tarih aralığını seçin; müşteriye gönderilecek ekstre ve PDF aynı veriyi kullanır.</div>
                </div>
            </div>

            <div class="cf-statement-actions">
                <form method="get" action="/customers.php" target="_blank" class="cf-form cf-statement-form">
                    <input type="hidden" name="statement" value="print">
                    <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
                    <div class="row">
                        <div>
                            <label>Başlangıç</label>
                            <input type="date" name="statement_from">
                        </div>
                        <div>
                            <label>Bitiş</label>
                            <input type="date" name="statement_to">
                        </div>
                    </div>
                    <button class="btn btn-primary">PDF / Yazdır</button>
                </form>

                <form method="post" class="cf-form cf-statement-form" data-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send_statement_email">
                    <input type="hidden" name="customer_id" value="<?= (int)$selected['id'] ?>">
                    <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
                    <div class="row">
                        <div>
                            <label>Başlangıç</label>
                            <input type="date" name="statement_from">
                        </div>
                        <div>
                            <label>Bitiş</label>
                            <input type="date" name="statement_to">
                        </div>
                    </div>
                    <button class="btn btn-outline" <?= filter_var((string)($selected['email'] ?? ''), FILTER_VALIDATE_EMAIL) ? '' : 'disabled title="Bu caride e-posta adresi yok"' ?>>PDF Olarak Mail Gönder</button>
                    <div class="muted" style="font-size:12px;">
                        Alıcı: <?= e($selected['email'] ?: 'Bu caride e-posta adresi yok') ?>. Ekstre PDF ekiyle gönderilir.
                    </div>
                </form>
            </div>
        </div>

        <div class="cf-card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;">Cari Hareketleri</h3>
                <form method="post" onsubmit="return confirm('Bu cari arşive alınsın mı?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="archive_customer">
                    <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
                    <button class="btn btn-ghost btn-sm">Arşive Al</button>
                </form>
            </div>
            <?php if ($editMovement): ?>
                <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;background:#f8fafc;">
                    <h3 style="margin:0 0 10px;">Hareketi Düzenle</h3>
                    <form method="post" class="cf-form" data-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_movement">
                        <input type="hidden" name="customer_id" value="<?= (int)$selected['id'] ?>">
                        <input type="hidden" name="id" value="<?= (int)$editMovement['id'] ?>">
                        <div class="row">
                            <div><label>Tutar</label><input type="text" name="amount" data-money inputmode="decimal" required value="<?= e(number_format((float)$editMovement['amount'], 2, ',', '.')) ?>"></div>
                            <div><label>Tarih</label><input type="date" name="tx_date" value="<?= e($editMovement['tx_date']) ?>" required></div>
                        </div>
                        <div class="row">
                            <div><label>Vade Tarihi</label><input type="date" name="due_date" value="<?= e($editMovement['due_date'] ?: date('Y-m-d')) ?>" required></div>
                            <div>
                                <label>Tür</label>
                                <select name="direction">
                                    <option value="debit" <?= $editMovement['direction'] === 'debit' ? 'selected' : '' ?>>Borç</option>
                                    <option value="credit" <?= $editMovement['direction'] === 'credit' ? 'selected' : '' ?>>Alacak / Ödeme</option>
                                </select>
                            </div>
                        </div>
                        <div><label>Açıklama</label><input type="text" name="title" required maxlength="160" value="<?= e($editMovement['title']) ?>"></div>
                        <div><label>Not</label><input type="text" name="note" maxlength="500" value="<?= e($editMovement['note'] ?? '') ?>"></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button class="btn btn-primary">Güncelle</button>
                            <a class="btn btn-ghost" href="/customers.php?id=<?= (int)$selected['id'] ?>">Vazgeç</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            <?php if (!$movements): ?>
                <div class="cf-empty" style="padding:28px;">Bu cariye ait hareket yok.</div>
            <?php else: ?>
            <div class="cf-table-wrap">
                <table class="cf-table cf-mobile-cards" style="box-shadow:none;border:0;border-radius:0;">
                    <thead><tr><th>Tarih</th><th>Vade</th><th>Başlık</th><th>Tür</th><th class="amount">Tutar</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td data-label="Tarih"><?= tr_date($m['tx_date']) ?></td>
                            <td data-label="Vade"><?= !empty($m['due_date']) ? tr_date($m['due_date']) : '-' ?><?php if (!empty($m['reminder_sent_at'])): ?><div style="font-size:12px;color:var(--cf-muted);">Mail gönderildi</div><?php endif; ?></td>
                            <td data-label="İşlem"><strong><?= e($m['title']) ?></strong><?php if ($m['note']): ?><div style="font-size:12px;color:var(--cf-muted);"><?= e($m['note']) ?></div><?php endif; ?></td>
                            <td data-label="Tür"><span class="cf-pill <?= $m['direction'] === 'debit' ? 'income' : 'expense' ?>"><?= $m['direction'] === 'debit' ? 'Borç' : 'Alacak' ?></span></td>
                            <td data-label="Tutar" class="amount <?= $m['direction'] === 'debit' ? 'income' : 'expense' ?>"><?= money($m['amount']) ?></td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
                                <a class="btn btn-ghost btn-sm" href="/customers.php?id=<?= (int)$selected['id'] ?>&edit_movement=<?= (int)$m['id'] ?>">Düzenle</a>
                                <form method="post" onsubmit="return confirm('Hareket silinsin mi?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_movement">
                                    <input type="hidden" name="customer_id" value="<?= (int)$selected['id'] ?>">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <button class="btn btn-ghost btn-sm">Sil</button>
                                </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
