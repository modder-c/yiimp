<?php
/**
 * Class to handle HTTP(s)-requests
 */
class cHTTP {
	/**
	 * CurlPointer
	 *
	 * @var integer
	 */
	private $fCurlPointer;

	/**
	 * URL
	 *
	 * @var string
	 */
	private $fURL;

	/**
	 * Port
	 *
	 * @var integer
	 */
	private $fPort;

	/**
	 * Parameter
	 *
	 * @var string
	 */
	private $fParameter;

	/**
	 * UseSSL
	 *
	 * @var boolean
	 */
	private $fDisableSSLCheck;

	/**
	 * UseCert
	 *
	 * @var boolean
	 */
	private $fUseCert;

	/**
	 * SSLCert
	 *
	 * @var string
	 */
	private $fSSLCert;

	/**
	 * SSLKey
	 *
	 * @var string
	 */
	private $fSSLKey;

	/**
	 * Cookies
	 *
	 * @var array
	 */
	private $fCookies;
	/**
	 * CookieCache
	 *
	 * @var array
	 */
	private $fCookieCache;
	
	/**
	 * Cookies
	 *
	 * @var array
	 */
	private $fEnableCloudFlareBypass;

	/**
	 * Referer
	 *
	 * @var string
	 */
	private $fReferer;

	/**
	 * Referer
	 *
	 * @var string
	 */
	private $fUserAgent;

	/**
	 * Headers
	 *
	 * @var string
	 */
	private $fHeaders;

	/**
	 * HTTPProxy
	 *
	 * @var string
	 */
	private $fHTTPProxy;

	/**
	 * HTTPProxyUser
	 *
	 * @var string
	 */
	private $fHTTPProxyUser;

	/**
	 * FollowRedirects
	 *
	 * @var boolean
	 */
	private $fFollowRedirects;

	/**
	 * MaxRedirects
	 *
	 * @var integer
	 */
	private $fMaxRedirects;

	/**
	 * FailOnError
	 *
	 * @var boolean
	 */
	private $fFailOnError;

	/**
	 * TimeOut
	 *
	 * @var integer
	 */
	private $fTimeOut;

	/**
	 * ContentFP
	 *
	 * @var integer
	 */
	private $fContentFP;

	/**
	 * Postfields
	 *
	 * @var string
	 */
	private $fPostfields;

	/**
	 * Result
	 *
	 * @var array
	 */
	public $fResult;


	public function __construct() {
		$this->fCurlPointer				= false;
		$this->fURL						= '';
		$this->fPort					= false;
		$this->fParameter				= '';
		$this->fDisableSSLCheck			= false;
		$this->fUseCert					= false;
		$this->fSSLCert					= false;
		$this->fSSLKey					= false;
		$this->fCookies					= array();
		$this->fReferer					= false;
		$this->fUserAgent				= false;
		$this->fPostfields				= '';
		$this->fHeaders					= '';
		$this->fHTTPProxy				= false;
		$this->fHTTPProxyUser			= '';
		$this->fFollowRedirects			= false;
		$this->fMaxRedirects			= 5;
		$this->fTimeOut					= 30;
		$this->fContentFP				= false;
		$this->fFailOnError				= true;
		$this->fEnableCloudFlareBypass	= true;
	}

	public function setURL( $URL ) {
		if ($this->fURL != $URL) {
			$this->fURL = $URL;
			//Reset Curl
			$this->fCurlPointer = false;
		}
		
		$this->fetchCookieCache();
	}

	public function setPort( $Port ) {
		$this->fPort = $Port;
	}

	public function setConnectionTimeOut( $fTimeOut ) {
		$this->fTimeOut = $fTimeOut;
	}

	/**
	 * @param string $Postfields - param-string (URL-encoded fields)
	 */
	public function setPostfields( $Postfields ) {
		$this->fPostfields = $Postfields;
	}

	public function setUserAgentString( $fUserAgent ) {
		$this->fUserAgent = $fUserAgent;
	}

	public function setReferer( $fReferer ) {
		$this->fReferer = $fReferer;
	}

	public function setHeaders( $fHeaders ) {
		$this->fHeaders = $fHeaders;
	}

	public function setFailOnError( $fFailOnError ) {
		if ($fFailOnError === false)
			$this->fFailOnError = false;
			else
				$this->fFailOnError = true;
	}

	public function setHTTPProxy( $fHTTPProxy ) {
		$this->fHTTPProxy = $fHTTPProxy;
	}

	public function setHTTPProxyUser( $fHTTPProxyUser ) {
		$this->fHTTPProxyUser = $fHTTPProxyUser;
	}

	public function enableFollowRedirects( $fValue ) {
		if ($fValue === true) {
			$this->fFollowRedirects = true;
		} else {
			$this->fFollowRedirects = false;
		}
	}
	
	public function enableCloudFlareBypass( $aBool ) {
		if ($aBool === true) {
			$this->fEnableCloudFlareBypass = true;
			$this->setFailOnError(false);
			if (empty($this->fUserAgent)) $this->setUserAgentString('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
		} else {
			$this->fEnableCloudFlareBypass = false;
		}
	}

	public function setMaxRedirects( $fMaxRedirects ) {
		$this->fMaxRedirects = $fMaxRedirects;
	}
	
	public function setContentFilepointer( $fContentFP ) {
		$this->fContentFP = $fContentFP;
	}

	public function disableSSLChecks( $fValue ) {
		if ($fValue === true) {
			$this->fDisableSSLCheck = true;
		} else {
			$this->fDisableSSLCheck = false;
		}
	}

	public function enableSSLCertificates( $fValue ) {
		if ($fValue === true) {
			$this->fUseCert = true;
		} else {
			$this->fUseCert = false;
		}
	}

	public function setSSLCertPath( $fSSLCertPath ) {
		$this->fSSLCert = $fSSLCertPath;
	}

	public function setSSLKeyPath( $fSSLKeyPath ) {
		$this->fSSLKey = $fSSLKeyPath;
	}

	private function buildCookieString( $BaseURL = false ) {

		if (( $this->fCookies !== false) && (is_array( $this->fCookies )) )  {

			if ($BaseURL !== false) {
				$split_url = parse_url($BaseURL);
			}
			else {
				$split_url = parse_url($this->fURL);
			}

			$cookie = array();
			foreach( $this->fCookies as $keyname => $d ) {
				$domain = null;
				if (isset($d['domain'])) {
					$domain = $d['domain'];
				}
				if (!is_null($domain)) {
					if (false === strstr($split_url["host"], $domain)) continue;
				}

				$cookie[] = $keyname.'='.$d['value'];
			}

			if( count( $cookie ) > 0 ) {
				return trim( implode( '; ', $cookie ) );
			}
		}
		return false;
	}

	public function parseHeaders( $headerstring ) {
		$headers = explode("\r\n",$headerstring);

		foreach( $headers as $key => $line ) {
			if ($line == '') unset($headers[$key]);
		}

		return $headers;
	}

	public function parseCookies( $headerstring ) {
		$cookies = array();
		$header = $this->parseHeaders( $headerstring );

		foreach( $header as $line ) {
			if( preg_match( '/^Set-Cookie: /i', $line ) ) {
				$line = preg_replace( '/^Set-Cookie: /i', '', trim( $line ) );
				$csplit = explode( ';', $line );
				$cdata = array(); unset($cname);
				foreach( $csplit as $data ) {
					$cinfo = explode( '=', $data, 2);
					if (!isset($cinfo[0])) continue;
					if (!isset($cinfo[1])) $cinfo[1] = '';
					$cinfo[0] = trim( $cinfo[0] );
					if( $cinfo[0] == 'expires' ) $cinfo[1] = strtotime( $cinfo[1] );
					if( $cinfo[0] == 'secure' ) $cinfo[1] = 'true';
					if( in_array( $cinfo[0], array( 'domain', 'expires', 'path', 'secure', 'comment' ) ) ) {
						$cdata[trim( $cinfo[0] )] = $cinfo[1];
					}
					else {
						if (!isset($cname)) {
							$cname = $cinfo[0];
							$cdata['value'] = urldecode($cinfo[1]);
						}
					}
				}
				$cookies[$cname] = $cdata;
			}
		}
		return $cookies;
	}

	public function deleteCookie( $fCookiename ) {
		$tmpcookies = array();
		$tmpcookies[$fCookiename] = array( 'value' => '' );
		$this->updateCookies( $tmpcookies );
	}

	public function updateCookies( $fNewCookies ) {
		if( is_array( $fNewCookies ) ) {
			foreach( $fNewCookies as $keyname => $cookievalue ) {
				if (isset($this->fCookies[$keyname])) unset($this->fCookies[$keyname]);
				if ((isset($cookievalue['value'])) &&
						(trim($cookievalue['value']) != '')) {
							$this->fCookies[$keyname] = $cookievalue;
						}
			}
		}

		// delete expired Cookies
		foreach( $this->fCookies as $keyname => $cookievalue ) {
			$currenttime=time();
			if (isset($this->fCookies[$keyname]['expires'])) {
				if ($this->fCookies[$keyname]['expires'] < $currenttime) {
					unset($this->fCookies[$keyname]);
				}
			}
		}
		
		$this->pushCookieCache();

		return false;
	}

	private function fetchCookieCache(){
		if (is_null($this->fCookieCache)) return;
		$this->fCookies = $this->fCookieCache->get('cHTTP_Cookie_'.$this->getDomainNameHash());
		if (!is_array($this->fCookies)) {
			$this->fCookies = array();
		}
	}
	private function pushCookieCache(){
		if (is_null($this->fCookieCache)) return;
		$this->fCookieCache->set('cHTTP_Cookie_'.$this->getDomainNameHash(), $this->fCookies);
	}
	private function getDomainNameHash(){
		return (preg_match('/^www./', $site_host))?md5(substr($this->fURL, 4)):md5($this->fURL);
	}
	public function setCookieCacheEnabled($CookieCacheEnabled){
		if ((bool)$CookieCacheEnabled){
			if (is_null($this->fCookieCache)) {
				$this->fCookieCache = new Memcached();
				$this->fetchCookieCache();
			}
		}
		else {
			if (!is_null($this->fCookieCache)) {
				unset($this->fCookieCache);
				$this->fCookieCache = null;
			}
		}
	}
	
	public function execRequest() {

		// Init & DataCheck
		if (($this->fURL === false) || ($this->fURL == '')) {
			return false;
		}

		if (($this->fCurlPointer === false) || (is_null($this->fCurlPointer))) {
			$this->fCurlPointer = curl_init( $this->fURL );
		}

		$this->fResult = array( 'errno' => 0,
				'HTTP_Code' => 0,
				'HTTP_Header' => false,
				'HTTP_Cookies' => false,
				'HTTP_Body' => false );

		// Content in File umleiten oder im Speicher zurückgeben
		// Für File-Rückgabe die Header-analyse nicht aktivieren
		if ( $this->fContentFP !== false ) {
			curl_setopt($this->fCurlPointer, CURLOPT_FILE, $this->fContentFP);
		}
		else {
			curl_setopt($this->fCurlPointer, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->fCurlPointer, CURLOPT_HEADER, 1);
		}
		
		curl_setopt($this->fCurlPointer, CURLINFO_HEADER_OUT, 1);
		curl_setopt($this->fCurlPointer, CURLOPT_NOBODY, 0);
		curl_setopt($this->fCurlPointer, CURLOPT_ENCODING, "gzip,deflate");

		curl_setopt($this->fCurlPointer, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
		
		// http-returncodes >400 triggers error-handling or not
		if ($this->fFailOnError)
			curl_setopt($this->fCurlPointer, CURLOPT_FAILONERROR, 1);
		else
			curl_setopt($this->fCurlPointer, CURLOPT_FAILONERROR, 0);

		if (( $this->fReferer !== false ) && ( $this->fReferer != '' )) {
			$tmpreferer = $this->fReferer;
			if (trim(parse_url($tmpreferer, PHP_URL_PATH))=='') $tmpreferer.='/';
			curl_setopt($this->fCurlPointer, CURLOPT_REFERER, $tmpreferer);
		}

		if (( $this->fUserAgent !== false ) && ( $this->fUserAgent != '' )) {
			curl_setopt($this->fCurlPointer, CURLOPT_USERAGENT, $this->fUserAgent);
		}

		if (( $this->fHTTPProxy !== false ) && ( $this->fHTTPProxy != '' )) {
			curl_setopt($this->fCurlPointer, CURLOPT_PROXY, $this->fHTTPProxy);

			if (( $this->fHTTPProxyUser !== false ) && ( $this->fHTTPProxyUser != '' )) {
				curl_setopt($this->fCurlPointer, CURLOPT_PROXYUSERPWD, $this->fHTTPProxyUser);
			}
		}

		// follow HTTP-redirects and set max. recursion-depth
		if ( $this->fFollowRedirects !== false ) {
			curl_setopt($this->fCurlPointer, CURLOPT_FOLLOWLOCATION, 1);

			if ( $this->fMaxRedirects !== false ) {
				curl_setopt($this->fCurlPointer, CURLOPT_MAXREDIRS, $this->fMaxRedirects);
			}
		}
		else {
			curl_setopt($this->fCurlPointer, CURLOPT_FOLLOWLOCATION, 0);
		}

		if (( $this->fPort !== false ) && ( $this->fPort != 0 )) {
			curl_setopt($this->fCurlPointer, CURLOPT_PORT, $this->fPort);
		}

		if (( $this->fPostfields !== false ) && ( $this->fPostfields != '' )) {
			curl_setopt($this->fCurlPointer, CURLOPT_POST, 1);
			curl_setopt($this->fCurlPointer, CURLOPT_POSTFIELDS, $this->fPostfields);
		}

		if ( $this->fUseCert !== false ) {
			if (( $this->fSSLCert !== false ) && ( $this->fSSLCert != '' )) {
				curl_setopt($this->fCurlPointer, CURLOPT_SSLCERT, $this->fSSLCert);
			}
			if (( $this->fSSLKey !== false ) && ( $this->fSSLKey != '' )) {
				curl_setopt($this->fCurlPointer, CURLOPT_SSLKEY, $this->fSSLKey);
			}
		}

		if ( $this->fDisableSSLCheck === true ) {
			curl_setopt($this->fCurlPointer, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($this->fCurlPointer, CURLOPT_SSL_VERIFYHOST, 0);
		}

		if ( $this->fCookies !== false ) {
			$tmpRequestCookies = $this->buildCookieString( false );
			curl_setopt($this->fCurlPointer, CURLOPT_COOKIE, $tmpRequestCookies);
		}

		if (( $this->fHeaders !== false ) && ( is_array($this->fHeaders) )) {
			curl_setopt($this->fCurlPointer, CURLOPT_HTTPHEADER, $this->fHeaders);
		}

		// max time for request, defaults to 30 sec
		if (( $this->fTimeOut !== false ) && ( $this->fTimeOut > 0 )) {
			curl_setopt($this->fCurlPointer, CURLOPT_TIMEOUT, $this->fTimeOut);
		}
		else {
			curl_setopt($this->fCurlPointer, CURLOPT_TIMEOUT, 30);
		}

		// all done, exec request
		$result = curl_exec($this->fCurlPointer);
		
		//CloufdFlareBypass if enabled
		if ($this->isProtectedByCloudFlare($result)){
			$result = $this->bypassCloudFlare($result);
		}

		// parse answer into result-structure fResult
		$this->fResult['errno'] = curl_errno($this->fCurlPointer);

		$info = curl_getinfo($this->fCurlPointer);

		$this->fResult['HTTP_Code'] = $info['http_code'];

		if ($this->fResult['errno'] === CURLE_OK) {
			if ($this->fContentFP === false) {
				$tmpheaders = substr($result, 0, $info['header_size']);
				$this->fResult['HTTP_Header'] = $this->parseHeaders($tmpheaders);
				$this->fResult['HTTP_Cookies'] = $this->parseCookies($tmpheaders);

				if (strlen($result) <= $info['header_size']) {
					$this->fResult['HTTP_Body'] = '';
				}
				else {
					$this->fResult['HTTP_Body'] = substr($result, $info['header_size']);
				}
			}
			else {
				$this->fResult['HTTP_Body'] = '';
			}

			$this->fURL = $info['url'];
		}

		return $this->fResult['HTTP_Body'];
	}

	/**
	 * Tools CloadFlare
	 */
	private function isProtectedByCloudFlare($OriginResult){		
		//Check 
		if (!$this->fEnableCloudFlareBypass) return false;
		
		//Check Cloudflare UAM page always throw a 503
		$Info = curl_getinfo($this->fCurlPointer);
		if ((int) $Info['http_code'] !== 503) {
			return false;
		}

		/*
		 * Cloudflare UAM page contains the following strings:
		 * - jschl_vc
		 * - pass
		 * - jschl_answer
		 * - /cdn-cgi/l/chk_jschl
		 */
		if (!(
			strpos($OriginResult, "jschl_vc")				!== false &&
			strpos($OriginResult, "pass")					!== false &&
			strpos($OriginResult, "jschl_answer")			!== false &&
			strpos($OriginResult, "/cdn-cgi/l/chk_jschl")	!== false
		)) {
			 return false;
		}
	
		return true;
	}
	private function bypassCloudFlare($OriginResult) {
		//UserAgent ist zwingend
		if (( $this->fUserAgent === false ) || ( $this->fUserAgent == '' )) {
			throw new ErrorException('CURLOPT_USERAGENT is a mandatory for cloudflare bypass!');
		}
		
		//Parse Cookies
		$Info = curl_getinfo($this->fCurlPointer);
		$OriginCookies = $this->parseCookies(substr($OriginResult, 0, $Info['header_size']));
		
		//Cookie "__cfduid" muss existieren
		if (!isset($OriginCookies['__cfduid'])) {
			return $OriginResult;
		}
		
		// Clone curl object handle.
		$CFCurlHandle = curl_copy_handle($this->fCurlPointer);
		
		//Set Cookie "__cfduid"
		$this->updateCookies(array('__cfduid' => $OriginCookies['__cfduid']));
		curl_setopt($this->fCurlPointer, CURLOPT_COOKIE, $this->buildCookieString( false ));
		
		//resolve CloadFlare recall origin 
		if ($this->resolveCloudFlare($OriginResult, $CFCurlHandle)) {			
			return curl_exec($this->fCurlPointer);
		}
		else {
			return $OriginResult;
		}
	}
	private function resolveCloudFlare($aOriginResult, $aCurlHandle, $aRetryCounter=1) {
		// Solve challenge and request clearance link
			$params = array();
			$CurlInfo = curl_getinfo($aCurlHandle);
			
			//1. Wait SleepTime
			sleep(4);
			
			//2. Extract "jschl_vc" and "pass" params
			preg_match_all('/name="\w+" value="(.+?)"/', $aOriginResult, $matches);
			if (!isset($matches[1]) || !isset($matches[1][1])) {
				return false;
				//throw new ErrorException('Unable to fetch jschl_vc and pass values; maybe not protected?');
			}
			list($params['jschl_vc'], $params['pass']) = $matches[1];
			
			//3. Extract JavaScript challenge logic
			preg_match_all('/:[!\[\]+()]+|[-*+\/]?=[!\[\]+()]+/', $aOriginResult, $matches);
			if (!isset($matches[0]) || !isset($matches[0][0])) {
				return false;
				//throw new \ErrorException('Unable to find javascript challenge logic; maybe not protected?');
			}
			
			try {
				//4. Convert challenge logic to PHP
				$php_code = '';
				foreach ($matches[0] as $js_code) {
					// [] causes "invalid operator" errors; convert to integer equivalents
					$js_code = str_replace(array(
						")+(",  
						"![]",
						"!+[]", 
						"[]"
					), array(
						").(", 
						"(!1)", 
						"(!0)", 
						"(0)"
					), $js_code);
	
					$php_code .= '$params[\'jschl_answer\']' . ($js_code[0] == ':' ? '=' . substr($js_code, 1) : $js_code) . ';';
				}
			
				// 5. Eval PHP and get solution
				eval($php_code);

				// Split url into components.
				$uri = parse_url($CurlInfo['url']);

				// Add host length to get final answer.
				$params['jschl_answer'] += strlen($uri['host']);
	
				/*
				 * 6. Generate clearance link
				 */
				$ClearanceLink = sprintf("%s://%s/cdn-cgi/l/chk_jschl?%s", 
					$uri['scheme'], 
					$uri['host'], 
					http_build_query($params)
				);
			}
			catch (Exception $ex) {
				// PHP evaluation bug; inform user to report bug
				throw new ErrorException(sprintf('Something went wrong! Please report an issue: %s', $ex->getMessage()));
			}
		 
		//Prepare CF clearance link 
		curl_setopt($aCurlHandle, CURLOPT_URL, $ClearanceLink);
		curl_setopt($aCurlHandle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($aCurlHandle, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($aCurlHandle, CURLOPT_HTTPGET, 1);
		$CFResult = curl_exec($aCurlHandle);
		$CurlInfo = curl_getinfo($aCurlHandle);
		
		//Extract "cf_clearance" cookie
		$CFCookies = $this->parseCookies(substr($CFResult, 0, $CurlInfo['header_size']));
		if (!isset($CFCookies['cf_clearance'])) {
			if ($aRetryCounter > 5) {
				throw new \ErrorException("Exceeded maximum retries trying to get CF clearance!");   
			}
			$CFResult = resolveCloudFlare($CFResult, $aCurlHandle, $aRetryCounter+1);
		}
		else {
			$this->updateCookies(array('cf_clearance' => $CFCookies['cf_clearance']));
			curl_setopt($this->fCurlPointer, CURLOPT_COOKIE, $this->buildCookieString( false ));
		}
		
		return true;
	}
}
?>