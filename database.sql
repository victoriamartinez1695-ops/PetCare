CREATE DATABASE IF NOT EXISTS petcare
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE petcare;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  `type` VARCHAR(80) NOT NULL,
  breed VARCHAR(120) NULL,
  birth_date DATE NULL,
  gender VARCHAR(30) NULL,
  photo VARCHAR(2048) NULL,
  notes TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pets_user_id_id (user_id, id),
  CONSTRAINT fk_pets_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  pet_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  category VARCHAR(80) NOT NULL,
  `date` DATE NOT NULL,
  `time` TIME NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'medium',
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (id),
  KEY idx_tasks_user_pet (user_id, pet_id),
  CONSTRAINT fk_tasks_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_tasks_pet_owner
    FOREIGN KEY (user_id, pet_id) REFERENCES pets (user_id, id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
