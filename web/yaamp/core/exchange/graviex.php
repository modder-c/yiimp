<?php

// https://graviex.net/api/v2/tickers.json

function graviex_api_query($method, $params='', $returnType='object')
{
	$uri = "https://graviex.net/api/v2/{$method}";
	if (!empty($params)) $uri .= "/{$params}";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$execResult = strip_tags(curl_exec($ch));

	if ($returnType == 'object')
		$ret = json_decode($execResult);
	else
		$ret = json_decode($execResult,true);
	return $ret;
}

function graviex_api_user($method, $url_params = [], $request_method='GET', $returnType='object') {
	$timedrift = 3;
	
	if (empty(EXCH_GRAVIEX_KEY) || empty(EXCH_GRAVIEX_SECRET)) return false;

	$epoch_time = (time() + $timedrift).rand(100,999); # tonce should be different from previous one
	
	$base = 'https://graviex.net';
	$path = '/api/v2/'.$method; $request = '';

	if (is_array($url_params)) {
		$url_params['access_key'] = EXCH_GRAVIEX_KEY;
		$url_params['tonce'] = $epoch_time;
		ksort($url_params);
		$request = http_build_query($url_params, '', '&');
	} elseif (is_string($url_params)) {
		$request = 'access_key=' . EXCH_GRAVIEX_KEY . $url_params. '&tonce=' . $epoch_time;;
	}

	$http_verb = ($request_method == 'POST')?"POST":"GET";
	
	$message = $http_verb."|".$path."|".$request ;
	$sign = hash_hmac('sha256', $message, EXCH_GRAVIEX_SECRET);

	if ($http_verb == 'POST') {
		$uri = $base.$path;
	}
	else {
		$uri = $base.$path.'?'.$request.'&signature='.$sign;
	}

	$httprequest = new cHTTP();
	$httprequest->setURL($uri);
	
	if ($http_verb == 'POST') {
		$httprequest->setPostfields($request.'&signature='.$sign);
	}
	$httprequest->setUserAgentString('Mozilla/4.0 (compatible; graviex API client; '.php_uname('s').'; PHP/'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.')');
	$httprequest->setFailOnError(false);
	$data = $httprequest->execRequest();

	if ($returnType == 'object')
		$res = json_decode($data);
	else
		$res = json_decode($data,true);
	
	$status = $httprequest->fResult['HTTP_Code'];
	
	if($status >= 300) {
		debuglog("graviex: $method failed ($status) ".strip_data($data));
		$res = false;
	}

	return $res;
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
