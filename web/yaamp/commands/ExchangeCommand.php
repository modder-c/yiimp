<?php
/**
 * ExchangeCommand is a console command, to check private API keys
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * php web/yaamp/yiic.php exchange test
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class ExchangeCommand extends CConsoleCommand
{
    protected $basePath;

    /**
     * Execute the action.
     * @param array $args command line parameters specific for this command
     * @return integer non zero application exit code after printing help
     */
    public function run($args)
    {
        $runner = $this->getCommandRunner();
        $commands = $runner->commands;

        $root = realpath(Yii::app()->getBasePath() . DIRECTORY_SEPARATOR . '..');
        $this->basePath = str_replace(DIRECTORY_SEPARATOR, '/', $root);

        if (!isset($args[0]) || $args[0] == 'help') {
            echo "Yiimp exchange command\n";
            echo "Usage: yiimp exchange apitest\n";
            echo "       yiimp exchange get <exchange> <key>\n";
            echo "       yiimp exchange set <exchange> <key> <value>\n";
            echo "       yiimp exchange unset <exchange> <key>\n";
            echo "       yiimp exchange settings <exchange>\n";
            return 1;
        } else if ($args[0] == 'get') {
            return $this->getExchangeSetting($args);
        } else if ($args[0] == 'set') {
            return $this->setExchangeSetting($args);
        } else if ($args[0] == 'unset') {
            return $this->unsetExchangeSetting($args);
        } else if ($args[0] == 'settings') {
            return $this->listExchangeSettings($args);
        } else if ($args[0] == 'apitest') {
            $this->testApiKeys();
            return 0;
        }
    }

    /**
     * Provides the command description.
     * @return string the command description.
     */
    public function getHelp()
    {
        return $this->run(array('help'));
    }

    public function getExchangeSetting($args)
    {
        if (count($args) < 3) {
            die("usage: yiimp exchange get <exchange> <key>\n");
        }
        $exchange = $args[1];
        $key = $args[2];
        $value = exchange_get($exchange, $key);
        echo "$value\n";
        return 0;
    }

    public function setExchangeSetting($args)
    {
        if (count($args) < 4) {
            die("usage: yiimp exchange set <exchange> <key> <value>\n");
        }
        $exchange = $args[1];
        $key = $args[2];
        $value = $args[3];
        $keys = exchange_valid_keys($exchange);
        if (!isset($keys[$key])) {
            echo "error: key '$key' is not handled!\n";
            return 1;
        }
        $res = exchange_set($exchange, $key, $value);
        $val = exchange_get($exchange, $key);
        echo ($res ? "$exchange $key " . json_encode($val) : "error") . "\n";
        return 0;
    }

    public function unsetExchangeSetting($args)
    {
        if (count($args) < 3) {
            die("usage: yiimp exchange unset <exchange> <key>\n");
        }
        $exchange = $args[1];
        $key = $args[2];
        exchange_unset($exchange, $key);
        echo "ok\n";
        return 0;
    }

    public function listExchangeSettings($args)
    {
        if (count($args) < 2) {
            die("usage: yiimp exchange settings <exchange>\n");
        }
        $exchange = $args[1];
        $keys = exchange_valid_keys($exchange);
        foreach ($keys as $key => $desc) {
            $val = exchange_get($exchange, $key);
            if ($val !== null) {
                echo "$exchange $key " . json_encode($val) . "\n";
            }
        }
        return 0;
    }

    public function testApiKeys()
    {
        // Check CoinEx balance
        if (!empty(EXCH_COINEX_KEY)) {
            $coinex_balance = $this->coinex_api_user('balance');
            if (!is_array($coinex_balance) || !isset($coinex_balance['data']['BTC'])) {
                echo "coinex error " . json_encode($coinex_balance) . "\n";
            } else {
                echo("coinex btc: " . json_encode($coinex_balance['data']['BTC']) . "\n");
            }
        }

        // Add checks for other exchanges here...
    }

    /**
     * Make a request to the CoinEx API.
     *
     * @param string $endpoint The API endpoint to call.
     * @param array $params Additional parameters for the request.
     * @return array|null The decoded JSON response or null on error.
     */
    private function coinex_api_user($endpoint, $params = [])
    {
        $url = "https://api.coinex.com/v1/$endpoint";
        $params['access_id'] = EXCH_COINEX_KEY;
        $params['tonce'] = time() * 1000; // Current timestamp in milliseconds

        // Create the signature
        $sign = http_build_query($params) . '&secret=' . EXCH_COINEX_SECRET;
        $params['sign'] = md5($sign);

        // Set up the request headers
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($params),
            ],
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            return null; // or handle the error as needed
        }

        return json_decode($result, true);
    }
}
