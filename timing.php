<?php
/**
 * 定时推送 邮件
 */
require_once 'simplewind/Lib/Util/class.phpmailer.php';

ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
set_time_limit(0);//让程序无限制运行下去
$interval = 60;//每隔*秒
date_default_timezone_set("PRC");//设定时区东八区

$mysql_host = '10.0.184.1';
$mysql_username = 'root';
$mysql_password = 'Admin123';
$mysql_database = 'studentabroad';
$mysql_port = '3306';

$conn = mysqli_connect($mysql_host,$mysql_username,$mysql_password,$mysql_database) ; //连接数据库
mysqli_query($conn, "SET NAMES utf8");


do {
	$configs = include './data/conf/config.php';
	if(!$configs['SP_MAIL_ISTIMING']) {
		die('process abort');
	}
	//ToDo
	
	$student_emails = array();
	$nowtimestr = date('Y-m-d H:i:s',strtotime('-1 minute',time()));
	$activities_sql = "select id,activity_name,activity_start_time,collection_time,collection_site,signup_end_time from cmf_activity where signup_end_time>='".$nowtimestr."' and activity_status=1 order by signup_end_time asc";
	$activities_result = mysqli_query($conn, $activities_sql);
	if (mysqli_num_rows($activities_result) > 0) {
		// 输出数据
		while($activity = mysqli_fetch_assoc($activities_result)) {
			$activity_student_sql = "select student_id from cmf_activity_student_relationship where activity_id=".$activity['id'];
			$activity_student_result = mysqli_query($conn, $activity_student_sql);
			if (mysqli_num_rows($activity_student_result) > 0) {
				// 输出数据
				while($activity_student = mysqli_fetch_assoc($activity_student_result)) {
					$student_sql = "select student_email from cmf_users where id=".$activity_student['student_id']." and user_status=1";
					$student_result = mysqli_query($conn, $student_sql);
					if (mysqli_num_rows($student_result) > 0) {
						// 输出数据
						while($student = mysqli_fetch_assoc($student_result)) {
							if ($student['student_email']) $student_emails[] = $student['student_email'];
						}
					}
				}
				$subject = "#".$activity['activity_name']."#报名已结束";
				$content = "#".$activity['activity_name']."#将于".$activity['activity_start_time']."正式开始，请同学们于".$activity['collection_time']."准时到".$activity['collection_site']."集合。 <a href='http://ciee.ccdcsh.cn/index.php?g=&m=activity&a=detail&id=".$activity['id']."'> 查看详情 </a> ";
				$nowtime = time();
				$signup_end_time = strtotime($activity['signup_end_time']);
				$signup_end_time_minute = strtotime('+1 minute',$signup_end_time);
				if ($nowtime >= $signup_end_time && $nowtime <= $signup_end_time_minute) {
					$mail=new \PHPMailer();
					// 设置PHPMailer使用SMTP服务器发送Email
					$mail->IsSMTP();
					$mail->IsHTML(true);
					// 设置邮件的字符编码，若不指定，则为'UTF-8'
					$mail->CharSet='UTF-8';
					// 添加收件人地址，可以多次使用来添加多个收件人
					//$mail->AddAddress("510912430@qq.com");
					foreach($student_emails as $student_email){
						$mail->AddAddress($student_email);
					}
					// 设置邮件正文
					$mail->Body=$content;
					// 设置邮件头的From字段。
					$mail->From=$configs['SP_MAIL_ADDRESS'];
					// 设置发件人名字
					$mail->FromName=$configs['SP_MAIL_SENDER'];;
					// 设置邮件标题
					$mail->Subject=$subject;
					// 设置SMTP服务器。
					$mail->Host=$configs['SP_MAIL_SMTP'];
					//by Rainfer
					// 设置SMTPSecure。
					$Secure=$configs['SP_MAIL_SECURE'];
					$mail->SMTPSecure=empty($Secure)?'':$Secure;
					// 设置SMTP服务器端口。
					$port=$configs['SP_MAIL_SMTP_PORT'];
					$mail->Port=empty($port)?"25":$port;
					// 设置为"需要验证"
					$mail->SMTPAuth=true;
					// 设置用户名和密码。
					$mail->Username=$configs['SP_MAIL_LOGINNAME'];
					$mail->Password=$configs['SP_MAIL_PASSWORD'];
					// 发送邮件。
					$mail->Send();
				} else {
					break;
				}
			}
		}
	}
	sleep($interval);// 等待*秒
}while (true);
?>