<?php

declare(strict_types=1);

/**
 * Mise en place d'un environnement de TEST LOCAL sous SQLite.
 *
 * Crée le fichier SQLite, applique le schéma, crée un administrateur et
 * insère quelques utilisateurs de démonstration. Crée aussi un .env
 * configuré pour SQLite s'il n'existe pas encore.
 *
 *   php scripts/setup_sqlite.php [admin_user] [admin_pass]
 *
 * Exemple :  php scripts/setup_sqlite.php paulk admin123
 *
 * ⚠️ Réservé au développement local. La production utilise MySQL.
 */

if (PHP_SAPI !== 'cli') {
    exit("Ce script doit être lancé en ligne de commande.\n");
}
if (!extension_loaded('pdo_sqlite')) {
    exit("L'extension PHP pdo_sqlite est requise. Activez-la dans php.ini.\n");
}

$root = dirname(__DIR__);
require $root . '/src/helpers.php';   // pour generate_user_code()

$adminUser = $argv[1] ?? 'paulk';
$adminPass = $argv[2] ?? 'admin123';

// --- 1. Fichier .env (créé s'il manque, configuré pour SQLite) --------
$envFile = $root . '/.env';
if (!file_exists($envFile)) {
    $example = (string) file_get_contents($root . '/.env.example');
    $example = preg_replace('/^DB_DRIVER=.*/m', 'DB_DRIVER=sqlite', $example);
    file_put_contents($envFile, $example);
    echo ".env créé (DB_DRIVER=sqlite).\n";
} else {
    echo ".env déjà présent — pensez à y mettre DB_DRIVER=sqlite pour le test local.\n";
}

// --- 2. Base SQLite + schéma -----------------------------------------
$dbPath = $root . '/database/local.sqlite';
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0775, true);
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$schema = (string) file_get_contents($root . '/sql/sqlite_schema.sql');
foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
    if ($stmt !== '') {
        $pdo->exec($stmt);
    }
}
echo "Schéma appliqué : {$dbPath}\n";

// --- 3. Administrateur (upsert) --------------------------------------
$hash = password_hash($adminPass, PASSWORD_DEFAULT);
$exist = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
$exist->execute([$adminUser]);
if ($exist->fetchColumn() !== false) {
    $pdo->prepare('UPDATE admins SET pwd_hash = ?, role = ? WHERE username = ?')
        ->execute([$hash, 'admin', $adminUser]);
} else {
    $pdo->prepare('INSERT INTO admins (username, pwd_hash, role) VALUES (?, ?, ?)')
        ->execute([$adminUser, $hash, 'admin']);
}
echo "Administrateur « {$adminUser} » prêt (mot de passe : {$adminPass}).\n";

// --- 4. Utilisateurs de démonstration (si la table est vide) ---------
$count = (int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn();
if ($count === 0) {
    $demo = [
        ['Awa Diallo',     'Programme Officer', 'awa.diallo',                    0, ['cit300' => 1, 'wifi' => 1]],
        ['IT Helpdesk',    'Helpdesk générique', 'helpdesk',                     0, []],
        ['John Mensah',    'Finance Analyst',   'john.mensah@waemu.ecowas.int',  1, ['cit400' => 1]],
        ['Fatou Sow',      'HR Manager',        'fatou.sow',                     0, ['usb' => 1]],
        ['Kwame Asante',   'Network Engineer',  'kwame.asante@parl.ecowas.int',  0, ['wifi' => 1, 'cit300' => 1]],
        ['Mariama Ba',     'Translator',        'mariama.ba',                    1, []],
    ];

    $demoPwd = 'Passw0rd!';
    $cols = ['codeUser', 'instidu', 'nom', 'poste', 'uname', 'pwd_hash', 'otheremail',
             'cit300', 'cit400', 'usb', 'wifi', 'intercom', 'obs', 'maj', 'dmaj',
             'position', 'commission', 'dept', 'sap'];
    $ph  = ':' . implode(', :', $cols);
    $ins = $pdo->prepare('INSERT INTO user (' . implode(', ', $cols) . ") VALUES ($ph)");
    $check = $pdo->prepare('SELECT 1 FROM user WHERE codeUser = ? LIMIT 1');

    foreach ($demo as [$nom, $poste, $uname, $sap, $flags]) {
        do {
            $code = generate_user_code();
            $check->execute([$code]);
        } while ($check->fetchColumn() !== false);

        $ins->execute([
            'codeUser'   => $code,
            'instidu'    => '',
            'nom'        => $nom,
            'poste'      => $poste,
            'uname'      => $uname,
            'pwd_hash'   => password_hash($demoPwd, PASSWORD_DEFAULT),
            'otheremail' => strtolower(str_replace([' ', '@ecowas.int'], ['.', ''], $nom)) . '@example.com',
            'cit300'     => $flags['cit300'] ?? 0,
            'cit400'     => $flags['cit400'] ?? 0,
            'usb'        => $flags['usb'] ?? 0,
            'wifi'       => $flags['wifi'] ?? 0,
            'intercom'   => '',
            'obs'        => '',
            'maj'        => 0,
            'dmaj'       => date('Ymd'),
            'position'   => '',
            'commission' => '',
            'dept'       => '',
            'sap'        => $sap,
        ]);
    }
    echo count($demo) . " utilisateur(s) de démonstration insérés (mot de passe : {$demoPwd}).\n";
} else {
    echo "Table user déjà peuplée ({$count} ligne(s)) — démo non réinsérée.\n";
}

echo "\n✔ Prêt. Lancez :\n";
echo "    php -S localhost:8000 -t public\n";
echo "  puis http://localhost:8000/index.php?action=login\n";
echo "  Connexion : {$adminUser} / {$adminPass}\n";
