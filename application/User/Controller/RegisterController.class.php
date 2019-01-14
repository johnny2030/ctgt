<?php
/* 
 * 会员注册
 * 11k
 */
namespace User\Controller;

use Common\Controller\HomebaseController;

class RegisterController extends HomebaseController {
	private $formData = array();
	private $formError = array();
	private $formReturn = array();
	private $user_model;
	private $teacher_major_model;
	
	function _initialize(){
		parent::_initialize();
		$this->user_model = D('Users');
		$this->teacher_major_model = D('TeacherMajorRelationship');
	}
    // 前台用户注册
	public function index(){
	    if(sp_is_user_login()){ //已经登录时直接跳到首页
	        redirect(__ROOT__."/");
	    }else{
	        if(IS_POST) {
	        	$this->formData = $_POST;
	        	$username = I('post.username1');
	        	$password = I('post.password1');
	        	$confirm_password = I('post.confirm_password');
	        	$user_type = I('post.user_type');
	        	$agree = I('post.agree');
	        	if (empty($username)) $this->formError[] = '请输入用户名';
	        	if (empty($password)) $this->formError[] = '请输入密码';
	        	if (empty($agree)) $this->formError[] = '请阅读并同意用户协议';
	        	if ($password != $confirm_password) $this->formError[] = '密码与确认密码不一致';
	        	if ($this->user_model->where(array('user_login' => $username))->count() > 0) $this->formError[] = '用户名已被注册';
	        	if (!$this->formError) {
	        		if ($user_type == 1) {
		        		$data = array(
		        				'user_login' => $username,
		        				'user_pass' => sp_password( $password ),
		        				'last_login_ip' => get_client_ip( 0, true ),
		        				'create_time' => date( 'Y-m-d H:i:s' ),
		        				'last_login_time' => date( 'Y-m-d H:i:s' ),
		        				'user_status' => 2,//未审核
		        				'user_type' => 1,  //teacher
		        				'teacher_apply_status' => 0
		        		);
	        		}
	        		if ($user_type == 3) {
	        			$data = array(
	        					'user_login' => $username,
	        					'user_pass' => sp_password( $password ),
	        					'last_login_ip' => get_client_ip( 0, true ),
	        					'create_time' => date( 'Y-m-d H:i:s' ),
	        					'last_login_time' => date( 'Y-m-d H:i:s' ),
	        					'user_status' => 1,//正常
	        					'user_type' => 3  //user
	        			);
	        		}
	        		$result = $this->user_model->add($data);
	        		if ($result) {
	        			//注册成功跳转页面
	        			$data['id'] = $result;
	        			session('user',$data);
	        			if ($user_type == 1) {
	        				redirect(leuu('portal/list/index',array('id' => 11)));
	        			}
	        			if ($user_type == 3) {
		        			$referer = $_SERVER['HTTP_REFERER'];
		        			$redirect = empty($_SESSION['login_http_referer']) ? ($referer ? $referer : __ROOT__.'/') : $_SESSION['login_http_referer'];
		        			$_SESSION['login_http_referer'] = '';
		        			redirect($redirect);
	        			}
	        		} else {
	        			$this->formReturn['success'] = false;
	        			$this->formReturn['msg'] = '注册失败';
	        		}
	        	}
	        }
	    }
	    $this->assign('formData',$this->formData);
	    $this->assign('formError',$this->formError);
	    $this->assign('formReturn',$this->formReturn);
	    $this->display(':register');
	}
	// 教师申请
	public function teacher_applytable(){
		if(IS_POST) {
			$this->formData = $_POST;
			if (empty($_POST['first_name'])) $this->formError[] = '请填写名';
			if (empty($_POST['last_name'])) $this->formError[] = '请填写姓';
			if (empty($_POST['school_name'])) $this->formError[] = '请填写学校名称';
			if (empty($_POST['work_experience'])) $this->formError[] = '请工作经验';
			$_POST['full_name'] = $_POST['first_name']." ".$_POST['last_name'];
			$_POST['teacher_apply_status'] = 1;
			
			$teacher_majors = array();
			$user_id = sp_get_current_userid();
			if (!empty($_POST['teacher_major'])) {
				foreach ($_POST['teacher_major'] as $teacher_major) {
					$teacher_major = (int)$teacher_major;
					if ($teacher_major) $teacher_majors[] = array('major_id' => $teacher_major,'teacher_id' => $user_id);
				}
			} else {
				$this->formError[] = '请选择专业';
			}
			unset($_POST['teacher_major']);
			if (!$this->formError) {
				
				$result = $this->user_model->where(array('id' => $user_id))->save($_POST);
				
				if ($result) {
					foreach ($teacher_majors as $teacher_major) {
						$this->teacher_major_model->add($teacher_major);
					}
					$this->success('提交申请成功',__ROOT__.'/');
				} else {
					$this->formReturn['success'] = false;
					$this->formReturn['msg'] = '提交申请失败';
				}
			}
		}
		$this->assign('formData',$this->formData);
		$this->assign('formError',$this->formError);
		$this->assign('formReturn',$this->formReturn);
		$this->display(':teacher_applytable');
	}
	
	// 前台用户注册提交
	public function doregister(){
    	
    	if(isset($_POST['email'])){
    	    
    	    //邮箱注册
    	    $this->_do_email_register();
    	    
    	}elseif(isset($_POST['mobile'])){
    	    
    	    //手机号注册
    	    $this->_do_mobile_register();
    	    
    	}else{
    	    $this->error("注册方式不存在！");
    	}
    	
	}
	
	// 前台用户手机注册
	private function _do_mobile_register(){
	    
	    if(!sp_check_verify_code()){
	        $this->error("验证码错误！");
	    }
	     
        $rules = array(
            //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
            array('mobile', 'require', '手机号不能为空！', 1 ),
            array('mobile','','手机号已被注册！！',0,'unique',3),
            array('password','require','密码不能为空！',1),
            array('password','5,20',"密码长度至少5位，最多20位！",1,'length',3),
        );
        	
	    $users_model=M("Users");
	     
	    if($users_model->validate($rules)->create()===false){
	        $this->error($users_model->getError());
	    }
	    
	    if(!sp_check_mobile_verify_code()){
	        $this->error("手机验证码错误！");
        }
	     
	    $password=I('post.password');
	    $mobile=I('post.mobile');
	    
	    $users_model=M("Users");
	    $data=array(
	        'user_login' => '',
	        'user_email' => '',
	        'mobile' =>$mobile,
	        'user_nicename' =>'',
	        'user_pass' => sp_password($password),
	        'last_login_ip' => get_client_ip(0,true),
	        'create_time' => date("Y-m-d H:i:s"),
	        'last_login_time' => date("Y-m-d H:i:s"),
	        'user_status' => 1,
	        "user_type"=>2,//会员
	    );
	    
	    $result = $users_model->add($data);
	    if($result){
	        //注册成功页面跳转
	        $data['id']=$result;
	        session('user',$data);
	        $this->success("注册成功！",__ROOT__."/");
	         
	    }else{
	        $this->error("注册失败！",U("user/register/index"));
	    }
	}
	
	// 前台用户邮件注册
	private function _do_email_register(){
	   
        if(!sp_check_verify_code()){
            $this->error("验证码错误！");
        }
        
        $rules = array(
            //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
            array('email', 'require', '邮箱不能为空！', 1 ),
            array('password','require','密码不能为空！',1),
            array('password','5,20',"密码长度至少5位，最多20位！",1,'length',3),
            array('repassword', 'require', '重复密码不能为空！', 1 ),
            array('repassword','password','确认密码不正确',0,'confirm'),
            array('email','email','邮箱格式不正确！',1), // 验证email字段格式是否正确
        );
	     
	    $users_model=M("Users");
	     
	    if($users_model->validate($rules)->create()===false){
	        $this->error($users_model->getError());
	    }
	     
	    $password=I('post.password');
	    $email=I('post.email');
	    $username=str_replace(array(".","@"), "_",$email);
	    //用户名需过滤的字符的正则
	    $stripChar = '?<*.>\'"';
	    if(preg_match('/['.$stripChar.']/is', $username)==1){
	        $this->error('用户名中包含'.$stripChar.'等非法字符！');
	    }
	     
// 	    $banned_usernames=explode(",", sp_get_cmf_settings("banned_usernames"));
	     
// 	    if(in_array($username, $banned_usernames)){
// 	        $this->error("此用户名禁止使用！");
// 	    }
	    
	    $where['user_login']=$username;
	    $where['user_email']=$email;
	    $where['_logic'] = 'OR';
	    
	    $ucenter_syn=C("UCENTER_ENABLED");
	    $uc_checkemail=1;
	    $uc_checkusername=1;
	    if($ucenter_syn){
	        include UC_CLIENT_ROOT."client.php";
	        $uc_checkemail=uc_user_checkemail($email);
	        $uc_checkusername=uc_user_checkname($username);
	    }
	     
	    $users_model=M("Users");
	    $result = $users_model->where($where)->count();
	    if($result || $uc_checkemail<0 || $uc_checkusername<0){
	        $this->error("用户名或者该邮箱已经存在！");
	    }else{
	        $uc_register=true;
	        if($ucenter_syn){
	             
	            $uc_uid=uc_user_register($username,$password,$email);
	            //exit($uc_uid);
	            if($uc_uid<0){
	                $uc_register=false;
	            }
	        }
	        if($uc_register){
	            $need_email_active=C("SP_MEMBER_EMAIL_ACTIVE");
	            $data=array(
	                'user_login' => $username,
	                'user_email' => $email,
	                'user_nicename' =>$username,
	                'user_pass' => sp_password($password),
	                'last_login_ip' => get_client_ip(0,true),
	                'create_time' => date("Y-m-d H:i:s"),
	                'last_login_time' => date("Y-m-d H:i:s"),
	                'user_status' => $need_email_active?2:1,
	                "user_type"=>2,//会员
	            );
	            $rst = $users_model->add($data);
	            if($rst){
	                //注册成功页面跳转
	                $data['id']=$rst;
	                session('user',$data);
	                	
	                //发送激活邮件
	                if($need_email_active){
	                    $this->_send_to_active();
	                    session('user',null);
	                    $this->success("注册成功，激活后才能使用！",U("user/login/index"));
	                }else {
	                    $this->success("注册成功！",__ROOT__."/");
	                }
	                	
	            }else{
	                $this->error("注册失败！",U("user/register/index"));
	            }
	             
	        }else{
	            $this->error("注册失败！",U("user/register/index"));
	        }
	         
	    }
	}
	
	// 前台用户邮件注册激活
	public function active(){
		$hash=I("get.hash","");
		if(empty($hash)){
			$this->error("激活码不存在");
		}
		
		$users_model=M("Users");
		$find_user=$users_model->where(array("user_activation_key"=>$hash))->find();
		
		if($find_user){
			$result=$users_model->where(array("user_activation_key"=>$hash))->save(array("user_activation_key"=>"","user_status"=>1));
			
			if($result){
				$find_user['user_status']=1;
				session('user',$find_user);
				$this->success("用户激活成功，正在登录中...",__ROOT__."/");
			}else{
				$this->error("用户激活失败!",U("user/login/index"));
			}
		}else{
			$this->error("用户激活失败，激活码无效！",U("user/login/index"));
		}
		
		
	}
	
	
}