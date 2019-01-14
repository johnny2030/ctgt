<?php
/** 
 * 前端招聘
 * @author 11k
 * likun_19911227@163.com
 */
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class RecruitController extends HomebaseController {

	private $formData = array();
	private $formError = array();
	private $formReturn = array();
	private $recruit_model;
	private $recruit_member_model;
	private $recruit_member_term_model;
	private $recruit_interviewdate_model;
	private $recruit_interviewtime_model;

	function _initialize() {
		parent::_initialize();

		$this->recruit_model = D( 'Recruit' );
		$this->recruit_member_model = D( 'RecruitMember' );
		$this->recruit_member_term_model = D( 'RecruitMemberTerm' );
		$this->recruit_interviewdate_model = D( 'RecruitInterviewdate' );
		$this->recruit_interviewtime_model = D( 'RecruitInterviewtime' );
	}
	//招聘列表
	public function index() {
		$where = array();
		
		$where['recruit_status'] = array('eq',1);
		$count = $this->recruit_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->recruit_model->where($where)->limit( $page->firstRow, $page->listRows )->order("recruit_modify_time desc")->select();
		$this->assign("page", $page->show('Portal'));
		$this->assign( 'list', $list );
		$this->display( '/../recruit' );
	}
	public function detail() {
		$id = (int)$_GET['id'];
		$recruit = $this->recruit_model->find($id);
	
		if ( empty( $recruit ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			header( 'Status:404 Not Found' );
			if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
			return;
		}
		
		$this->assign( $recruit );
		$this->display( '/../recruit_detail' );
	}
	public function interview() {
		$id = (int)$_GET['id'];
		$recruit = $this->recruit_model->find($id);
	
		if ( empty( $recruit ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			header( 'Status:404 Not Found' );
			if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
			return;
		}
	
		$recruit_interviewdate = $this->recruit_interviewdate_model->where(array('recruit_id' => $id))->select();
		$recruit_interviewtime = $this->recruit_interviewtime_model->where(array('recruit_id' => $id))->select();
	
		$this->assign( $recruit );
		$this->assign( 'recruit_interviewdate',$recruit_interviewdate );
		$this->assign( 'recruit_interviewtime',$recruit_interviewtime );
	
		$this->display( '/../recruit_interview' );
	}
	
	public function resume() {
		$recruit_member_id = (int)$_GET['recruit_member_id'];
		$recruit_member = $this->recruit_member_model->find($recruit_member_id);
		$recruit = $this->recruit_model->find($recruit_member['recruit_id']);
	
		if ( empty( $recruit_member ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			header( 'Status:404 Not Found' );
			if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
			return;
		}
	
		$this->assign( 'recruit',$recruit );
		$this->assign( 'recruit_member',$recruit_member );
	
		$this->display( '/../resume' );
	}
	
	public function confirm() {
		//面试状态：0，简历待审核；1，通过之后选择面试时间；2，未通过；3，面试通过；4，面试未通过；5，预约面试成功
		//通过手机号判断招聘成员是否已经存在于数据库中
		$recruit_member_existed = $this->recruit_member_model->where(array('phone' => $_POST['phone']))->find();
		if ($_FILES) {//申请表提交
			if ($recruit_member_existed) {//数据库中已经存在此手机号提交的申请
				if ($recruit_member_existed['recruit_id'] == $_POST['recruit_id']) {//首先判断此手机号提交的申请是否为同一个招聘，如果为同一招聘，则在未审核前再次提交的申请归类为修改信息，若为另一个招聘，则归类为续住申请
					if ($recruit_member_existed['interview_status'] == 0) {//简历待审核状态，可以对信息进行修改重复提交，此为新同屋申请修改信息
						if (empty($_POST['renew_status'])) {
							$uploadConfig = array(
									'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
									'rootPath' => './'.C( 'UPLOADPATH' ),
									'savePath' => './recruit/',
									'saveName' => array( 'uniqid', '' ),
									'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
									'autoSub' => false
							);
							$upload = new \Think\Upload( $uploadConfig );
							$info = $upload->upload();
								
							if($info) {
								//头像
								if ($info['avatar']) {
									$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
									$avatar_file = substr($avatar_file,1);
									$_POST['avatar']['name'] = $_FILES['avatar']['name'];
									$_POST['avatar']['url'] = "/data/upload".$avatar_file;
									$_POST['avatar'] = json_encode($_POST['avatar']);
								}
								$_POST['update_time'] = date('Y-m-d H:i:s',time());
								
								$result = $this->recruit_member_model->where(array('id' => $recruit_member_existed['id']))->save($_POST);
								if ($result) {
									$this->success('申请修改成功');
								} else {
									$this->error("申请修改失败");
								}
							}else{
								$this->error("未获取到上传头像信息");
							}
						} else {
							$this->error('请前往新同屋申请修改信息');
						}
					} elseif ($recruit_member_existed['interview_status'] == 3) {//续住申请，可以对信息进行修改重复提交
						if (!empty($_POST['renew_status'])) {
							$uploadConfig = array(
									'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
									'rootPath' => './'.C( 'UPLOADPATH' ),
									'savePath' => './recruit/',
									'saveName' => array( 'uniqid', '' ),
									'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
									'autoSub' => false
							);
							$upload = new \Think\Upload( $uploadConfig );
							$info = $upload->upload();
								
							if($info) {
								//头像
								if ($info['avatar']) {
									$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
									$avatar_file = substr($avatar_file,1);
									$_POST['avatar']['name'] = $_FILES['avatar']['name'];
									$_POST['avatar']['url'] = "/data/upload".$avatar_file;
									$_POST['avatar'] = json_encode($_POST['avatar']);
								}
								$_POST['update_time'] = date('Y-m-d H:i:s',time());
									
								$result = $this->recruit_member_model->where(array('id' => $recruit_member_existed['id']))->save($_POST);
								if ($result) {
									$this->success('申请修改成功');
								} else {
									$this->error("申请修改失败");
								}
							}else{
								$this->error("未获取到上传头像信息");
							}
						} else {
							$this->error('请前往续住申请修改信息');
						}
					} else {
						$this->error('您提交的申请已经处于审核中，不能修改！');
					}
				} else {
					if ($recruit_member_existed['interview_status'] == 0 || $recruit_member_existed['interview_status'] == 1 || $recruit_member_existed['interview_status'] == 5) {
						//已经有一个申请正在审核中，不能提交下个申请
						$this->error('您已经有招聘申请正在审核当中，不能提交另外的招聘申请');
					} elseif ($recruit_member_existed['interview_status'] == 2 || $recruit_member_existed['interview_status'] == 4) {
						//数据库已经存在的招聘申请审核未通过，可以提交下个申请
						if (empty($_POST['renew_status'])) {
							$uploadConfig = array(
									'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
									'rootPath' => './'.C( 'UPLOADPATH' ),
									'savePath' => './recruit/',
									'saveName' => array( 'uniqid', '' ),
									'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
									'autoSub' => false
							);
							$upload = new \Think\Upload( $uploadConfig );
							$info = $upload->upload();
							
							if($info) {
								//头像
								if ($info['avatar']) {
									$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
									$avatar_file = substr($avatar_file,1);
									$_POST['avatar']['name'] = $_FILES['avatar']['name'];
									$_POST['avatar']['url'] = "/data/upload".$avatar_file;
									$_POST['avatar'] = json_encode($_POST['avatar']);
								}
								$_POST['update_time'] = date('Y-m-d H:i:s',time());
								$_POST['interview_status'] = 0;//状态重新归0
								
								$result = $this->recruit_member_model->where(array('id' => $recruit_member_existed['id']))->save($_POST);
								if ($result) {
									$this->success('申请提交成功');
								} else {
									$this->error("申请提交失败");
								}
							}else{
								$this->error("未获取到上传头像信息");
							}
						} else {
							$this->error('请前往新同屋申请填写信息');
						}
					} elseif ($recruit_member_existed['interview_status'] == 3) {
						//面试通过，归类为续住申请
						if (!empty($_POST['renew_status'])) {
							
							$uploadConfig = array(
									'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
									'rootPath' => './'.C( 'UPLOADPATH' ),
									'savePath' => './recruit/',
									'saveName' => array( 'uniqid', '' ),
									'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
									'autoSub' => false
							);
							$upload = new \Think\Upload( $uploadConfig );
							$info = $upload->upload();
								
							if($info) {
								//头像
								if ($info['avatar']) {
									$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
									$avatar_file = substr($avatar_file,1);
									$_POST['avatar']['name'] = $_FILES['avatar']['name'];
									$_POST['avatar']['url'] = "/data/upload".$avatar_file;
									$_POST['avatar'] = json_encode($_POST['avatar']);
								}
								$_POST['update_time'] = date('Y-m-d H:i:s',time());
								$_POST['renew_status'] = 1;//状态重新归0
							
								$result = $this->recruit_member_model->where(array('id' => $recruit_member_existed['id']))->save($_POST);
								if ($result) {
									$this->success('申请提交成功');
								} else {
									$this->error("申请提交失败");
								}
							}else{
								$this->error("未获取到上传头像信息");
							}
						} else {
							$this->error('请前往续住申请填写信息');
						}
					} else {
						
					}
				}
				
			} else {//首次提交申请
				if (empty($_POST['renew_status'])) {
					//同屋招聘
					if ($_POST['status'] == 1) {
					
						$uploadConfig = array(
								'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
								'rootPath' => './'.C( 'UPLOADPATH' ),
								'savePath' => './recruit/',
								'saveName' => array( 'uniqid', '' ),
								'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
								'autoSub' => false
						);
						$upload = new \Think\Upload( $uploadConfig );
						$info = $upload->upload();
					
						if($info) {
							//头像
							if ($info['avatar']) {
								$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
								$avatar_file = substr($avatar_file,1);
								$_POST['avatar']['name'] = $_FILES['avatar']['name'];
								$_POST['avatar']['url'] = "/data/upload".$avatar_file;
								$_POST['avatar'] = json_encode($_POST['avatar']);
							}
							$_POST['create_time'] = date('Y-m-d H:i:s',time());
							$result = $this->recruit_member_model->add($_POST);
							if ($result) {
								$this->success('申请提交成功');
							} else {
								$this->error("申请提交失败");
							}
						}else{
							$this->error("未获取到上传头像信息");
						}
					}
				} else {
					$this->error('请前往新同屋申请提交信息');
				}
			}
		} else {
			$this->error('上传图片不符合要求');
		}
		
	}
	
	public function book() {
		$admin_id = 779;
		//面试状态：0，简历待审核；1，通过之后选择面试时间；2，未通过；3，面试通过；4，面试未通过；5，预约面试成功
		//通过手机号判断招聘成员是否已经存在于数据库中
		$recruit_member_existed = $this->recruit_member_model->where(array('phone' => $_POST['phone']))->find();
		//预约面试
		if ($recruit_member_existed) {
			if ($recruit_member_existed['recruit_id'] == $_POST['recruit_id']) {
				if ($recruit_member_existed['interview_status'] == 0) {
					$this->error("申请还未通过审核！");
				} elseif ($recruit_member_existed['interview_status'] == 1) {
					$_POST['interview_status'] = 5;
					$recruit_member_id = $this->recruit_member_model->where(array('id' => $recruit_member_existed['id']))->save($_POST);
					if ($recruit_member_id) {
						$recruit_member = $this->recruit_member_model->where(array('id' => $recruit_member_existed['id']))->find();
						$recruit = $this->recruit_model->find($recruit_member['recruit_id']);
						$interview_date = $this->recruit_interviewdate_model->find($recruit_member['interviewdate_id']);
						$interview_time = $this->recruit_interviewtime_model->find($recruit_member['interviewtime_id']);
						$subject = $recruit['recruit_name'];
						$content = $recruit_member['name']."同学，您好。<br>您预约的".$recruit['recruit_name']."面试将于".$interview_date['interview_date']." ".$interview_time['interview_start_time']."开始，请准时到".$recruit['recruit_address']."面试。 ";
						$content = htmlspecialchars_decode($content);
						$result = sp_send_email_front($recruit_member['email'], $subject, $content,$admin_id);
							
						$this->success('面试预约成功');
					} else {
						$this->error("面试预约失败");
					}
				} elseif ($recruit_member_existed['interview_status'] == 2) {
					$this->error("申请未通过审核！");
				} else {
					$this->error("已经预约过面试，不能重复预约！");
				}
					
			} else {
				$this->error('此招聘没有通知您面试，不能预约');
			}
		} else {
			$this->error("尚未提交申请表，不能预约面试！");
		}
	}
	//备份方法
	public function confirm_bak() {
		$recruit = $this->recruit_model->find($_POST['recruit_id']);
		$recruit_member_existed = $this->recruit_member_model->where(array('phone' => $_POST['phone']))->find();
		if ($_FILES) {
			if ($recruit_member_existed) {
				//同屋招聘
				if ($_POST['status'] == 1) {
					$recruit_member_terms = $this->recruit_member_term_model->where(array('recruit_member_id' => $recruit_member_existed['id']))->select();
					$recruit_member_terms_arr = array();
					foreach ($recruit_member_terms as $recruit_member_term) $recruit_member_terms_arr[] = $recruit_member_term['term_year'];
					$recruit_term_year = json_decode($recruit['term_year'],true);
					//招聘成员表里边已经存在这个招聘信息
					if (in_array($recruit_term_year, $recruit_member_terms_arr)) {
						$this->error("此手机号已经提交过此招聘申请表，请勿重复提交！");
					} else {
						foreach ($recruit_term_year as $rty) {
							//$rty_arr = explode('-', $rty);
							//$this->recruit_member_term_model->add(array('term_year' => $rty,'recruit_member_id' => $recruit_member_existed['id'],'term' => $rty_arr[1],'year' => $rty_arr[0]));
							$this->recruit_member_term_model->add(array('term_year' => $rty,'recruit_member_id' => $recruit_member_existed['id']));
						}
						if ($recruit_member_existed['interview_status'] == 3) {
							$_POST['renew_status'] = 1;//以前提交申请并且面试通过的；定义为续住
						} elseif ($recruit_member_existed['interview_status'] == 0 || $recruit_member_existed['interview_status'] == 1 || $recruit_member_existed['interview_status'] == 5) {
							$this->error("此手机号提交的上个申请正在处理中，请静待结果！");
						} else {//状态为2,4
							//$this->recruit_member_term_model->where(array('recruit_member_id' => $recruit_member_existed['id']))->delete();
							$_POST['renew_status'] = 0;//以前提交过申请但是由于申请未通过审核或面试未通过
							$_POST['interview_status'] = 0;
						}
						
						$uploadConfig = array(
								'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
								'rootPath' => './'.C( 'UPLOADPATH' ),
								'savePath' => './recruit/',
								'saveName' => array( 'uniqid', '' ),
								'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
								'autoSub' => false
						);
						$upload = new \Think\Upload( $uploadConfig );
						$info = $upload->upload();
						
						if($info) {
							//简历
							if ($info['resume']) {
								$resume_file = $info['resume']['savepath'].$info['resume']['savename'];
								$resume_file = substr($resume_file,1);
								$_POST['smeta']['resume_name'] = $_FILES['resume']['name'];
								$_POST['smeta']['resume_url'] = "/data/upload".$resume_file;
								$_POST['smeta'] = json_encode($_POST['smeta']);
							}
							//头像
							if ($info['avatar']) {
								$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
								$avatar_file = substr($avatar_file,1);
								$_POST['avatar']['name'] = $_FILES['avatar']['name'];
								$_POST['avatar']['url'] = "/data/upload".$avatar_file;
								$_POST['avatar'] = json_encode($_POST['avatar']);
							}
							$recruit_member = $this->recruit_member_model->where(array('phone' => $_POST['phone']))->save($_POST);
							if ($recruit_member) {
								$this->success('申请表提交成功');
							} else {
								$this->error("申请表提交失败");
							}
						}else{
							$this->error("申请表提交失败");
						}
					}
				}
				if ($_POST['status'] == 2) {
					
					if (empty($_POST['renew_status'])){
						$this->error("此手机号已经提交申请！");
					}else {
						if ($recruit_member_existed['interview_status'] != 3) {
							$this->error("此手机号提交的申请未通过，不能选择续用申请");
						} else {
							$uploadConfig = array(
									'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
									'rootPath' => './'.C( 'UPLOADPATH' ),
									'savePath' => './recruit/',
									'saveName' => array( 'uniqid', '' ),
									'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
									'autoSub' => false
							);
							$upload = new \Think\Upload( $uploadConfig );
							$info = $upload->upload();
							
							if($info) {
								//简历
								if ($info['resume']) {
									$resume_file = $info['resume']['savepath'].$info['resume']['savename'];
									$resume_file = substr($resume_file,1);
									$_POST['smeta']['resume_name'] = $_FILES['resume']['name'];
									$_POST['smeta']['resume_url'] = "/data/upload".$resume_file;
									$_POST['smeta'] = json_encode($_POST['smeta']);
								}
								//头像
								if ($info['avatar']) {
									$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
									$avatar_file = substr($avatar_file,1);
									$_POST['avatar']['name'] = $_FILES['avatar']['name'];
									$_POST['avatar']['url'] = "/data/upload".$avatar_file;
									$_POST['avatar'] = json_encode($_POST['avatar']);
								}
								$recruit_member = $this->recruit_member_model->where(array('phone' => $_POST['phone']))->save($_POST);
								if ($recruit_member) {
									$this->success('申请表提交成功');
								} else {
									$this->error("申请表提交失败");
								}
							}else{
								$this->error("申请表提交失败");
							}
						}
					}
				}
				
			}else {
				//同屋招聘
				if ($_POST['status'] == 1) {
					
					$uploadConfig = array(
							'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
							'rootPath' => './'.C( 'UPLOADPATH' ),
							'savePath' => './recruit/',
							'saveName' => array( 'uniqid', '' ),
							'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
							'autoSub' => false
					);
					$upload = new \Think\Upload( $uploadConfig );
					$info = $upload->upload();
			
					if($info) {
						//简历
						if ($info['resume']) {
							$resume_file = $info['resume']['savepath'].$info['resume']['savename'];
							$resume_file = substr($resume_file,1);
							$_POST['smeta']['resume_name'] = $_FILES['resume']['name'];
							$_POST['smeta']['resume_url'] = "/data/upload".$resume_file;
							$_POST['smeta'] = json_encode($_POST['smeta']);
						}
						//头像
						if ($info['avatar']) {
							$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
							$avatar_file = substr($avatar_file,1);
							$_POST['avatar']['name'] = $_FILES['avatar']['name'];
							$_POST['avatar']['url'] = "/data/upload".$avatar_file;
							$_POST['avatar'] = json_encode($_POST['avatar']);
						}
						$recruit_member_id = $this->recruit_member_model->add($_POST);
						if ($recruit_member_id) {
							/* $recruit_term_year = json_decode($recruit['term_year'],true);
							foreach ($recruit_term_year as $rty) {
								//$rty_arr = explode('-', $rty);
								//$this->recruit_member_term_model->add(array('term_year' => $rty,'recruit_member_id' => $recruit_member_id,'term' => $rty_arr[1],'year' => $rty_arr[0]));
								$this->recruit_member_term_model->add(array('term_year' => $rty,'recruit_member_id' => $recruit_member_id));
							} */
							$this->success('申请表提交成功');
						} else {
							$this->error("申请表提交失败");
						}
					}else{
						$this->error("申请表提交失败");
					}
				}
				if ($_POST['status'] == 2) {
					
					if (empty($_POST['renew_status'])){
						$uploadConfig = array(
								'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
								'rootPath' => './'.C( 'UPLOADPATH' ),
								'savePath' => './recruit/',
								'saveName' => array( 'uniqid', '' ),
								'exts' => array(  'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm', 'jpg', 'jpeg', 'png' ),
								'autoSub' => false
						);
						$upload = new \Think\Upload( $uploadConfig );
						$info = $upload->upload();
						
						if($info) {
							//简历
							if ($info['resume']) {
								$resume_file = $info['resume']['savepath'].$info['resume']['savename'];
								$resume_file = substr($resume_file,1);
								$_POST['smeta']['resume_name'] = $_FILES['resume']['name'];
								$_POST['smeta']['resume_url'] = "/data/upload".$resume_file;
								$_POST['smeta'] = json_encode($_POST['smeta']);
							}
							//头像
							if ($info['avatar']) {
								$avatar_file = $info['avatar']['savepath'].$info['avatar']['savename'];
								$avatar_file = substr($avatar_file,1);
								$_POST['avatar']['name'] = $_FILES['avatar']['name'];
								$_POST['avatar']['url'] = "/data/upload".$avatar_file;
								$_POST['avatar'] = json_encode($_POST['avatar']);
							}
							$recruit_member = $this->recruit_member_model->add($_POST);
							if ($recruit_member) {
								$this->success('申请表提交成功');
							} else {
								$this->error("申请表提交失败");
							}
						}else{
							$this->error("申请表提交失败");
						}
					} else {
						$this->error("此手机号尚未提交过申请，请选择首次提交！");
					}
				}
			}
		} else {
			if ($recruit_member_existed) {
				if ($recruit_member_existed['interview_status'] == 0) {
					$this->error("申请还未通过审核！");
				} elseif ($recruit_member_existed['interview_status'] == 1) {
					$_POST['interview_status'] = 5;
					unset($_POST['phone']);
					$recruit_member_id = $this->recruit_member_model->where(array('phone' => $recruit_member_existed['phone']))->save($_POST);
					if ($recruit_member_id) {
						$recruit_member = $this->recruit_member_model->where(array('phone' => $recruit_member_existed['phone']))->find();
						$recruit = $this->recruit_model->find($recruit_member['recruit_id']);
						$interview_date = $this->recruit_interviewdate_model->find($recruit_member['interviewdate_id']);
						$interview_time = $this->recruit_interviewtime_model->find($recruit_member['interviewtime_id']);
						$subject = $recruit['recruit_name'];
						$content = $recruit_member['name']."同学，您好。<br>您预约的".$recruit['recruit_name']."面试将于".$interview_date['interview_date']." ".$interview_time['interview_start_time']."开始，请准时到".$recruit['recruit_address']."面试。 ";
						$content = htmlspecialchars_decode($content);
						$result = sp_send_email($recruit_member['email'], $subject, $content);
						
						$this->success('面试预约成功');
					} else {
						$this->error("面试预约失败");
					}
				} elseif ($recruit_member_existed['interview_status'] == 2) {
					$this->error("申请未通过审核！");
				} else {
					$this->error("已经预约过面试，不能重复预约！");
				}
				
			} else {
				$this->error("尚未提交申请表，不能预约面试！");
			}
			
		}
	}

	//检测此时间点是否被预约
	function checkTime() {
		if (IS_AJAX){
			$interviewdate_id = (int)I('interviewdate_id');
			$recruit_id = (int)I('recruit_id');
			$recruit_times = $this->recruit_interviewtime_model->where(array('recruit_id' => $recruit_id))->select();
			$data = array();
			$index = 0;
			foreach ($recruit_times as $recruit_time) {
				$recruit_member_count = $this->recruit_member_model->where(array('interviewdate_id' => $interviewdate_id,'interviewtime_id' => $recruit_time['id'],'recruit_id' => $recruit_id))->count();
				if ($recruit_member_count >= $recruit_time['interview_people_number']) {
					$data['interviewtime_id'][] = $recruit_time['id'];
					$index++;
				}
			}
			$data['index'] = $index;
			$this->ajaxReturn($data);
		}
	}
}