<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\Finance;

final class BudgetController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        app()->render('budgets/index', [
            'title' => 'Butceler',
            'budgets' => Finance::budgets((int) $user['id']),
        ]);
    }

    public function store(): void
    {
        $this->postOnly();
        $user = $this->requireAuth();
        Finance::createBudget((int) $user['id'], $_POST);
        Session::flash('success', 'Butce limiti kaydedildi.');
        redirect('/budgets');
    }
}
