<?php
/**
 * CODEGA Finans - Yönetici kimlik doğrulama
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

function admin_login(string $email, string $password): array
{
    auth_throttle_check($email, 'admin');

    $admin = db_one(
        'SELECT * FROM ' . t('admins') . ' WHERE email = :e LIMIT 1',
        [':e' => cf_str_lower($email)]
    );

    if (!$admin || !password_verify($password, $admin['password']) || (int)$admin['is_active'] !== 1) {
        auth_throttle_record($email, false, 'admin');
        return ['ok' => false, 'message' => 'E-posta veya şifre hatalı.'];
    }

    auth_throttle_record($email, true, 'admin');

    db_exec(
        'UPDATE ' . t('admins') . ' SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id',
        [':ip' => client_ip(), ':id' => $admin['id']]
    );

    start_session();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_role'] = $admin['role'];

    audit('admin.login', null, (int)$admin['id']);

    return ['ok' => true, 'admin' => $admin];
}

function admin_logout(): void
{
    start_session();
    $aid = $_SESSION['admin_id'] ?? null;
    if ($aid) { audit('admin.logout', null, (int)$aid); }
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
    session_regenerate_id(true);
}

function admin_user(): ?array
{
    start_session();
    if (empty($_SESSION['admin_id'])) { return null; }
    static $cache = null;
    if ($cache !== null && $cache['id'] === $_SESSION['admin_id']) { return $cache; }
    $a = db_one('SELECT * FROM ' . t('admins') . ' WHERE id = :id LIMIT 1',
                [':id' => (int)$_SESSION['admin_id']]);
    if (!$a) { admin_logout(); return null; }
    $cache = $a;
    return $a;
}

function admin_require(): array
{
    $a = admin_user();
    if (!$a) {
        redirect('/admin/login.php');
    }
    return $a;
}

function admin_require_role(string ...$roles): array
{
    $a = admin_require();
    if (!in_array($a['role'], $roles, true)) {
        http_response_code(403);
        die('Bu işlem için yetkiniz yok.');
    }
    return $a;
}
