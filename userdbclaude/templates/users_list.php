<?php

declare(strict_types=1);

use App\Auth;

/**
 * Liste des utilisateurs.
 * Variables : $result (rows,total,page,perPage,pages), $totalAll, $filters
 */
$rows    = $result['rows'];
$isAdmin = Auth::isAdmin();
$sort    = $filters['sort'] ?? 'nom';
$dir     = strtoupper($filters['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$search  = $filters['search'] ?? '';
$crit    = $filters['criteria'] ?? 'nom';

/** Construit une URL de tri en conservant les filtres courants. */
$sortUrl = function (string $col) use ($filters, $sort, $dir): string {
    $newDir = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    $q = array_merge($filters, ['action' => 'users.list', 'sort' => $col, 'dir' => $newDir]);
    return 'index.php?' . http_build_query($q);
};

/** En-tête de colonne triable. */
$th = function (string $col, string $label) use ($sortUrl, $sort, $dir): string {
    $icon = 'chevrons-up-down';
    if ($sort === $col) { $icon = $dir === 'ASC' ? 'chevron-up' : 'chevron-down'; }
    return '<th><a class="sort" href="' . e($sortUrl($col)) . '">'
         . e($label) . ' <i data-lucide="' . $icon . '"></i></a></th>';
};

$badge = fn(int $v) => $v
    ? '<span class="badge badge--on">Oui</span>'
    : '<span class="badge badge--off">—</span>';
?>

<section class="page-head">
    <div>
        <h1>Utilisateurs</h1>
        <p class="muted">
            <?= count($rows) ?> affiché(s) ·
            <strong><?= (int) $result['total'] ?></strong> filtré(s) /
            <strong><?= (int) $totalAll ?></strong> au total
        </p>
    </div>
    <?php if ($isAdmin): ?>
        <a href="index.php?action=users.new" class="btn btn--primary">
            <i data-lucide="user-plus"></i> Nouvel utilisateur
        </a>
    <?php endif; ?>
</section>

<!-- Barre de recherche serveur (par critère) -->
<form class="toolbar" method="GET" action="index.php">
    <input type="hidden" name="action" value="users.list">
    <div class="search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Rechercher…">
    </div>
    <select name="criteria" class="select">
        <?php foreach (['nom' => 'Nom', 'poste' => 'Poste', 'uname' => 'Username', 'codeUser' => 'Code', 'instidu' => 'Institution ID'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $crit === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit"><i data-lucide="filter"></i> Filtrer</button>
    <a class="btn btn--ghost" href="index.php?action=users.list"><i data-lucide="x"></i> Réinit.</a>
</form>

<!-- Filtre alphabétique -->
<div class="alpha">
    <?php foreach (range('A', 'Z') as $c): ?>
        <a class="alpha__item" href="index.php?action=users.list&letter=<?= $c ?>"><?= $c ?></a>
    <?php endforeach; ?>
</div>

<!-- Filtre client instantané (ne recharge pas la page) -->
<div class="search search--client">
    <i data-lucide="zap"></i>
    <input type="text" id="liveFilter" placeholder="Filtrer la page affichée…">
</div>

<?php if (!$rows): ?>
    <div class="empty">
        <i data-lucide="search-x"></i>
        <p>Aucun utilisateur ne correspond à ces critères.</p>
        <a class="btn" href="index.php?action=users.list">Revenir à la liste</a>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table class="data-table" id="usersTable">
        <thead>
            <tr>
                <th>#</th>
                <?= $th('codeUser', 'Code') ?>
                <?= $th('nom', 'Nom') ?>
                <th>Poste</th>
                <?= $th('uname', 'Username') ?>
                <?php if ($isAdmin): ?>
                    <?= $th('maj', 'MAJ') ?>
                    <?= $th('created_at', 'Date création') ?>
                    <?= $th('dmaj', 'Date MAJ') ?>
                    <th class="col-actions">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php $i = ($result['page'] - 1) * $result['perPage']; ?>
        <?php foreach ($rows as $u): $i++; $id = urlencode((string) $u['userid']); ?>
            <tr>
                <td class="muted"><?= $i ?></td>
                <td data-label="Code"><code class="code-user"><?= e($u['codeUser']) ?></code></td>
                <td data-label="Nom"><strong><?= e($u['nom']) ?></strong></td>
                <td data-label="Poste"><?= e($u['poste']) ?></td>
                <td data-label="Username"><code><?= e($u['uname']) ?></code></td>
                <?php if ($isAdmin): ?>
                    <td data-label="MAJ"><?= $badge((int) $u['maj']) ?></td>
                    <td data-label="Date création"><?= e(fmt_datetime($u['created_at'] ?? null)) ?></td>
                    <td data-label="Date MAJ"><?= e(fmt_dmaj($u['dmaj'] ?? null)) ?></td>
                    <td class="col-actions">
                        <div class="actions">
                            <a class="iconbtn" title="Aperçu du message"
                               href="index.php?action=mail.preview&id=<?= $id ?>"><i data-lucide="eye"></i></a>
                            <a class="iconbtn" title="Renvoyer les identifiants"
                               href="index.php?action=mail.confirm&type=rs&id=<?= $id ?>"><i data-lucide="send"></i></a>
                            <a class="iconbtn" title="Compte générique"
                               href="index.php?action=mail.confirm&type=generic&id=<?= $id ?>"><i data-lucide="users-round"></i></a>
                            <a class="iconbtn" title="Réinitialiser le mot de passe"
                               href="index.php?action=mail.confirm&type=rspw&id=<?= $id ?>"><i data-lucide="key-round"></i></a>
                            <a class="iconbtn" title="Réactivation"
                               href="index.php?action=mail.confirm&type=reactivation&id=<?= $id ?>"><i data-lucide="power"></i></a>
                            <a class="iconbtn iconbtn--warn" title="Notification phishing"
                               href="index.php?action=mail.confirm&type=phishing&id=<?= $id ?>"><i data-lucide="shield-alert"></i></a>
                            <?php if ((int) $u['sap'] === 1): ?>
                                <a class="iconbtn" title="Notifier ECOLink"
                                   href="index.php?action=mail.confirm&type=ecolink&id=<?= $id ?>"><i data-lucide="link"></i></a>
                            <?php endif; ?>
                            <a class="iconbtn iconbtn--edit" title="Modifier"
                               href="index.php?action=users.edit&id=<?= $id ?>"><i data-lucide="pencil"></i></a>
                            <button type="button" class="iconbtn iconbtn--danger js-delete"
                                    title="Supprimer"
                                    data-id="<?= e($u['userid']) ?>"
                                    data-nom="<?= e($u['nom']) ?>"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination compacte : Préc./Suiv. + fenêtre autour de la page courante -->
<?php if ($result['pages'] > 1):
    $cur  = $result['page'];
    $last = $result['pages'];
    $win  = 2; // nombre de pages affichées de chaque côté de la page courante

    $pageUrl = fn(int $p): string => 'index.php?' . http_build_query(
        array_merge($filters, ['action' => 'users.list', 'page' => $p])
    );

    // Liste compacte des numéros à afficher : 1, …, (cur-win .. cur+win), …, last
    $nums = [1, $last];
    for ($p = $cur - $win; $p <= $cur + $win; $p++) {
        if ($p >= 1 && $p <= $last) { $nums[] = $p; }
    }
    $nums = array_values(array_unique($nums));
    sort($nums);
?>
    <nav class="pagination" aria-label="Pagination">
        <a class="page page--nav <?= $cur <= 1 ? 'page--disabled' : '' ?>"
           href="<?= $cur <= 1 ? '#' : e($pageUrl($cur - 1)) ?>" aria-label="Page précédente">‹</a>

        <?php $prev = 0; foreach ($nums as $p): ?>
            <?php if ($prev && $p - $prev > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <a class="page <?= $p === $cur ? 'page--active' : '' ?>" href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
            <?php $prev = $p; ?>
        <?php endforeach; ?>

        <a class="page page--nav <?= $cur >= $last ? 'page--disabled' : '' ?>"
           href="<?= $cur >= $last ? '#' : e($pageUrl($cur + 1)) ?>" aria-label="Page suivante">›</a>

        <span class="page-info">Page <?= $cur ?> / <?= $last ?></span>
    </nav>
<?php endif; ?>
<?php endif; ?>

<!-- Formulaire de suppression (POST + CSRF), déclenché par la modale JS -->
<form id="deleteForm" method="POST" action="index.php?action=users.delete" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="userid" id="deleteUserid">
</form>
