ALTER TABLE `coins` ADD `version_github` VARCHAR(1024) NULL DEFAULT NULL AFTER `specifications`;
ALTER TABLE `coins` ADD `version_installed` VARCHAR(1024) NULL DEFAULT NULL AFTER `version_github`;
