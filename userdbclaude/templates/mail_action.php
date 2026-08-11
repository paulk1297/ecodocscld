<?php

declare(strict_types=1);

/**
 * Écran de confirmation d'une action e-mail.
 * Variables : $type, $meta (label,needsPwd,updatePwd), $user, $info
 */
?>
<section class="page-head">
    <div>
        <h1><?= e($meta['label']) ?></h1>
        <p class="muted">Confirmer l'envoi pour <strong><?= e($user['nom']) ?></strong></p>
    </div>
    <a href="index.php?action=users.list" class="btn btn--ghost"><i data-lucide="arrow-left"></i> Retour</a>
</section>

<form method="POST" action="index.php?action=mail.send" class="card confirm">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <input type="hidden" name="id" value="<?= e($user['userid']) ?>">

    <dl class="recap">
        <div><dt>Destinataire</dt><dd><?= e($info['cemail']) ?></dd></div>
        <?php if (!empty($user['otheremail'])): ?>
            <div><dt>Copie (perso)</dt><dd><?= e($user['otheremail']) ?></dd></div>
        <?php endif; ?>
        <div><dt>Poste</dt><dd><?= e($user['poste']) ?></dd></div>
        <div><dt>Action</dt><dd><?= e($meta['label']) ?></dd></div>
    </dl>

    <?php if ($meta['needsPwd']): ?>
        <label class="field">
            <span>Mot de passe <em>*</em></span>
            <input type="text" name="pwd" required autocomplete="off"
                   placeholder="Mot de passe défini sur le serveur mail">
            <small class="hint">
                <?= $meta['updatePwd']
                    ? 'Ce mot de passe sera envoyé à l\'utilisateur ET mémorisé (hashé).'
                    : 'Ce mot de passe sera inclus dans l\'e-mail envoyé.' ?>
            </small>
        </label>
    <?php else: ?>
        <p class="muted"><i data-lucide="info"></i> Cette notification ne contient pas de mot de passe.</p>
    <?php endif; ?>

    <div class="form-actions">
        <a href="index.php?action=users.list" class="btn btn--ghost">Annuler</a>
        <button type="submit" class="btn btn--primary">
            <i data-lucide="send"></i> Envoyer l'e-mail
        </button>
    </div>
</form>
