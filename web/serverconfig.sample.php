<?php

ini_set('date.timezone', 'UTC');

define('YAAMP_LOGS', '/var/www/log');
define('YAAMP_HTDOCS', '/var/www');
define('YAAMP_BIN', '/var/www/bin');

define('YAAMP_DBHOST', 'localhost');
define('YAAMP_DBNAME', 'yaamp');
define('YAAMP_DBUSER', 'root');
define('YAAMP_DBPASSWORD', 'password');

define('YAAMP_SITE_URL', 'yiimp.ccminer.org');
define('YAAMP_STRATUM_URL', YAAMP_SITE_URL); // change if your stratum server is on a different host
define('YAAMP_SITE_NAME', 'YiiMP');

define('YAAMP_PRODUCTION', true);

define('YIIMP_PUBLIC_EXPLORER', true);
define('YIIMP_PUBLIC_BENCHMARK', false);

define('YAAMP_RENTAL', true);
define('YAAMP_LIMIT_ESTIMATE', false);

define('YAAMP_FEES_SOLO', 1);
define('YAAMP_FEES_MINING', 0.5);
define('YAAMP_FEES_EXCHANGE', 2);
define('YAAMP_FEES_RENTING', 2);
define('YAAMP_TXFEE_RENTING_WD', 0.002);
define('YAAMP_PAYMENTS_FREQ', 3*60*60);
define('YAAMP_PAYMENTS_MINI', 0.001);

define('YAAMP_ALLOW_EXCHANGE', true);
define('YIIMP_FIAT_ALTERNATIVE', 'EUR'); // USD is main

define('YAAMP_USE_NICEHASH_API', false);

define('YAAMP_BTCADDRESS', '1Auhps1mHZQpoX4mCcVL8odU81VakZQ6dR');

define('YIIMP_ADMIN_LOGIN', false);
define('YAAMP_ADMIN_EMAIL', 'yiimp@spam.la');
define('YAAMP_ADMIN_USER', 'yiimpadmin');
define('YAAMP_ADMIN_PASS', 'set-a-password');
define('YAAMP_ADMIN_IP', ''); // samples: "80.236.118.26,90.234.221.11" or "10.0.0.1/8"
define('YAAMP_ADMIN_WEBCONSOLE', true);
define('YAAMP_CREATE_NEW_COINS', true);
define('YAAMP_NOTIFY_NEW_COINS', false);
define('YAAMP_DEFAULT_ALGO', 'x11');

/* Github access token used to scan coin repos for new releases */
define('GITHUB_ACCESSTOKEN', '<username>:<api-secret>');

/* mail server access data to send mails using external mailserver */
define('SMTP_HOST', 'mail.example.com');
define('SMTP_PORT', 25);
define('SMTP_USEAUTH', true);
define('SMTP_USERNAME', 'mailuser');
define('SMTP_PASSWORD', 'mailpassword');
define('SMTP_DEFAULT_FROM', 'mailuser@example.com');
define('SMTP_DEFAULT_HELO', 'mypool-server.example.com');

define('YAAMP_USE_NGINX', false);

/* Sample config file to put in /etc/yiimp/keys.php */

define('YIIMP_MYSQLDUMP_USER', 'root');
define('YIIMP_MYSQLDUMP_PASS', '<my_mysql_password>');

/* 
 * Exchange access keys
 * for public fronted use separate container instance and leave keys unconfigured
 *
 * access tokens required to create/cancel orders and access your balances/deposit addresses
 */


define('EXCH_XEGGEX_KEY', 'a4fe74547363d748c141183c663be476');
define('EXCH_XEGGEX_SECRET', '71386947e9091f857c7791e696b37ada2638f53ac7db4a72');

// Automatic withdraw to Yaamp btc wallet if btc balance > 0.3
define('EXCH_AUTO_WITHDRAW', 0.0001);

// nicehash keys deposit account & amount to deposit at a time
define('NICEHASH_API_KEY','521c254d-8cc7-4319-83d2-ac6c604b5b49');
define('NICEHASH_API_ID','9205');
define('NICEHASH_DEPOSIT','3J9tapPoFCtouAZH7Th8HAPsD8aoykEHzk');
define('NICEHASH_DEPOSIT_AMOUNT','0.01');


$cold_wallet_table = array(
	'1C23KmLeCaQSLLyKVykHEUse1R7jRDv9j9' => 0.10,
);

// Sample fixed pool fees
$configFixedPoolFees = array(
        'zr5' => 2.0,
        'scrypt' => 20.0,
        'sha256' => 5.0,
);

// Sample fixed pool fees solo
$configFixedPoolFeesSolo = array(
		'zr5' => 2.0,
        'scrypt' => 2.0,
        'sha256' => 5.0,
);

// Sample custom stratum ports
$configCustomPorts = array(
//	'x11' => 7000,
);

// mBTC Coefs per algo (default is 1.0)
$configAlgoNormCoef = array(
//	'x11' => 5.0,
);

