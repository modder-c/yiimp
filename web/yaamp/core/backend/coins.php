<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function percent_feedback($v, $n, $p)
{
	return ($v*(100-$p) + $n*$p) / 100;
}

function string_to_hashrate($s)
{
	$value = floatval(trim(preg_replace('/,/', '', $s)));

	if(stripos($s, 'Kh/s')) $value *= 1000;
	if(stripos($s, 'Mh/s')) $value *= 1000000;
	if(stripos($s, 'Gh/s')) $value *= 1000000000;

	return $value;
}

/////////////////////////////////////////////////////////////////////////////////////////////

function BackendCoinsUpdate()
{
	$debug = false;

//	debuglog(__FUNCTION__);
	$t1 = microtime(true);

	$pool_rate = array();
	foreach(yaamp_get_algos() as $algo)
		$pool_rate[$algo] = yaamp_pool_rate($algo);

	$coins = getdbolist('db_coins', "installed");
	foreach($coins as $coin)
	{
//		debuglog("doing $coin->name");

		$remote = new WalletRPC($coin);

		$info = $remote->getinfo();
		if(!$info && $coin->enable)
		{
			debuglog("{$coin->symbol} no getinfo answer, retrying...");
			sleep(3);
			$info = $remote->getinfo();
			if (!$info) {
				debuglog("{$coin->symbol} disabled, no answer after 2 attempts. {$remote->error}");
				$coin->enable = false;
				$coin->connections = 0;
				$coin->save();
				continue;
			}
		}

		// auto-enable if auto_ready is set
		if($coin->auto_ready && !empty($info))
			$coin->enable = true;
		else if (empty($info))
			continue;

		if ($debug) echo "{$coin->symbol}\n";

		if(isset($info['difficulty']))
			$difficulty = $info['difficulty'];
		else
			$difficulty = $remote->getdifficulty();

		if(is_array($difficulty)) {
			$coin->difficulty = arraySafeVal($difficulty,'proof-of-work');
			$coin->difficulty_pos = arraySafeVal($difficulty,'proof-of-stake');
		}
		else
			$coin->difficulty = $difficulty;

		if($coin->algo == 'quark')
			$coin->difficulty /= 0x100;

		if($coin->difficulty == 0)
			$coin->difficulty = 1;

		$coin->errors = isset($info['errors'])? $info['errors']: '';
		$coin->txfee = isset($info['paytxfee'])? $info['paytxfee']: '';
		$coin->connections = isset($info['connections'])? $info['connections']: '';
		$coin->multialgos = (int) isset($info['pow_algo_id']);
		$coin->balance = isset($info['balance'])? $info['balance']: 0;
		$coin->stake = isset($info['stake'])? $info['stake'] : $coin->stake;
		$coin->mint = dboscalar("select sum(amount) from blocks where coin_id=$coin->id and category='immature'");

		if(empty($coin->master_wallet))
		{
			if ($coin->rpcencoding == 'DCR' && empty($coin->account)) $coin->account = 'default';
			$coin->master_wallet = $remote->getaccountaddress($coin->account);
		}

		if(empty($coin->rpcencoding))
		{
			$difficulty = $remote->getdifficulty();
			if(is_array($difficulty))
				$coin->rpcencoding = 'POS';
			else if ($coin->symbol == 'DCR')
				$coin->rpcencoding = 'DCR';
			else if ($coin->symbol == 'ETH')
				$coin->rpcencoding = 'GETH';
			else if ($coin->symbol == 'NIRO')
				$coin->rpcencoding = 'NIRO';
			else
				$coin->rpcencoding = 'POW';
		}

		if(is_null($coin->hassubmitblock))
		{
			$remote->submitblock('');
			if(strcasecmp($remote->error, 'method not found') == 0)
				$coin->hassubmitblock = false;
			else
				$coin->hassubmitblock = true;
		}

		if(is_null($coin->auxpow))
		{
			$ret = $remote->getauxblock();

			if(strcasecmp($remote->error, 'method not found') == 0)
				$coin->auxpow = false;
			else
				$coin->auxpow = true;
		}

        // Change for segwit
        if ($coin->usesegwit) {
            $template = $remote->getblocktemplate('{"rules":["segwit"]}');
        } else {
            $template = $remote->getblocktemplate('{}');
        }
        // Change for segwit end

		if($template && isset($template['coinbasevalue']))
		{
			$coin->reward = $template['coinbasevalue']/100000000*$coin->reward_mul;

			if($coin->symbol == 'TAC' && isset($template['_V2']))
				$coin->charity_amount = $template['_V2']/100000000;

			if(isset($template['payee_amount']) && $coin->symbol != 'LIMX') 
			{
				$coin->charity_amount = doubleval($template['payee_amount'])/100000000;
				$coin->reward -= $coin->charity_amount;
			}

			else if(isset($template['masternode']) && arraySafeVal($template,'masternode_payments_enforced')) 
			{
				if (arraySafeVal($template,'masternode_payments_started'))
				$coin->reward -= arraySafeVal($template['masternode'],'amount',0)/100000000;
				$coin->hasmasternodes = true;
			}

			else if($coin->symbol == 'XZC') 
			{
				// coinbasevalue here is the amount available for miners, not the full block amount
				$coin->reward = arraySafeVal($template,'coinbasevalue')/100000000 * $coin->reward_mul;
				$coin->charity_amount = $coin->reward * $coin->charity_percent / 100;
			}
				
			else if($coin->symbol == 'BNODE') 
			{
                   if(isset($template['masternode'])) 
				{
					if (arraySafeVal($template,'masternode_payments_started'))
					$coin->reward -= arraySafeVal($template['masternode'],'amount',0)/100000000;
				}
				if(isset($template['evolution'])) 
				{
					$coin->reward -= arraySafeVal($template['evolution'],'amount',10000000)/100000000;
				}
           	}
				
			else if($coin->symbol == 'BCRS') 
			{
				if(isset($template['masternode'])) 
				{
					if (arraySafeVal($template,'masternode_payments_started'))
					$coin->reward -= arraySafeVal($template['masternode'],'amount',0)/100000000;
				}
				
				if(isset($template['fundreward'])) 
				{
					$coin->reward -= arraySafeVal($template['fundreward'],'amount',0)/100000000;
				}
			}				

			else if($coin->symbol == 'IOTS') 
			{
				if(isset($template['masternode'])) 
				{
					if (arraySafeVal($template,'masternode_payments_started'))
					$coin->reward -= arraySafeVal($template['masternode'],'amount',0)/100000000;
				}
			}				
			
			else if($coin->symbol == 'VKAX') 
			{
				if(isset($template['masternode'])) 
				{
					if (arraySafeVal($template,'masternode_payments_started'))
					$coin->reward -= arraySafeVal($template['masternode'],'amount',0)/100000000;
				}
			}				
			
			else if(!empty($coin->charity_address)) 
			{
				if(!$coin->charity_amount)
				$coin->reward -= $coin->reward * $coin->charity_percent / 100;
			}

			if(isset($template['bits']))
			{
				$target = decode_compact($template['bits']);
				$coin->difficulty = target_to_diff($target);
			}
		}

		else if ($coin->rpcencoding == 'GETH' || $coin->rpcencoding == 'NIRO')
		{
			$coin->auto_ready = ($coin->connections > 0);
		}

		else if(strcasecmp($remote->error, 'method not found') == 0)
		{
			$template = $remote->getmemorypool();
			if($template && isset($template['coinbasevalue']))
			{
				$coin->usememorypool = true;
				$coin->reward = $template['coinbasevalue']/100000000*$coin->reward_mul;

				if(isset($template['bits']))
				{
					$target = decode_compact($template['bits']);
					$coin->difficulty = target_to_diff($target);
				}
			} 
			else 
			{
				$coin->auto_ready = false;
				$coin->errors = $remote->error;
			}
		}

			else if ($coin->symbol == 'ZEC' || $coin->rpcencoding == 'ZEC')
			{
				if($template && isset($template['coinbasetxn']))
				{
					// no coinbasevalue in ZEC blocktemplate :/
					$txn = $template['coinbasetxn'];
					$coin->charity_amount = arraySafeVal($txn,'foundersreward',0)/100000000;
					$coin->reward = $coin->charity_amount * 4 + arraySafeVal($txn,'fee',0)/100000000;
					// getmininginfo show current diff, getinfo the last block one
					$mininginfo = $remote->getmininginfo();
					$coin->difficulty = ArraySafeVal($mininginfo,'difficulty',$coin->difficulty);
					//$target = decode_compact($template['bits']);
					//$diff = target_to_diff($target); // seems not standard 0.358557563 vs 187989.937 in getmininginfo
					//target 00000002c0930000000000000000000000000000000000000000000000000000 => 0.358557563 (bits 1d02c093)
					//$diff = hash_to_difficulty($coin, $template['target']);
					//debuglog("ZEC target {$template['bits']} -> $diff");
				} else {
					$coin->auto_ready = false;
					$coin->errors = $remote->error;
				}
			}

			else if ($coin->rpcencoding == 'DCR')
			{
				$wi = $remote->walletinfo();
				$coin->auto_ready = ($coin->connections > 0 && arraySafeVal($wi,"daemonconnected"));
				if ($coin->auto_ready && arraySafeVal($wi,"unlocked",false) == false) {
					debuglog($coin->symbol." wallet is not unlocked!");
				}
			}

			else
			{
				$coin->auto_ready = false;
				$coin->errors = $remote->error;
			}

			if(strcasecmp($coin->errors, 'No more PoW blocks') == 0)
			{
				$coin->dontsell = true;
				$coin->auto_ready = false;
			}
//		}

		if($coin->block_height != $info['blocks'])
		{
			$count = $info['blocks'] - $coin->block_height;
			$ttf = $count > 0 ? (time() - $coin->last_network_found) / $count : 0;

			if(empty($coin->actual_ttf)) $coin->actual_ttf = $ttf;

			$coin->actual_ttf = percent_feedback($coin->actual_ttf, $ttf, 5);
			$coin->last_network_found = time();
		}

		$coin->version = substr($info['version'], 0, 32);
		$coin->block_height = $info['blocks'];

		if($coin->powend_height > 0 && $coin->block_height > $coin->powend_height) {
			if ($coin->auto_ready) {
				$coin->auto_ready = false;
				$coin->errors = 'PoW end reached';
			}
		}

		$coin->save();

		if ($coin->available < 0 || $coin->cleared > $coin->balance) {
			// can happen after a payout (waiting first confirmation)
			BackendUpdatePoolBalances($coin->id);
		}
	//	debuglog(" end $coin->name");

	}

	$coins = getdbolist('db_coins', "enable order by auxpow desc");
	foreach($coins as $coin)
	{
		$coin = getdbo('db_coins', $coin->id);
		if(!$coin) continue;

		if($coin->difficulty)
		{
			$coin->index_avg = $coin->reward * $coin->price * 10000 / $coin->difficulty;
			if(!$coin->auxpow && $coin->rpcencoding == 'POW')
			{
				$indexaux = dboscalar("SELECT SUM(index_avg) FROM coins WHERE enable AND visible AND auto_ready AND auxpow AND algo='{$coin->algo}'");
				$coin->index_avg += $indexaux;
			}
		}

		if($coin->network_hash) {
			$coin->network_ttf = intval($coin->difficulty * 0x100000000 / $coin->network_hash);
			if($coin->network_ttf > 2147483647) $coin->network_ttf = 2147483647;
		}

		if(isset($pool_rate[$coin->algo]))
			$coin->pool_ttf = intval($coin->difficulty * 0x100000000 / $pool_rate[$coin->algo]);
		if($coin->pool_ttf > 2147483647) $coin->pool_ttf = 2147483647;

		if(strstr($coin->image, 'http'))
		{
			$data = file_get_contents($coin->image);
			$coin->image = "/images/coin-$coin->id.png";

			@unlink(YAAMP_HTDOCS.$coin->image);
			file_put_contents(YAAMP_HTDOCS.$coin->image, $data);
		}

		$coin->save();
	}

	$d1 = microtime(true) - $t1;
	controller()->memcache->add_monitoring_function(__METHOD__, $d1);
}

function BackendCoinsVersionUpdate($check_algo = '')
{
	$link = false;
	$current_algo = '';

	$mail_source = SMTP_DEFAULT_FROM;
	$mail_dest = YAAMP_ADMIN_EMAIL;
	$mail_subject = "new wallet versions found";
	$mail_text = '';
	$algo_text = '';

	if ($check_algo == '') {
		$coins = getdbolist('db_coins', "installed order by algo asc");
	}
	else {
		$coins = getdbolist('db_coins', "installed and algo = '$check_algo'");
	}

	foreach($coins as $coin) {
		if ($current_algo != $coin->algo) {
		    if ($algo_text != '') {
		      $mail_text .= "#### Algo: $current_algo ####\n".$algo_text;
		    }
			$current_algo = $coin->algo;
			$algo_text = '';
		}

		if ($coin->link_github && ($coin->link_github != '') && (strstr($coin->link_github, 'github'))) {
			debuglog("requesting $coin->name $coin->symbol from github");

			$link = str_replace('https://github.com/', 'https://api.github.com/repos/', $coin->link_github);
			if ($link) {
				$link .= '/releases/latest';
				// request
				$ch = curl_init($link);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_MAXREDIRS , 5);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_USERAGENT, 'coinupdater v0.1 (checking for latest release version)');
				$execResult = strip_tags(curl_exec($ch));
				$obj = json_decode($execResult);

				if (($obj) && (isset($obj->tag_name)) && ($obj->tag_name != '') && ($obj->tag_name != $coin->version_github)) {
					debuglog("update ".$coin->id." version ".$obj->tag_name);
					dborun("UPDATE coins SET version_github=:github WHERE id=:coinid",
							array(':github' => $obj->tag_name, ':coinid'=>intval($coin->id)));
					$algo_text .= $coin->name.'('.$coin->symbol.') ID:'.$coin->id.' algo:'.$coin->algo.' : New release found ('.$obj->tag_name.")\n";
				}
/*				if ((!$obj) || (!isset($obj->tag_name))) {
					debuglog("link $link version ".var_export($execResult,true));
					if (($obj) && (isset($obj->message))) {
						$mail_text .= $coin->name.'('.$coin->symbol.') ID:'.$coin->id.' algo:'.$coin->algo.' : link '.$link.' message '.$obj->message."\n";
					}
				}
*/
			}
			sleep(5); // slow down
		}
		elseif ((!$coin->link_github) || ($coin->link_github == '')) {
		    $algo_text .= $coin->name.'('.$coin->symbol.') ID:'.$coin->id.' algo:'.$coin->algo.' : missing github-repo'."\n";
		}

		if ($coin->version_installed != $coin->version_github) {
		    $algo_text .= $coin->name.'('.$coin->symbol.') ID:'.$coin->id.' algo:'.$coin->algo.' : version installed ('.$coin->version_installed.') differs from latest release ('.$coin->version_github.")\n";
		}
	}

	// add last algo
	if ($algo_text != '') {
	    $mail_text .= "#### Algo: $current_algo ####\n".$algo_text;
	    $algo_text = '';
	}
	
	if ($mail_text != '') {
		debuglog($mail_text);
		
		// prepare Report-Email
	    $mail = new PHPMailer(false);

	    $mail->isSMTP();
		$mail->Helo = SMTP_DEFAULT_HELO;
	    $mail->Host       = SMTP_HOST;
	    $mail->SMTPAuth   = SMTP_USEAUTH;
	    $mail->Port       = SMTP_PORT;
	    if (SMTP_USEAUTH) {
			$mail->Username   = SMTP_USERNAME;
			$mail->Password   = SMTP_PASSWORD;
	    }

	    $mail->setFrom($mail_source, 'GitHub Version Updater');
		$mail->addAddress($mail_dest, 'Yiimp Poolsystem');
		
		$mail->isHTML(false);
		$mail->Subject = $mail_subject;
	    $mail->Body    = $mail_text;

		$mail->send();
		unset($mail); // reset mailer
	}
}
