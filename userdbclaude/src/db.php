<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

/**
 * Fournit une connexion PDO unique (singleton) à la base MySQL.
 *
 * Toutes les requêtes de l'application passent par cette connexion en
 * utilisant des requêtes PRÉPARÉES — c'est ce qui élimine les injections
 * SQL omniprésentes dans le legacy.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $cfg = $GLOBALS['config']['db'];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if (($cfg['driver'] ?? 'mysql') === 'sqlite') {
                // --- Mode test local (SQLite) ---
                $path = self::resolveSqlitePath($cfg['sqlite_path']);
                self::$instance = new PDO('sqlite:' . $path, null, null, $options);
                self::$instance->exec('PRAGMA foreign_keys = ON');
            } else {
                // --- Production (MySQL / MariaDB) ---
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    $cfg['host'],
                    $cfg['name'],
                    $cfg['charset']
                );
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            $msg = ($GLOBALS['config']['debug'] ?? false)
                ? 'Connexion DB échouée : ' . $e->getMessage()
                : 'Service momentanément indisponible.';
            exit($msg);
        }

        return self::$instance;
    }

    /** Résout le chemin du fichier SQLite (relatif → racine du projet). */
    private static function resolveSqlitePath(string $path): string
    {
        if ($path === ':memory:') {
            return $path;
        }
        // Absolu (Unix /... ou Windows C:\...) ? On garde tel quel, sinon on
        // le rend relatif à la racine du projet.
        if (!preg_match('#^(/|[A-Za-z]:[\\\\/])#', $path)) {
            $path = dirname(__DIR__) . '/' . $path;
        }
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $path;
    }
}

/** Raccourci pratique pour récupérer la connexion PDO. */
function db(): PDO
{
    return Database::connection();
}
