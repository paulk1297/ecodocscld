<?php

declare(strict_types=1);

/**
 * Chargement de la configuration depuis le fichier .env.
 *
 * Retourne un tableau de configuration structuré. Aucune valeur sensible
 * n'est codée en dur : tout vient de .env (non versionné, hors web).
 */

/**
 * Mini-chargeur de fichier .env (pas de dépendance externe).
 * Les valeurs sont placées dans getenv()/$_ENV pour tout le process.
 */
function load_env(string $path): void
{
    if (!is_readable($path)) {
        http_response_code(500);
        exit('Configuration manquante : copiez .env.example en .env et renseignez-le.');
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Retire d'éventuels guillemets entourant la valeur.
        if (strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"')
             || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

/** Lit une variable d'environnement avec valeur par défaut. */
function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

load_env(dirname(__DIR__) . '/.env');

return [
    'debug' => env('APP_DEBUG', 'false') === 'true',

    'db' => [
        // 'mysql' (production) ou 'sqlite' (test local uniquement).
        'driver'      => env('DB_DRIVER', 'mysql'),
        'sqlite_path' => env('DB_SQLITE_PATH', 'database/local.sqlite'),
        'host'        => env('DB_HOST', 'localhost'),
        'name'        => env('DB_NAME'),
        'user'        => env('DB_USER'),
        'pass'        => env('DB_PASS'),
        'charset'     => env('DB_CHARSET', 'utf8mb4'),
    ],

    // Transport SMTP principal : mails destinés aux utilisateurs (Office365).
    'smtp' => [
        'host'      => env('SMTP_HOST'),
        'port'      => (int) env('SMTP_PORT', '587'),
        'secure'    => env('SMTP_SECURE', 'tls'),
        'auth'      => env('SMTP_AUTH', 'true') === 'true',
        'user'      => env('SMTP_USER'),
        'pass'      => env('SMTP_PASS'),
        'from'      => env('SMTP_FROM', 'webmaster@ecowas.int'),
        'from_name' => env('SMTP_FROM_NAME', 'ECOWASmail Admin'),
    ],

    // Transport relais interne : notifications ECOLink (serveur interne, sans auth).
    'relay' => [
        'host' => env('RELAY_HOST'),
        'port' => (int) env('RELAY_PORT', '25'),
        'auth' => env('RELAY_AUTH', 'false') === 'true',
        'user' => env('RELAY_USER'),
    ],

    'ecolink_officer' => env('ECOLINK_OFFICER', 'aoderinde@ecowas.int'),
    'default_domain'  => env('DEFAULT_DOMAIN', 'ecowas.int'),
];
