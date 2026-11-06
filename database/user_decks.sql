-- Règles du deck (cf. issue [Deck] Définir les règles d'un deck) :
--   - taille maximale : 10 cartes ;
--   - limite de doublons : 2 exemplaires d'une même carte ;
--   - nombre total de cartes contrôlé côté serveur uniquement (deck_cards.deck_id, movie_id).
-- À exécuter après user_cards.sql.
CREATE TABLE IF NOT EXISTS `user_decks` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL DEFAULT 'Mon deck',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_decks_user` (`user_id`),
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
