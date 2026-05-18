<?php
/**
 * CODEGA Finans - Kullanıcı kimlik doğrulama
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/subscription.php';

function auth_login(string $email, string $password): array
{
    auth_throttle_check($email, 'user');

    $user = db_one(
        'SELECT * FROM ' . t('users') . ' WHERE email = :e LIMIT 1',
        [':e' => cf_str_lower($email)]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        auth_throttle_record($email, false, 'user');
        return ['ok' => false, 'message' => 'E-posta veya şifre hatalı.'];
    }

    if ($user['status'] !== 'active') {
        return ['ok' => false, 'message' => 'Hesabınız ' . $user['status'] . ' durumunda. Lütfen iletişime geçin.'];
    }

    auth_throttle_record($email, true, 'user');

    // Şifre yeniden hash gerekiyorsa güncelle
    if (password_needs_rehash($user['password'], CF_PASSWORD_ALGO)) {
        db_exec(
            'UPDATE ' . t('users') . ' SET password = :p WHERE id = :id',
            [':p' => password_hash($password, CF_PASSWORD_ALGO), ':id' => $user['id']]
        );
    }

    db_exec(
        'UPDATE ' . t('users') . ' SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id',
        [':ip' => client_ip(), ':id' => $user['id']]
    );

    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    audit('user.login', (int)$user['id']);

    return ['ok' => true, 'user' => $user];
}

function auth_logout(): void
{
    start_session();
    $uid = $_SESSION['user_id'] ?? null;
    if ($uid) { audit('user.logout', (int)$uid); }
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
    session_regenerate_id(true);
}

function auth_user(): ?array
{
    start_session();
    if (empty($_SESSION['user_id'])) { return null; }
    static $cache = null;
    if ($cache !== null && $cache['id'] === $_SESSION['user_id']) {
        return $cache;
    }
    $u = db_one('SELECT * FROM ' . t('users') . ' WHERE id = :id LIMIT 1',
                [':id' => (int)$_SESSION['user_id']]);
    if (!$u) {
        auth_logout();
        return null;
    }
    $cache = $u;
    return $u;
}

function auth_require(): array
{
    $u = auth_user();
    if (!$u) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        redirect('/login.php?next=' . $next);
    }
    return $u;
}

function auth_require_active_subscription(): array
{
    $u = auth_require();
    $sub = subscription_active_for($u['id']);
    // Trial veya aktif - geçişe izin ver
    if (!$sub || !in_array($sub['status'], ['trial', 'active'], true)) {
        redirect('/subscription.php?reason=expired');
    }
    return $u;
}

/* --------------------------------------------------------------------------
 * Kayıt
 * -------------------------------------------------------------------------- */
function auth_register(array $data): array
{
    $name = s($data['name'] ?? '', 120);
    $email = cf_str_lower(s($data['email'] ?? '', 160));
    $phone = s($data['phone'] ?? '', 40);
    $pass = (string)($data['password'] ?? '');
    $pass2 = (string)($data['password_confirmation'] ?? '');

    if ($name === '' || cf_str_len($name) < 2) {
        return ['ok' => false, 'message' => 'Geçerli bir isim girin.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Geçerli bir e-posta girin.'];
    }
    if (cf_str_len($pass) < 8) {
        return ['ok' => false, 'message' => 'Şifre en az 8 karakter olmalı.'];
    }
    if ($pass !== $pass2) {
        return ['ok' => false, 'message' => 'Şifreler eşleşmiyor.'];
    }

    $exists = db_one('SELECT id FROM ' . t('users') . ' WHERE email = :e', [':e' => $email]);
    if ($exists) {
        return ['ok' => false, 'message' => 'Bu e-posta ile bir hesap zaten var.'];
    }

    $trialEnds = date('Y-m-d', strtotime('+' . CF_TRIAL_DAYS . ' days'));

    $userId = db_insert(
        'INSERT INTO ' . t('users') . ' (name, email, phone, password, currency, trial_ends_at, status, created_at)
         VALUES (:n, :e, :ph, :p, :c, :t, "active", NOW())',
        [
            ':n'  => $name,
            ':e'  => $email,
            ':ph' => $phone ?: null,
            ':p'  => password_hash($pass, CF_PASSWORD_ALGO),
            ':c'  => CF_DEFAULT_CURRENCY,
            ':t'  => $trialEnds,
        ]
    );

    // Otomatik trial aboneliği aç
    subscription_start_trial($userId);

    audit('user.register', $userId);

    return ['ok' => true, 'user_id' => $userId];
}

/* --------------------------------------------------------------------------
 * Throttle (giriş deneme limiti)
 * -------------------------------------------------------------------------- */
function auth_throttle_check(string $email, string $area): void
{
    $cnt = db_one(
        'SELECT COUNT(*) AS c FROM ' . t('login_attempts') . '
         WHERE email = :e AND area = :a AND ok = 0
           AND created_at > (NOW() - INTERVAL :m MINUTE)',
        [':e' => cf_str_lower($email), ':a' => $area, ':m' => CF_LOGIN_LOCK_MIN]
    );
    if ($cnt && (int)$cnt['c'] >= CF_LOGIN_MAX_TRY) {
        http_response_code(429);
        die('Çok fazla başarısız giriş. Lütfen ' . CF_LOGIN_LOCK_MIN . ' dakika sonra tekrar deneyin.');
    }
}

function auth_throttle_record(string $email, bool $ok, string $area): void
{
    db_exec(
        'INSERT INTO ' . t('login_attempts') . ' (email, ip, ok, area, created_at)
         VALUES (:e, :ip, :ok, :a, NOW())',
        [
            ':e'  => cf_str_lower($email),
            ':ip' => client_ip(),
            ':ok' => $ok ? 1 : 0,
            ':a'  => $area,
        ]
    );
    // 30 günden eski kayıtları temizle (lazy)
    if (random_int(1, 100) === 1) {
        db_exec('DELETE FROM ' . t('login_attempts') . ' WHERE created_at < (NOW() - INTERVAL 30 DAY)');
    }
}
