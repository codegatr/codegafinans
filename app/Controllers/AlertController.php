<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Finance;

final class AlertController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        app()->render('alerts/index', [
            'title' => 'Akilli Uyarilar',
            'alerts' => Finance::smartAlerts((int) $user['id']),
        ]);
    }

    public function read(): void
    {
        $this->postOnly();
        redirect('/alerts');
    }
}
