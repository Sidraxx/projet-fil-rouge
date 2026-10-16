-- Paquets disponibles par utilisateur (recharge : 1 paquet toutes les 5 minutes, max 10).
CREATE TABLE IF NOT EXISTS `user_pack_state` (
  `user_id` int UNSIGNED NOT NULL,
  `packs_available` tinyint UNSIGNED NOT NULL DEFAULT 10,
  `last_refill_ts` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_pack_state_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
