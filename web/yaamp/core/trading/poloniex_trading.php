<?php

function doCoinExCancelOrder($OrderID=false, $pair=false, $coinex=false)
{
    if(!$OrderID) return;
    if(!$pair) return;

    if(!$coinex) $coinex = new CoinEx();

    $res = $coinex->cancelOrder($pair, $OrderID);
    if($res && isset($res['code']) && $res['code'] == 0) // CoinEx returns code 0 for success
    {
        $db_order = getdbosql('db_orders', "market=:market AND uuid=:uuid", array(
            ':market'=>'coinex', ':uuid'=>$OrderID
        ));
        if($db_order) $db_order->delete();
    }
}

function doCoinExTrading()
{
    $exchange = 'coinex';
    $updatebalances = true;

    $coinex = new CoinEx();

    // Add orders
    $savebalance = getdbosql('db_balances', "name='$exchange'");
    $balances = $coinex->getBalance();

    if (is_array($balances))
    foreach($balances as $symbol => $balance)
    {
        if ($symbol == 'BTC') {
            if (is_object($savebalance)) {
                $savebalance->balance = $balance['available'];
                $savebalance->onsell = $balance['onOrders'];
                $savebalance->save();
            }
            continue;
        }

        if ($updatebalances) {
            // Store available balance in market table
            $coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
                array(':symbol'=>$symbol)
            );
            if (empty($coins)) continue;
            foreach ($coins as $coin) {
                $market = getdbosql('db_markets', "coinid=:coinid AND name='$exchange'", array(':coinid'=>$coin->id));
                if (!$market) continue;
                $market->balance = $balance['available'];
                $market->ontrade = $balance['onOrders'];
                $market->balancetime = time();
                $market->save();
            }
        }
    }

    if (!YAAMP_ALLOW_EXCHANGE) return;

    $flushall = rand(0, 8) == 0;

    // Minimum order allowed by the exchange
    $min_btc_trade = exchange_get($exchange, 'trade_min_btc', 0.00010000);
    // Sell on ask price + 5%
    $sell_ask_pct = exchange_get($exchange, 'trade_sell_ask_pct', 1.05);
    // Cancel order if our price is more than ask price + 20%
    $cancel_ask_pct = exchange_get($exchange, 'trade_cancel_ask_pct', 1.20);

    sleep(1);
    $tickers = $coinex->getTicker();
    if(!$tickers) return;

    // Update orders
    $coins = getdbolist('db_coins', "enable=1 AND dontsell=0 AND id IN (SELECT DISTINCT coinid FROM markets WHERE name='coinex')");
    foreach($coins as $coin)
    {
        $pair = "{$coin->symbol}_BTC"; // Example: ETH_BTC
        if(!isset($tickers[$pair])) continue;

        sleep(1);
        $orders = $coinex->getOpenOrders($pair);
        if(!$orders || !isset($orders[0]))
        {
            dborun("DELETE FROM orders WHERE coinid={$coin->id} AND market='coinex'");
            continue;
        }

        foreach($orders as $order)
        {
            if(!isset($order['order_id']))
            {
                debuglog($order);
                continue;
            }

            if($order['rate'] > $tickers[$pair]['lowestAsk']*$cancel_ask_pct || $flushall)
            {
                debuglog("coinex: cancel order for $pair {$order['order_id']}");
                sleep(1);
                doCoinExCancelOrder($order['order_id'], $pair, $coinex);
            }
            else
            {
                $db_order = getdbosql('db_orders', "market=:market AND uuid=:uuid", array(
                    ':market'=>'coinex', ':uuid'=>$order['order_id']
                ));
                if($db_order) continue;

                // Save order
                $db_order = new db_orders;
                $db_order->market = 'coinex';
                $db_order->coinid = $coin->id;
                $db_order->amount = $order['amount'];
                $db_order->price = $order['rate'];
                $db_order->ask = $tickers[$pair]['lowestAsk'];
                $db_order->bid = $tickers[$pair]['highestBid'];
                $db_order->uuid = $order['order_id'];
                $db_order->created = time();
                $db_order->save();
            }
        }

        // Clean up old orders
        $list = getdbolist('db_orders', "coinid={$coin->id} AND market='coinex'");
        foreach($list as $db_order)
        {
            $found = false;
            foreach($orders as $order)
            {
                if(!isset($order['order_id'])) {
                    debuglog("coinex no order id: ".json_encode($order));
                    continue;
                }

                if($order['order_id'] == $db_order->uuid) {
                    $found = true;
                    break;
                }
            }

            if(!$found)
            {
                debuglog("coinex: deleting order {$coin->symbol} $db_order->amount");
                $db_order->delete();
            }
        }
    }

    // Add orders
    if (is_array($balances))
    foreach($balances as $symbol=>$balance)
    {
        if(!$balance || !arraySafeVal($balance,'available')) continue;
        if($symbol == 'BTC') continue;

        $coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
        if(!$coin || $coin->dontsell) continue;

        $market = getdbosql('db_markets', "coinid={$coin->id} AND name='coinex'");
        if($market) {
            $market->lasttraded = time();
            $market->balance = $balance['onOrders'];
            $market->save();
        }

        $pair = "{$symbol}_BTC"; // Example: ETH_BTC
        if(!isset($tickers[$pair])) continue;

        if($coin->sellonbid)
            $sellprice = bitcoinvaluetoa($tickers[$pair]['highestBid']);
        else
            $sellprice = bitcoinvaluetoa($tickers[$pair]['lowestAsk'] * $sell_ask_pct);

        if($balance['available'] * $sellprice < $min_btc_trade) continue;

        sleep(1);
        $res = $coinex->sell($pair, $sellprice, $balance['available']);

        if(!isset($res['order_id']))
        {
            debuglog($res, 5);
            continue;
        }

        if(!isset($tickers[$pair])) continue;

        $coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
        if(!$coin) continue;

        $db_order = new db_orders;
        $db_order->market = 'coinex';
        $db_order->coinid = $coin->id;
        $db_order->amount = $balance['available'];
        $db_order->price = $sellprice;
        $db_order->ask = $tickers[$pair]['lowestAsk'];
        $db_order->bid = $tickers[$pair]['highestBid'];
        $db_order->uuid = $res['order_id'];
        $db_order->created = time();
        $db_order->save();
    }

    $withdraw_min = exchange_get($exchange, 'withdraw_min_btc', EXCH_AUTO_WITHDRAW);
    $withdraw_fee = exchange_get($exchange, 'withdraw_fee_btc', 0.0001);

    if(is_object($savebalance))
    if(floatval($withdraw_min) > 0 && $savebalance->balance >= ($withdraw_min + $withdraw_fee))
    {
        // $btcaddr = exchange_get($exchange, 'withdraw_btc_address', YAAMP_BTCADDRESS);
        $btcaddr = YAAMP_BTCADDRESS;

        $amount = $savebalance->balance - $withdraw_fee;
        debuglog("coinex: withdraw $amount BTC to $btcaddr");

        sleep(1);
        $res = $coinex->withdraw('BTC', $amount, $btcaddr);
        debuglog($res);

        if($res && isset($res['code']) && $res['code'] == 0)
        {
            $withdraw = new db_withdraws;
            $withdraw->market = 'coinex';
            $withdraw->address = $btcaddr;
            $withdraw->amount = $amount;
            $withdraw->time = time();
            $withdraw->save();

            $savebalance->balance = 0;
            $savebalance->save();
        }
    }
}
