-- Règles du deck (cf. issue [Deck] Définir les règles d'un deck) :
--   - taille maximale : 10 cartes ;
--   - limite de doublons : 2 exemplaires d'une même carte ;
--   - plusieurs decks nommés par joueur (issue #117), un seul actif à la fois :
--     c'est le code applicatif qui garantit l'unicité du deck actif.
-- À exécuter après user_cards.sql. Idempotent : réexécutable sans erreur.
CREATE TABLE IF NOT EXISTS `user_decks` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL DEFAULT 'Mon deck',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_decks_user` (`user_id`),
  CONSTRAINT `fk_user_decks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Une ligne par carte du deck : quantity porte le nombre d'exemplaires
-- placés dans le deck (limité à 2 par les règles du deck).
CREATE TABLE IF NOT EXISTS `deck_cards` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `deck_id` int UNSIGNED NOT NULL,
  `movie_id` int UNSIGNED NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deck_cards_deck_movie` (`deck_id`, `movie_id`),
  CONSTRAINT `fk_deck_cards_deck` FOREIGN KEY (`deck_id`) REFERENCES `user_decks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_deck_cards_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration depuis la version « un seul deck » : l'unicité sur user_id est
-- levée (plusieurs decks par joueur) et remplacée par un index classique.
-- La clé étrangère s'appuie sur cet index : on la retire puis on la recrée.
DROP TEMPORARY TABLE IF EXISTS deck_migration_state;
CREATE TEMPORARY TABLE deck_migration_state (name VARCHAR(64) PRIMARY KEY);

INSERT INTO deck_migration_state
SELECT 'uq' FROM information_schema.STATISTICS s
WHERE s.table_schema = DATABASE()
  AND s.table_name = 'user_decks'
  AND s.index_name = 'uq_user_decks_user'
HAVING COUNT(*) > 0;

INSERT INTO deck_migration_state
SELECT 'idx' FROM information_schema.STATISTICS s
WHERE s.table_schema = DATABASE()
  AND s.table_name = 'user_decks'
  AND s.index_name = 'idx_user_decks_user'
HAVING COUNT(*) = 0;

INSERT INTO deck_migration_state
SELECT 'fk' FROM information_schema.TABLE_CONSTRAINTS tc
WHERE tc.table_schema = DATABASE()
  AND tc.table_name = 'user_decks'
  AND tc.constraint_name = 'fk_user_decks_user'
  AND tc.constraint_type = 'FOREIGN KEY'
AND NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS s
  WHERE s.table_schema = DATABASE()
    AND s.table_name = 'user_decks'
    AND s.index_name = 'idx_user_decks_user');

SET @drop_fk_user_decks = IF(
  (SELECT COUNT(*) FROM deck_migration_state WHERE name = 'fk') > 0,
  'ALTER TABLE `user_decks` DROP FOREIGN KEY `fk_user_decks_user`',
  'SELECT 1'
);
PREPARE deck_migration FROM @drop_fk_user_decks;
EXECUTE deck_migration;
DEALLOCATE PREPARE deck_migration;

SET @drop_unique_user_decks = IF(
  (SELECT COUNT(*) FROM deck_migration_state WHERE name = 'uq') > 0,
  'ALTER TABLE `user_decks` DROP KEY `uq_user_decks_user`',
  'SELECT 1'
);
PREPARE deck_migration FROM @drop_unique_user_decks;
EXECUTE deck_migration;
DEALLOCATE PREPARE deck_migration;

SET @add_index_user_decks = IF(
  (SELECT COUNT(*) FROM deck_migration_state WHERE name = 'idx') > 0,
  'ALTER TABLE `user_decks` ADD KEY `idx_user_decks_user` (`user_id`)',
  'SELECT 1'
);
PREPARE deck_migration FROM @add_index_user_decks;
EXECUTE deck_migration;
DEALLOCATE PREPARE deck_migration;

SET @add_fk_user_decks = IF(
  (SELECT COUNT(*) FROM deck_migration_state WHERE name = 'fk') > 0,
  'ALTER TABLE `user_decks` ADD CONSTRAINT `fk_user_decks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE deck_migration FROM @add_fk_user_decks;
EXECUTE deck_migration;
DEALLOCATE PREPARE deck_migration;

DROP TEMPORARY TABLE IF EXISTS deck_migration_state;
