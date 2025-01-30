<?php
/**
 * This function adds the new markets
 * It also create new coins in the database (if present on the most common exchanges)
 */
function updateRawcoins()
{
	debuglog(__FUNCTION__);
	// exit();
	exchange_set_default('bittrex', 'disabled', true);
         exchange_set_default('poloniex', 'disabled', true);
        exchange_set_default('binance', 'disabled', true);
        exchange_set_default('kraken', 'disabled', true);
       exchange_set_default('kucoin', 'disabled', true);
       exchange_set_default('xeggex', 'disabled', false);
	
	settings_prefetch_all();

	// $markets_name = array('p2pb2b','btc-alpha','tradeogre','bibox','poloniex','yobit','coinsmarkets','escodex','hitbtc','kraken','binance','gateio','kucoin','shapeshift');
	$exchanges = getdbolist('db_balances');
	foreach ($exchanges as $exchange) {
		updateRawCoinExchange($exchange->name);
	}

	//////////////////////////////////////////////////////////

	$markets = dbocolumn("SELECT DISTINCT name FROM markets");
	foreach ($markets as $exchange) {
		if (exchange_get($exchange, 'disabled')) {
			$res = dborun("UPDATE markets SET disabled=8 WHERE name='$exchange'");
			if(!$res) continue;
			$coins = getdbolist('db_coins', "id IN (SELECT coinid FROM markets WHERE name='$exchange')");
			foreach($coins as $coin) {
				// allow to track a single market on a disabled exchange (dev test)
				if (market_get($exchange, $coin->getOfficialSymbol(), 'disabled', 1) == 0) {
					$res -= dborun("UPDATE markets SET disabled=0 WHERE name='$exchange' AND coinid={$coin->id}");
				}
			}
			debuglog("$exchange: $res markets disabled from db settings");
		} else {
			$res = dborun("UPDATE markets SET disabled=0 WHERE name='$exchange' AND disabled=8");
			if($res) debuglog("$exchange: $res markets re-enabled from db settings");
		}
	}

	dborun("DELETE FROM markets WHERE deleted");

	$list = getdbolist('db_coins', "not enable and not installed and id not in (select distinct coinid from markets)");
	foreach($list as $coin)
	{
		if ($coin->visible)
			debuglog("{$coin->symbol} is no longer active");
	// todo: proper cleanup in all tables (like "yiimp coin SYM delete")
	//	if ($coin->symbol != 'BTC')
	//		$coin->delete();
	}
}

function updateRawCoinExchange($marketname)
{
	debuglog(__FUNCTION__);
	debuglog("==== Exchange $marketname ====");
	switch ($marketname) {
		case 'altmarkets':
			if (!exchange_get($marketname, 'disabled')) 
			{
				$list = altmarkets_api_query('markets');
				if(is_array($list) && !empty($list))
				{
					// debuglog(json_encode($list));
					dborun("UPDATE markets SET deleted=true WHERE name='$marketname'");
					foreach($list as $key=>$data) {
						// debuglog(json_encode($data));
						$e = explode("/",$data->name);
						// debuglog(json_encode($e));
						$base = $e[1];
						if (strtoupper($base) !== 'BTC')
							continue;
						$symbol = strtoupper($e[0]);
						// debuglog($symbol);
						updateRawCoin($marketname, $symbol);
					}
				}
			}
		break;

		case 'exbitron':
			if (!exchange_get($marketname, 'disabled')) 
			{
				$list = exbitron_api_query('markets');
				if(is_array($list) && !empty($list))
				{
					// debuglog(json_encode($list));
					dborun("UPDATE markets SET deleted=true WHERE name='$marketname'");
					foreach($list as $key=>$data) {
						// debuglog(json_encode($data));
						$e = explode("/",$data->name);
						// debuglog(json_encode($e));
						$base = $e[1];
						if (strtoupper($base) !== 'BTC'||strtoupper($base) !== 'USDT')
							continue;
						$symbol = strtoupper($e[0]);
						// debuglog($symbol);
						updateRawCoin($marketname, $symbol);
					}
				}
			}
		break;

		case 'p2pb2b':
			debuglog("Start P2PB2B");
			if (!exchange_get('p2pb2b', 'disabled')) 
			{
				debuglog("Ok");
				$list = p2pb2b_api_query('tickers');
				#debuglog(json_encode($list));
				if(is_object($list) && !empty($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='p2pb2b'");
					foreach($list->result as $name=>$ticker) {
						#debuglog("==== " .$name. " ====");
						#debuglog(json_encode($ticker));
						$e = explode('_', $name);
						if (strtoupper($e[1]) !== 'BTC')
							continue;
						$symbol = strtoupper($e[0]);
						updateRawCoin('p2pb2b', $symbol);
					}
				}
			}
		break;
		case 'btc-alpha':
			if (!exchange_get('btc-alpha', 'disabled')) {
				$list = btcalpha_api_query('ticker');
				if(is_array($list) && !empty($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='btcalpha'");
					foreach($list as $ticker) {
						$e = explode('_', $ticker->pair);
						if (strtoupper($e[1]) !== 'BTC')
							continue;
						$symbol = strtoupper($e[0]);
						updateRawCoin('btc-alpha', $symbol);
					}
				}
			}
		break;
		case 'xeggex':
			if (!exchange_get('xeggex', 'disabled')) {
				$list = xeggex_api_query('tickers','','array');
				if(is_array($list) && !empty($list)) {
					dborun("UPDATE markets SET deleted=true WHERE name='xeggex'");
					foreach ($list as $tickers) {
						$base = strtoupper($tickers['target_currency']);
						if (strtoupper($base) !== 'BTC'||strtoupper($base) !== 'USDT')
						$symbol = strtoupper($tickers['base_currency']);
						updateRawCoin('xeggex', $symbol, $symbol);
					}
				}
			}
		break;
	
		case 'nonkyc':
		if (!exchange_get('nonkyc', 'disabled')) {
			$list = nonkyc_api_query('tickers','','array');
			if(is_array($list) && !empty($list)) {
				dborun("UPDATE markets SET deleted=true WHERE name='nonkyc'");
				foreach ($list as $tickers) {
					$base = strtoupper($tickers['target_currency']);
					if (strtoupper($base) !== 'BTC'||strtoupper($base) !== 'USDT')
					$symbol = strtoupper($tickers['base_currency']);
					updateRawCoin('nonkyc', $symbol, $symbol);
				}
			}
		}
		break;
	
		case 'safetrade':
			if (!exchange_get('safetrade', 'disabled')) {
				$list = safetrade_api_query('trade/public/markets','','array');

				if(is_array($list) && !empty($list)) {
					dborun("UPDATE markets SET deleted=true WHERE name='safetrade'");
					foreach ($list as $tickers) {
						$base = strtoupper($tickers['quote_unit']);
						if (strtoupper($base) !== 'BTC'||strtoupper($base) !== 'USDT')
						$symbol = strtoupper($tickers['base_unit']);
						updateRawCoin('safetrade', $symbol, $symbol);
					}
				}
			}
			break;

		case 'tradeogre':
			if (!exchange_get('tradeogre', 'disabled')) {
				$list = tradeogre_api_query('markets');
				if(is_array($list) && !empty($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='tradeogre'");
					foreach($list as $ticker) {
						$symbol_index = key($ticker);
						$e = explode('-', $symbol_index);
						if (strtoupper($e[0]) !== 'BTC')
							continue;
						$symbol = strtoupper($e[1]);
						updateRawCoin('tradeogre', $symbol);
					}
				}
			}
		break;
		case 'poloniex':
			if (!exchange_get('poloniex', 'disabled')) {
				$poloniex = new poloniex;
				$tickers = $poloniex->get_currencies();
				if (!$tickers)
					$tickers = array();
				else
					dborun("UPDATE markets SET deleted=true WHERE name='poloniex'");
				foreach($tickers as $symbol=>$ticker)
				{
					if(arraySafeVal($ticker,'disabled')) continue;
					if(arraySafeVal($ticker,'delisted')) continue;
					updateRawCoin('poloniex', $symbol);
				}
			}
		break;
		case 'yobit':
			if (!exchange_get('yobit', 'disabled')) {
				$res = yobit_api_query('info');
				if($res)
				{
					dborun("UPDATE markets SET deleted=true WHERE name='yobit'");
					foreach($res->pairs as $i=>$item)
					{
						$e = explode('_', $i);
						$symbol = strtoupper($e[0]);
						updateRawCoin('yobit', $symbol);
					}
				}
			}
		break;
		case 'coinsmarkets':
			if (!exchange_get('coinsmarkets', 'disabled')) {
				$list = coinsmarkets_api_query('apicoin');
				if(!empty($list) && is_array($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='coinsmarkets'");
					foreach($list as $pair=>$data) {
						$e = explode('_', $pair);
						if ($e[0] != 'BTC') continue;
						$symbol = strtoupper($e[1]);
						updateRawCoin('coinsmarkets', $symbol);
					}
				}
			}
		break;
		case 'escodex':
			if (!exchange_get('escodex', 'disabled')) {
				$list = escodex_api_query('ticker');
				if(is_array($list) && !empty($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='escodex'");
					foreach($list as $ticker) {
						#debuglog (json_encode($ticker));
						if (strtoupper($ticker->base) !== 'BTC')
							continue;
						$symbol = strtoupper($ticker->quote);
						updateRawCoin('escodex', $symbol);
					}
				}
			}
		break;
		case 'hitbtc':
			if (!exchange_get('hitbtc', 'disabled')) {
				$list = hitbtc_api_query('symbols');
				if(is_object($list) && isset($list->symbols) && is_array($list->symbols))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='hitbtc'");
					foreach($list->symbols as $data) {
						$base = strtoupper($data->currency);
						if ($base != 'BTC') continue;
						$symbol = strtoupper($data->commodity);
						updateRawCoin('hitbtc', $symbol);
					}
				}
			}
		break;
		case 'kraken':
			if (!exchange_get('kraken', 'disabled')) {
				$list = kraken_api_query('AssetPairs');
				if(is_array($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='kraken'");
					foreach($list as $pair => $item) {
						$pairs = explode('-', $pair);
						$base = reset($pairs); $symbol = end($pairs);
						if($symbol == 'BTC' || $base != 'BTC') continue;
						if(in_array($symbol, array('GBP','CAD','EUR','USD','JPY'))) continue;
						if(strpos($symbol,'.d') !== false) continue;
						$symbol = strtoupper($symbol);
						updateRawCoin('kraken', $symbol);
					}
				}
			}
		break;
		case 'binance':
			if (!exchange_get('binance', 'disabled')) {
				$list = binance_api_query('ticker/allBookTickers');
				if(is_array($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='binance'");
					foreach($list as $ticker) {
						$base = substr($ticker->symbol, -3, 3);
						// XXXBTC XXXETH BTCUSDT (no separator!)
						if ($base != 'BTC') continue;
						$symbol = substr($ticker->symbol, 0, strlen($ticker->symbol)-3);
						updateRawCoin('binance', $symbol);
					}
				}
			}
		break;
		case 'gateio':
			if (!exchange_get('gateio', 'disabled')) {
				$json = gateio_api_query('marketlist');
				$list = arraySafeVal($json,'data');
				if(!empty($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='gateio'");
					foreach($list as $item) {
						if ($item['curr_b'] != 'BTC')
							continue;
						$symbol = trim(strtoupper($item['symbol']));
						$name = trim($item['name']);
						updateRawCoin('gateio', $symbol, $name);
					}
				}
			}
		break;
		case 'kucoin':
			if (!exchange_get('kucoin', 'disabled')) {
				$list = kucoin_api_query('currencies');
				if(kucoin_result_valid($list) && !empty($list->data))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='kucoin'");
					foreach($list->data as $item) {
						$symbol = $item->name;
						$name = $item->fullName;
						updateRawCoin('kucoin', $symbol, $name);
					}
				}
			}
		break;
		case 'shapeshift':
			if (!exchange_get('shapeshift', 'disabled')) {
				$list = shapeshift_api_query('getcoins');
				if(is_array($list) && !empty($list))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='shapeshift'");
					foreach($list as $item) {
						$status = $item['status'];
						if ($status != 'available') continue;
						$symbol = strtoupper($item['symbol']);
						$name = trim($item['name']);
						updateRawCoin('shapeshift', $symbol, $name);
						//debuglog("shapeshift: $symbol $name");
					}
				}
			}
		break;
		case 'bibox':
			if (!exchange_get('bibox', 'disabled')) {
				$list = bibox_api_query('marketAll');
				if(isset($list["result"]) && !empty($list["result"]))
				{
					dborun("UPDATE markets SET deleted=true WHERE name='bibox'");
					foreach($list["result"] as $currency) {
						if ($currency["currency_symbol"] == 'BTC') continue;
						updateRawCoin('bibox', $currency["coin_symbol"]);
					}
				}
			}
		break;
	}
	debuglog("==== END Exchange ====");
}

function updateRawCoin($marketname, $symbol, $name='unknown')
{
	if($symbol == 'BTC') return;

	$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
	if(!$coin && YAAMP_CREATE_NEW_COINS)
	{
		$algo = '';

		if (in_array($marketname, array('askcoin','binance','coinsmarkets','hitbtc'))) {
			// don't polute too much the db with new coins, its better from exchanges with labels
			return;
		}

		// some other to ignore...
		if (in_array($marketname, array('yobit','kucoin')))
			return;

		if (market_get($marketname, $symbol, "disabled")) {
			return;
		}

		debuglog("new coin $marketname $symbol $name");

		$coin = new db_coins;
		$coin->txmessage = true;
		$coin->hassubmitblock = true;
		$coin->name = $name;
		$coin->algo = $algo;
		$coin->symbol = $symbol;
		$coin->created = time();
		$coin->save();

		$url = getMarketUrl($coin, $marketname);
		// if (YAAMP_NOTIFY_NEW_COINS)
		// 	mail(YAAMP_ADMIN_EMAIL, "New coin $symbol", "new coin $symbol ($name) on $marketname\r\n\r\n$url");
		sleep(1);
	}

	else if($coin && $coin->name == 'unknown' && $name != 'unknown')
	{
		$coin->name = $name;
		$coin->save();
	}

	$list = getdbolist('db_coins', "symbol=:symbol or symbol2=:symbol", array(':symbol'=>$symbol));
	foreach($list as $coin)
	{
		$market = getdbosql('db_markets', "coinid=$coin->id and name='$marketname'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = $marketname;
		}

		$market->deleted = false;
		$market->save();
	}

}

