<?php

/**
 * sms
 */

namespace Today;

class SMS {

	private static $key = '3207487cfe62bb9f7090b65cbaee06fd';
	private static $get_user_url = 'https://sms.yunpian.com/v1/user/get.json';
	private static $get_send_url = 'https://sms.yunpian.com/v1/sms/send.json';
	private static $get_tpl_url = 'https://sms.yunpian.com/v1/sms/tpl_send.json';

	public static function send( $tpl, $phone, $value ) {
		header( 'Content-Type:text/html; charset=utf-8' );

		$result = self::sendSmsByTpl( $tpl, $phone, $value );
		$result = json_decode( $result, true );
		return array( 'status' => $result['code'] == 0 ? true : false, 'msg' => $result['msg'], 'detail' => $result['result'] );
	}

	private function init() {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Accept:text/plain;charset=utf-8',
			'Content-Type:application/x-www-form-urlencoded',
			'charset=utf-8') );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		return $ch;
	}

	private function getUser() {
		$ch = self::init();
		curl_setopt( $ch, CURLOPT_URL, self::$get_user_url );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
			'apikey' => self::$key ) ) );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
	}

	private function sendSms( $phone, $content ) {
		$ch = self::init();
		curl_setopt( $ch, CURLOPT_URL, self::$get_send_url );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
			'apikey' => self::$key,
			'mobile' => $phone,
			'text' => $content
		) ) );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
	}

	private function sendSmsByTpl( $tpl, $phone, $value ) {
		$ch = self::init();
		curl_setopt( $ch, CURLOPT_URL, self::$get_tpl_url );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
			'apikey' => self::$key,
			'mobile' => $phone,
			'tpl_id' => $tpl,
			'tpl_value' => $value
		) ) );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
	}

}

//$sms = new \Today\SMS;
//$sms->send( '1372879', '15922448358', '#code#'.'='.urlencode( '123456' ) );

?>