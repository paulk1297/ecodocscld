<?php

declare(strict_types=1);

/**
 * Formulaire de création / édition d'un utilisateur.
 * Variables : $mode ('create'|'edit'), $user (array|null)
 */
$isEdit = ($mode === 'edit');
$u = $user ?? [];
$val = fn(string $k, $d = '') => e((string) ($u[$k] ?? $d));
$checked = fn(string $k) => !empty($u[$k]) ? 'checked' : '';
$action  = $isEdit ? 'users.update' : 'users.create';
?>
<section class="page-head">
    <div>
        <h1><?= $isEdit ? 'Modifier un utilisateur' : 'Nouvel utilisateur' ?></h1>
        <p class="muted"><?= $isEdit ? e($u['nom'] ?? '') : 'Créer un compte ECOWASmail' ?></p>
    </div>
    <a href="index.php?action=users.list" class="btn btn--ghost"><i data-lucide="arrow-left"></i> Retour</a>
</section>

<form method="POST" action="index.php?action=<?= $action ?>" class="card form" id="userForm">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="userid" value="<?= $val('userid') ?>">
    <?php endif; ?>

    <div class="form-grid">
        <label class="field">
            <span>Code utilisateur</span>
            <?php if ($isEdit): ?>
                <input type="text" value="<?= $val('codeUser') ?>" readonly class="readonly">
                <small class="hint">Identifiant unique, non modifiable.</small>
            <?php else: ?>
                <input type="text" value="Généré automatiquement" readonly class="readonly" disabled>
                <small class="hint">Un code unique (ex. <code>ECW-7F3A9C</code>) sera attribué à la création.</small>
            <?php endif; ?>
        </label>
        <?php if ($isEdit && !empty($u['created_at'])): ?>
        <label class="field">
            <span>Date de création</span>
            <input type="text" value="<?= e(fmt_datetime($u['created_at'])) ?>" readonly class="readonly">
        </label>
        <?php endif; ?>
        <label class="field">
            <span>Nom complet <em>*</em></span>
            <input type="text" name="nom" value="<?= $val('nom', old('nom')) ?>" required>
        </label>
        <label class="field">
            <span>Poste / Département</span>
            <input type="text" name="poste" value="<?= $val('poste', old('poste')) ?>">
        </label>
        <label class="field">
            <span>Username (identifiant) <em>*</em></span>
            <input type="text" name="uname" value="<?= $val('uname', old('uname')) ?>"
                   placeholder="prenom.nom ou prenom.nom@sigle.ecowas.int" required>
            <small class="hint">Sans « @ » → compte Commission (<code>@ecowas.int</code>).
                Avec « @sigle.ecowas.int » → autre institution.</small>
        </label>
        <label class="field">
            <span>Autre e-mail (personnel)</span>
            <input type="email" name="oemail" value="<?= $val('otheremail', old('oemail')) ?>">
        </label>
        <label class="field">
            <span>Institution ID</span>
            <input type="text" name="instidu" value="<?= $val('instidu', old('instidu')) ?>">
        </label>

        <?php if (!$isEdit): ?>
        <label class="field">
            <span>Mot de passe initial <em>*</em></span>
            <input type="text" name="pwd" value="" required autocomplete="off">
            <small class="hint">Envoyé une seule fois par e-mail, puis stocké hashé.</small>
        </label>
        <?php endif; ?>

        <label class="field">
            <span>Commission</span>
            <input type="text" name="commission" value="<?= $val('commission', old('commission')) ?>">
        </label>
        <label class="field">
            <span>Département</span>
            <input type="text" name="dept" value="<?= $val('dept', old('dept')) ?>">
        </label>
        <label class="field field--full">
            <span>Observations</span>
            <textarea name="obs" rows="2"><?= $val('obs', old('obs')) ?></textarea>
        </label>
    </div>

    <fieldset class="toggles">
        <legend>Options</legend>
        <label class="switch"><input type="checkbox" name="maj" value="1" <?= $checked('maj') ?>><span>MAJ</span></label>
        <label class="switch"><input type="checkbox" name="sap" value="1" <?= $checked('sap') ?>><span>SAP / ECOLink</span></label>
    </fieldset>

    <div class="form-actions">
        <a href="index.php?action=users.list" class="btn btn--ghost">Annuler</a>
        <button type="submit" class="btn btn--primary">
            <i data-lucide="<?= $isEdit ? 'save' : 'user-plus' ?>"></i>
            <?= $isEdit ? 'Enregistrer' : 'Créer et envoyer l\'e-mail' ?>
        </button>
    </div>
</form>
