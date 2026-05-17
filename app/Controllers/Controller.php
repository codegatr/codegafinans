<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;

abstract class Controller
{
    protected function requireAuth(): array
    {
        $user = auth_user();
        if (!$user) {
            redirect('/login');
        }
        return $user;
    }

    protected function postOnly(): void
    {
        Session::verifyCsrf();
    }

    protected function rememberOld(): void
    {
        Session::put('old', $_POST);
    }
}
