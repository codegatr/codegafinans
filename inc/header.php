<?php
/**
 * CODEGA Finans - Kullanıcı paneli üst layout
 */
require_once __DIR__ . '/auth.php';

$__pageTitle  = $pageTitle  ?? 'CODEGA Finans';
$__pageHeader = $pageHeader ?? $__pageTitle;
$__user       = auth_user();

start_session();
$flashes = flash_pull();
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<title><?= e($__pageTitle) ?> · <?= e(CF_APP_NAME) ?></title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="cf-app">
    <?php require __DIR__ . '/nav.php'; ?>
    <div class="cf-backdrop"></div>
    <main class="cf-main">
        <header class="cf-topbar">
            <div class="left" style="display:flex;align-items:center;gap:14px;">
                <button class="cf-burger" aria-label="Menü">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.4" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1><?= e($__pageHeader) ?></h1>
            </div>
            <div class="right">
                <?php if ($__user): ?>
                    <a href="/alerts.php" title="Uyarılar"
                       style="color:#475569;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        Uyarılar
                    </a>
                    <span class="cf-userchip">
                        <span class="avatar"><?= e(cf_initial($__user['name'])) ?></span>
                        <?= e($__user['name']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>
        <div class="cf-content">
            <?php foreach ($flashes as $f): ?>
                <div class="cf-flash <?= e($f['type']) ?>" data-auto><?= e($f['message']) ?></div>
            <?php endforeach; ?>