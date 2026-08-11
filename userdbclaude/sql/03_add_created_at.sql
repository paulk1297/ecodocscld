-- =====================================================================
--  ECOWASmail Admin — Ajout d'une date de création (created_at)
--  À exécuter sur la base existante. Non destructif.
-- =====================================================================

-- 1. Nouvelle colonne (nullable pour l'existant).
ALTER TABLE `user`
    ADD COLUMN `created_at` DATETIME NULL AFTER `dmaj`;

-- 2. Backfill : pour les comptes existants, on estime la date de création
--    à partir de `dmaj` (AAAAMMJJ) quand elle est valide. Les autres restent
--    NULL (date de création inconnue).
UPDATE `user`
   SET created_at = STR_TO_DATE(dmaj, '%Y%m%d')
 WHERE created_at IS NULL
   AND dmaj REGEXP '^[0-9]{8}$';

-- 3. Index pour un tri rapide par date de création.
ALTER TABLE `user` ADD INDEX idx_user_created (created_at);
