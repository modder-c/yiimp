ALTER TABLE `coins` ADD `auto_exchange` TINYINT(1) NOT NULL DEFAULT '0' AFTER `version_installed`;
ALTER TABLE `coins` ADD `enable_rpcdebug` TINYINT(1) NOT NULL DEFAULT '0' AFTER `auto_exchange`;
