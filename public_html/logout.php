<?php
/**
 * CODEGA Finans - Çıkış
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';

auth_logout();
flash('success', 'Çıkış yaptınız. Tekrar görüşmek üzere.');
redirect('/');
