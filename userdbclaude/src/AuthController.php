<?php

declare(strict_types=1);

namespace App;

use function view_bare;
use function redirect;
use function flash;
use function csrf_verify;

/** Connexion / déconnexion au panneau d'administration. */
final class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('index.php?action=users.list');
        }
        view_bare('login', [], 'Connexion');
    }

    public function login(): void
    {
        csrf_verify();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '' || !Auth::attempt($username, $password)) {
            flash('error', 'Identifiants invalides.');
            redirect('index.php?action=login');
        }

        flash('success', 'Bienvenue, ' . Auth::username() . '.');
        redirect('index.php?action=users.list');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('index.php?action=login');
    }
}
