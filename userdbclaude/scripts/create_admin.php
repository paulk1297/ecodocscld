<?php

declare(strict_types=1);

/**
 * Crée (ou met à jour) un administrateur du panneau.
 *
 * Usage en ligne de commande :
 *     php scripts/create_admin.php <username> <password> [admin|reader]
 *
 * Exemple :
 *     php scripts/create_admin.php paulk "MonMotDePasse" admin
 */

if (PHP_SAPI !== 'cli') {
    exit("Ce script doit être lancé en ligne de commande.\n");
}

require dirname(__DIR__) . '/vendor/autoload.php';
$GLOBALS['config'] = require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/src/db.php';

use function App\db;

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';
$role     = $argv[3] ?? 'admin';

if ($username === '' || $password === '') {
    exit("Usage : php scripts/create_admin.php <username> <password> [admin|reader]\n");
}
if (!in_array($role, ['admin', 'reader'], true)) {
    exit("Le rôle doit être 'admin' ou 'reader'.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Upsert portable (MySQL + SQLite) : on teste l'existence puis insert/update.
$exist = db()->prepare('SELECT id FROM admins WHERE username = ?');
$exist->execute([$username]);
$id = $exist->fetchColumn();

if ($id !== false) {
    db()->prepare('UPDATE admins SET pwd_hash = ?, role = ? WHERE id = ?')
        ->execute([$hash, $role, $id]);
} else {
    db()->prepare('INSERT INTO admins (username, pwd_hash, role) VALUES (?, ?, ?)')
        ->execute([$username, $hash, $role]);
}

echo "Administrateur « {$username} » ({$role}) enregistré.\n";
