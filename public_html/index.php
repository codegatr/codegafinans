<?php

declare(strict_types=1);

use App\Controllers\AlertController;
use App\Controllers\AuthController;
use App\Controllers\BudgetController;
use App\Controllers\DashboardController;
use App\Controllers\DebtController;
use App\Controllers\GoalController;
use App\Controllers\SettingController;
use App\Controllers\TransactionController;
use App\Controllers\UpdateController;
use App\Core\Router;

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();

$router->get('/', [DashboardController::class, 'home']);
$router->get('/privacy', [DashboardController::class, 'privacy']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'authenticate']);
$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'store']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/transactions', [TransactionController::class, 'index']);
$router->post('/transactions', [TransactionController::class, 'store']);
$router->get('/budgets', [BudgetController::class, 'index']);
$router->post('/budgets', [BudgetController::class, 'store']);
$router->get('/goals', [GoalController::class, 'index']);
$router->post('/goals', [GoalController::class, 'store']);
$router->post('/goals/deposit', [GoalController::class, 'deposit']);
$router->get('/debts', [DebtController::class, 'index']);
$router->post('/debts', [DebtController::class, 'store']);
$router->post('/debts/pay', [DebtController::class, 'pay']);
$router->get('/alerts', [AlertController::class, 'index']);
$router->post('/alerts/read', [AlertController::class, 'read']);
$router->get('/settings', [SettingController::class, 'index']);
$router->post('/settings', [SettingController::class, 'update']);
$router->get('/updates', [UpdateController::class, 'index']);
$router->post('/updates/apply', [UpdateController::class, 'apply']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
