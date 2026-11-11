-- JODER_ENRIQUEEE - fresh-install schema (MySQL / MariaDB, InnoDB)
-- Import this into an empty database (e.g. `joder` locally).
-- NOTE: this file mirrors schema.sql; schema.sql is the canonical fresh install.
create database if not exists joder;
use joder;
CREATE TABLE IF NOT EXISTS `Usuario` (
	`Mote` VARCHAR(255) NOT NULL,
	`Nombre` VARCHAR(255) NOT NULL,
	`url_pfp` VARCHAR(255),
	`email` VARCHAR(255) NOT NULL,
	`password` VARCHAR(255) NOT NULL,
	PRIMARY KEY(`email`),
	UNIQUE KEY `Usuario_Mote_unique` (`Mote`)
);


CREATE INDEX `Usuario_index_0`
ON `Usuario` (`Mote`, `Nombre`, `url_pfp`);
CREATE TABLE IF NOT EXISTS `categoria` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nombre` VARCHAR(255) NOT NULL,
	`id_creador` VARCHAR(255) NOT NULL,
	`creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`id_creador`) REFERENCES `Usuario`(`email`)
	ON UPDATE NO ACTION ON DELETE NO ACTION
);


CREATE INDEX `categoria_index_0`
ON `categoria` (`nombre`, `id_creador`);
CREATE TABLE IF NOT EXISTS `tarea` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nombre` VARCHAR(255) NOT NULL,
	`completada` BOOLEAN NOT NULL DEFAULT FALSE,
	`ID_usuario` VARCHAR(255) NOT NULL,
	`id_categoria` INTEGER UNSIGNED,
	`creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`ID_usuario`) REFERENCES `Usuario`(`email`)
	ON UPDATE NO ACTION ON DELETE NO ACTION,
	FOREIGN KEY(`id_categoria`) REFERENCES `categoria`(`id`)
	ON UPDATE NO ACTION ON DELETE SET NULL
);


CREATE INDEX `tarea_index_0`
ON `tarea` (`id`, `nombre`, `completada`);
CREATE TABLE IF NOT EXISTS `INFODUMPS` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`Info` VARCHAR(255),
	`id_usuario` VARCHAR(255) NOT NULL,
	`accesible_al_resto` BOOLEAN NOT NULL,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`id_usuario`) REFERENCES `Usuario`(`email`)
	ON UPDATE NO ACTION ON DELETE NO ACTION
);


CREATE INDEX `INFODUMPS_index_0`
ON `INFODUMPS` (`id`, `Info`, `accesible_al_resto`);
CREATE TABLE IF NOT EXISTS `reaccion` (
	`id_dump` INTEGER UNSIGNED NOT NULL,
	`Id_usuario` VARCHAR(255) NOT NULL,
	`reaccion` VARCHAR(255) NOT NULL,
	PRIMARY KEY(`id_dump`, `Id_usuario`),
	FOREIGN KEY(`id_dump`) REFERENCES `INFODUMPS`(`id`)
	ON UPDATE NO ACTION ON DELETE NO ACTION,
	FOREIGN KEY(`Id_usuario`) REFERENCES `Usuario`(`email`)
	ON UPDATE NO ACTION ON DELETE NO ACTION
);


CREATE INDEX `reaccion_index_0`
ON `reaccion` (`reaccion`, `Id_usuario`, `id_dump`);
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
