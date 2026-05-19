<?php
/**
 * CODEGA Finans - Raporlar
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
    ob_start();
    ?>
    <style><?= cari_document_styles() ?></style>
    <div>
        <div class="doc-head">
            <div>
                <div class="brand"><?= e($senderName) ?></div>
                <div class="doc-kicker">Cari mutabakat belgesi</div>
                <div class="muted"><?= e($senderContact) ?></div>
            </div>
            <div class="doc-title">
                <h1>Cari Hesap Ekstresi</h1>
                <div class="muted"><?= e($period) ?></div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info"><small>Cari Ünvanı</small><strong><?= e($customer['name']) ?></strong></div>
            <div class="info"><small>E-posta / Telefon</small><strong><?= e(($customer['email'] ?: '-') . ' / ' . ($customer['phone'] ?: '-')) ?></strong></div>
            <div class="info"><small>Hazırlayan</small><strong><?= e($senderName) ?></strong><br><span class="muted"><?= e($senderContact) ?></span></div>
        </div>
        <div class="summary">
            <div class="sum debit"><small>Toplam Borç</small><strong><?= e(money($debit)) ?></strong></div>
            <div class="sum credit"><small>Toplam Alacak / Ödeme</small><strong><?= e(money($credit)) ?></strong></div>
            <div class="sum net"><small>Net Durum</small><strong><?= e(money(abs($balance))) ?> <?= $balance >= 0 ? 'Alacak' : 'Borç' ?></strong></div>
        </div>
        <div class="balance-note">Oluşturma zamanı: <?= e(date('d.m.Y H:i')) ?>. Dönem: <?= e($period) ?>.</div>
        <table>
            <thead>
            <tr>
                <th>Tarih</th><th>İşlem / Açıklama</th><th class="amount">Borç</th><th class="amount">Alacak</th><th class="amount">Ara Bakiye</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row):
                $amount = (float)$row['amount'];
                $running += $row['direction'] === 'debit' ? $amount : -$amount;
            ?>
                <tr>
                    <td><?= e(tr_date($row['tx_date'])) ?></td>
                    <td><strong><?= e($row['title']) ?></strong><?php if ($row['note']): ?><br><span class="muted"><?= e($row['note']) ?></span><?php endif; ?></td>
                    <td class="amount"><?= $row['direction'] === 'debit' ? e(money($amount)) : '-' ?></td>
                    <td class="amount"><?= $row['direction'] === 'credit' ? e(money($amount)) : '-' ?></td>
                    <td class="amount"><?= e(money(abs($running))) ?> <?= $running >= 0 ? 'Alacak' : 'Borç' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="5" class="muted">Bu dönem için hareket yok.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <div class="foot">Bu ekstre bilgilendirme ve mutabakat amacıyla oluşturulmuştur. Ödeme, tahsilat ve itiraz süreçleri için kendi kayıtlarınızla karşılaştırarak kontrol ediniz.</div>
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
    <style><?= cari_document_styles() ?></style>
    <div>
        <div class="doc-head">
            <div>
                <div class="brand"><?= e($senderName) ?></div>
                <div class="doc-kicker">Cari rapor belgesi</div>
                <div class="muted"><?= e($senderContact) ?></div>
            </div>
            <div class="doc-title">
                <h1><?= e($title) ?></h1>
                <div class="muted"><?= e($period) ?></div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info"><small>Raporu Hazırlayan</small><strong><?= e($senderName) ?></strong><br><span class="muted"><?= e($senderContact) ?></span></div>
            <div class="info"><small>Rapor Kapsamı</small><strong><?= e($reportCustomer['name'] ?? (count($rows) . ' cari')) ?></strong></div>
            <div class="info"><small>Oluşturma Zamanı</small><strong><?= e(date('d.m.Y H:i')) ?></strong></div>
        </div>
        <div class="summary">
            <div class="sum debit"><small>Toplam Borç</small><strong><?= e(money($debit)) ?></strong></div>
            <div class="sum credit"><small>Toplam Alacak / Ödeme</small><strong><?= e(money($credit)) ?></strong></div>
            <div class="sum net"><small>Net Portföy</small><strong><?= e(money(abs($net))) ?> <?= $net >= 0 ? 'Alacak' : 'Borç' ?></strong></div>
        </div>
        <table>
            <thead><tr><th>Cari</th><th>İletişim</th><th class="amount">Borç</th><th class="amount">Alacak</th><th class="amount">Net Bakiye</th><th>Son Hareket</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row):
                $balance = (float)$row['balance'];
            ?>
                <tr>
                    <td><strong><?= e($row['name']) ?></strong><br><span class="muted"><?= e($row['type']) ?></span></td>
                    <td><?= e($row['phone'] ?: '-') ?><br><span class="muted"><?= e($row['email'] ?: '-') ?></span></td>
                    <td class="amount"><?= e(money($row['debit_total'])) ?></td>
                    <td class="amount"><?= e(money($row['credit_total'])) ?></td>
                    <td class="amount"><?= e(money(abs($balance))) ?> <?= $balance >= 0 ? 'Alacak' : 'Borç' ?></td>
                    <td><?= $row['last_tx_date'] ? e(tr_date($row['last_tx_date'])) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="muted">Raporlanacak cari bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="foot">Nakliye operasyonlarında cari risk, tahsilat ve ödeme takibini hızlı okumak için hazırlanmıştır.</div>
    </div>
    <?php
    return (string)ob_get_clean();
}

function cari_pdf_utf16_hex(string $text): string
{
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);
    if (function_exists('iconv')) {
        $encoded = iconv('UTF-8', 'UTF-16BE//IGNORE', $text);
    } elseif (function_exists('mb_convert_encoding')) {
        $encoded = mb_convert_encoding($text, 'UTF-16BE', 'UTF-8');
    } else {
        $encoded = '';
    }
    if ($encoded === false || $encoded === '') {
        $encoded = '';
        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $code = ord($char[0]);
            $encoded .= chr(0) . chr($code < 128 ? $code : 63);
        }
    }
    return '<FEFF' . strtoupper(bin2hex($encoded)) . '>';
}

function cari_pdf_wrap(string $text, int $limit = 82): array
{
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        $candidate = $line === '' ? $word : $line . ' ' . $word;
        if (function_exists('mb_strlen') ? mb_strlen($candidate, 'UTF-8') > $limit : strlen($candidate) > $limit) {
            if ($line !== '') {
                $lines[] = $line;
            }
            $line = $word;
        } else {
            $line = $candidate;
        }
    }
    if ($line !== '') {
        $lines[] = $line;
    }
    return $lines ?: [''];
}

function cari_pdf_document(string $title, array $lines): string
{
    $objects = [];
    $pageRefs = [];
    $chunks = array_chunk($lines, 36);
    $pageCount = max(1, count($chunks));
    $catalogObj = 1;
    $pagesObj = 2;
    $fontObj = 3;
    $nextObj = 4;

    foreach ($chunks as $pageIndex => $chunk) {
        $content = "BT\n/F1 16 Tf\n48 800 Td\n" . cari_pdf_utf16_hex($title) . " Tj\n";
        $content .= "/F1 9 Tf\n0 -18 Td\n" . cari_pdf_utf16_hex('Oluşturma: ' . date('d.m.Y H:i') . ' | Sayfa ' . ($pageIndex + 1) . '/' . $pageCount) . " Tj\n";
        $content .= "/F1 10 Tf\n0 -22 Td\n";
        foreach ($chunk as $lineIndex => $line) {
            if ($lineIndex > 0) {
                $content .= "0 -15 Td\n";
            }
            $content .= cari_pdf_utf16_hex((string)$line) . " Tj\n";
        }
        $content .= "ET\n";
        $contentObj = $nextObj++;
        $pageObj = $nextObj++;
        $objects[$contentObj] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $objects[$pageObj] = "<< /Type /Page /Parent {$pagesObj} 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObj} 0 R >> >> /Contents {$contentObj} 0 R >>";
        $pageRefs[] = "{$pageObj} 0 R";
    }

    $objects[$catalogObj] = "<< /Type /Catalog /Pages {$pagesObj} 0 R >>";
    $objects[$pagesObj] = "<< /Type /Pages /Kids [" . implode(' ', $pageRefs) . "] /Count " . count($pageRefs) . " >>";
    $objects[$fontObj] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogObj} 0 R >>\nstartxref\n{$xref}\n%%EOF";
    return $pdf;
}

function cari_statement_pdf(array $customer, array $rows, ?string $from, ?string $to, array $user): string
{
    [$debit, $credit, $balance] = cari_statement_totals($rows);
    $senderName = cari_user_name($user);
    $senderContact = cari_user_contact($user);
    $lines = [
        $senderName . ' - Cari Hesap Ekstresi',
        'Gönderen iletişim: ' . $senderContact,
        'Dönem: ' . cari_doc_period($from, $to),
        'Cari: ' . (string)$customer['name'],
        'E-posta / Telefon: ' . (($customer['email'] ?: '-') . ' / ' . ($customer['phone'] ?: '-')),
        'Hazırlayan: ' . $senderName . ' / ' . $senderContact,
        'Toplam Borç: ' . money($debit) . ' | Toplam Alacak/Ödeme: ' . money($credit) . ' | Net: ' . money(abs($balance)) . ' ' . ($balance >= 0 ? 'Alacak' : 'Borç'),
        str_repeat('-', 92),
        'Tarih | İşlem / Açıklama | Borç | Alacak | Ara Bakiye',
    ];
    $running = 0.0;
    foreach ($rows as $row) {
        $amount = (float)$row['amount'];
        $running += $row['direction'] === 'debit' ? $amount : -$amount;
        $line = tr_date($row['tx_date']) . ' | ' . $row['title'];
        if (!empty($row['note'])) {
            $line .= ' - ' . $row['note'];
        }
        $line .= ' | ' . ($row['direction'] === 'debit' ? money($amount) : '-');
        $line .= ' | ' . ($row['direction'] === 'credit' ? money($amount) : '-');
        $line .= ' | ' . money(abs($running)) . ' ' . ($running >= 0 ? 'Alacak' : 'Borç');
        array_push($lines, ...cari_pdf_wrap($line));
    }
    if (!$rows) {
        $lines[] = 'Bu dönem için hareket yok.';
    }
    $lines[] = str_repeat('-', 92);
    $lines[] = 'Bu ekstre bilgilendirme ve mutabakat amacıyla oluşturulmuştur.';
    return cari_pdf_document('Cari Hesap Ekstresi', $lines);
}

function cari_report_pdf(array $rows, ?string $from, ?string $to, array $user, ?array $reportCustomer = null): string
{
    $debit = 0.0; $credit = 0.0;
    foreach ($rows as $row) {
        $debit += (float)$row['debit_total'];
        $credit += (float)$row['credit_total'];
    }
    $net = $debit - $credit;
    $senderName = cari_user_name($user);
    $senderContact = cari_user_contact($user);
    $title = $reportCustomer ? 'Müşteri Cari Raporu' : 'Cari Raporu';
    $lines = [
        $senderName . ' - ' . $title,
        'Gönderen iletişim: ' . $senderContact,
        'Dönem: ' . cari_doc_period($from, $to),
        'Hazırlayan: ' . $senderName . ' / ' . $senderContact,
        'Rapor Kapsamı: ' . (string)($reportCustomer['name'] ?? (count($rows) . ' cari')),
        'Toplam Borç: ' . money($debit) . ' | Toplam Alacak/Ödeme: ' . money($credit) . ' | Net Portföy: ' . money(abs($net)) . ' ' . ($net >= 0 ? 'Alacak' : 'Borç'),
        str_repeat('-', 92),
        'Cari | İletişim | Borç | Alacak | Net Bakiye | Son Hareket',
    ];
    foreach ($rows as $row) {
        $balance = (float)$row['balance'];
        $line = $row['name'] . ' | ' . ($row['phone'] ?: '-') . ' / ' . ($row['email'] ?: '-');
        $line .= ' | ' . money($row['debit_total']) . ' | ' . money($row['credit_total']);
        $line .= ' | ' . money(abs($balance)) . ' ' . ($balance >= 0 ? 'Alacak' : 'Borç');
        $line .= ' | ' . ($row['last_tx_date'] ? tr_date($row['last_tx_date']) : '-');
        array_push($lines, ...cari_pdf_wrap($line));
    }
    if (!$rows) {
        $lines[] = 'Raporlanacak cari bulunamadı.';
    }
    return cari_pdf_document($title, $lines);
}


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
      WHERE c.user_id = :u AND c.is_active = 1
      ORDER BY c.name',
    [':u' => $uid, ':mu' => $uid]
);

$reportFrom = isset($_REQUEST['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_REQUEST['from']) ? (string)$_REQUEST['from'] : null;
$reportTo = isset($_REQUEST['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_REQUEST['to']) ? (string)$_REQUEST['to'] : null;
$reportCustomerId = intval_safe($_REQUEST['customer_id'] ?? 0, 0);
$reportCustomer = $reportCustomerId ? cari_customer_or_fail($uid, $reportCustomerId) : null;
if ($reportFrom && $reportTo && $reportFrom > $reportTo) { [$reportFrom, $reportTo] = [$reportTo, $reportFrom]; }
$reportRows = cari_report_rows($uid, $reportFrom, $reportTo, $reportCustomerId ?: null);
$reportDebit = 0.0; $reportCredit = 0.0;
foreach ($reportRows as $r) { $reportDebit += (float)$r['debit_total']; $reportCredit += (float)$r['credit_total']; }
$reportNet = $reportDebit - $reportCredit;
$reportQuery = http_build_query(array_filter([
    'from' => $reportFrom,
    'to' => $reportTo,
    'customer_id' => $reportCustomerId ?: null,
], static fn($value) => $value !== null && $value !== ''));

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cari-rapor-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Cari', 'Tür', 'Telefon', 'E-posta', 'Borç', 'Alacak', 'Bakiye', 'Durum', 'Son Hareket'], ';');
    foreach ($reportRows as $r) {
        $balance = (float)$r['balance'];
        fputcsv($out, [$r['name'], $r['type'], $r['phone'], $r['email'], number_format((float)$r['debit_total'], 2, ',', '.'), number_format((float)$r['credit_total'], 2, ',', '.'), number_format(abs($balance), 2, ',', '.'), $balance >= 0 ? 'Alacak' : 'Borç', $r['last_tx_date'] ?: ''], ';');
    }
    fclose($out);
    exit;
}

if (isset($_GET['report']) && $_GET['report'] === 'print') {
    $html = cari_report_html($reportRows, $reportFrom, $reportTo, $user, $reportCustomer);
    cari_document_shell($reportCustomer ? 'Müşteri Cari Raporu' : 'Cari Raporu', $html, '/reports.php' . ($reportQuery ? '?' . $reportQuery : ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_report_email') {
    csrf_check();
    $email = s($_POST['report_email'] ?? ($user['email'] ?? ''), 160);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'Cari raporu için geçerli bir alıcı e-posta adresi yazın.');
        redirect('/reports.php' . ($reportQuery ? '?' . $reportQuery : ''));
    }
    try {
        $html = cari_report_html($reportRows, $reportFrom, $reportTo, $user, $reportCustomer);
        $pdf = cari_report_pdf($reportRows, $reportFrom, $reportTo, $user, $reportCustomer);
        cf_send_mail(
            $email,
            ($reportCustomer ? 'Müşteri Cari Raporu - ' . $reportCustomer['name'] : 'Cari Raporu') . ' - ' . cari_doc_period($reportFrom, $reportTo),
            $html,
            'Cari raporunuz PDF olarak ektedir. Detaylar HTML mesaj içeriğinde de yer alır.',
            [[ 'filename' => 'cari-raporu-' . date('Ymd-His') . '.pdf', 'content_type' => 'application/pdf', 'content' => $pdf ]]
        );
        audit('customer.report.email', $uid, null, 'email=' . $email);
        flash('success', 'Cari raporu PDF olarak ' . $email . ' adresine g?nderildi.');
    } catch (Throwable $e) {
        flash('danger', 'Cari raporu g?nderilemedi: ' . $e->getMessage());
    }
    redirect('/reports.php' . ($reportQuery ? '?' . $reportQuery : ''));
}

$pageTitle = 'Raporlar';
$pageHeader = 'Raporlar';
require __DIR__ . '/../inc/header.php';
?>

<div class="cf-cari-page">
    <div class="cf-page-head cf-cari-head">
        <h2>Cari Raporlar</h2>
        <div class="actions">
            <a href="/customers.php" class="btn btn-ghost">Carilere Dön</a>
        </div>
    </div>

    <div id="cari-rapor" class="cf-card cf-cari-report-card">
        <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h3 style="margin-bottom:4px;">Cari Hesap Özeti</h3>
                <div class="muted">Tüm cariler veya seçili müşteri için tarih aralığına göre borç, alacak ve net bakiye.</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="btn btn-ghost" target="_blank" href="/reports.php?report=print<?= $reportQuery ? '&' . e($reportQuery) : '' ?>">PDF Rapor</a>
                <a class="btn btn-outline" href="/reports.php?export=csv<?= $reportQuery ? '&' . e($reportQuery) : '' ?>">CSV İndir</a>
            </div>
        </div>
        <form method="get" class="cf-form" style="margin-top:14px;">
            <div>
                <label>Cari Seçimi</label>
                <select name="customer_id">
                    <option value="0">Tüm cariler</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $reportCustomerId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div><label>Başlangıç</label><input type="date" name="from" value="<?= e($reportFrom ?? '') ?>"></div>
                <div><label>Bitiş</label><input type="date" name="to" value="<?= e($reportTo ?? '') ?>"></div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-primary">Raporu Getir</button>
                <a class="btn btn-ghost" href="/reports.php">Sıfırla</a>
            </div>
        </form>
        <form method="post" class="cf-form" data-once style="margin-top:14px;border-top:1px solid #eef0f4;padding-top:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="send_report_email">
            <input type="hidden" name="from" value="<?= e($reportFrom ?? '') ?>">
            <input type="hidden" name="to" value="<?= e($reportTo ?? '') ?>">
            <input type="hidden" name="customer_id" value="<?= (int)$reportCustomerId ?>">
            <div class="row">
                <div><label>Rapor Mail Alıcısı</label><input type="email" name="report_email" value="<?= e($user['email'] ?? '') ?>" placeholder="rapor@alan.com" required></div>
                <div style="display:flex;align-items:end;"><button class="btn btn-outline" style="width:100%;">Cari Raporunu PDF Mail Gönder</button></div>
            </div>
        </form>
    </div>

    <div class="cf-grid cf-grid-3 cf-cari-summary">
        <div class="cf-stat income"><div class="label"><?= $reportCustomer ? 'Müşteri Borç' : 'Rapor Borç' ?></div><div class="value"><?= money($reportDebit) ?></div><div class="sub">İşlenen borç toplamı</div></div>
        <div class="cf-stat expense"><div class="label"><?= $reportCustomer ? 'Müşteri Alacak' : 'Rapor Alacak' ?></div><div class="value"><?= money($reportCredit) ?></div><div class="sub">Ödeme / alacak toplamı</div></div>
        <div class="cf-stat <?= $reportNet >= 0 ? 'gold' : 'expense' ?>"><div class="label"><?= $reportCustomer ? 'Müşteri Net' : 'Rapor Net' ?></div><div class="value"><?= money(abs($reportNet)) ?></div><div class="sub"><?= $reportNet >= 0 ? 'Tahsil edilecek' : 'Ödenecek' ?></div></div>
    </div>

    <div class="cf-card" style="padding:0;overflow:hidden;">
        <div class="cf-table-wrap">
            <table class="cf-table cf-mobile-cards" style="box-shadow:none;border-radius:10px;">
                <thead><tr><th>Cari</th><th>Tür</th><th class="amount">Borç</th><th class="amount">Alacak</th><th class="amount">Bakiye</th><th>Son Hareket</th></tr></thead>
                <tbody>
                <?php foreach ($reportRows as $r): $bal = (float)$r['balance']; ?>
                    <tr>
                        <td data-label="Cari"><strong><?= e($r['name']) ?></strong><div style="font-size:12px;color:var(--cf-muted);"><?= e($r['phone'] ?: '') ?> <?= e($r['email'] ?: '') ?></div></td>
                        <td data-label="Tür"><?= e($r['type']) ?></td>
                        <td data-label="Borç" class="amount income"><?= money($r['debit_total']) ?></td>
                        <td data-label="Alacak" class="amount expense"><?= money($r['credit_total']) ?></td>
                        <td data-label="Bakiye" class="amount <?= $bal >= 0 ? 'income' : 'expense' ?>"><?= money(abs($bal)) ?> <?= $bal >= 0 ? 'Alacak' : 'Borç' ?></td>
                        <td data-label="Son Hareket"><?= $r['last_tx_date'] ? tr_date($r['last_tx_date']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reportRows): ?><tr><td colspan="6" class="muted">Raporlanacak cari bulunamadı.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
