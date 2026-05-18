<?php
/**
 * CODEGA Finans - Abonelik yönetimi
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Kullanıcının aktif/trial aboneliğini döner (null mümkün).
 */
function subscription_active_for(int $userId): ?array
{
    return db_one(
        'SELECT s.*, p.name AS plan_name, p.code AS plan_code, p.price, p.currency, p.period
           FROM ' . t('subscriptions') . ' s
           JOIN ' . t('plans') . ' p ON p.id = s.plan_id
          WHERE s.user_id = :u
            AND s.status IN ("trial","active","past_due")
            AND s.current_period_end >= CURDATE()
          ORDER BY s.id DESC LIMIT 1',
        [':u' => $userId]
    );
}

function subscription_latest_for(int $userId): ?array
{
    return db_one(
        'SELECT s.*, p.name AS plan_name, p.code AS plan_code, p.price, p.currency, p.period
           FROM ' . t('subscriptions') . ' s
           JOIN ' . t('plans') . ' p ON p.id = s.plan_id
          WHERE s.user_id = :u
          ORDER BY s.id DESC LIMIT 1',
        [':u' => $userId]
    );
}

function subscription_start_trial(int $userId): int
{
    $trialPlan = db_one('SELECT * FROM ' . t('plans') . ' WHERE code = "trial" LIMIT 1');
    if (!$trialPlan) {
        // Trial planı yoksa yarat
        $pid = db_insert(
            'INSERT INTO ' . t('plans') . ' (code,name,price,currency,period,is_active,sort)
             VALUES ("trial","Deneme",0,"TRY","trial",1,1)'
        );
    } else {
        $pid = (int)$trialPlan['id'];
    }

    $end = date('Y-m-d', strtotime('+' . CF_TRIAL_DAYS . ' days'));

    $subId = db_insert(
        'INSERT INTO ' . t('subscriptions') . '
            (user_id, plan_id, status, started_at, current_period_end, source, auto_renew, created_at)
         VALUES (:u, :p, "trial", CURDATE(), :e, "web", 0, NOW())',
        [':u' => $userId, ':p' => $pid, ':e' => $end]
    );

    db_exec(
        'UPDATE ' . t('users') . ' SET subscription_id = :s WHERE id = :u',
        [':s' => $subId, ':u' => $userId]
    );
    return $subId;
}

/**
 * Manuel abonelik etkinleştirme (admin tarafından, ödeme aldıktan sonra).
 */
function subscription_activate_manual(int $userId, int $planId, ?string $note = null, ?int $adminId = null): array
{
    $plan = db_one('SELECT * FROM ' . t('plans') . ' WHERE id = :id', [':id' => $planId]);
    if (!$plan) {
        return ['ok' => false, 'message' => 'Plan bulunamadı.'];
    }

    $start = date('Y-m-d');
    $end = match ($plan['period']) {
        'monthly'  => date('Y-m-d', strtotime('+1 month')),
        'yearly'   => date('Y-m-d', strtotime('+1 year')),
        'lifetime' => '2099-12-31',
        default    => date('Y-m-d', strtotime('+' . CF_TRIAL_DAYS . ' days')),
    };

    $subId = db_insert(
        'INSERT INTO ' . t('subscriptions') . '
            (user_id, plan_id, status, started_at, current_period_end, source, auto_renew, created_at)
         VALUES (:u, :p, "active", :s, :e, "manual", 1, NOW())',
        [':u' => $userId, ':p' => $planId, ':s' => $start, ':e' => $end]
    );

    db_exec(
        'UPDATE ' . t('users') . ' SET subscription_id = :s WHERE id = :u',
        [':s' => $subId, ':u' => $userId]
    );

    $payId = db_insert(
        'INSERT INTO ' . t('payments') . '
            (subscription_id, user_id, amount, currency, status, method, note, paid_at, created_at)
         VALUES (:s,:u,:a,:c,"succeeded","manual",:n,NOW(),NOW())',
        [
            ':s' => $subId, ':u' => $userId,
            ':a' => $plan['price'], ':c' => $plan['currency'],
            ':n' => $note,
        ]
    );

    audit('subscription.activate', $userId, $adminId, "plan={$plan['code']} sub={$subId} pay={$payId}");
    return ['ok' => true, 'subscription_id' => $subId, 'payment_id' => $payId];
}

function subscription_cancel(int $userId, ?int $adminId = null): array
{
    $sub = subscription_active_for($userId);
    if (!$sub) { return ['ok' => false, 'message' => 'Aktif abonelik bulunamadı.']; }
    db_exec(
        'UPDATE ' . t('subscriptions') . ' SET status="cancelled", cancelled_at=NOW(), auto_renew=0 WHERE id=:id',
        [':id' => $sub['id']]
    );
    audit('subscription.cancel', $userId, $adminId, "sub={$sub['id']}");
    return ['ok' => true];
}

/**
 * Süresi dolmuş trial/abonelikleri 'expired' işaretle. Cron çağırır.
 */
function subscription_sweep_expired(): int
{
    return db_exec(
        'UPDATE ' . t('subscriptions') . '
            SET status = "expired"
          WHERE status IN ("trial","active","past_due")
            AND current_period_end < CURDATE()'
    );
}

/**
 * Plan listesi (aktif).
 */
function plans_active(): array
{
    return db_all('SELECT * FROM ' . t('plans') . ' WHERE is_active = 1 AND code != "trial" ORDER BY sort, id');
}
