<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MailerController extends AdminbaseController {
	
	private $course_model;
	private $user_model;
	private $activity_model;
	private $activity_student_model;
	private $activity_time_model;
	private $activity_address_model;
	private $course_student_model;
	private $score_model;
	private $course_requirement_model;
	private $recruit_model;
	private $recruit_member_model;
	private $recruit_member_term_model;
	private $email_template_model;
	private $email_conf_model;
	private $house_user_model;
	private $homestay_model;
	private $assessment_model;
	private $assessment_answer_model;
	
	function _initialize() {
		parent::_initialize();
	
		$this->course_model = D( 'Course' );
		$this->user_model = D( 'Users' );
		$this->activity_model = D( 'Activity' );
		$this->activity_time_model = D( 'ActivityTime' );
		$this->activity_address_model = D( 'ActivityAddress' );
		$this->activity_student_model = D( 'ActivityStudentRelationship' );
		$this->course_student_model = D( 'CourseStudentRelationship' );
		$this->score_model = D( 'Score' );
		$this->course_requirement_model = D( 'CourseRequirement' );
		$this->recruit_model = D( 'Recruit' );
		$this->recruit_member_model = D( 'RecruitMember' );
		$this->recruit_member_term_model = D( 'RecruitMemberTerm' );
		$this->email_template_model = D( 'EmailTemplate' );
		$this->email_conf_model = D( 'EmailConf' );
		$this->house_user_model = D( 'HouseUserRelationship' );
		$this->homestay_model = D( 'Homestay' );
		$this->assessment_model = D( 'Assessment' );
		$this->assessment_answer_model = D( 'AssessmentAnswer' );
	}

	// SMTP配置
	public function index() {
		$this->display();
	}
	    
	// SMTP配置处理
	public function index_post() {
		$post = array_map('trim', I('post.'));
		
		if(in_array('', $post) && !empty($post['smtpsecure'])) $this->error("不能留空！");
		
		$configs['SP_MAIL_ADDRESS'] = $post['address'];
		$configs['SP_MAIL_SENDER'] = $post['sender'];
		$configs['SP_MAIL_SMTP'] = $post['smtp'];
		$configs['SP_MAIL_SECURE'] = $post['smtpsecure'];
		$configs['SP_MAIL_SMTP_PORT'] = $post['smtp_port'];
		$configs['SP_MAIL_LOGINNAME'] = $post['loginname'];
		$configs['SP_MAIL_PASSWORD'] = $post['password'];
		$configs['SP_MAIL_ISTIMING'] = empty($post['is_timing'])?0:1;
		$result=sp_set_dynamic_config($configs);
		sp_clear_cache();
		if ($result) {
			if ($configs['SP_MAIL_ISTIMING'] == 1) {
				$this->success("保存成功！定时推送已开启",__ROOT__."/timing.php");
			} else {
				$this->success("保存成功！定时推送已关闭");
			}
		} else {
			$this->error("保存失败！");
		}
	}
	
	function select_email() {
		if (IS_AJAX) {
			$email_id = (int)$_POST['email_id'];
			$email_template = $this->email_template_model->find($email_id);
			$this->ajaxReturn($email_template);
		}
	}
	// 会员注册邮件模板
	public function active(){
		$where = array('option_name'=>'member_email_active');
		$option = M('Options')->where($where)->find();
		if($option){
			$options = json_decode($option['option_value'], true);
			$this->assign('options', $options);
		}
		$this->display();
	}
    
    // 会员注册邮件模板提交
    public function active_post(){
        $configs=array();
    	$configs['SP_MEMBER_EMAIL_ACTIVE'] = I('post.lightup',0,'intval');
    	sp_set_dynamic_config($configs);

    	$data=array();
    	$data['option_name'] = "member_email_active";
    	$options=I('post.options/a');
    	$options['template']=htmlspecialchars_decode($options['template']);
    	$data['option_value']= json_encode($options);
    	$options_model= M('Options');
    	if($options_model->where("option_name='member_email_active'")->find()){
    		$result = $options_model->where("option_name='member_email_active'")->save($data);
    	}else{
    		$result = $options_model->add($data);
    	}
    	
    	if ($result!==false) {
    		$this->success("保存成功！");
    	} else {
    		$this->error("保存失败！");
    	}
    }
    
    // 邮件发送测试
    public function test(){
        if(IS_POST){
            $rules = array(
                 array('to','require','收件箱不能为空！',1,'regex',3),
                 array('to','email','收件箱格式不正确！',1,'regex',3),
                 array('subject','require','标题不能为空！',1,'regex',3),
                 array('content','require','内容不能为空！',1,'regex',3),
            );
            
            $model = M(); // 实例化User对象
            if ($model->validate($rules)->create()!==false){
                $data=I('post.');
                $data['content'] = htmlspecialchars_decode($data['content']);
                $result=sp_send_email($data['to'], $data['subject'], $data['content']);
                if($result && empty($result['error'])){
                    $this->success('发送成功！');
                }else{
                    $this->error('发送失败：'.$result['message']);
                }
            }else{
                $this->error($model->getError());
            }
            
        }else{
            $this->display();
        }
        
    }
    // 多人邮件发送(课程)
    public function course_send_mails(){
    	if(IS_POST){
    		$rules = array(
    				array('subject','require','标题不能为空！',1,'regex',3),
    				array('content','require','内容不能为空！',1,'regex',3),
    		);
    
    		$model = M(); // 实例化User对象
    		if ($model->validate($rules)->create()!==false){
    			$data=I('post.');
    			$course = $this->course_model->where(array('id' => $data['course_id'],'course_status' => 1))->find();
    			$class_users = $this->class_user_model->where(array('class_id' => $course['class_id'],'user_type' => 2))->select();
    			$student_emails = array();
    			foreach ($class_users as $class_user) {
    				$user = $this->user_model->where(array('passport_number' => $class_user['user_id'],'user_status' => 1))->find();
    				if ($user) {
    					if ($user['student_email']) $student_emails[] = $user['student_email'];
    				}
    			}
    			$data['content'] = htmlspecialchars_decode($data['content'])."<a href='http://ciee.ccdcsh.cn/index.php?g=&m=course&a=detail&id=".$data['course_id']."'> 查看详情 </a> ";
    			$result=sp_send_email($student_emails, $data['subject'], $data['content']);
    			if($result && empty($result['error'])){
    				//$this->assign('course_id',$data['course_id']);
    				$this->success('发送成功！');
    			}else{
    				$this->error('发送失败：'.$result['message']);
    			}
    		}else{
    			$this->error($model->getError());
    		}
    
    	}else{
    		$course_id = I('get.course_id');
    		$course = $this->course_model->where(array('id' => $course_id,'course_status' => 1))->find();
    		$this->assign('course',$course);
    		$this->display();
    	}
    }
	// 多人邮件发送(招聘)
	public function recruit_send_mails(){
		if(IS_POST ){
			$rules = array(
					array('subject','require','标题不能为空！',1,'regex',3),
					array('content','require','内容不能为空！',1,'regex',3),
			);
			 
			$model = M(); // 实例化User对象
			if ($model->validate($rules)->create()!==false){
				$data=I('post.');
				$recruit_member_id = $data['recruit_member_id'];
				$content = htmlspecialchars_decode($data['content']);
				$result = sp_send_email($data['email'], $data['subject'], $content);
				if($result && empty($result['error'])){
					$email_status = array();
					$recruit_member = $this->recruit_member_model->find($recruit_member_id);
					if ($recruit_member['email_status']) $email_status = json_decode($recruit_member['email_status'],true);
					if ($_POST['approve']) $email_status['approve'] = $_POST['approve'];
					if ($_POST['unapprove']) $email_status['unapprove'] = $_POST['unapprove'];
					$email_status = json_encode($email_status);
					$this->recruit_member_model->where(array('id' => $recruit_member_id))->save(array('email_status' => $email_status));
					$this->success(L('SEND_SUCCESS'));
				}else{
					$this->error(L('SEND_FAILED').$result['message']);
				}
			}else{
				$this->error($model->getError());
			}
		} else {
			//邮件模板
			$email_templates = $this->email_template_model->where(array('email_status' => 1))->select();
			$email_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
			foreach ($email_templates as $email) {
				$email_html .= "<option value='".$email['id']."'>".$email['email_title']."</option>";
			}
			$this->assign('email_html',$email_html);
			//发件人
			$email_conf = $this->email_conf_model->where(array('admin_id' => sp_get_current_admin_id()))->find();
			$email_sender = empty($email_conf) ? C('SP_MAIL_SENDER') : $email_conf['email_sender'];
			$email_address = empty($email_conf) ? C('SP_MAIL_ADDRESS') : $email_conf['email_address'];
			$this->assign('email_sender',$email_sender);
			$this->assign('email_address',$email_address);
			
			$recruit_member_id = I('get.recruit_member_id');
			$recruit_member = $this->recruit_member_model->find($recruit_member_id);
			$recruit = $this->recruit_model->find($recruit_member['recruit_id']);
			$this->assign('approve',$_GET['approve']);
			$this->assign('unapprove',$_GET['unapprove']);
			$this->assign('recruit_member',$recruit_member);
			$this->assign('recruit',$recruit);
			$this->display();
		}
	}
	// 多人邮件发送(住宿)
	public function house_send_mails(){
		if(IS_POST ){
			$rules = array(
					array('subject','require','标题不能为空！',1,'regex',3),
					array('content','require','内容不能为空！',1,'regex',3),
			);
	
			$model = M(); // 实例化User对象
			if ($model->validate($rules)->create()!==false){
				$data=I('post.');
				$student_email = $data['student_email'];
				$chinese_roommate_email = $data['chinese_roommate_email'];
				if (empty($student_email) && empty($chinese_roommate_email)) $this->error('请选择收件人');
				$student_id = $data['student_id'];
				$student = $this->user_model->find($student_id);
				$term_year = trim($student['year'])."-".trim($student['term']);
				$house_user = $this->house_user_model->where(array('user_id' => $student_id))->find();
				$recruit_member_term = $this->recruit_member_term_model->where(array('recruit_member_id' => $house_user['owner_id'],'term_year' => $term_year))->find();
				if ($student_email) {
					if ($house_user['flg'] == 1) {//中国同屋
						$chinese_roommate = $this->recruit_member_model->find($house_user['owner_id']);
						$key_arr = array(
								'#name#',
								'#phone#',
								'#email#',
								'#hobby#',
								'#speciality#',
								'#address#'
						);
						$value_arr = array(
								$chinese_roommate['name'],
								$chinese_roommate['phone'],
								$chinese_roommate['email'],
								$chinese_roommate['hobby'],
								$chinese_roommate['speciality'],
								$recruit_member_term['address']
						);
						$student_content = str_replace($key_arr, $value_arr,$data['content']);
					}
					if ($house_user['flg'] == 2) {//友好家庭
						$host_family = $this->homestay_model->find($house_user['owner_id']);
						$key_arr = array(
								'#name#',
								'#phone#',
								'#email#',
								'#postcode#',
								'#hobby#',
								'#address#',
						);
						$value_arr = array(
								$host_family['name'],
								$host_family['phone'],
								$host_family['email'],
								$host_family['postcode'],
								$host_family['family_hobby'],
								$host_family['address']
						);
						$student_content = str_replace($key_arr, $value_arr,$data['content']);
					}
					if ($house_user['flg'] == 3) {//留学生
						$student = $this->user_model->find($house_user['owner_id']);
						$key_arr = array(
								'#name#',
								'#phone#',
								'#email#',
								'#address#',
						);
						$value_arr = array(
								$student['full_name'],
								$student['phone'],
								$student['student_email'],
								$house_user['address']
						);
						$student_content = str_replace($key_arr, $value_arr,$data['content']);
					}
					$student_content = htmlspecialchars_decode($student_content);
					$student_result = sp_send_email($student_email, $data['subject'], $student_content);
				}
				if ($chinese_roommate_email) {
					$cr_key_arr = array(
							'#name#',
							'#phone#',
							'#email#',
							'#hobby#',
							'#address#'
					);
					$cr_value_arr = array(
							$student['full_name'],
							$student['phone'],
							$student['student_email'],
							$student['hobby'],
							$recruit_member_term['address']
					);
					$chinese_roommate_content = str_replace($cr_key_arr, $cr_value_arr,$data['content']);
					$chinese_roommate_content = htmlspecialchars_decode($chinese_roommate_content);
					$chinese_roommate_result = sp_send_email($chinese_roommate_email, $data['subject'], $chinese_roommate_content);
				}
				if(empty($student_result['error']) && empty($chinese_roommate_result['error'])){
					$this->success(L('SEND_SUCCESS'));
				}else{
					$this->error(L('SEND_FAILED').$result['message']);
				}
			}else{
				$this->error($model->getError());
			}
		} else {
			//邮件模板
			$email_templates = $this->email_template_model->where(array('email_status' => 1))->select();
			$email_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
			foreach ($email_templates as $email) {
				$email_html .= "<option value='".$email['id']."'>".$email['email_title']."</option>";
			}
			$this->assign('email_html',$email_html);
			//发件人
			$email_conf = $this->email_conf_model->where(array('admin_id' => sp_get_current_admin_id()))->find();
			$email_sender = empty($email_conf) ? C('SP_MAIL_SENDER') : $email_conf['email_sender'];
			$email_address = empty($email_conf) ? C('SP_MAIL_ADDRESS') : $email_conf['email_address'];
			$this->assign('email_sender',$email_sender);
			$this->assign('email_address',$email_address);
				
			$student_id = I('get.student_id');
			$student = $this->user_model->find($student_id);
			$house_user = $this->house_user_model->where(array('user_id' => $student_id))->find();
			$this->assign('student',$student);
			$this->assign('house_user',$house_user);
			$this->display();
		}
	}
	// 多人邮件发送(住宿)群发
	public function house_group_mails(){
		if(IS_POST ){
			$rules = array(
					array('term_year','require','请选择学季！',1,'regex',3),
					array('subject','require','标题不能为空！',1,'regex',3),
					array('content','require','内容不能为空！',1,'regex',3),
			);
	
			$model = M(); // 实例化User对象
			if ($model->validate($rules)->create()!==false){
				$data=I('post.');
				$term_year = $data['term_year'];
				$term_year_arr = explode('-', $term_year);
				$year = $term_year_arr[0];
				$term = $term_year_arr[1];
				if (empty($data['content'])) $this->error('内容不能为空');
				
				$house_users = $this->house_user_model
								->alias('hu')
								->field('hu.*,u.student_email,trim(u.year) as year,trim(u.term) as term,u.full_name,u.phone')
								->join('__USERS__ u ON u.id=hu.user_id')
								->where(array('year' => $year,'term' => $term))
								->select();
				if ($house_users) {
					foreach ($house_users as $house_user) {
						if ($house_user['flg'] == 1) {//中国同屋
							$chinese_roommate = $this->recruit_member_model->find($house_user['owner_id']);
							$recruit_member_term = $this->recruit_member_term_model->where(array('recruit_member_id' => $house_user['owner_id'],'term_year' => $term_year))->find();
							$key_arr = array(
									'#name#',
									'#phone#',
									'#email#',
									'#hobby#',
									'#speciality#',
									'#address#'
							);
							$value_arr = array(
									$chinese_roommate['name'],
									$chinese_roommate['phone'],
									$chinese_roommate['email'],
									$chinese_roommate['hobby'],
									$chinese_roommate['speciality'],
									$recruit_member_term['address']
							);
							$student_content = str_replace($key_arr, $value_arr,$data['content']);
							
							$cr_key_arr = array(
									'#name#',
									'#phone#',
									'#email#',
									'#hobby#',
									'#address#'
							);
							$cr_value_arr = array(
									$house_user['full_name'],
									$house_user['phone'],
									$house_user['student_email'],
									$house_user['hobby'],
									$recruit_member_term['address']
							);
							$chinese_roommate_content = str_replace($cr_key_arr, $cr_value_arr,$data['content']);
							$chinese_roommate_content = htmlspecialchars_decode($chinese_roommate_content);
							$chinese_roommate_result = sp_send_email($chinese_roommate['email'], $data['subject'], $chinese_roommate_content);
						}
						if ($house_user['flg'] == 2) {//友好家庭
							$host_family = $this->homestay_model->find($house_user['owner_id']);
							$key_arr = array(
									'#name#',
									'#phone#',
									'#email#',
									'#postcode#',
									'#hobby#',
									'#address#'
							);
							$value_arr = array(
									$host_family['name'],
									$host_family['mobile'],
									$host_family['email'],
									$host_family['postcode'],
									$host_family['family_hobby'],
									$host_family['address']
							);
							$student_content = str_replace($key_arr, $value_arr,$data['content']);
						}
						if ($house_user['flg'] == 3) {//留学生
							$student = $this->user_model->find($house_user['owner_id']);
							$key_arr = array(
									'#name#',
									'#phone#',
									'#email#',
									'#hobby#',
									'#address#'
							);
							$value_arr = array(
									$student['full_name'],
									$student['phone'],
									$student['student_email'],
									$student['hobby'],
									$house_user['address']
							);
							$student_content = str_replace($key_arr, $value_arr,$data['content']);
						}
						$student_content = htmlspecialchars_decode($student_content);
						$student_result = sp_send_email($house_user['student_email'], $data['subject'], $student_content);
					}
				} else {
					$this->error('此学季下暂无匹配学生');
				}
				if(empty($student_result['error']) && empty($chinese_roommate_result['error'])){
					$this->success(L('SEND_SUCCESS'));
				}else{
					$this->error(L('SEND_FAILED').$result['message']);
				}
			}else{
				$this->error($model->getError());
			}
		} else {
			//邮件模板
			$email_templates = $this->email_template_model->where(array('email_status' => 1))->select();
			$email_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
			foreach ($email_templates as $email) {
				$email_html .= "<option value='".$email['id']."'>".$email['email_title']."</option>";
			}
			$this->assign('email_html',$email_html);
			//发件人
			$email_conf = $this->email_conf_model->where(array('admin_id' => sp_get_current_admin_id()))->find();
			$email_sender = empty($email_conf) ? C('SP_MAIL_SENDER') : $email_conf['email_sender'];
			$email_address = empty($email_conf) ? C('SP_MAIL_ADDRESS') : $email_conf['email_address'];
			$this->assign('email_sender',$email_sender);
			$this->assign('email_address',$email_address);
	
			//学季下拉框
			$term_year_html = StudentController::term_years();
			$this->assign('term_year_html',$term_year_html);
			$this->display();
		}
	}
    // 推送学生所选课程邮件(课程)
    public function course_student_mails(){
    	if(IS_POST){
    		$rules = array(
    				array('subject','require','标题不能为空！',1,'regex',3),
    				array('content','require','内容不能为空！',1,'regex',3),
    		);
    
    		$model = M(); // 实例化User对象
    		if ($model->validate($rules)->create()!==false){
    			$data=I('post.');
    			$data['content'] = htmlspecialchars_decode($data['content']);
    			$result=sp_send_email($data['student_email'], $data['subject'], $data['content']);
    			if($result && empty($result['error'])){
    				$this->success('发送成功！');
    			}else{
    				$this->error('发送失败：'.$result['message']);
    			}
    		}else{
    			$this->error($model->getError());
    		}
    
    	}else{
    		$student_id = (int)I('get.student_id');
    		$student = $this->user_model->where(array('id' => $student_id))->find();
    		$student_courses = $this->course_model
    								->alias('c')
    								->field('c.*,u.full_name')
    								->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.course_id=c.id')
    								->join('__USERS__ u ON u.id=c.headteacher_id')
    								->where(array('cs.student_id' => $student_id,'cs.course_student_status' => 1))
    								->select();
    		$this->assign('student',$student);
    		$this->assign('student_courses',$student_courses);
    		$this->display();
    	}
    
    }
    // 推送学生成绩邮件(课程)
    public function score_student_mails(){
    	if(IS_POST){
    		$rules = array(
    				array('subject','require','标题不能为空！',1,'regex',3),
    				array('content','require','内容不能为空！',1,'regex',3),
    		);
    
    		$model = M(); // 实例化User对象
    		if ($model->validate($rules)->create()!==false){
    			$data=I('post.');
    			$where = array();
    			$course_id = $data['course_id'];
    			//邮箱
    			$student_ids = $data['student_ids'];
    			$student_ids_str = implode(',', $student_ids);
    			$students = $this->user_model->where(array('id' => array('in',$student_ids_str)))->select();
    			//内容
    			foreach ($students as $student) {
	    			$content = htmlspecialchars_decode($data['content']);
    				if ($student['student_email']) {
    					$where['student_id'] = $student['id'];
    					
    					if ($course_id) {//一门固定课程
    						$where['course_id'] = $course_id;
    					}
    					$where['course_student_status'] = 1;
    					$student_courses = $this->course_student_model->where($where)->select();
    					foreach ($student_courses as $student_course) {
    						//评级
    						$score_requirement_ids = array();
    						$score_requirements = $this->score_model->field('distinct requirement_id')->where(array('student_id' => $student['id'],'course_id' => $student_course['course_id']))->select();
    						foreach ( $score_requirements as $score_requirement) $score_requirement_ids[] = $score_requirement['requirement_id'];
    						$score_requirement_ids_str = implode(',',$score_requirement_ids);
    							
    						$course = $this->course_model->where(array('id' => $student_course['course_id']))->find();
    						$course_scores = $this->score_model->where(array('student_id' => $student['id'],'course_id' => $student_course['course_id']))->select();
    						$course_requirements = $this->course_requirement_model->where(array('id' => array('in',$score_requirement_ids_str)))->select();
    							
    						$old_total_percent = 0;
    						foreach ($course_requirements as $course_requirement) {
    							$old_total_percent = $old_total_percent + $course_requirement['requirement_grade_percent'];
    						}
    						$ave_percent = 100/$old_total_percent;
    						$real_score = 0;
    						foreach ($course_requirements as $course_requirement) {
    							$real_percent = $course_requirement['requirement_grade_percent']*$ave_percent;
    							$requirement_scores = $this->score_model->where(array('student_id' => $student['id'],'course_id' => $student_course['course_id'],'requirement_id' => $course_requirement['id']))->select();
    							$total_score = 0;
    							$index = 0;
    							foreach ($requirement_scores as $requirement_score) {
    								$total_score = $total_score + $requirement_score['test_score']*$requirement_score['points_system']-$requirement_score['deduct_marks'];
    								$index++;
    							}
    							$real_score = $real_score + ($total_score/$index)*($real_percent/100);
    						}
    						if ($course_scores) {
	    						$content .= '<table border="1"><tr><th colspan="2">课程代码:<span style="color:blue;">'.$course['course_code'].' </span> 课程名称:<span style="color:blue;"> '.$course['course_name'].' </span> >> 评级: ';
	    						if ($real_score >= 92.50 && $real_score <= 100) $content .= '<span style="color:green;">A</span>';
	    						if ($real_score >= 89.50 && $real_score <= 92.49) $content .= '<span style="color:green;">A-</span>';
	    						if ($real_score >= 86.50 && $real_score <= 89.49) $content .= '<span style="color:green;">B+</span>';
	    						if ($real_score >= 82.50 && $real_score <= 86.49) $content .= '<span style="color:green;">B</span>';
	    						if ($real_score >= 79.50 && $real_score <= 82.49) $content .= '<span style="color:green;">B-</span>';
	    						if ($real_score >= 76.50 && $real_score <= 79.49) $content .= '<span style="color:green;">C+</span>';
	    						if ($real_score >= 69.50 && $real_score <= 76.49) $content .= '<span style="color:green;">C</span>';
	    						if ($real_score >= 59.50 && $real_score <= 69.49) $content .= '<span style="color:green;">D</span>';
	    						if ($real_score >= 0 && $real_score <= 59.49) $content .= '<span style="color:green;">F</span>';
	    						$content .= '</th></tr>';
	    						foreach ($course_requirements as $course_requirement) {
	    							$course_requirement_scores = $this->score_model->where(array('student_id' => $student['id'],'course_id' => $student_course['course_id'],'requirement_id' => $course_requirement['id']))->select();
	    							if ($course_requirement_scores) {
	    								$content .= '<tr><th width="100">'.$course_requirement['requirement_description'].'(占比 '.$course_requirement['requirement_grade_percent'].'%)</th><td><table border="1"><thead><tr><th width="50">序号</th><th width="200">考试时间</th><th width="100">分制计算</th><th width="50">考试分数</th><th width="50">额外扣分（签到等）</th></tr></thead>';
	    								$index = 0;
	    								foreach ($course_requirement_scores as $course_requirement_score) {
	    									$index++;
	    									$content .= '<tr><td>'.$index.'</td><td>'.$course_requirement_score['test_time'].'</td>';
	    									if ($course_requirement_score['points_system'] == 1) $content .= '<td>百分比</td>';
	    									if ($course_requirement_score['points_system'] == 10) $content .= '<td>十分比</td>';
	    									$content .= '<td><span style="color:red;">'.$course_requirement_score['test_score'].'</span></td><td><span style="color:red;">'.$course_requirement_score['deduct_marks'].'</span></td></tr>';
	    								}
	    								$content .= '</table></td></tr>';
	    							}
	    						}
	    						$content .= '</table>';
    						}
    					}
    					
		    			$result=sp_send_email($student['student_email'], $data['subject'], $content);
    				}
    			}
    			
    			if($result && empty($result['error'])){
    				$this->success('发送成功！');
    			}else{
    				$this->error('发送失败：'.$result['message']);
    			}
    		}else{
    			$this->error($model->getError());
    		}
    
    	}else{
    		$where = array();
    		$term_year_sess = trim(I('get.term_year_sess'));
    		$course_id = (int)I('get.course_id');
    		
    		$term_arr = explode('-', $term_year_sess);
    		$where['u.year'] = $term_arr[0];
    		$where['u.term'] = $term_arr[1];
    		$where['u.session'] = $term_arr[2];
    		$where['u.program'] = $term_arr[3];
    		
    		if ($course_id) {
    			$course = $this->course_model->where(array('id' => $course_id))->find();
    			$this->assign('course',$course);
    			
    			$where['cs.course_id'] = $course_id;
    			$where['cs.course_student_status'] = 1;
    			$students = $this->user_model
    							->alias('u')
    							->field('u.id,u.full_name')
    							->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.student_id=u.id')
    							->where($where)
    							->select();
    		} else {
    			$students = $this->user_model
								->alias('u')
								->field('u.id,u.full_name')
								->where($where)
								->select();
    		}
    		$this->assign('students',$students);
    		$this->assign('term_year_sess',$term_year_sess);
    		$this->assign('course_id',$course_id);
    		$this->display();
    	}
    
    }
	// 多人邮件发送(活动)
	public function activity_send_mails(){
		if(IS_POST){
			$rules = array(
					array('subject','require','标题不能为空！',1,'regex',3),
					array('content','require','内容不能为空！',1,'regex',3),
			);
	
			$model = M(); // 实例化User对象
			if ($model->validate($rules)->create()!==false){
				$activity = $this->activity_model->find($_POST['activity_id']);
				$student_flg = $_POST['student_flg'];
				$student_emails = array();
				//可报名所有学生
				if ($student_flg == 1) {
					$students = $this->user_model->where(array('id' => array('in',$_POST['student_ids'])))->select();
					foreach ($students as $student) {
						if ($student['student_email']) $student_emails[] = $student['student_email'];
					}
				}
				//报名此活动的学生
				if ($student_flg == 2) {
					$activity_students = $this->user_model->where(array('id' => array('in',$_POST['activity_student_ids'])))->select();
					foreach ($activity_students as $activity_student) {
						if ($activity_student['student_email']) $student_emails[] = $activity_student['student_email'];
					}
				}
				//未报名此活动的学生
				if ($student_flg == 3) {
					$no_activity_students = $this->user_model->where(array('id' => array('in',$_POST['no_activity_student_ids'])))->select();
					foreach ($no_activity_students as $no_activity_student) {
						if ($no_activity_student['student_email']) $student_emails[] = $no_activity_student['student_email'];
					}
				}
				$key_arr = array(
					'http://#link#'
				);
				$value_arr = array(
					"<a href='http://ciee.ccdcsh.cn/index.php?g=&m=activity&a=detail&id=".$_POST['activity_id']."'> View details</a> "
				);
				$_POST['content'] = str_replace($key_arr, $value_arr,$_POST['content']);
				$_POST['content'] = htmlspecialchars_decode($_POST['content']);//."<a href='http://ciee.ccdcsh.cn/index.php?g=&m=activity&a=detail&id=".$data['activity_id']."'> 查看详情 </a> ";
				$result=sp_send_email($student_emails, $_POST['subject'], $_POST['content']);
				if($result && empty($result['error'])){
					//$this->assign('activity_id',$data['activity_id']);
					$this->success('发送成功！');
				}else{
					$this->error('发送失败：'.$result['message']);
				}
				
			}else{
				$this->error($model->getError());
			}
		}else{
			$where = array();
			$where['email_status'] = 1;
			$list = $this->email_template_model->where($where)->select();
			$email_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
			foreach ($list as $email) {
				$email_html .= "<option value='".$email['id']."'>".$email['email_title']."</option>";
			}
			$this->assign('email_html',$email_html);

			$activity_id = I('get.activity_id');
			$activity = $this->activity_model->where(array('id' => $activity_id))->find();
			$activity_time = $this->activity_time_model->where(array('activity_id' => $activity_id))->order('activity_start_time asc')->select();
			$activity_address = $this->activity_address_model->where(array('activity_id' => $activity_id))->select();
			//此活动下的学生（学季）
			$terms = json_decode($activity['term_year_sess'],true);
			$student_ids = array();
			foreach ($terms as $tys) {
				$term_year_sess_arr = explode('-', $tys);
				$year = $term_year_sess_arr[0];
				$term = $term_year_sess_arr[1];
				$sess = $term_year_sess_arr[2];
				$program = $term_year_sess_arr[3];
				$student_where = array();
				if ($year) {
					$student_where['u.year'] = $year;
				} else {
					$student_where[] = "u.year = '' OR u.year is null";
				}
				if ($term) {
					$student_where['u.term'] = $term;
				} else {
					$student_where[] = "u.term = '' OR u.term is null";
				}
				if ($sess) {
					$student_where['u.session'] = $sess;
				} else {
					$student_where[] = "u.session = '' OR u.session is null";
				}
				if ($program) {
					$student_where['u.program'] = $program;
				} else {
					$student_where[] = "u.program = '' OR u.program is null";
				}
				$student_where['u.user_status'] = 1;
				$term_students = $this->user_model->alias('u')->field('u.*')->where($student_where)->order('u.first_name asc,u.last_name asc')->select();
				foreach ($term_students as $term_student) $student_ids[] = $term_student['id'];
			}
			$students = $this->user_model->alias('u')->field('u.*')->where(array('id' => array('in',implode(',', $student_ids))))->order('u.first_name asc,u.last_name asc')->select();
			$student_names = array();
			foreach ($students as $student) {
				$student_names[] = $student['full_name'];
			}
			$student_ids_str = implode(',', $student_ids);
			$student_names_str = implode(',', $student_names);
			//此活动下已报名的学生
			$activity_students = $this->user_model->alias('u')->field('u.*')->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.student_id=u.id')->where(array('asr.activity_id' => $activity_id,'u.user_status' => 1))->order('u.first_name asc,u.last_name asc')->select();
			$activity_student_ids = array();
			$activity_student_names = array();
			foreach ($activity_students as $activity_student) {
				$activity_student_ids[] = $activity_student['id'];
				$activity_student_names[] = $activity_student['full_name'];
			}
			$activity_student_ids_str = implode(',', $activity_student_ids);
			$activity_student_names_str = implode(',', $activity_student_names);
			//未报名的学生
			$no_activity_student_ids = array_diff($student_ids, $activity_student_ids);
			$no_activity_students = $this->user_model->alias('u')->field('u.*')->where(array('u.id' => array('in',implode(',', $no_activity_student_ids))))->select();
			$no_activity_student_names = array();
			foreach ($no_activity_students as $no_activity_student) {
				$no_activity_student_names[] = $no_activity_student['full_name'];
			}
			$no_activity_student_ids_str = implode(',', $no_activity_student_ids);
			$no_activity_student_names_str = implode(',', $no_activity_student_names);
			//发件人
			$email_conf = $this->email_conf_model->where(array('admin_id' => sp_get_current_admin_id()))->find();
			$email_sender = empty($email_conf) ? C('SP_MAIL_SENDER') : $email_conf['email_sender'];
			$email_address = empty($email_conf) ? C('SP_MAIL_ADDRESS') : $email_conf['email_address'];
			$this->assign('email_sender',$email_sender);
			$this->assign('email_address',$email_address);
			$this->assign('activity',$activity);
			$this->assign('activity_time',$activity_time);
			$this->assign('activity_address',$activity_address);
			$this->assign('students',$students);
			$this->assign('activity_students',$activity_students);
			$this->assign('no_activity_students',$no_activity_students);
			$this->assign('student_ids_str',$student_ids_str);
			$this->assign('activity_student_ids_str',$activity_student_ids_str);
			$this->assign('no_activity_student_ids_str',$no_activity_student_ids_str);
			$this->assign('student_names_str',$student_names_str);
			$this->assign('activity_student_names_str',$activity_student_names_str);
			$this->assign('no_activity_student_names_str',$no_activity_student_names_str);
			$this->display();
		}
	}
	// 多人邮件发送(评估)
	public function assessment_send_mails(){
		if(IS_POST){
			$rules = array(
					array('subject','require','标题不能为空！',1,'regex',3),
					array('content','require','内容不能为空！',1,'regex',3),
			);
	
			$model = M(); // 实例化User对象
			if ($model->validate($rules)->create()!==false){
				$assessment = $this->assessment_model->find($_POST['assessment_id']);
				$student_flg = $_POST['student_flg'];
				$student_emails = array();
				//可评估所有学生
				if ($student_flg == 1) {
					$students = $this->user_model->where(array('id' => array('in',$_POST['student_ids'])))->select();
					foreach ($students as $student) {
						if ($student['student_email']) $student_emails[] = $student['student_email'];
					}
				}
				//评估此评估的学生
				if ($student_flg == 2) {
					$assessment_students = $this->user_model->where(array('id' => array('in',$_POST['assessment_student_ids'])))->select();
					foreach ($activity_students as $activity_student) {
						if ($assessment_student['student_email']) $student_emails[] = $assessment_student['student_email'];
					}
				}
				//未评估此评估的学生
				if ($student_flg == 3) {
					$no_assessment_students = $this->user_model->where(array('id' => array('in',$_POST['no_assessment_student_ids'])))->select();
					foreach ($no_assessment_students as $no_assessment_student) {
						if ($no_assessment_student['student_email']) $student_emails[] = $no_assessment_student['student_email'];
					}
				}
				$key_arr = array(
						'http://#link#'
				);
				$value_arr = array(
						"<a href='http://ciee.ccdcsh.cn/index.php?g=&m=assessment&a=detail&id=".$_POST['assessment_id']."'> View details</a> "
				);
				$_POST['content'] = str_replace($key_arr, $value_arr,$_POST['content']);
				$_POST['content'] = htmlspecialchars_decode($_POST['content']);//."<a href='http://ciee.ccdcsh.cn/index.php?g=&m=activity&a=detail&id=".$data['activity_id']."'> 查看详情 </a> ";
				$result=sp_send_email($student_emails, $_POST['subject'], $_POST['content']);
				if($result && empty($result['error'])){
					//$this->assign('activity_id',$data['activity_id']);
					$this->success('发送成功！');
				}else{
					$this->error('发送失败：'.$result['message']);
				}
	
			}else{
				$this->error($model->getError());
			}
		}else{
			$where = array();
			$where['email_status'] = 1;
			$list = $this->email_template_model->where($where)->select();
			$email_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
			foreach ($list as $email) {
				$email_html .= "<option value='".$email['id']."'>".$email['email_title']."</option>";
			}
			$this->assign('email_html',$email_html);
	
			$assessment_id = I('get.id');
			$assessment = $this->assessment_model->where(array('id' => $assessment_id))->find();
			//此评估下的学生（学季）
			$terms = json_decode($assessment['term_year_sess'],true);
			$student_ids = array();
			foreach ($terms as $tys) {
				$term_year_sess_arr = explode('-', $tys);
				$year = $term_year_sess_arr[0];
				$term = $term_year_sess_arr[1];
				$sess = $term_year_sess_arr[2];
				$program = $term_year_sess_arr[3];
				$student_where = array();
				if ($year) {
					$student_where['u.year'] = $year;
				} else {
					$student_where[] = "u.year = '' OR u.year is null";
				}
				if ($term) {
					$student_where['u.term'] = $term;
				} else {
					$student_where[] = "u.term = '' OR u.term is null";
				}
				if ($sess) {
					$student_where['u.session'] = $sess;
				} else {
					$student_where[] = "u.session = '' OR u.session is null";
				}
				if ($program) {
					$student_where['u.program'] = $program;
				} else {
					$student_where[] = "u.program = '' OR u.program is null";
				}
				$student_where['u.user_status'] = 1;
				$term_students = $this->user_model->alias('u')->field('u.*')->where($student_where)->order('u.first_name asc,u.last_name asc')->select();
				foreach ($term_students as $term_student) $student_ids[] = $term_student['id'];
			}
			$students = $this->user_model->alias('u')->field('u.*')->where(array('id' => array('in',implode(',', $student_ids))))->order('u.first_name asc,u.last_name asc')->select();
			$student_names = array();
			foreach ($students as $student) {
				$student_names[] = $student['full_name'];
			}
			$student_ids_str = implode(',', $student_ids);
			$student_names_str = implode(',', $student_names);
			//此评估下已评估的学生
			$assessment_answers = $this->assessment_answer_model->field('distinct student_id')->where(array('assessment_id' => $assessment_id))->select();
			$assessment_student_ids = array();
			$assessment_student_names = array();
			foreach ($assessment_answers as $assessment_answer) $assessment_student_ids[] = $assessment_answer['student_id'];
			$assessment_students = $this->user_model->alias('u')->field('u.*')->where(array('u.id' => array('in',implode(',', $assessment_student_ids))))->select();
			
			foreach ($assessment_students as $assessment_student) {
				$assessment_student_names[] = $assessment_student['full_name'];
			}
			$assessment_student_ids_str = implode(',', $assessment_student_ids);
			$assessment_student_names_str = implode(',', $assessment_student_names);
			//未评估的学生
			$no_assessment_student_ids = array_diff($student_ids, $assessment_student_ids);
			$no_assessment_students = $this->user_model->alias('u')->field('u.*')->where(array('u.id' => array('in',implode(',', $no_assessment_student_ids))))->select();
			$no_assessment_student_names = array();
			foreach ($no_assessment_students as $no_assessment_student) {
				$no_assessment_student_names[] = $no_assessment_student['full_name'];
			}
			$no_assessment_student_ids_str = implode(',', $no_assessment_student_ids);
			$no_assessment_student_names_str = implode(',', $no_assessment_student_names);
			//发件人
			$email_conf = $this->email_conf_model->where(array('admin_id' => sp_get_current_admin_id()))->find();
			$email_sender = empty($email_conf) ? C('SP_MAIL_SENDER') : $email_conf['email_sender'];
			$email_address = empty($email_conf) ? C('SP_MAIL_ADDRESS') : $email_conf['email_address'];
			$this->assign('email_sender',$email_sender);
			$this->assign('email_address',$email_address);
			$this->assign('assessment',$assessment);
			$this->assign('students',$students);
			$this->assign('assessment_students',$assessment_students);
			$this->assign('no_assessment_students',$no_assessment_students);
			$this->assign('student_ids_str',$student_ids_str);
			$this->assign('assessment_student_ids_str',$assessment_student_ids_str);
			$this->assign('no_assessment_student_ids_str',$no_assessment_student_ids_str);
			$this->assign('student_names_str',$student_names_str);
			$this->assign('assessment_student_names_str',$assessment_student_names_str);
			$this->assign('no_assessment_student_names_str',$no_assessment_student_names_str);
			$this->display();
		}
	}
	// 多人邮件发送(评估)
	public function allotlist_send_mails(){
		if(IS_POST){
			$rules = array(
					array('inbox','require','收件箱不能为空！',1,'regex',3),
					array('subject','require','标题不能为空！',1,'regex',3),
					array('content','require','内容不能为空！',1,'regex',3),
			);
	
			$model = M(); // 实例化User对象
			if ($model->validate($rules)->create()!==false){
				$teachers = $this->user_model->where(array('id' => array('in',$_POST['inbox'])))->select();
				$teacher_emails = array();
				foreach ($teachers as $teacher) {
					if ($teacher['user_email']) $teacher_emails[] = $teacher['user_email'];
				}
				$key_arr = array(
						'http://#link#'
				);
				$value_arr = array(
						"<a href='http://ciee.ccdcsh.cn/index.php?g=&m=course&a=roster&id=".$_POST['course_id']."&term=".$_POST['term']."&year=".$_POST['year']."&sess=".$_POST['sess']."&program=".$_POST['program']."'> View details</a> "
				);
				$_POST['content'] = str_replace($key_arr, $value_arr,$_POST['content']);
				$_POST['content'] = htmlspecialchars_decode($_POST['content']);//."<a href='http://ciee.ccdcsh.cn/index.php?g=&m=activity&a=detail&id=".$data['activity_id']."'> 查看详情 </a> ";
				$result=sp_send_email($teacher_emails, $_POST['subject'], $_POST['content']);
				if($result && empty($result['error'])){
					//$this->assign('activity_id',$data['activity_id']);
					$this->success('发送成功！');
				}else{
					$this->error('发送失败：'.$result['message']);
				}
	
			}else{
				$this->error($model->getError());
			}
		}else{
			$where = array();
			$where['email_status'] = 1;
			$list = $this->email_template_model->where($where)->select();
			$email_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
			foreach ($list as $email) {
				$email_html .= "<option value='".$email['id']."'>".$email['email_title']."</option>";
			}
			$this->assign('email_html',$email_html);
	
			$course_id = I('get.id');
			$term = str_replace('*', '&', trim(I( 'get.term' ))) ;
			$year = str_replace('*', '&', trim(I( 'get.year' ))) ;
			$sess = str_replace('*', '&', trim(I( 'get.session' ))) ;
			$program = str_replace('*', '&', trim(I( 'get.program' ))) ;
			$course = $this->course_model->find($course_id);
			//staff
			$staff_id = $course['headteacher_id'];
			$staff = $this->user_model->find($staff_id);
			$staff_name = $staff['full_name'];
			//教师
			$teacher_ids = $course['parttimeteacher_id'];
			if ($teacher_ids) {
				$teachers = $this->user_model->where(array('id' => array('in',$teacher_ids)))->select();
				$teacher_names_arr = array();
				foreach ($teachers as $teacher) $teacher_names_arr[] = $teacher['full_name'];
				$teacher_names_str = implode(',', $teacher_names_arr);
			}
			
			$this->assign( 'staff_id', $staff_id );
			$this->assign( 'staff_name', $staff_name );
			$this->assign( 'teacher_ids', $teacher_ids );
			$this->assign( 'teacher_names_str', $teacher_names_str );
			$this->assign( 'course_id', $course_id );
			$this->assign( 'course', $course );
			$this->assign( 'term', $term );
			$this->assign( 'year', $year );
			$this->assign( 'sess', $sess );
			$this->assign( 'program', $program );
			//发件人
			$email_conf = $this->email_conf_model->where(array('admin_id' => sp_get_current_admin_id()))->find();
			$email_sender = empty($email_conf) ? C('SP_MAIL_SENDER') : $email_conf['email_sender'];
			$email_address = empty($email_conf) ? C('SP_MAIL_ADDRESS') : $email_conf['email_address'];
			$this->assign('email_sender',$email_sender);
			$this->assign('email_address',$email_address);
			$this->display();
		}
	}
    // 多人邮件发送(住宿)
    public function accommodation_send_mails(){
    	if(IS_POST){
    		$rules = array(
    				array('subject','require','标题不能为空！',1,'regex',3),
    				array('content','require','内容不能为空！',1,'regex',3),
    		);
    
    		$model = M(); // 实例化User对象
    		if ($model->validate($rules)->create()!==false){
    			$data=I('post.');
    			$student_emails = array();
    			//邮箱
    			$student_ids = $data['student_ids'];
    			$student_ids_str = implode(',', $student_ids);
    			$students = $this->user_model->where(array('id' => array('in',$student_ids_str)))->select();
    			foreach ($students as $student) {
    				if ($student['student_email']) $student_emails[] = $student['student_email'];
    			}
    			$data['content'] = htmlspecialchars_decode($data['content']);
    			$result=sp_send_email($student_emails, $data['subject'], $data['content']);
    			if($result && empty($result['error'])){
    				//$this->assign('activity_id',$data['activity_id']);
    				$this->success('发送成功！');
    			}else{
    				$this->error('发送失败：'.$result['message']);
    			}
    			 
    		}else{
    			$this->error($model->getError());
    		}
    
    	}else{
    		$where = array();
    		$where['user_type'] = 2;
    		$where['user_status'] = 1;
    		$where['student_email'] = array('neq','');
    		$students = $this->user_model->field('id,full_name,student_email')->where($where)->select();
    		$this->assign('students',$students);
    		$this->display();
    	}
    
    }
    public function timing_mails(){
    	ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
    	set_time_limit(0);//让程序无限制运行下去
    	$interval = 60;//每隔*秒
    	do {
    		$run = include './data/conf/config.php';
    		if(!$run['SP_MAIL_ISTIMING']) die('process abort');
    		
    		$student_emails = array();
    		$activities = $this->activity_model->where(array('is_timed' => 0,'activity_status' => 1))->order(' signup_end_time asc')->select();
    		foreach($activities as $activity) {
    			$activity_students = $this->activity_student_model->where(array('activity_id' => $activity['id']))->select();
    			foreach ($activity_students as $activity_student) {
    				$student = $this->user_model->where(array('id' => $activity_student['student_id'],'user_status' => 1))->find();
    				if ($student) {
    					if ($student['student_email']) $student_emails[] = $student['student_email'];
    				}
    			}
    			$subject = "#".$activity['activity_name']."#报名已结束";
    			$content = "#".$activity['activity_name']."#将于".$activity['activity_start_time']."正式开始，请同学们于".$activity['collection_time']."准时到".$activity['collection_site']."集合。 <a href='".leuu('portal/activity/detail',array('id' => $activity['id']))."'> 查看详情 </a> ";
    			$nowtime = time();
    			$signup_end_time = strtotime($activity['signup_end_time']);
    			if ($nowtime >= $signup_end_time && $nowtime <= strtotime('+1 minute',$signup_end_time)) {
	    			$result = sp_send_email($student_emails, $subject, $content);
	    			if($result && empty($result['error'])){
	    				$this->activity_model->where(array('id' => $activity['id']))->save(array('is_timed' => 1));
	    			}
    			} else {
    				break;
    			}
    		}
    		sleep($interval);// 等待*秒
    	}while (true);
    }
    	 
}

