<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Finance
{
    public static function summary(int $userId): array
    {
        $month = date('Y-m');
        $income = self::sumTransactions($userId, 'income', $month);
        $expense = self::sumTransactions($userId, 'expense', $month);
        $budget = self::sumBudgets($userId, $month);
        $saved = self::sumGoals($userId);
        $debt = self::sumDebts($userId);

        return compact('income', 'expense', 'budget', 'saved', 'debt', 'month') + [
            'balance' => $income - $expense,
            'budget_usage' => $budget > 0 ? min(100, round(($expense / $budget) * 100)) : 0,
        ];
    }

    public static function transactions(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM transactions WHERE user_id = :user_id ORDER BY transaction_date DESC, id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function recentTransactions(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM transactions WHERE user_id = :user_id ORDER BY transaction_date DESC, id DESC LIMIT 6');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function createTransaction(int $userId, array $data): void
    {
        $stmt = db()->prepare('INSERT INTO transactions (user_id, type, category, title, amount, transaction_date, note) VALUES (:user_id, :type, :category, :title, :amount, :transaction_date, :note)');
        $stmt->execute([
            'user_id' => $userId,
            'type' => in_array($data['type'] ?? '', ['income', 'expense'], true) ? $data['type'] : 'expense',
            'category' => trim((string) ($data['category'] ?? 'Genel')) ?: 'Genel',
            'title' => trim((string) ($data['title'] ?? 'Hareket')) ?: 'Hareket',
            'amount' => max(0, (float) ($data['amount'] ?? 0)),
            'transaction_date' => $data['transaction_date'] ?: date('Y-m-d'),
            'note' => trim((string) ($data['note'] ?? '')),
        ]);
    }

    public static function budgets(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM budgets WHERE user_id = :user_id ORDER BY month DESC, category ASC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function createBudget(int $userId, array $data): void
    {
        $stmt = db()->prepare('INSERT INTO budgets (user_id, month, category, limit_amount) VALUES (:user_id, :month, :category, :limit_amount)');
        $stmt->execute([
            'user_id' => $userId,
            'month' => preg_match('/^\d{4}-\d{2}$/', (string) ($data['month'] ?? '')) ? $data['month'] : date('Y-m'),
            'category' => trim((string) ($data['category'] ?? 'Genel')) ?: 'Genel',
            'limit_amount' => max(0, (float) ($data['limit_amount'] ?? 0)),
        ]);
    }

    public static function goals(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM saving_goals WHERE user_id = :user_id ORDER BY id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function createGoal(int $userId, array $data): void
    {
        $stmt = db()->prepare('INSERT INTO saving_goals (user_id, title, target_amount, current_amount, deadline) VALUES (:user_id, :title, :target_amount, :current_amount, :deadline)');
        $stmt->execute([
            'user_id' => $userId,
            'title' => trim((string) ($data['title'] ?? 'Hedef')) ?: 'Hedef',
            'target_amount' => max(1, (float) ($data['target_amount'] ?? 1)),
            'current_amount' => max(0, (float) ($data['current_amount'] ?? 0)),
            'deadline' => $data['deadline'] ?: null,
        ]);
    }

    public static function depositGoal(int $userId, int $id, float $amount): void
    {
        $stmt = db()->prepare('UPDATE saving_goals SET current_amount = current_amount + :amount WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['amount' => max(0, $amount), 'id' => $id, 'user_id' => $userId]);
    }

    public static function debts(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM debts WHERE user_id = :user_id ORDER BY due_date IS NULL, due_date ASC, id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function createDebt(int $userId, array $data): void
    {
        $stmt = db()->prepare('INSERT INTO debts (user_id, creditor, total_amount, paid_amount, due_date) VALUES (:user_id, :creditor, :total_amount, :paid_amount, :due_date)');
        $stmt->execute([
            'user_id' => $userId,
            'creditor' => trim((string) ($data['creditor'] ?? 'Borc')) ?: 'Borc',
            'total_amount' => max(0, (float) ($data['total_amount'] ?? 0)),
            'paid_amount' => max(0, (float) ($data['paid_amount'] ?? 0)),
            'due_date' => $data['due_date'] ?: null,
        ]);
    }

    public static function payDebt(int $userId, int $id, float $amount): void
    {
        $capFunction = Database::driver() === 'mysql' ? 'LEAST' : 'MIN';
        $stmt = db()->prepare("UPDATE debts SET paid_amount = {$capFunction}(total_amount, paid_amount + :amount) WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['amount' => max(0, $amount), 'id' => $id, 'user_id' => $userId]);
    }

    public static function categoryBreakdown(int $userId): array
    {
        $monthSql = self::monthSql('transaction_date');
        $stmt = db()->prepare("SELECT category, SUM(amount) total FROM transactions WHERE user_id = :user_id AND type = 'expense' AND {$monthSql} = :month GROUP BY category ORDER BY total DESC LIMIT 5");
        $stmt->execute(['user_id' => $userId, 'month' => date('Y-m')]);
        return $stmt->fetchAll();
    }

    public static function rates(): array
    {
        self::refreshRates();
        return db()->query('SELECT * FROM exchange_rates ORDER BY code ASC')->fetchAll();
    }

    private static function refreshRates(): void
    {
        $dateSql = Database::driver() === 'mysql' ? 'DATE(updated_at) = CURDATE()' : "date(updated_at) = date('now')";
        $fresh = db()->query("SELECT COUNT(*) total FROM exchange_rates WHERE {$dateSql}")->fetch();
        if ((int) ($fresh['total'] ?? 0) >= 3 || !function_exists('simplexml_load_string')) {
            return;
        }

        $xml = @file_get_contents('https://www.tcmb.gov.tr/kurlar/today.xml');
        if (!$xml) {
            return;
        }

        $document = @simplexml_load_string($xml);
        if (!$document) {
            return;
        }

        foreach ($document->Currency as $currency) {
            $code = (string) $currency['CurrencyCode'];
            if (!in_array($code, ['USD', 'EUR', 'GBP'], true)) {
                continue;
            }
            $buy = (float) str_replace(',', '.', (string) $currency->ForexBuying);
            $sell = (float) str_replace(',', '.', (string) $currency->ForexSelling);
            if ($buy <= 0 || $sell <= 0) {
                continue;
            }
            self::updateRate($code, (string) $currency->Isim, $buy, $sell);
        }
    }

    private static function updateRate(string $code, string $name, float $buy, float $sell): void
    {
        $exists = db()->prepare('SELECT id FROM exchange_rates WHERE code = :code LIMIT 1');
        $exists->execute(['code' => $code]);
        if ($exists->fetch()) {
            $stmt = db()->prepare('UPDATE exchange_rates SET name = :name, buy_rate = :buy_rate, sell_rate = :sell_rate, updated_at = CURRENT_TIMESTAMP WHERE code = :code');
        } else {
            $stmt = db()->prepare('INSERT INTO exchange_rates (code, name, buy_rate, sell_rate) VALUES (:code, :name, :buy_rate, :sell_rate)');
        }
        $stmt->execute(['code' => $code, 'name' => $name, 'buy_rate' => $buy, 'sell_rate' => $sell]);
    }

    public static function smartAlerts(int $userId): array
    {
        $summary = self::summary($userId);
        $alerts = [];

        if ($summary['budget'] > 0 && $summary['budget_usage'] >= 85) {
            $alerts[] = ['type' => 'danger', 'title' => 'Butce sinirina yaklastiniz', 'message' => 'Bu ay toplam butcenizin %' . $summary['budget_usage'] . ' kadarini kullandiniz.'];
        }
        if ($summary['expense'] > $summary['income'] && $summary['income'] > 0) {
            $alerts[] = ['type' => 'danger', 'title' => 'Giderler gelirden yuksek', 'message' => 'Bu ay nakit akisi negatif gorunuyor.'];
        }
        if ($summary['debt'] > 0) {
            $alerts[] = ['type' => 'info', 'title' => 'Aktif borc takibi', 'message' => 'Odenmemis toplam borcunuz: ' . money($summary['debt'])];
        }
        if (!$alerts) {
            $alerts[] = ['type' => 'success', 'title' => 'Finansal durum dengeli', 'message' => 'Su an kritik bir uyariniz bulunmuyor.'];
        }

        return $alerts;
    }

    private static function sumTransactions(int $userId, string $type, string $month): float
    {
        $monthSql = self::monthSql('transaction_date');
        $stmt = db()->prepare("SELECT COALESCE(SUM(amount), 0) total FROM transactions WHERE user_id = :user_id AND type = :type AND {$monthSql} = :month");
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'month' => $month]);
        return (float) $stmt->fetch()['total'];
    }

    private static function monthSql(string $column): string
    {
        return Database::driver() === 'mysql'
            ? "DATE_FORMAT({$column}, '%Y-%m')"
            : "strftime('%Y-%m', {$column})";
    }

    private static function sumBudgets(int $userId, string $month): float
    {
        $stmt = db()->prepare('SELECT COALESCE(SUM(limit_amount), 0) total FROM budgets WHERE user_id = :user_id AND month = :month');
        $stmt->execute(['user_id' => $userId, 'month' => $month]);
        return (float) $stmt->fetch()['total'];
    }

    private static function sumGoals(int $userId): float
    {
        $stmt = db()->prepare('SELECT COALESCE(SUM(current_amount), 0) total FROM saving_goals WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (float) $stmt->fetch()['total'];
    }

    private static function sumDebts(int $userId): float
    {
        $stmt = db()->prepare('SELECT COALESCE(SUM(total_amount - paid_amount), 0) total FROM debts WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (float) $stmt->fetch()['total'];
    }
}
