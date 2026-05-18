<?php
/**
 * CODEGA Finans - Finansal hesaplamalar
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function fin_monthly_summary(int $userId, ?string $month = null): array
{
    $month = $month ?: date('Y-m');

    $income = (float) (db_one(
        'SELECT COALESCE(SUM(amount),0) s FROM ' . t('transactions') . '
          WHERE user_id = :u AND type = "income" AND DATE_FORMAT(tx_date,"%Y-%m") = :m',
        [':u' => $userId, ':m' => $month]
    )['s'] ?? 0);

    $expense = (float) (db_one(
        'SELECT COALESCE(SUM(amount),0) s FROM ' . t('transactions') . '
          WHERE user_id = :u AND type = "expense" AND DATE_FORMAT(tx_date,"%Y-%m") = :m',
        [':u' => $userId, ':m' => $month]
    )['s'] ?? 0);

    $budget = (float) (db_one(
        'SELECT COALESCE(SUM(limit_amount),0) s FROM ' . t('budgets') . '
          WHERE user_id = :u AND month = :m',
        [':u' => $userId, ':m' => $month]
    )['s'] ?? 0);

    $saved = (float) (db_one(
        'SELECT COALESCE(SUM(current_amount),0) s FROM ' . t('goals') . ' WHERE user_id = :u',
        [':u' => $userId]
    )['s'] ?? 0);

    $debt = (float) (db_one(
        'SELECT COALESCE(SUM(total_amount - paid_amount),0) s FROM ' . t('debts') . '
          WHERE user_id = :u AND is_closed = 0',
        [':u' => $userId]
    )['s'] ?? 0);

    $balance = $income - $expense;
    $usage = $budget > 0 ? min(100, round(($expense / $budget) * 100)) : 0;

    return compact('month','income','expense','budget','saved','debt','balance','usage');
}

function fin_recent_transactions(int $userId, int $limit = 8): array
{
    return db_all(
        'SELECT t.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
           FROM ' . t('transactions') . ' t
           LEFT JOIN ' . t('categories') . ' c ON c.id = t.category_id
          WHERE t.user_id = :u
          ORDER BY t.tx_date DESC, t.id DESC
          LIMIT ' . (int)$limit,
        [':u' => $userId]
    );
}

function fin_category_breakdown(int $userId, ?string $month = null): array
{
    $month = $month ?: date('Y-m');
    return db_all(
        'SELECT COALESCE(c.name, "Diğer") AS name,
                COALESCE(c.color,"#94a3b8") AS color,
                SUM(t.amount) AS total
           FROM ' . t('transactions') . ' t
           LEFT JOIN ' . t('categories') . ' c ON c.id = t.category_id
          WHERE t.user_id = :u
            AND t.type = "expense"
            AND DATE_FORMAT(t.tx_date,"%Y-%m") = :m
          GROUP BY c.id, c.name, c.color
          ORDER BY total DESC',
        [':u' => $userId, ':m' => $month]
    );
}

function fin_categories_for(int $userId, string $type = 'both'): array
{
    if ($type === 'both') {
        $where = '(user_id IS NULL OR user_id = :u)';
    } else {
        $where = '(user_id IS NULL OR user_id = :u) AND type IN (:t, "both")';
    }
    $params = [':u' => $userId];
    if ($type !== 'both') { $params[':t'] = $type; }
    return db_all(
        'SELECT * FROM ' . t('categories') . " WHERE is_active = 1 AND $where ORDER BY sort, name",
        $params
    );
}

function fin_monthly_series(int $userId, int $months = 6): array
{
    $rows = db_all(
        'SELECT DATE_FORMAT(tx_date,"%Y-%m") AS ym,
                SUM(IF(type="income", amount, 0))  AS income,
                SUM(IF(type="expense", amount, 0)) AS expense
           FROM ' . t('transactions') . '
          WHERE user_id = :u
            AND tx_date >= (CURDATE() - INTERVAL :m MONTH)
          GROUP BY ym
          ORDER BY ym',
        [':u' => $userId, ':m' => $months]
    );
    return $rows;
}

/**
 * Akıllı uyarı üreteci. Mevcut uyarıları temizleyip yeniden hesaplayabilir.
 */
function fin_generate_alerts(int $userId, bool $persist = true): array
{
    $sum = fin_monthly_summary($userId);
    $alerts = [];

    if ($sum['budget'] > 0 && $sum['usage'] >= 85) {
        $alerts[] = [
            'level' => $sum['usage'] >= 100 ? 'danger' : 'warning',
            'title' => 'Bütçe sınırına yaklaştınız',
            'message' => 'Bu ay toplam bütçenizin %' . $sum['usage'] . ' kadarını kullandınız.',
            'link' => '/budgets.php',
        ];
    }

    if ($sum['income'] > 0 && $sum['expense'] > $sum['income']) {
        $alerts[] = [
            'level' => 'danger',
            'title' => 'Giderler gelirden yüksek',
            'message' => 'Bu ay nakit akışı negatif görünüyor. Tasarruf önerilir.',
            'link' => '/transactions.php',
        ];
    }

    if ($sum['debt'] > 0) {
        $alerts[] = [
            'level' => 'info',
            'title' => 'Aktif borç takibi',
            'message' => 'Ödenmemiş toplam borcunuz: ' . money($sum['debt']),
            'link' => '/debts.php',
        ];
    }

    // Yakın deadline'lı borçlar
    $soonDebts = db_all(
        'SELECT creditor, due_date FROM ' . t('debts') . '
          WHERE user_id = :u AND is_closed = 0
            AND due_date IS NOT NULL
            AND due_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 7 DAY)',
        [':u' => $userId]
    );
    foreach ($soonDebts as $d) {
        $alerts[] = [
            'level' => 'warning',
            'title' => 'Yaklaşan borç ödemesi',
            'message' => $d['creditor'] . ' - vade ' . tr_date($d['due_date']),
            'link' => '/debts.php',
        ];
    }

    // Tasarruf hedefi tamamlananlar
    $doneGoals = db_all(
        'SELECT id, title FROM ' . t('goals') . '
          WHERE user_id = :u AND is_completed = 0 AND target_amount > 0
            AND current_amount >= target_amount',
        [':u' => $userId]
    );
    foreach ($doneGoals as $g) {
        $alerts[] = [
            'level' => 'success',
            'title' => 'Tasarruf hedefini tamamladınız 🎉',
            'message' => '"' . $g['title'] . '" hedefiniz tamamlandı.',
            'link' => '/goals.php',
        ];
        db_exec('UPDATE ' . t('goals') . ' SET is_completed = 1 WHERE id = :id',
                [':id' => $g['id']]);
    }

    if (!$alerts) {
        $alerts[] = [
            'level' => 'success',
            'title' => 'Finansal durum dengeli',
            'message' => 'Şu an dikkat etmeniz gereken kritik bir uyarınız yok.',
            'link' => null,
        ];
    }

    if ($persist) {
        // Aynı başlıklı okunmamış uyarıyı bir daha eklememek için basit korunma
        foreach ($alerts as $a) {
            $exists = db_one(
                'SELECT id FROM ' . t('alerts') . '
                  WHERE user_id = :u AND title = :t AND is_read = 0
                    AND created_at > (NOW() - INTERVAL 1 DAY)',
                [':u' => $userId, ':t' => $a['title']]
            );
            if (!$exists) {
                db_exec(
                    'INSERT INTO ' . t('alerts') . ' (user_id,level,title,message,link,created_at)
                     VALUES (:u,:l,:t,:m,:k,NOW())',
                    [
                        ':u' => $userId,
                        ':l' => $a['level'],
                        ':t' => $a['title'],
                        ':m' => $a['message'],
                        ':k' => $a['link'],
                    ]
                );
            }
        }
    }

    return $alerts;
}
