<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\User;

final class SettingController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        app()->render('settings/index', ['title' => 'Ayarlar', 'user' => $user]);
    }

    public function update(): void
    {
        $this->postOnly();
        $user = $this->requireAuth();
        $updated = User::updateProfile((int) $user['id'], trim((string) ($_POST['name'] ?? '')), (float) ($_POST['monthly_income'] ?? 0));
        Session::put('user', User::publicUser($updated));
        Session::flash('success', 'Profil guncellendi.');
        redirect('/settings');
    }
}
