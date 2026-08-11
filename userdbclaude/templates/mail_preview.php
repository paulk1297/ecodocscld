<?php

declare(strict_types=1);

/**
 * Aperçu (sans envoi) du message d'identifiants.
 * Variables : $user, $info, $preview (HTML du message)
 */
?>
<section class="page-head">
    <div>
        <h1>Aperçu du message</h1>
        <p class="muted"><?= e($user['nom']) ?> · <?= e($info['cemail']) ?></p>
    </div>
    <a href="index.php?action=users.list" class="btn btn--ghost"><i data-lucide="arrow-left"></i> Retour</a>
</section>

<div class="card">
    <dl class="recap">
        <div><dt>From</dt><dd><?= e($GLOBALS['config']['smtp']['from']) ?></dd></div>
        <div><dt>To</dt><dd><?= e($user['otheremail'] ?: $info['cemail']) ?></dd></div>
        <div><dt>CC</dt><dd><?= e($info['cemail']) ?></dd></div>
        <div><dt>Subject</dt><dd>ECOWASMAIL ACCOUNT</dd></div>
    </dl>
    <hr class="sep">
    <div class="mail-preview"><?= $preview ?></div>
    <p class="muted"><i data-lucide="info"></i> Le mot de passe réel n'est jamais affiché (stocké hashé). Cet aperçu utilise un masque.</p>
</div>
