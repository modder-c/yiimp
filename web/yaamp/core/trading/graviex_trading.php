<?php
function doGraviexCancelOrder($OrderID = false) {
	if(!$OrderID) return;

	$params = [ 'id' => $OrderID ];
	$res = graviex_api_user('order/delete.json', $params, 'POST' ,'array');

	if(is_array($res)) {
		$db_order = getdbosql('db_orders', "market=:market AND uuid=:uuid", array(
			':market'=>'graviex', ':uuid'=>$OrderID
		));
		if($db_order) $db_order->delete();
	}
}

function doGraviexTrading($quick=false) {

	$exchange = 'graviex';
	$updatebalances = true;
	if (exchange_get($exchange, 'disabled')) return;

	$balances = graviex_api_user('members/me.json','','','array');
	//debuglog("graviex ".var_export($balances,true));
	if (!is_array($balances) || !isset($balances['accounts'])) return;

	$savebalance = getdbosql('db_balances', "name='$exchange'");
	foreach($balances['accounts'] as $balance) {
		if (strtoupper($balance['currency']) == 'BTC') {
			if (is_object($savebalance)) {
				$savebalance->balance = $balance['balance'];
				$savebalance->onsell = $balance['locked'];
				$savebalance->save();
			}
			continue;
		}
		if ($updatebalances) {
			// store available balance in market table
			$coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
				array(':symbol'=>strtoupper($balance['currency']))
			);
			if (empty($coins)) continue;
			foreach ($coins as $coin) {
				$market = getdbosql('db_markets', "coinid=:coinid AND name='$exchange'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				$market->balance = $balance['balance'];
				$market->ontrade = $balance['locked'];
				$market->balancetime = time();
				$market->save();
			}
		}
	}
	if (!YAAMP_ALLOW_EXCHANGE) return;

	$flushall = rand(0, 8) == 0;
	if($quick) $flushall = false;
	
	$min_btc_trade = 0.000001000; // minimum allowed by the exchange
	$sell_ask_pct = 1.01;        // sell on ask price + 5%
	$cancel_ask_pct = 1.20;      // cancel order if our price is more than ask price + 20%
	
	//error_log(var_export($balances,1));
	$marketprices = graviex_api_query('tickers.json','','array');
	if (!is_array($marketprices)) return;

	//debuglog("graviex ".var_export($marketprices,true));

	// auto trade
	foreach ($balances['accounts'] as $balance) {
		if ($balance['currency'] == 'BTC') continue;
		if (($balance['balance'] == 0) && ($balance['locked'] == 0)) continue;
		
		$marketsummary = null;
		$tickersymbol = strtolower($balance['currency'].'btc');
		if ((isset($marketprices[$tickersymbol])) && (isset($marketprices[$tickersymbol]['ticker']))) {
			$marketsummary = $marketprices[$tickersymbol]['ticker'];
		}

		if (!is_array($marketsummary)) continue;
		$heldForTrades = $balance['locked'];
		// debuglog("graviex ".var_export($marketsummary,true));
	
		$coin = getdbosql('db_coins', "symbol=:symbol AND dontsell=0", array(':symbol'=>strtoupper($balance['currency'])));
		if(!$coin) continue;
		$symbol = $coin->symbol;
		if (!empty($coin->symbol2)) $symbol = $coin->symbol2;

		$market = getdbosql('db_markets', "coinid=:coinid AND name='graviex'", array(':coinid'=>$coin->id));
		if(!$market) continue;
		$market->balance = $heldForTrades;
		//$market->message = $balance->StatusMessage;
	
		$orders = NULL;
		if ($heldForTrades > 0) {
			$params = [ 'market' => strtolower($symbol.'btc') ];
			$orders = graviex_api_user('orders.json', $params, '', 'array' );
		}

		// debuglog("graviex ".var_export($orders,true));

		if(is_array($orders) && !empty($orders)) {
			foreach($orders as $order) {
				$tmpsymbol = strtoupper($symbol); $tmpbase = strtoupper('btc');
				if ($tmpsymbol != $symbol) continue;
				if ($tmpbase != 'BTC') continue;
				
				// ignore buy orders
				if(stripos($order['side'], 'sell') === false) continue;
	
				$ask = bitcoinvaluetoa($marketsummary['sell']);
				$sellprice = bitcoinvaluetoa($order['price']);

				// cancel orders not on the wanted ask range
				if($sellprice > $ask*$cancel_ask_pct || $flushall) {
					debuglog("graviex: cancel order ".$symbol." at $sellprice, ask price is now $ask");
					doGraviexCancelOrder($order['id']);
				}
				// store existing orders
				else
				{
					$db_order = getdbosql('db_orders', "market=:market AND uuid=:uuid", array(
							':market'=>'graviex', ':uuid'=>$order['id']
					));
					if($db_order) continue;
	
					// debuglog("graviex: store order of {$order->Amount} {$symbol} at $sellprice BTC");
					$db_order = new db_orders;
					$db_order->market = 'graviex';
					$db_order->coinid = $coin->id;
					$db_order->amount = $order['volume'];
					$db_order->price = $sellprice;
					$db_order->ask = $marketsummary['sell'];
					$db_order->bid = $marketsummary['buy'];
					$db_order->uuid = $order['id'];
					$db_order->created = time(); // $order->TimeStamp 2016-03-07T20:04:05.3947572"
					$db_order->save();
				}
			}
		}

		// drop obsolete orders
		$list = getdbolist('db_orders', "coinid={$coin->id} AND market='graviex'");
		foreach($list as $db_order)
		{
			$found = false;
			if(is_array($orders) && !empty($orders)) {
				foreach($orders as $order) {
					if(stripos($order['side'], 'sell') === false) continue;
					if($order['id'] == $db_order->uuid) {
						$found = true;
						break;
					}
				}
			}
	
			if(!$found) {
				// debuglog("graviex: delete db order {$db_order->amount} {$coin->symbol} at {$db_order->price} BTC");
				$db_order->delete();
			}
		}
	
		if($coin->dontsell) continue;
	
		$market->lasttraded = time();
		$market->save();

		// new orders
		//$amount = floatval($balance->available) - 0.00000001;
		$amount = floatval($balance['balance']);
		if(!$amount) continue;
	
		if($amount*$coin->price < $min_btc_trade) continue;
				
		$orderparameters = 'market='.strtolower($balance['currency']).'btc';
		$data = graviex_api_query('order_book.json', $orderparameters, 'array');

		// debuglog("graviex ".var_export($data,true));

		if(!is_array($data) || empty($data)) continue;
		if($coin->sellonbid) {
			for($i = 0; ($i < 5) && ($amount >= 0); $i++) {
				if(!isset($data['bids'][$i])) break;
	
				$nextbuy = $data['bids'][$i];
				if($amount*1.1 < $nextbuy['volume']) break;
	
				$sellprice = bitcoinvaluetoa($nextbuy['price']);
				$sellamount = min($amount, $nextbuy['volume']);

				if($sellamount*$sellprice < $min_btc_trade) continue;

				debuglog("graviex: selling on bid $sellamount $symbol at $sellprice");
				$orderparameters = [ 'market' => strtolower($balance['currency']).'btc' ,
									 'price' => number_format($sellprice,9) ,
									 'side' => 'sell' ,
									 'volume' => $sellamount ];
				$res = graviex_api_user('orders.json', $orderparameters , 'POST', 'array');
	
				if(!is_array($res)) {
					debuglog("graviex SubmitTrade err: ".json_encode($res));
					continue;
				}
	
				$amount -= $sellamount;
			}
		}
	
		if($amount <= 0) continue;
	
		if($coin->sellonbid)
			$sellprice = bitcoinvaluetoa($marketsummary['buy']);
		else
			$sellprice = bitcoinvaluetoa($marketsummary['sell']  - 0.00000001); // lowest ask price +5%

			if($amount * $sellprice < $min_btc_trade) continue;

			debuglog("graviex: selling $amount $symbol at $sellprice");

			$orderparameters = [ 'market' => strtolower($balance['currency']).'btc' ,
								 'price' => number_format($sellprice,9) ,
								 'side' => 'sell' ,
								 'volume' => $amount ];
			$res = graviex_api_user('orders.json', $orderparameters , 'POST', 'array');
			
			if(!is_array($res)) {
				debuglog("graviex SubmitTrade err: ".json_encode($res));
				continue;
			}

			$db_order = new db_orders;
			$db_order->market = 'graviex';
			$db_order->coinid = $coin->id;
			$db_order->amount = $amount;
			$db_order->price = $sellprice;
			$db_order->ask = $marketsummary['sell'];
			$db_order->bid = $marketsummary['buy'];
			$db_order->uuid = $res['id'];
			$db_order->created = time();
			$db_order->save();
	}
	
	return;
	
}