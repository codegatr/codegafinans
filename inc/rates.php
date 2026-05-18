<?php
/**
 * CODEGA Finans - Döviz kuru servisi (TCMB)
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function rates_all(bool $refresh_if_stale = true): array
{
    if ($refresh_if_stale && rates_is_stale()) {
        rates_refresh_from_tcmb();
    }
    return db_all('SELECT * FROM ' . t('rates') . ' ORDER BY code');
}

function rates_is_stale(): bool
{
    $row = db_one('SELECT MAX(updated_at) AS m FROM ' . t('rates'));
    if (!$row || empty($row['m'])) { return true; }
    $age = time() - strtotime($row['m']);
    return $age > (CF_TCMB_REFRESH_MIN * 60);
}

function rates_refresh_from_tcmb(): array
{
    $codes = ['USD', 'EUR', 'GBP', 'CHF', 'JPY', 'SAR', 'KWD', 'AED'];

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'user_agent' => 'CODEGAFinans/' . CF_VERSION . ' (+https://finans.codega.com.tr)',
            'header' => "Accept: application/xml\r\n",
        ],
    ]);
    $xml = @file_get_contents(CF_TCMB_URL, false, $ctx);
    if (!$xml) {
        return ['ok' => false, 'message' => 'TCMB kaynağına ulaşılamadı.'];
    }

    libxml_use_internal_errors(true);
    $doc = @simplexml_load_string($xml);
    if (!$doc) {
        return ['ok' => false, 'message' => 'TCMB XML ayrıştırılamadı.'];
    }

    $updated = 0;
    foreach ($doc->Currency as $cur) {
        $code = (string)$cur['CurrencyCode'];
        if (!in_array($code, $codes, true)) { continue; }
        $name = (string)$cur->Isim;
        $buy  = (float) str_replace(',', '.', (string)$cur->ForexBuying);
        $sell = (float) str_replace(',', '.', (string)$cur->ForexSelling);
        if ($buy <= 0 || $sell <= 0) {
            // JPY gibi: birim 100 olabilir → BanknoteBuying'e bak
            $buy = (float) str_replace(',', '.', (string)$cur->BanknoteBuying);
            $sell = (float) str_replace(',', '.', (string)$cur->BanknoteSelling);
            if ($buy <= 0 || $sell <= 0) { continue; }
        }

        $row = db_one('SELECT id FROM ' . t('rates') . ' WHERE code = :c', [':c' => $code]);
        if ($row) {
            db_exec(
                'UPDATE ' . t('rates') . ' SET name=:n, buy_rate=:b, sell_rate=:s, source="TCMB", updated_at=NOW()
                  WHERE id=:id',
                [':n' => $name, ':b' => $buy, ':s' => $sell, ':id' => $row['id']]
            );
        } else {
            db_exec(
                'INSERT INTO ' . t('rates') . ' (code,name,buy_rate,sell_rate,source,updated_at)
                 VALUES (:c,:n,:b,:s,"TCMB",NOW())',
                [':c' => $code, ':n' => $name, ':b' => $buy, ':s' => $sell]
            );
        }
        $updated++;
    }

    db_exec(
        'INSERT INTO ' . t('settings') . ' (key_name, value)
         VALUES ("rates_last_at", :v)
         ON DUPLICATE KEY UPDATE value = :v',
        [':v' => date('Y-m-d H:i:s')]
    );

    return ['ok' => true, 'updated' => $updated];
}
