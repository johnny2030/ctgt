<?php

namespace Today;

require_once 'lib/alipay_submit.class.php';
require_once 'lib/alipay_notify.class.php';

class Alipay_DirectPay {

	public $returnUrl = '';
	public $notifyUrl = '';
	public $wap = false;

	private $partner = '2088621551599344';
	private $sellerEmail = '13585745679@163.com';
	private $key = 'wpch2316m7h6uvo2mq604be6walenkmn';
	private $signType = 'MD5';
	private $inputCharset = 'utf-8';
	private $transport = 'http';
	private $cacert = 'cacert.pem';

	public function __construct() {
		$ua = \Today\Today::getUserDevice();
		$this->wap = !in_array( $ua, array( 'desktop', 'tablet' ) );
	}

	public function setUrl( $returnUrl, $notifyUrl ) {
		$this->returnUrl = $returnUrl;
		$this->notifyUrl = $notifyUrl;
	}

	private function getConfigs() {
		$configs = array(
			'partner' => $this->partner,
			'seller_email' => $this->sellerEmail,
			'key' => $this->key,
			'sign_type' => $this->signType,
			'input_charset' => $this->inputCharset,
			'cacert' => getcwd().'/today/Alipay/create_direct_pay_by_user/'.$this->cacert,
			'transport' => $this->transport
		);

		if ( $this->wap ) {
			$configs['seller_id'] = $configs['partner'];
			$configs['payment_type'] = '1';
			$configs['service'] = 'alipay.wap.create.direct.pay.by.user';
			$configs['notify_url'] = $this->notifyUrl;
			$configs['return_url'] = $this->returnUrl;
			unset( $configs['seller_email'] );
		}

		return $configs;
	}

	public function pay( $subject, $body, $orderId, $total ) {
		header( 'Content-Type:text/html;charset=utf-8;' );

		$params = array(
			'service' => 'create_direct_pay_by_user',
			'partner' => $this->partner,
			'seller_email' => $this->sellerEmail,
			'payment_type' => 1,
			'notify_url' => $this->notifyUrl,
			'return_url' => $this->returnUrl,
			'out_trade_no' => $orderId,
			//'subject' => mb_convert_encoding( $subject, $this->inputCharset ),
			'subject' => $subject,
			'total_fee' => $total,
			//'body' => mb_convert_encoding( $body, $this->inputCharset ),
			'body' => $body,
			'show_url' => '',
			'anti_phishing_key' => '',
			'exter_invoke_ip' => '',
			'_input_charset' => $this->inputCharset
		);
		//print_r($params);exit;

		if ( $this->wap ) {
			unset( $params['seller_email'] );
			unset( $params['anti_phishing_key'] );
			unset( $params['exter_invoke_ip'] );
			$params['seller_id'] = $params['partner'];
			$params['app_pay'] = 'Y';
			$params['service'] = 'alipay.wap.create.direct.pay.by.user';  //alipay.wap.trade.create.direct
		}

		$alipaySubmit = new \AlipaySubmit( $this->getConfigs() );
		$html = $alipaySubmit->buildRequestForm( $params, 'get', 'Loading...' );

		//file_put_contents( 'alipay2.logs', $html, FILE_APPEND );

		echo $html;
		exit;
	}

	public function verifyNotify() {
		header( 'Content-Type:text/html;charset=utf-8;' );
		$alipayNotify = new \AlipayNotify( $this->getConfigs() );
		$result = $alipayNotify->verifyNotify();
		if ( $result && in_array( $_POST['trade_status'], array( 'TRADE_SUCCESS', 'TRADE_FINISHED' ) ) ) {
			return true;
		}
		return false;
	}

	public function verifyReturn() {
		header( 'Content-Type:text/html;charset=utf-8;' );
		$alipayNotify = new \AlipayNotify( $this->getConfigs() );
		$result = $alipayNotify->verifyReturn();
		if ( $result && in_array( $_GET['trade_status'], array( 'TRADE_SUCCESS', 'TRADE_FINISHED' ) ) ) {
			return true;
		}
		return false;
	}

}

?>