<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Finance;

final class DashboardController extends Controller
{
    public function home(): void
    {
        if (auth_user()) {
            redirect('/dashboard');
        }
        app()->render('dashboard/home', ['title' => 'Codega Finans']);
    }

    public function privacy(): void
    {
        app()->render('dashboard/privacy', ['title' => 'Gizlilik Politikasi']);
    }

    public function index(): void
    {
        $user = $this->requireAuth();
        app()->render('dashboard/index', [
            'title' => 'Panel',
            'summary' => Finance::summary((int) $user['id']),
            'transactions' => Finance::recentTransactions((int) $user['id']),
            'categories' => Finance::categoryBreakdown((int) $user['id']),
            'rates' => Finance::rates(),
            'alerts' => Finance::smartAlerts((int) $user['id']),
        ]);
    }
}
