<?php

/**
 *
 */

namespace Today;

class Today {

	/**
	 * 得到指定长度的随机数字字符串
	 */
	public static function rnd( $len = 6 ) {
		$rnd = '';
		while ( strlen( $rnd ) < $len ) $rnd .= rand();
		if ( strlen( $rnd ) > $len ) $rnd = substr( $rnd, 0, $len );
		return $rnd;
	}

	/**
	 * 得到访问者UA
	 */
	public static function getUserDevice( $ua = null ) {
		if ( ! isset( $ua ) ) {
			$ua = $_SERVER['HTTP_USER_AGENT'];
		}
		$iphone = strstr( strtolower( $ua ), 'mobile' );
		$android = strstr( strtolower( $ua ), 'android' );
		$windowsPhone = strstr( strtolower( $ua ), 'phone' );
		$microMessenger = strstr( strtolower( $ua ), 'micromessenger' );

		if ( $microMessenger ) return 'wechat';

		

		$androidTablet = self::androidTablet( $ua );
		$ipad = strstr( strtolower( $ua ), 'ipad' );

		if ( $androidTablet || $ipad ) {
			return 'tablet';
		}
		elseif ( $iphone && ! $ipad || $android && ! $androidTablet || $windowsPhone ) {
			return 'mobile';
		}
		else {
			return 'desktop';
		}
	}

	private function androidTablet( $ua ) {
		if ( strstr( strtolower( $ua ), 'android' ) ) {
			if ( ! strstr( strtolower( $ua ), 'mobile' ) ) {
				return true;
			}
		}
	}

	/**
	 * 将数组转成xml对象
	 */
	public static function Array2Xml( $arrayObj, $xmlDoc = null, $ele = null, $rootName = '', $version = '1.0', $charset = 'UTF-8' ) {
		if ( ! isset( $xmlDoc ) ) {
			$xmlDoc = new \DOMDocument( $version, $charset );
			$xmlDoc->formatOutput = true;
		}
		if ( ! isset( $ele ) ) {
			$ele = $xmlDoc->createElement( $rootName );
			$xmlDoc->appendChild( $ele );
		}
	 
		foreach ( $arrayObj as $key => $val ) {
			$elex = $xmlDoc->createElement( is_string( $key ) ? $key : 'item' );
			$ele->appendChild( $elex );
			if ( is_array( $val ) ) {
				self::Array2Xml( $val, $xmlDoc, $elex );
			}
			else {
				$elexText = $xmlDoc->createCDATASection( $val );
				$elex->appendChild( $elexText );
			}
		}
		return $xmlDoc;
	}

	/**
	 * 将xml对象转成数组
	 */
	public static function Xml2Array( $xmlObj ) {
		$result = array();
		$array = $xmlObj;
		if ( get_class($array) == 'SimpleXMLElement' ) {
			$array = get_object_vars( $xmlObj );
		}
		if ( is_array( $array ) ) {
			if ( count( $array ) <= 0 ) {
				return trim( strval( $xmlObj ) );
			}
			foreach ( $array as $key => $val ) {
				$result[$key] = self::Xml2Array( $val );
			}
			return $result;
		}
		else {
			return trim( strval( $array ) );
		}
	}

	/**
	 * CURL
	 */
	public static function httpRequest( $url, $data = null ) {
		//$data是字符串，则application/x-www-form-urlencoded
		//$data是数组，则multipart/form-data
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, FALSE );
		if ( !empty( $data ) ) {
			curl_setopt( $curl, CURLOPT_POST, 1 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
		}
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		$output = curl_exec( $curl );
		curl_close( $curl );
		return $output;
	}

	/**
	 * 得到IP
	 */
	public static function ip() {
		$unknown = 'unknown';

		if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown) )
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown))
			$ip = $_SERVER['REMOTE_ADDR'];
		$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches)?$matches[0]:$unknown;
		if ( false !== strpos($ip, ',') ) $ip = reset( explode(',', $ip) );

		return $ip;
	}

	/**
	 * 得到当前网址
	 */
	public static function currentUrl( $last = true ) {
		require_once 'ParseURL.php';
		$ParseURL = new \ParseURL();
		$url = $ParseURL->toString();
		return $last ? $url : substr( $url, 0, strlen( $url ) - 1 );
	}

}

?>