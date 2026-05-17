<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\Finance;

final class TransactionController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        app()->render('transactions/index', [
            'title' => 'Gelir Gider',
            'transactions' => Finance::transactions((int) $user['id']),
        ]);
    }

    public function store(): void
    {
        $this->postOnly();
        $user = $this->requireAuth();
        Finance::createTransaction((int) $user['id'], $_POST);
        Session::flash('success', 'Hareket kaydedildi.');
        redirect('/transactions');
    }
}
