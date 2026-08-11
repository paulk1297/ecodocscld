<?php

declare(strict_types=1);

/**
 * Front controller unique de l'application.
 *
 * Toutes les requêtes passent par ce fichier (seul le dossier /public est
 * exposé au web). Le routage se fait sur le paramètre `action`, ce qui
 * remplace l'ancien switch($op) géant noyé dans le HTML.
 */

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/AuthController.php';
require dirname(__DIR__) . '/src/Users/UserController.php';
require dirname(__DIR__) . '/src/Mail/MailController.php';

use App\Auth;
use App\AuthController;
use App\Users\UserController;
use App\Users\UserRepository;
use App\Mail\MailController;

$repo  = new UserRepository();
$users = new UserController($repo);
$mails = new MailController($repo);
$auth  = new AuthController();

$action = $_GET['action'] ?? 'users.list';
$method = $_SERVER['REQUEST_METHOD'];
$post   = $method === 'POST';

// Table de routage : action => callable. Les actions POST sont signalées.
switch ($action) {
    // --- Authentification ---
    case 'login':
        $post ? $auth->login() : $auth->loginForm();
        break;
    case 'logout':
        $auth->logout();
        break;

    // --- Utilisateurs ---
    case 'users.list':
        $users->index();
        break;
    case 'users.new':
        $users->createForm();
        break;
    case 'users.create':
        $post ? $users->store() : method_not_allowed();
        break;
    case 'users.edit':
        $users->editForm();
        break;
    case 'users.update':
        $post ? $users->update() : method_not_allowed();
        break;
    case 'users.delete':
        $post ? $users->delete() : method_not_allowed();
        break;

    // --- Actions e-mail ---
    case 'mail.confirm':
        $mails->confirm();
        break;
    case 'mail.send':
        $post ? $mails->send() : method_not_allowed();
        break;
    case 'mail.preview':
        $mails->preview();
        break;

    default:
        http_response_code(404);
        Auth::check() ? $users->index() : $auth->loginForm();
        break;
}

/** Réponse 405 pour une action sensible appelée en GET. */
function method_not_allowed(): never
{
    http_response_code(405);
    exit('Méthode non autorisée.');
}
