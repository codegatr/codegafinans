<?php
/**
 * CODEGA Finans - Yardımcı Fonksiyonlar
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/* --------------------------------------------------------------------------
 * Oturum
 * -------------------------------------------------------------------------- */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(CF_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => CF_SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Idle timeout
    $now = time();
    if (isset($_SESSION['_last']) && ($now - (int)$_SESSION['_last']) > CF_SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['_last'] = $now;
}

/* --------------------------------------------------------------------------
 * Output helpers
 * -------------------------------------------------------------------------- */
function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cf_str_lower(string $v): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
}

function cf_str_len(string $v): int
{
    return function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') : strlen($v);
}

function cf_str_sub(string $v, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($v, $start, null, 'UTF-8') : mb_substr($v, $start, $length, 'UTF-8');
    }
    return $length === null ? substr($v, $start) : substr($v, $start, $length);
}

function cf_initial(string $v): string
{
    $v = trim($v);
    if ($v === '') {
        return '?';
    }
    $first = cf_str_sub($v, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($first, 'UTF-8') : strtoupper($first);
}

function redirect(string $path): never
{
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Location: ' . $path);
    exit;
}

function json_out(array $data, int $status = 200): never
{
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* --------------------------------------------------------------------------
 * CSRF
 * -------------------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), (string)$sent)) {
        http_response_code(419);
        die('Oturum süresi dolmuş veya istek geçersiz. Lütfen sayfayı yenileyin.');
    }
}

/* --------------------------------------------------------------------------
 * Para / sayı formatlama
 * -------------------------------------------------------------------------- */
function money(float|int|string|null $v, string $cur = 'TL'): string
{
    return number_format((float)$v, 2, ',', '.') . ' ' . $cur;
}

function money_raw(float|int|string|null $v): string
{
    return number_format((float)$v, 2, ',', '.');
}

function pct(float|int $v): string
{
    return number_format((float)$v, 1, ',', '.') . '%';
}

function tr_date(?string $d): string
{
    if (!$d) return '-';
    $ts = strtotime($d);
    if ($ts === false) return e($d);
    $aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
    return date('d', $ts) . ' ' . $aylar[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function tr_datetime(?string $d): string
{
    if (!$d) return '-';
    $ts = strtotime($d);
    return $ts === false ? e($d) : date('d.m.Y H:i', $ts);
}

function tr_month(string $ym): string
{
    if (!preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) return e($ym);
    $aylar = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    return $aylar[(int)$m[2] - 1] . ' ' . $m[1];
}

/* --------------------------------------------------------------------------
 * URL helpers
 * -------------------------------------------------------------------------- */
function url(string $path = '/'): string
{
    return rtrim(CF_APP_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/')) . '?v=' . CF_VERSION;
}

function active(string $needle): string
{
    $req = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($req, $needle) ? ' active' : '';
}

/* --------------------------------------------------------------------------
 * Flash mesaj
 * -------------------------------------------------------------------------- */
function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) { start_session(); }
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) { start_session(); }
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* --------------------------------------------------------------------------
 * Eski input
 * -------------------------------------------------------------------------- */
function old(string $key, mixed $default = ''): mixed
{
    if (session_status() !== PHP_SESSION_ACTIVE) { start_session(); }
    $old = $_SESSION['_old'] ?? [];
    return $old[$key] ?? $default;
}

function remember_old(array $data): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) { start_session(); }
    unset($data['password'], $data['password_confirmation'], $data['_token']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) { start_session(); }
    unset($_SESSION['_old']);
}

/* --------------------------------------------------------------------------
 * IP / kullanıcı bilgisi
 * -------------------------------------------------------------------------- */
function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', (string)$_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function user_agent(): string
{
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
}

/* --------------------------------------------------------------------------
 * Audit log
 * -------------------------------------------------------------------------- */
function audit(string $action, ?int $userId = null, ?int $adminId = null, ?string $meta = null): void
{
    try {
        db_exec(
            'INSERT INTO ' . t('audit_log') .
            ' (user_id, admin_id, action, ip, ua, meta, created_at)
              VALUES (:u, :a, :act, :ip, :ua, :m, NOW())',
            [
                ':u'   => $userId,
                ':a'   => $adminId,
                ':act' => substr($action, 0, 80),
                ':ip'  => client_ip(),
                ':ua'  => user_agent(),
                ':m'   => $meta !== null ? substr($meta, 0, 2000) : null,
            ]
        );
    } catch (Throwable $e) {
        // Tablo yoksa veya yazılamadıysa sessiz geç
    }
}

/* --------------------------------------------------------------------------
 * Validation kısa yolları
 * -------------------------------------------------------------------------- */
function s(mixed $v, int $max = 255): string
{
    $v = is_string($v) ? trim($v) : '';
    return cf_str_sub($v, 0, $max);
}

function intval_safe(mixed $v, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
{
    $i = (int)$v;
    return max($min, min($max, $i));
}

function money_in(mixed $v): float
{
    if (is_string($v)) {
        $v = str_replace(['.',  ' '], '', $v);
        $v = str_replace(',', '.', $v);
    }
    return max(0.0, (float)$v);
}
