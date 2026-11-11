-- Migration 0001: authentication + timestamps + category-delete behavior
-- Apply to an existing database created from the corrected joder.sql
-- (the version WITHOUT the password column and timestamps).
-- Fresh installs should just import schema.sql instead.
--
-- NOTE: foreign-key constraint names below are the MariaDB/MySQL defaults.
-- Verify with: SHOW CREATE TABLE tarea; and adjust the DROP FOREIGN KEY
-- name if it differs.

-- 1. Usuario: store hashed passwords + enforce unique handles
ALTER TABLE `Usuario`
	ADD COLUMN `password` VARCHAR(255) NOT NULL AFTER `email`,
	ADD UNIQUE KEY `Usuario_Mote_unique` (`Mote`);

-- 2. categoria: creation timestamp
ALTER TABLE `categoria`
	ADD COLUMN `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 3. tarea: creation timestamp + match categoria.id type
ALTER TABLE `tarea`
	ADD COLUMN `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	MODIFY COLUMN `id_categoria` INTEGER UNSIGNED NULL;

-- 4. tarea.id_categoria: null the category when it is deleted
ALTER TABLE `tarea`
	DROP FOREIGN KEY `tarea_ibfk_2`;
ALTER TABLE `tarea`
	ADD CONSTRAINT `tarea_id_categoria_fk`
	FOREIGN KEY (`id_categoria`) REFERENCES `categoria`(`id`)
	ON UPDATE NO ACTION ON DELETE SET NULL;
