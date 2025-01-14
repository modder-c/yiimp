-- add equihash specific options to coin table
	
ALTER TABLE `coins` ADD `wallet_zaddress` VARCHAR(1024) NULL DEFAULT NULL AFTER `master_wallet`;

ALTER TABLE `coins` ADD `powlimit_bits` TINYINT(3) NULL DEFAULT NULL AFTER `enable_rpcdebug`;
ALTER TABLE `coins` ADD `personalization` VARCHAR(1024) NULL DEFAULT NULL AFTER `powlimit_bits`;
