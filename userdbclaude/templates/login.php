<?php

declare(strict_types=1);

/** Page de connexion autonome (sans la barre de navigation). */
$flashes = flash_pull();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Connexion') ?></title>
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
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-card__head">
            <i data-lucide="mail-check"></i>
            <h1>ECOWAS<strong>mail</strong> Admin</h1>
            <p>Panneau d'administration des comptes</p>
        </div>

        <?php foreach ($flashes as $f): ?>
            <div class="flash flash--<?= e($f['type']) ?>">
                <span><?= e($f['message']) ?></span>
            </div>
        <?php endforeach; ?>

        <form method="POST" action="index.php?action=login" class="login-form" autocomplete="off">
            <?= csrf_field() ?>
            <label class="field">
                <span>Identifiant</span>
                <div class="field__input">
                    <i data-lucide="user"></i>
                    <input type="text" name="username" required autofocus>
                </div>
            </label>
            <label class="field">
                <span>Mot de passe</span>
                <div class="field__input">
                    <i data-lucide="lock"></i>
                    <input type="password" name="password" required>
                </div>
            </label>
            <button type="submit" class="btn btn--primary btn--block">
                <i data-lucide="log-in"></i> Se connecter
            </button>
        </form>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
