-- =====================================================================
--  ECOWASmail Admin — Étape 1 : préparation (NON destructive)
--  À exécuter sur la base existante `userdb_db`.
--  Cette étape n'efface aucune donnée. Elle ajoute la colonne pwd_hash
--  et crée la table des administrateurs du panneau.
-- =====================================================================

-- 1. Table des administrateurs du panneau (login / rôle).
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    pwd_hash   VARCHAR(255) NOT NULL,
    role       ENUM('admin', 'reader') NOT NULL DEFAULT 'reader',
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Colonne pour le mot de passe hashé des comptes ECOWASmail.
--    (l'ancienne colonne `pwd` en clair est conservée jusqu'au backfill)
ALTER TABLE `user`
    ADD COLUMN `pwd_hash` VARCHAR(255) NULL AFTER `pwd`;

-- 3. Code utilisateur unique (ex. ECW-7F3A9C). Nullable pour l'instant :
--    le backfill (étape 2) remplit les comptes existants, puis 02_cleanup.sql
--    le passe en NOT NULL.
ALTER TABLE `user`
    ADD COLUMN `codeUser` VARCHAR(20) NULL AFTER `userid`,
    ADD UNIQUE INDEX uniq_user_code (codeUser);

-- 4. Index utiles pour la recherche / le tri.
--    `nom` et `uname` étant des colonnes TEXT, MySQL exige une longueur de
--    préfixe (191 = sûr en utf8mb4). Ces index sont optionnels (performance).
ALTER TABLE `user` ADD INDEX idx_user_nom (nom(191));
ALTER TABLE `user` ADD INDEX idx_user_uname (uname(191));
