<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\User;

final class AuthController extends Controller
{
    public function login(): void
    {
        if (auth_user()) {
            redirect('/dashboard');
        }
        app()->render('auth/login', ['title' => 'Giris'], 'layouts/auth');
    }

    public function authenticate(): void
    {
        $this->postOnly();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->rememberOld();
            Session::flash('error', 'E-posta veya sifre hatali.');
            redirect('/login');
        }

        session_regenerate_id(true);
        Session::put('user', User::publicUser($user));
        redirect('/dashboard');
    }

    public function register(): void
    {
        if (auth_user()) {
            redirect('/dashboard');
        }
        app()->render('auth/register', ['title' => 'Kayit Ol'], 'layouts/auth');
    }

    public function store(): void
    {
        $this->postOnly();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $this->rememberOld();
            Session::flash('error', 'Ad, gecerli e-posta ve en az 6 karakter sifre zorunludur.');
            redirect('/register');
        }

        if (User::findByEmail($email)) {
            $this->rememberOld();
            Session::flash('error', 'Bu e-posta ile kayit zaten var.');
            redirect('/register');
        }

        $user = User::create($name, $email, password_hash($password, PASSWORD_DEFAULT), (float) ($_POST['monthly_income'] ?? 0));
        session_regenerate_id(true);
        Session::put('user', User::publicUser($user));
        redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->postOnly();
        Session::logout();
        redirect('/');
    }
}
