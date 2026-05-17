<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\Finance;

final class GoalController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        app()->render('goals/index', [
            'title' => 'Tasarruf Hedefleri',
            'goals' => Finance::goals((int) $user['id']),
        ]);
    }

    public function store(): void
    {
        $this->postOnly();
        $user = $this->requireAuth();
        Finance::createGoal((int) $user['id'], $_POST);
        Session::flash('success', 'Hedef olusturuldu.');
        redirect('/goals');
    }

    public function deposit(): void
    {
        $this->postOnly();
        $user = $this->requireAuth();
        Finance::depositGoal((int) $user['id'], (int) ($_POST['id'] ?? 0), (float) ($_POST['amount'] ?? 0));
        redirect('/goals');
    }
}
