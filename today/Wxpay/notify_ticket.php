<?php
@ini_set('date.timezone','Asia/Shanghai');

require_once "lib/WxPay.Api.php";
require_once 'lib/WxPay.Notify.php';

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($input);
		//Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		//Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();

		//file_put_contents( 'wxpay.logs', 'process start '.serialize( $data )."\r\n", FILE_APPEND );

		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}

		//file_put_contents( 'wxpay.logs', 'process end '.serialize( $data )."\r\n", FILE_APPEND );

		D( 'TicketOrder' )->where( array( 'orderNo' => $data['out_trade_no'] ) )->save( array( 'isPay' => 1, 'payTime' => time(), 'tradeNo' => $data['transaction_id'], 'payResult' => serialize( $data ) ) );

		$ticket = D( 'Ticket' )->where( array( 'id' => $order['tid'] ) )->find();
		$match = D( 'Match' )->where( array( 'match_id' => $order['mid'] ) )->find();
		$scene = D( 'TicketScene' )->where( array( 'id' => $order['sid'] ) )->find();
		$zone = D( 'TicketZone' )->where( array( 'id' => $order['zid'] ) )->find();
		$user = D( 'Users' )->where( array( 'id' => $order['userid'] ) )->find();
		$mobile = $user['mobile'];
		if ( $mobile ) {
			global $SMS_ENABLE, $TIME_LIMIT;
			//require_once 'today/class.sms.php';
			//$sms = new \Today\SMS;
			//if ( $SMS_ENABLE ) $sendResult = $sms->send( '1385749', $mobile, '#content#'.'='.urlencode( $match['match_name'] ) );
			require_once 'today/emaysms/class.emaysms.php';
			require_once 'today/emaysms/include/Client.php';
			
			$content = '【兜动体育】您已购票成功！购票信息如下：赛事名称：'.$match['match_name'].'，场次：'.$scene['name'].'，时间：'.date( 'm月d日 H:i', $scene['time'] ).'，分区：'.$zone['name'].'，票价：'.$zone['price'].'元';
			if ( $SMS_ENABLE ) $statusCode = $client->sendSMS(array($mobile),$content);
		}
		

		return true;
	}
}

//$notify = new PayNotifyCallBack();
//$notify->Handle(false);
