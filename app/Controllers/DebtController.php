<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\Finance;

final class DebtController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        app()->render('debts/index', [
            'title' => 'Borc Kontrolu',
            'debts' => Finance::debts((int) $user['id']),
        ]);
    }

    public function store(): void
    {
        $this->postOnly();
        $user = $this->requireAuth();
        Finance::createDebt((int) $user['id'], $_POST);
        Session::flash('success', 'Borc kaydedildi.');
        redirect('/debts');
    }

    public function pay(): void
    {
        $this->postOnly();
        $user = $this->requireAuth();
        Finance::payDebt((int) $user['id'], (int) ($_POST['id'] ?? 0), (float) ($_POST['amount'] ?? 0));
        redirect('/debts');
    }
}
