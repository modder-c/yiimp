-- add blocknumber and blockrewarded to shares for real prop implementation

ALTER TABLE `shares` ADD `blocknumber` INT(10) NULL DEFAULT NULL AFTER `solo`;
ALTER TABLE `shares` ADD `blockrewarded` INT(10) NULL DEFAULT NULL AFTER `blocknumber`;
