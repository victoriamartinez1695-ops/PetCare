USE `veterinaria petca`;

ALTER TABLE `health_problems`
  ADD COLUMN `solution` text DEFAULT NULL AFTER `description`;
