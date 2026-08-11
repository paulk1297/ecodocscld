<?php

declare(strict_types=1);

namespace App;

use PDO;

// db() est défini dans le même namespace (App\db) : appel direct, sans import.
use function redirect;
use function flash;

/**
 * Authentification au panneau d'administration.
 *
 * Remplace l'ancien contrôle bricolé ($_SESSION['bien'] == 1 + comparaison
 * du HTTP_REFERER non fonctionnelle) par une vraie session authentifiée
 * adossée à la table `admins` (mots de passe hashés Argon2/bcrypt).
 */
final class Auth
{
    /** Tente la connexion ; retourne true si OK, sinon false. */
    public static function attempt(string $username, string $password): bool
    {
        $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['pwd_hash'])) {
            return false;
        }

        // Réhash si l'algorithme par défaut a évolué.
        if (password_needs_rehash($admin['pwd_hash'], PASSWORD_DEFAULT)) {
            $up = db()->prepare('UPDATE admins SET pwd_hash = ? WHERE id = ?');
            $up->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
        }

        // CURRENT_TIMESTAMP est portable MySQL + SQLite (contrairement à NOW()).
        db()->prepare('UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$admin['id']]);

        // Régénère l'ID de session pour prévenir la fixation de session.
        session_regenerate_id(true);

        $_SESSION['admin'] = [
            'id'       => (int) $admin['id'],
            'username' => $admin['username'],
            'role'     => $admin['role'],
        ];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['admin']);
    }

    /** @return array{id:int,username:string,role:string}|null */
    public static function user(): ?array
    {
        return $_SESSION['admin'] ?? null;
    }

    public static function username(): string
    {
        return $_SESSION['admin']['username'] ?? '';
    }

    /** Le rôle "admin" voit et fait tout ; "reader" a une vue réduite. */
    public static function isAdmin(): bool
    {
        return (($_SESSION['admin']['role'] ?? '') === 'admin');
    }

    /** Exige une session authentifiée, sinon redirige vers le login. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('index.php?action=login');
        }
    }

    /** Exige le rôle admin pour les actions sensibles (création, suppression, mails). */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            flash('error', "Action réservée aux administrateurs.");
            redirect('index.php?action=users.list');
        }
    }
}
