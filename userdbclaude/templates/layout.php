<?php

declare(strict_types=1);

use App\Auth;

/**
 * Gabarit principal. Reçoit $content (HTML déjà rendu) et $title.
 * Affiche la barre de navigation et les messages flash.
 */
$flashes = flash_pull();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'ECOWASmail Admin') ?></title>
    <!-- Applique le thème AVANT le rendu pour éviter tout clignotement -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme')
                    || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Icônes Lucide (SVG modernes et épurés) -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<header class="topbar">
    <div class="topbar__brand">
        <i data-lucide="mail-check"></i>
        <span>ECOWAS<strong>mail</strong> Admin</span>
    </div>
    <nav class="topbar__nav">
        <a href="index.php?action=users.list" class="navlink">
            <i data-lucide="users"></i><span>Utilisateurs</span>
        </a>
        <?php if (Auth::isAdmin()): ?>
            <a href="index.php?action=users.new" class="navlink">
                <i data-lucide="user-plus"></i><span>Nouvel utilisateur</span>
            </a>
        <?php endif; ?>
        <span class="navlink navlink--user">
            <i data-lucide="user-circle"></i><span><?= e(Auth::username()) ?></span>
        </span>
        <button type="button" id="themeToggle" class="navlink navlink--theme"
                title="Basculer clair / sombre" aria-label="Basculer le thème">
            <i data-lucide="moon"></i>
        </button>
        <a href="index.php?action=logout" class="navlink navlink--logout">
            <i data-lucide="log-out"></i><span>Déconnexion</span>
        </a>
    </nav>
</header>

<main class="container">
    <?php if ($flashes): ?>
        <div class="flash-stack">
            <?php foreach ($flashes as $f): ?>
                <div class="flash flash--<?= e($f['type']) ?>">
                    <i data-lucide="<?= $f['type'] === 'success' ? 'check-circle'
                        : ($f['type'] === 'error' ? 'alert-circle' : 'info') ?>"></i>
                    <span><?= e($f['message']) ?></span>
                    <button class="flash__close" aria-label="Fermer">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>

<script src="assets/js/app.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
