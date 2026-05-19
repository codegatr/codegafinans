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

        if ($amount <= 0 || $title === '') {
            flash('danger', 'Hareket basligi ve pozitif tutar zorunludur.');
            cari_redirect($customerId);
        }

        $mid = db_insert(
            'INSERT INTO ' . t('customer_movements') . '
                (user_id,customer_id,direction,amount,tx_date,title,note,created_at)
             VALUES (:u,:c,:d,:a,:dt,:t,:n,NOW())',
            [
                ':u' => $uid,
                ':c' => $customerId,
                ':d' => $direction,
                ':a' => $amount,
                ':dt' => $date,
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

$selectedId = isset($_GET['id']) ? intval_safe($_GET['id'], 1) : 0;

$selected = null;
foreach ($customers as $c) {
    if ((int)$c['id'] === $selectedId) { $selected = $c; break; }
}

$movements = [];
if ($selected) {
    $movements = db_all(
        'SELECT * FROM ' . t('customer_movements') . '
          WHERE user_id=:u AND customer_id=:c
          ORDER BY tx_date DESC, id DESC',
        [':u' => $uid, ':c' => (int)$selected['id']]
    );
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
        <a href="/customers.php?new=1" class="btn btn-primary">+ Yeni Cari</a>
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
        Henüz cari kartı yok. İlk müşteriyi veya tedarikçiyi ekleyin.
    </div>
<?php else: ?>
<div class="cf-grid cf-cari-layout">
    <div class="cf-card cf-cari-list" style="padding:0;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid #eef0f4;">
            <h3 style="margin:0;">Cari Listesi</h3>
            <div class="muted">Kartı açın, hareket ekleyin veya ekstre gönderin.</div>
        </div>
        <div style="display:grid;">
            <?php foreach ($customers as $c):
                $bal = (float)$c['balance'];
                $active = $selected && (int)$selected['id'] === (int)$c['id'];
            ?>
                <a class="<?= $active ? 'active' : '' ?>" href="/customers.php?id=<?= (int)$c['id'] ?>" style="display:flex;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:1px solid #eef0f4;background:<?= $active ? '#f8fafc' : '#fff' ?>;">
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
    <div class="cf-card cf-empty cf-cari-detail">
        <div class="icon">□</div>
        İşlem yapmak için soldan bir cari seçin. Borç, alacak, ekstre ve hareket alanları seçimden sonra açılır.
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
            <?php if (!$movements): ?>
                <div class="cf-empty" style="padding:28px;">Bu cariye ait hareket yok.</div>
            <?php else: ?>
            <div class="cf-table-wrap">
                <table class="cf-table cf-mobile-cards" style="box-shadow:none;border:0;border-radius:0;">
                    <thead><tr><th>Tarih</th><th>Başlık</th><th>Tür</th><th class="amount">Tutar</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td data-label="Tarih"><?= tr_date($m['tx_date']) ?></td>
                            <td data-label="İşlem"><strong><?= e($m['title']) ?></strong><?php if ($m['note']): ?><div style="font-size:12px;color:var(--cf-muted);"><?= e($m['note']) ?></div><?php endif; ?></td>
                            <td data-label="Tür"><span class="cf-pill <?= $m['direction'] === 'debit' ? 'income' : 'expense' ?>"><?= $m['direction'] === 'debit' ? 'Borç' : 'Alacak' ?></span></td>
                            <td data-label="Tutar" class="amount <?= $m['direction'] === 'debit' ? 'income' : 'expense' ?>"><?= money($m['amount']) ?></td>
                            <td style="text-align:right;">
                                <form method="post" onsubmit="return confirm('Hareket silinsin mi?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_movement">
                                    <input type="hidden" name="customer_id" value="<?= (int)$selected['id'] ?>">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <button class="btn btn-ghost btn-sm">Sil</button>
                                </form>
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
