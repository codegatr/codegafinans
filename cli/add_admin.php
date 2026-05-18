<?php
/**
 * CODEGA Finans - CLI: Yönetici hesabı ekle/güncelle.
 *
 * Kullanım:
 *   php cli/add_admin.php "Yunus AKSOY" yunus@codega.com.tr "şifre123" superadmin
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only.');
}

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/migrate.php';
require_once __DIR__ . '/../inc/functions.php';

// Önce migration'ları çalıştır (admins tablosu olmayabilir)
cf_migrate_all();

$name  = $argv[1] ?? null;
$email = $argv[2] ?? null;
$pass  = $argv[3] ?? null;
$role  = $argv[4] ?? 'admin';

if (!$name || !$email || !$pass) {
    echo "Kullanım: php cli/add_admin.php \"Ad Soyad\" e-posta şifre [rol]\n";
    echo "Rol: superadmin | admin | viewer (vars: admin)\n";
    exit(1);
}
if (!in_array($role, ['superadmin','admin','viewer'], true)) {
    echo "Geçersiz rol: {$role}\n";
    exit(1);
}

$email = cf_str_lower(trim($email));
$hash  = password_hash($pass, PASSWORD_BCRYPT);

$existing = db_one('SELECT id FROM ' . t('admins') . ' WHERE email = :e', [':e' => $email]);
if ($existing) {
    db_exec(
        'UPDATE ' . t('admins') . '
            SET name = :n, password = :p, role = :r, is_active = 1
          WHERE id = :id',
        [':n' => $name, ':p' => $hash, ':r' => $role, ':id' => $existing['id']]
    );
    echo "Mevcut yönetici güncellendi (id={$existing['id']}): {$email}\n";
} else {
    $id = db_insert(
        'INSERT INTO ' . t('admins') . ' (name, email, password, role, is_active, created_at)
         VALUES (:n, :e, :p, :r, 1, NOW())',
        [':n' => $name, ':e' => $email, ':p' => $hash, ':r' => $role]
    );
    echo "Yönetici eklendi (id={$id}): {$email} · rol={$role}\n";
}
echo "Giriş: " . (defined('CF_APP_URL') ? CF_APP_URL : 'https://...') . "/admin/login.php\n";
