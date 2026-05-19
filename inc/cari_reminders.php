<?php
/**
 * CODEGA Finans - Cari vade hatırlatma mailleri
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

function cari_reminder_contact(array $user): string
{
    $parts = [];
    if (!empty($user['phone'])) { $parts[] = (string)$user['phone']; }
    if (!empty($user['email'])) { $parts[] = (string)$user['email']; }
    return $parts ? implode(' / ', $parts) : CF_APP_NAME;
}

function cari_due_reminder_html(array $row): string
{
    $sender = trim((string)($row['user_name'] ?? '')) ?: CF_APP_NAME;
    $contact = cari_reminder_contact(['phone' => $row['user_phone'] ?? '', 'email' => $row['user_email'] ?? '']);
    $direction = $row['direction'] === 'debit' ? 'Borç' : 'Alacak / Ödeme';
    ob_start();
    ?>
    <div style="margin:0;padding:24px;background:#f3f6fa;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #dbe3ef;">
            <tr><td style="padding:28px 32px;">
                <div style="font-size:25px;font-weight:800;color:#06152e;"><?= e($sender) ?></div>
                <div style="margin-top:8px;font-size:13px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#0f766e;">Cari vade hatırlatması</div>
                <div style="margin-top:8px;font-size:14px;color:#50627d;"><?= e($contact) ?></div>
                <div style="height:3px;background:#111827;margin:22px 0;"></div>
                <div style="font-size:18px;line-height:1.45;color:#0f172a;">
                    Sayın <strong><?= e($row['customer_name']) ?></strong>, cari hesabınıza ait aşağıdaki kaydın vadesi gelmiştir.
                </div>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 10px;margin-top:18px;">
                    <tr>
                        <td style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;"><div style="font-size:12px;text-transform:uppercase;color:#50627d;">İşlem</div><div style="margin-top:7px;font-size:17px;font-weight:800;color:#06152e;"><?= e($row['title']) ?></div></td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:10px;background:#fbfdff;"><div style="font-size:12px;text-transform:uppercase;color:#50627d;">Tür / Tutar / Vade</div><div style="margin-top:7px;font-size:17px;font-weight:800;color:#06152e;"><?= e($direction) ?> · <?= e(money($row['amount'])) ?> · <?= e(tr_date($row['due_date'])) ?></div></td>
                    </tr>
                </table>
                <?php if (!empty($row['note'])): ?>
                    <div style="margin-top:12px;padding:14px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;color:#334155;"><?= e($row['note']) ?></div>
                <?php endif; ?>
                <div style="margin-top:20px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:13px;line-height:1.5;color:#50627d;">
                    Bu e-posta cari vade takibi için otomatik oluşturulmuştur. Ödeme, tahsilat veya mutabakat detayları için <?= e($sender) ?> ile iletişime geçebilirsiniz.
                </div>
            </td></tr>
        </table>
    </div>
    <?php
    return (string)ob_get_clean();
}

function cari_send_due_reminders(?string $date = null, int $limit = 100): array
{
    $date = $date ?: date('Y-m-d');
    $rows = db_all(
        'SELECT m.*, c.name AS customer_name, c.email AS customer_email,
                u.name AS user_name, u.email AS user_email, u.phone AS user_phone
           FROM ' . t('customer_movements') . ' m
           JOIN ' . t('customers') . ' c ON c.id = m.customer_id AND c.user_id = m.user_id
           JOIN ' . t('users') . ' u ON u.id = m.user_id
          WHERE m.due_date <= :d
            AND m.reminder_sent_at IS NULL
            AND c.is_active = 1
          ORDER BY m.id
          LIMIT ' . max(1, min(500, $limit)),
        [':d' => $date]
    );

    $sent = 0;
    $skipped = 0;
    $failed = 0;
    foreach ($rows as $row) {
        $email = trim((string)($row['customer_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $skipped++;
            continue;
        }
        try {
            $html = cari_due_reminder_html($row);
            $subject = 'Cari Vade Hatırlatması - ' . $row['customer_name'];
            cf_send_mail(
                $email,
                $subject,
                $html,
                'Cari hesabınıza ait ' . $row['title'] . ' kaydının vadesi gelmiştir: ' . tr_date($row['due_date']) . '. Tutar: ' . money($row['amount'])
            );
            db_exec(
                'UPDATE ' . t('customer_movements') . ' SET reminder_sent_at = NOW() WHERE id = :id AND reminder_sent_at IS NULL',
                [':id' => (int)$row['id']]
            );
            $sent++;
        } catch (Throwable) {
            $failed++;
        }
    }

    return ['checked' => count($rows), 'sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
}
