<?php

declare(strict_types=1);

/**
 * Amorçage de l'application : autoloader, configuration, session, erreurs.
 * Inclus en tête de chaque point d'entrée (public/index.php).
 */

// --- Autoloader Composer (PHPMailer, PSR-4 App\, helpers) ------------
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_readable($autoload)) {
    http_response_code(500);
    exit('Dépendances non installées. Lancez "composer install".');
}
require $autoload;

// --- Configuration (lit .env) ---------------------------------------
$GLOBALS['config'] = require dirname(__DIR__) . '/config/config.php';

// --- Gestion des erreurs --------------------------------------------
if ($GLOBALS['config']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

// --- Session sécurisée ----------------------------------------------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $secure,
    'samesite' => 'Lax',
]);
session_start();

// --- Connexion DB (chargée à la demande via App\db()) ---------------
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/Users/UserRepository.php';
require __DIR__ . '/Mail/Mailer.php';
require __DIR__ . '/Mail/templates.php';

// --- Aide au rendu des vues -----------------------------------------

/**
 * Rend une vue dans le gabarit principal (layout) et l'envoie au client.
 *
 * @param string               $template Nom de fichier dans /templates (sans .php)
 * @param array<string, mixed> $data     Variables exposées à la vue
 */
function view(string $template, array $data = [], string $title = 'ECOWASmail Admin'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/templates/' . $template . '.php';
    $content = ob_get_clean();
    require dirname(__DIR__) . '/templates/layout.php';
}

/** Rend une vue SANS le layout (pages autonomes : login). */
function view_bare(string $template, array $data = [], string $title = 'ECOWASmail Admin'): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/templates/' . $template . '.php';
}
