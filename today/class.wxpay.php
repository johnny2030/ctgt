<?php

/**
 * 微信支付接口
 */

require_once 'class.today.php';
require_once 'Wechat.php';

class Wxpay {

	private $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
	private $appId = 'wx69217b5af9a538fb';
	private $appSecret = '98e91168e2ac5e907068e62ea340ec23';
	private $mchId = '1489351662';
	private $key = 'blaumdqfginslrv1vqcxh9bfygtzs073';
	private $version = '1.0';

	private $returnUrl = '';
	private $notifyUrl = '';

	/**
	 * 设置URL
	 */
	public function setUrl( $returnUrl, $notifyUrl ) {
		$this->returnUrl = $returnUrl;
		$this->notifyUrl = $notifyUrl;
	}

	/**
	 * 创建支付
	 */
	public function pay( $openId, $ip, $orderNo, $total, $productBody ) {
       // $nonce_str =$this->getNonceStr();
        $time = time();
        $rand = rand();

		$params = array(
            'appid' => $this->appId,
			'trade_type' => 'JSAPI',
			'mch_id' => $this->mchId,
			'notify_url' => $this->notifyUrl,
            'nonce_str' => uniqid().uniqid().mt_rand(100000, 999999),  //随机字符串，不长于 32 位
			'out_trade_no' => $orderNo,
			'body' => $productBody,
			'total_fee' => $total * 100,
			'openid' => $openId,
			'spbill_create_ip' => $ip,
		);
		//测试用
		//$params['total_fee'] = 1;
		$params['sign'] = $this->sign( $params );
		echo json_encode($params);die;
		echo '<pre style="font-size: 20px;">';
        var_dump($params);
		$xml = \Today\Today::Array2Xml( $params, null, null, 'xml' );
		$xml = $xml->saveXML();
		echo $xml;die;
		$result = \Today\Today::httpRequest( $this->url, $xml );
		$xml = \Today\Today::Xml2Array( simplexml_load_string( $result ) );

		if ( $xml['result_code'] == 'SUCCESS' && $this->isSign( $xml ) ) {
			if ( $xml['result_code'] == 'SUCCESS' && $xml['return_code'] == 'SUCCESS' ) return array( 'status' => 200, 'prepay_id' => $xml['prepay_id'] );
			else return array( 'status' => 500, 'msg' => $xml['return_msg'] );
		}
		else return array( 'status' => 500, 'msg' => $xml['status'] == 0 ? '验证签名失败' : $xml['return_msg'] );
	}


	public function verify( $data = array() ) {
		if ( $data ) $xml = $data;
		else {
			$result = file_get_contents( 'php://input' );
			$xml = simplexml_load_string( $result );
			$xml = \Today\Today::Xml2Array( $xml );
		}

		if ( $this->isSign( $xml ) ) {
			if ( $xml['result_code'] == 'SUCCESS' && $xml['return_code'] == 'SUCCESS' ) return array( 'status' => true, 'data' => $xml );
		}
		return array( 'status' => false, 'data' => $xml );
	}

	public function getJsApiPayParams( $prepay_id ) {
		$timeStamp = time();
		$params = array(
			'appId' => $this->appId,
			'timeStamp' => "$timeStamp",  //必须是字符串形式，所以外面的双引号不能省去
			'nonceStr' => $this->getNonceStr(),  //同样必须是字符串
			'package' => 'prepay_id='.$prepay_id,
			'signType' => 'MD5'
		);
		$params['paySign'] = $this->sign( $params );
		return $params;
	}
	/**
	 * 签名
	 */
	private function sign( $data ) {
		$sign = '';
		ksort( $data );
		foreach ( $data as $key => $val ) {
			if ( $val != '' && $key != 'sign' ) {
				$sign .= "{$key}={$val}&";
			}
		}
		$sign .= 'key='.$this->key;
		$sign = strtoupper( md5( $sign ) );
		return $sign;
	}

	/**
	 * 验证签名是否合法
	 */
	private function isSign( $data ) {
		$sign = $this->sign( $data );
		return $sign == $data['sign'];
	}



}

?>