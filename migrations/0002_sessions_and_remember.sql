-- 0002: sessions and persistent login
-- Run this on an existing database that predates the session mechanism.
-- Fresh installs get these tables from schema.sql.

CREATE TABLE IF NOT EXISTS `sesion` (
	`id` VARCHAR(128) NOT NULL,
	`data` MEDIUMTEXT NOT NULL,
	`ultima_actividad` INTEGER UNSIGNED NOT NULL,
	PRIMARY KEY(`id`),
	KEY `sesion_ultima_actividad_index` (`ultima_actividad`)
);

CREATE TABLE IF NOT EXISTS `remember_token` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`selector` VARCHAR(64) NOT NULL,
	`validator_hash` VARCHAR(255) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	`expira_en` INTEGER UNSIGNED NOT NULL,
	`creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	UNIQUE KEY `remember_token_selector_unique` (`selector`),
	FOREIGN KEY(`email`) REFERENCES `Usuario`(`email`)
	ON UPDATE NO ACTION ON DELETE CASCADE
);
