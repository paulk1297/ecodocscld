-- =====================================================================
--  ECOWASmail Admin — Étape 3 : nettoyage final
--  À exécuter UNIQUEMENT après avoir lancé scripts/backfill_hash.php
--  (étape 2) et VÉRIFIÉ que la colonne pwd_hash est bien remplie.
--
--  ⚠️ Cette étape supprime des colonnes. Faites une sauvegarde avant.
-- =====================================================================

-- Le mot de passe en clair n'est plus stocké : on retire l'ancienne colonne.
ALTER TABLE `user` DROP COLUMN `pwd`;

-- Le champ `status` n'est plus utilisé par l'application.
ALTER TABLE `user` DROP COLUMN `status`;

-- Tous les comptes ont désormais un codeUser : on impose la contrainte NOT NULL.
ALTER TABLE `user` MODIFY COLUMN `codeUser` VARCHAR(20) NOT NULL;
