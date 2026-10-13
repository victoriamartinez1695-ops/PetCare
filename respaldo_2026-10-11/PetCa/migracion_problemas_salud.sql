USE `veterinaria petca`;

CREATE TABLE IF NOT EXISTS `health_problems` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `pet_id` bigint(20) UNSIGNED NOT NULL,
  `reported_at` date NOT NULL,
  `description` text NOT NULL,
  `solution` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `idx_health_problems_user_pet` (`user_id`, `pet_id`),
  KEY `idx_health_problems_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_health_problems_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_health_problems_pet_owner`
    FOREIGN KEY (`user_id`, `pet_id`) REFERENCES `pets` (`user_id`, `id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
