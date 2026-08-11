<?php

declare(strict_types=1);

/**
 * Étape 2 de la migration. Sur les comptes existants :
 *   1. hashe les mots de passe en clair (`pwd` → `pwd_hash`) ;
 *   2. attribue un `codeUser` unique aux comptes qui n'en ont pas.
 *
 * À lancer en ligne de commande, UNE SEULE FOIS, après 01_init.sql :
 *     php scripts/backfill_hash.php
 *
 * Idempotent : ne retouche pas les lignes déjà traitées.
 */

if (PHP_SAPI !== 'cli') {
    exit("Ce script doit être lancé en ligne de commande.\n");
}

require dirname(__DIR__) . '/vendor/autoload.php';
$GLOBALS['config'] = require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/src/db.php';

use function App\db;

// --- 1. Hash des mots de passe -------------------------------------
$rows = db()->query("SELECT userid, pwd FROM `user`
                     WHERE pwd_hash IS NULL OR pwd_hash = ''")->fetchAll();

$totalPwd = count($rows);
echo "Hash des mots de passe : {$totalPwd} compte(s) à traiter (bcrypt, ~quelques minutes)...\n";

$update = db()->prepare('UPDATE `user` SET pwd_hash = ? WHERE userid = ?');
$hashed = 0;

foreach ($rows as $r) {
    $plain = (string) $r['pwd'];
    if ($plain === '') {
        // Pas de mot de passe : on pose un hash aléatoire inutilisable.
        $plain = bin2hex(random_bytes(8));
    }
    $update->execute([password_hash($plain, PASSWORD_DEFAULT), $r['userid']]);
    $hashed++;
    if ($hashed % 100 === 0 || $hashed === $totalPwd) {
        echo "  {$hashed}/{$totalPwd} hashés\n";
    }
}

// --- 2. Attribution d'un codeUser unique ---------------------------
$noCode = db()->query("SELECT userid FROM `user`
                       WHERE codeUser IS NULL OR codeUser = ''")->fetchAll();

$totalCode = count($noCode);
echo "Attribution des codeUser : {$totalCode} compte(s) à traiter...\n";

$check    = db()->prepare('SELECT 1 FROM `user` WHERE codeUser = ? LIMIT 1');
$setCode  = db()->prepare('UPDATE `user` SET codeUser = ? WHERE userid = ?');
$coded = 0;

foreach ($noCode as $r) {
    do {
        $code = generate_user_code();
        $check->execute([$code]);
    } while ($check->fetchColumn() !== false);
    $setCode->execute([$code, $r['userid']]);
    $coded++;
    if ($coded % 200 === 0 || $coded === $totalCode) {
        echo "  {$coded}/{$totalCode} codes attribués\n";
    }
}

echo "Backfill terminé : {$hashed} mot(s) de passe hashé(s), {$coded} code(s) attribué(s).\n";
echo "Vérifiez les colonnes pwd_hash et codeUser, puis lancez sql/02_cleanup.sql.\n";
