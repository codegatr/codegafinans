<?php
/**
 * CODEGA Finans - Yönetici paneli üst layout
 */
require_once __DIR__ . '/admin_auth.php';

$__pageTitle  = $pageTitle  ?? 'Yönetim Paneli';
$__pageHeader = $pageHeader ?? $__pageTitle;
$__admin      = admin_user();

start_session();
$flashes = flash_pull();
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<meta name="robots" content="noindex, nofollow">
<title><?= e($__pageTitle) ?> · Yönetim · <?= e(CF_APP_NAME) ?></title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="cf-admin">
<div class="cf-app">
    <?php require __DIR__ . '/admin_nav.php'; ?>
    <div class="cf-backdrop"></div>
    <main class="cf-main">
        <header class="cf-topbar">
            <div class="left" style="display:flex;align-items:center;gap:14px;">
                <button class="cf-burger" aria-label="Menü">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.4" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1><?= e($__pageHeader) ?><span class="cf-admin-flag">Yönetici</span></h1>
            </div>
            <div class="right">
                <a href="/" target="_blank" style="font-size:13px;color:#475569;">→ Siteye git</a>
                <?php if ($__admin): ?>
                    <span class="cf-userchip">
                        <span class="avatar" style="background:linear-gradient(135deg,#b45309,#f59e0b);color:#422006">
                            <?= e(cf_initial($__admin['name'])) ?>
                        </span>
                        <?= e($__admin['name']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>
        <div class="cf-content">
            <?php foreach ($flashes as $f): ?>
                <div class="cf-flash <?= e($f['type']) ?>" data-auto><?= e($f['message']) ?></div>
            <?php endforeach; ?>
