<?php

declare(strict_types=1);

/**
 * Fonctions utilitaires globales, chargées par l'autoloader Composer.
 *
 * Volontairement procédurales et sans namespace pour rester simples à
 * appeler depuis les vues (e(), csrf_field(), old()...).
 */

/**
 * Échappe une valeur pour un affichage HTML sûr (anti-XSS).
 * Accepte string|int|float|null car PDO (mode natif) renvoie les colonnes
 * numériques comme des entiers — et strict_types refuserait un int typé string.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirige vers une URL interne puis stoppe le script. */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Décode un identifiant (uname) en informations mail.
 *
 * Remplace les ~300 lignes dupliquées du legacy. La règle réelle est simple :
 *  - uname contient "@"  → c'est déjà l'adresse complète ; le domaine est la
 *    partie après "@" (ex. "jdoe@waemu.ecowas.int").
 *  - uname sans "@"      → compte de la Commission ; on ajoute "@ecowas.int".
 *
 * @return array{cemail:string, domaine:string, mail_url:string}
 */
function parse_uname(string $uname): array
{
    // Comme movespace() du legacy : un identifiant ne contient pas d'espaces.
    $uname = str_replace(' ', '', trim($uname));

    if (str_contains($uname, '@')) {
        $domaine = substr($uname, strpos($uname, '@') + 1);
        $cemail  = $uname;
    } else {
        $domaine = $GLOBALS['config']['default_domain'] ?? 'ecowas.int';
        $cemail  = $uname . '@' . $domaine;
    }

    return [
        'cemail'   => $cemail,
        'domaine'  => $domaine,
        'mail_url' => 'https://mail.' . $domaine,
    ];
}

/* ------------------------------------------------------------------ *
 *  Protection CSRF
 * ------------------------------------------------------------------ */

/** Retourne (en le créant au besoin) le jeton CSRF de la session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Champ caché à insérer dans chaque formulaire POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Vérifie le jeton CSRF d'une requête POST ; coupe la requête si invalide. */
function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expirée ou requête non autorisée. Rechargez la page.');
    }
}

/* ------------------------------------------------------------------ *
 *  Messages flash (notifications one-shot après redirection)
 * ------------------------------------------------------------------ */

/** Enregistre un message flash de type "success" | "error" | "info". */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Récupère et vide les messages flash. */
function flash_pull(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ------------------------------------------------------------------ *
 *  Aide formulaires
 * ------------------------------------------------------------------ */

/** Valeur précédemment saisie (réaffichage de formulaire après erreur). */
function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

/** Mémorise les valeurs saisies pour réaffichage après une redirection. */
function remember_old(array $data): void
{
    unset($data['_csrf'], $data['pwd']);
    $_SESSION['old'] = $data;
}

/** Vide les anciennes valeurs (en début de rendu de formulaire propre). */
function clear_old(): void
{
    unset($_SESSION['old']);
}

/** Date du jour au format AAAAMMJJ (champ dmaj, conservé du legacy). */
function dmaj_today(): string
{
    return date('Ymd');
}

/** Formate un dmaj (AAAAMMJJ) en JJ/MM/AAAA lisible. */
function fmt_dmaj(?string $ymd): string
{
    if (!$ymd || !preg_match('/^\d{8}$/', $ymd)) {
        return '—';
    }
    return substr($ymd, 6, 2) . '/' . substr($ymd, 4, 2) . '/' . substr($ymd, 0, 4);
}

/** Formate un DATETIME SQL en JJ/MM/AAAA lisible (ou tiret si absent). */
function fmt_datetime(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts ? date('d/m/Y', $ts) : '—';
}

/**
 * Génère un code utilisateur court et lisible : "ECW-XXXXXX".
 *
 * Alphabet sans caractères ambigus (pas de 0/O, 1/I/L) pour éviter les
 * confusions de lecture. L'unicité est garantie par le repository, qui
 * réessaie en cas (très rare) de collision.
 */
function generate_user_code(string $prefix = 'ECW', int $length = 6): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $max  = strlen($alphabet) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }
    return $prefix . '-' . $code;
}
