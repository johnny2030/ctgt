<?php
/**
 * 招聘管理
 * @author 11k
 * likun_19911227@163.com
 * 2018/1/17 14:50
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class RecruitController extends AdminbaseController {

	private $recruit_model;
	private $recruit_interviewdate_model;
	private $recruit_interviewtime_model;
	private $recruit_member_model;
	private $recruit_member_term_model;
	private $users_model;
	private $role_user_model;

	function _initialize() {
		parent::_initialize();
		$this->recruit_model = D( 'Recruit' );
		$this->recruit_interviewdate_model = D( 'RecruitInterviewdate' );
		$this->recruit_interviewtime_model = D( 'RecruitInterviewtime' );
		$this->recruit_member_model = D( 'RecruitMember' );
		$this->recruit_member_term_model = D( 'RecruitMemberTerm' );
		$this->users_model = D( 'Users' );
		$this->role_user_model = D( 'RoleUser' );
	}
	//招聘信息列表
	function index() {
		$where = array();
		//招聘信息标题搜索
		$keyword = trim( I('request.keyword') );
		$this->assign( 'keyword', $keyword );
		
		$recruit_type = I('request.recruit_type');
		$this->assign( 'recruit_type', $recruit_type );
		if ( !empty($keyword) ) {
			$where['recruit_name'] = array('like',"%$keyword%",'and');
		}
		if ($recruit_type) $where['recruit_type'] = $recruit_type;
		$where['recruit_status'] = array('neq',2);
		$count = $this->recruit_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->recruit_model->where($where)->limit( $page->firstRow, $page->listRows )->order("recruit_modify_time desc")->select();
	
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//招聘人员申请列表
	function apply_member() {
		$id = (int)$_REQUEST['id'];
		$keyword = $_REQUEST['keyword'];
		$this->assign( 'keyword', $keyword );
		
		$recruit = $this->recruit_model->find($id);
		$this->assign($recruit);
		
		$where = array();
		if ( $keyword ) $where['rm.name|rm.phone|rm.email'] = array('like',"%$keyword%");
		//招聘信息标题搜索
		$interview_status = (int)$_REQUEST['interview_status'] ;
		$this->assign( 'interview_status', $interview_status );
		
		$where['rm.interview_status'] = $interview_status;
		$where['rm.recruit_id'] = $id;
		//预约时间搜索
		if ($interview_status == 5 && $recruit['recruit_type'] == 1) {
			$start_date = I('request.start_date') ;
			$this->assign( 'start_date', $start_date );
			$end_date = I('request.end_date') ;
			$this->assign( 'end_date', $end_date );
			if ($start_date && $end_date && $start_date > $end_date) $this->error('开始日期不能大于结束日期');
			
			if ($start_date) {
				$where['rid.interview_date'] =array( array('egt',$start_date) ) ;
			}
			if ($end_date) {
				if(empty($where['rid.interview_date'])){
					$where['rid.interview_date']=array();
				}
				array_push($where['rid.interview_date'], array('elt',$end_date));
			}
			$count = $this->recruit_member_model
							->alias('rm')
							->join('__RECRUIT_INTERVIEWDATE__ rid ON rid.id=rm.interviewdate_id')
							->join('__RECRUIT_INTERVIEWTIME__ rit ON rit.id=rm.interviewtime_id')
							->where($where)
							->count();
			$page = $this->page($count, 10);
			$list = $this->recruit_member_model
						->alias('rm')
						->field('rm.*')
						->join('__RECRUIT_INTERVIEWDATE__ rid ON rid.id=rm.interviewdate_id')
						->join('__RECRUIT_INTERVIEWTIME__ rit ON rit.id=rm.interviewtime_id')
						->where($where)
						->order('rid.interview_date asc,rit.interview_start_time asc')
						->limit( $page->firstRow, $page->listRows )
						->select();
		} else {
			$count = $this->recruit_member_model->alias('rm')->where($where)->count();
			$page = $this->page($count, 10);
			$list = $this->recruit_member_model->alias('rm')->where($where)->order('teacher_interviewtime asc')->limit( $page->firstRow, $page->listRows )->select();
		}
	
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加招聘信息
	function add() {
		if ( IS_POST ) {
			/* if(!empty($_FILES['template']['tmp_name'])){
				$uploadConfig = array(
						'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
						'rootPath' => './'.C( 'UPLOADPATH' ),
						'savePath' => './excel/recruit/',
						'saveName' => array( 'uniqid', '' ),
						'exts' => array( 'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm' ),
						'autoSub' => false
				);
				$upload = new \Think\Upload( $uploadConfig );
				$info = $upload->upload();
				if($info) {
					$template = $info['template']['savepath'].$info['template']['savename'];
					$template = substr($template,1);
					$_POST['template'] = "/data/upload".$template;
				}else{
					$this->error("文件上传失败");
				}
				$_POST['template_name'] = $_FILES['template']['name'];
			} */
			
			//面试日期
			$recruit_dates = array();
			foreach ($_POST['recruit_date'] as $recruit_date){
				$recruit_date = $recruit_date;
			
				if ($recruit_date) $recruit_dates[] = array('interview_date' => $recruit_date);
			}
			//面试时间
			$recruit_times = array();
			foreach ($_POST['recruit_start_time'] as $k => $recruit_start_time){
				$recruit_start_time = $recruit_start_time;
				$recruit_end_time = $_POST['recruit_end_time'][$k];
				$interview_people_number = $_POST['interview_people_number'][$k];
					
				if ($recruit_start_time) $recruit_times[] = array('interview_start_time' => $recruit_start_time,'interview_end_time' => $recruit_end_time,'interview_people_number' => $interview_people_number);
			}
			
			$_POST['term_year'] = json_encode($_POST['term_year']);
			$_POST['recruit_content'] = htmlspecialchars_decode($_POST['recruit_content']);
			$_POST['recruit_modify_time'] = date('Y-m-d H:i:s',time());
			
			$recruit_id = $this->recruit_model->add($_POST);
			if ($recruit_id) {
				foreach ($recruit_dates as $recruit_date) {
					$recruit_date['recruit_id'] = $recruit_id;
					$this->recruit_interviewdate_model->add($recruit_date);
				}
				foreach ($recruit_times as $recruit_time) {
					$recruit_time['recruit_id'] = $recruit_id;
					$this->recruit_interviewtime_model->add($recruit_time);
				}
				//记录日志
				LogController::log_record($recruit_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$term_year = $this->users_model->field('distinct term,year,term_status')->where($map)->order('year desc')->select();
			$term_year_html = " ";
			foreach ($term_year as $ty) {
				$term_year_str = trim($ty['year'])."-".trim($ty['term']);
				$term_year_html .= "<option value='".$term_year_str."'>".$term_year_str."</option>";
			}
			$this->assign('term_year_html',$term_year_html);
			$this->display();
		}
	}
	//填写面试时间
	function enter_interviewtime() {
		if ( IS_POST ) {
			
			$recruit_member_id = (int)$_POST['recruit_member_id'];
			unset($_POST['recruit_member_id']);
			$_POST['interview_status'] = 5;//已预约面试状态
				
			$recruit_member_save = $this->recruit_member_model->where(array('id' => $recruit_member_id))->save($_POST);
			$recruit_member = $this->recruit_member_model->find($recruit_member_id);
			$recruit = $this->recruit_model->find($recruit_member['recruit_id']);
			$subject = $recruit['recruit_name'];
			$content = $recruit_member['name'];
			if ($recruit_member['gender'] == 1) $content += " 先生";
			if ($recruit_member['gender'] == 2) $content += " 女士";
			$content += "，您好。<br>您预约的".$recruit['recruit_name']."面试将于".$recruit_member['teacher_interviewtime']."开始，请准时到".$recruit['recruit_address']."面试。 ";
			$content = htmlspecialchars_decode($content);
			$result = sp_send_email($recruit_member['email'], $subject, $content);
			if ($recruit_member_save && $result) {
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->assign('recruit_member_id',$_GET['recruit_member_id']);
			$this->display();
		}
	}
	//删除招聘信息
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['recruit_status'] = 2;
			if ( $this->recruit_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['recruit_status'] = 1;
			if ( $this->recruit_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->recruit_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['recruit_status'] = 2;
			if ( $this->recruit_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
	}
	//编辑招聘信息
	function edit() {
		if ( IS_POST ) {
			$recruit_id = (int)$_POST['id'];
			/* if(!empty($_FILES['template']['tmp_name'])){
				$uploadConfig = array(
						'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
						'rootPath' => './'.C( 'UPLOADPATH' ),
						'savePath' => './excel/recruit/',
						'saveName' => array( 'uniqid', '' ),
						'exts' => array( 'xls', 'xlsx', 'docx', 'doc', 'dot', 'dotx', 'docm' ),
						'autoSub' => false
				);
				$upload = new \Think\Upload( $uploadConfig );
				$info = $upload->upload();
				if($info) {
					$template = $info['template']['savepath'].$info['template']['savename'];
					$template = substr($template,1);
					$_POST['template'] = "/data/upload".$template;
				}else{
					$this->error("文件上传失败");
				}
				$_POST['template_name'] = $_FILES['template']['name'];
			} */
				
			//面试日期
			$recruit_interviewdates = $this->recruit_interviewdate_model->where(array('recruit_id' => $recruit_id))->select();
			$recruit_interviewdate_ids = array();
			foreach ($recruit_interviewdates as $recruit_interviewdate) $recruit_interviewdate_ids[] = $recruit_interviewdate['id'];
			
			$recruit_interviewdate_add = array();
			$recruit_interviewdate_edit = array();
			$recruit_interviewdate_del = array();
			$recruit_interviewdate_edit_id = array();
			foreach ($_POST['recruit_date'] as $k => $recruit_date){
				$recruit_date_id = (int)$_POST['recruit_interviewdate_id'][$k];
				$recruit_date = $recruit_date;
			
				if ($recruit_date) {
					if (in_array($recruit_date_id, $recruit_interviewdate_ids)){
						//修改
						$recruit_interviewdate_edit[] = array('id' => $recruit_date_id,'recruit_id' => $recruit_id,'interview_date' => $recruit_date);
						$recruit_interviewdate_edit_id[] = $recruit_date_id;
					} else {
						//新增
						$recruit_interviewdate_add[] = array('recruit_id' => $recruit_id,'interview_date' => $recruit_date);
					}
				}
			}
			foreach ($recruit_interviewdate_ids as $recruit_interviewdate_id) {
				if (!in_array($recruit_interviewdate_id, $recruit_interviewdate_edit_id)) $recruit_interviewdate_del[] = $recruit_interviewdate_id;
			}
			
			//面试时间
			$recruit_interviewtimes = $this->recruit_interviewtime_model->where(array('recruit_id' => $recruit_id))->select();
			$recruit_interviewtime_ids = array();
			foreach ($recruit_interviewtimes as $recruit_interviewtime) $recruit_interviewtime_ids[] = $recruit_interviewtime['id'];
				
			$recruit_interviewtime_add = array();
			$recruit_interviewtime_edit = array();
			$recruit_interviewtime_del = array();
			$recruit_interviewtime_edit_id = array();
			foreach ($_POST['recruit_start_time'] as $k => $recruit_start_time){
				$recruit_time_id = (int)$_POST['recruit_interviewtime_id'][$k];
				$recruit_start_time = $recruit_start_time;
				$recruit_end_time = $_POST['recruit_end_time'][$k];
				$interview_people_number = (int)$_POST['interview_people_number'][$k];
					
				if ($recruit_start_time) {
					if (in_array($recruit_time_id, $recruit_interviewtime_ids)){
						//修改
						$recruit_interviewtime_edit[] = array('id' => $recruit_time_id,'recruit_id' => $recruit_id,'interview_start_time' => $recruit_start_time,'interview_end_time' => $recruit_end_time,'interview_people_number' => $interview_people_number);
						$recruit_interviewtime_edit_id[] = $recruit_time_id;
					} else {
						//新增
						$recruit_interviewtime_add[] = array('recruit_id' => $recruit_id,'interview_start_time' => $recruit_start_time,'interview_end_time' => $recruit_end_time,'interview_people_number' => $interview_people_number);
					}
				}
			}
			foreach ($recruit_interviewtime_ids as $recruit_interviewtime_id) {
				if (!in_array($recruit_interviewtime_id, $recruit_interviewtime_edit_id)) $recruit_interviewtime_del[] = $recruit_interviewtime_id;
			}
			
			$_POST['term_year'] = json_encode($_POST['term_year']);
			$_POST['recruit_content'] = htmlspecialchars_decode($_POST['recruit_content']);
			$_POST['recruit_modify_time'] = date('Y-m-d H:i:s',time());
			
			$result = $this->recruit_model->where(array('id' => $recruit_id))->save($_POST);
			if ($result) {
				foreach ($recruit_interviewdate_add as $recruit_interviewdate) $this->recruit_interviewdate_model->add($recruit_interviewdate);//新增面试日期
				foreach ($recruit_interviewdate_edit as $recruit_interviewdate) {//在原有面试日期上修改
					$recruit_date_id = $recruit_interviewdate['id'];
					unset($recruit_interviewdate['id']);
					$this->recruit_interviewdate_model->where(array('id' => $recruit_date_id))->save($recruit_interviewdate);
				}
				if ($recruit_interviewdate_del) {//本来有的面试日期，在编辑过程中删除
					$this->recruit_interviewdate_model->where("id in (".implode(',',$recruit_interviewdate_del).")")->delete();
				}
				
				foreach ($recruit_interviewtime_add as $recruit_interviewtime) $this->recruit_interviewtime_model->add($recruit_interviewtime);//新增面试时间
				foreach ($recruit_interviewtime_edit as $recruit_interviewtime) {//在原有面试时间上修改
					$recruit_time_id = $recruit_interviewtime['id'];
					unset($recruit_interviewtime['id']);
					$this->recruit_interviewtime_model->where(array('id' => $recruit_time_id))->save($recruit_interviewtime);
				}
				if ($recruit_interviewtime_del) {//本来有的面试时间，在编辑过程中删除
					$this->recruit_interviewtime_model->where("id in (".implode(',',$recruit_interviewtime_del).")")->delete();
				}
				//记录日志
				LogController::log_record($recruit_id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$recruit = $this->recruit_model->find($id);
			$this->assign($recruit);
			//面试日期
			$recruit_interviewdates = $this->recruit_interviewdate_model->where(array('recruit_id' => $id))->select();
			$this->assign('recruit_interviewdates',$recruit_interviewdates);
			//面试时间
			$recruit_interviewtimes = $this->recruit_interviewtime_model->where(array('recruit_id' => $id))->select();
			$this->assign('recruit_interviewtimes',$recruit_interviewtimes);
			//学季
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$term_year = $this->users_model->field('distinct term,year,term_status')->where($map)->order('year desc')->select();
			$term_year_html = " ";
			$term_year_arr = json_decode($recruit['term_year'],true);
			foreach ($term_year as $ty) {
				$term_year_str = trim($ty['year'])."-".trim($ty['term']);
				$term_year_html .= "<option";
				foreach ($term_year_arr as $tya) {
					if ($tya == $term_year_str) {
						$term_year_html .= " selected";
					}
				}
				$term_year_html .= " value='".$term_year_str."'>".$term_year_str."</option>";
			}
			$this->assign('term_year_html',$term_year_html);
			$this->display();
		}
	}
	//招聘信息批量打开关闭
	function toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['recruit_status'] = 0;
			if ( $this->recruit_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('HIDE_SUCCESS') );
			} else {
				$this->error( L('HIDE_FAILED') );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['recruit_status'] = 1;
			if ( $this->recruit_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('DISPLAY_SUCCESS') );
			} else {
				$this->error( L('DISPLAY_FAILED') );
			}
		}
	}
	//简历审核
	function exchange_status() {
		$admin_id = 779;//shen
		$recruit = $this->recruit_model->find($_GET['recruit_id']);
		$recruit_term_year = json_decode($recruit['term_year'],true);
		if ( isset( $_POST['ids'] ) && $_GET['check_pass'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['interview_status'] = 1;
			if ( $this->recruit_member_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$recruit_members = $this->recruit_member_model->where("id in ($ids)")->select();
				$subject = $recruit['recruit_name'];
				$content = '您的申请表已通过审核，请尽快预约面试<a href="http://ciee.ccdcsh.cn/index.php?g=&m=recruit&a=interview&id='.$recruit['id'].'">点击预约</a>';
				$content = htmlspecialchars_decode($content);
				foreach ($recruit_members as $recruit_member) $result=sp_send_email($recruit_member['email'], $subject, $content);
				$this->success( L('OPERATE_SUCCESS'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
			} else {
				$this->error( L('OPERATE_FAILED'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['check_nopass'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['interview_status'] = 2;
			if ( $this->recruit_member_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$recruit_members = $this->recruit_member_model->where("id in ($ids)")->select();
				$subject = $recruit['recruit_name'];
				$content = '非常感谢您对CIEE同屋项目感兴趣并提交申请表！但很遗憾，由于我们下学期申请中国同屋的CIEE学生有限，我们未能通过你的CIEE同屋申请。希望理解！<br>
开学初如有变动、仍需中国同屋，我们会联系你的。';
				$content = htmlspecialchars_decode($content);
				foreach ($recruit_members as $recruit_member) $result=sp_send_email($recruit_member['email'], $subject, $content);
				$this->success( L('OPERATE_SUCCESS'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
				} else {
				$this->error( L('OPERATE_FAILED'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['interview_pass'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['interview_status'] = 3;
			if ( $this->recruit_member_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//面试通过创建兼职教师账号
				$recruit_members = $this->recruit_member_model->where("id in ($ids)")->select();
				
				$subject = $recruit['recruit_name'];
				$content = "您的面试已通过";
				if ($recruit['training_time'] != 0) $content .= ", 培训时间 : ".date('Y-m-d H:i',strtotime($recruit['training_time']));
				if ($recruit['training_address']) $content .= ", 培训地址  : ".$recruit['training_address'];
				if ($recruit['training_content']) $content .= ", 培训内容 : ".$recruit['training_content'];
				$content = htmlspecialchars_decode($content);
				foreach ($recruit_members as $recruit_member) {
					foreach ($recruit_term_year as $rty) {
						$recruit_member_term = $this->recruit_member_term_model->where(array('term_year' => $rty,'recruit_member_id' => $recruit_member['id']))->find();
						if ($recruit_member_term) {
							continue;
						} else {
							$this->recruit_member_term_model->add(array('term_year' => $rty,'recruit_member_id' => $recruit_member['id']));
						}
					}
					$result=sp_send_email($recruit_member['email'], $subject, $content);
				}
				$this->success( L('OPERATE_SUCCESS'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
				} else {
				$this->error( L('OPERATE_FAILED'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['interview_nopass'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['interview_status'] = 4;
			if ( $this->recruit_member_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$recruit_members = $this->recruit_member_model->where("id in ($ids)")->select();
				$subject = $recruit['recruit_name'];
				$content = "非常感谢您对CIEE同屋项目感兴趣并前来参加同屋面试！但很遗憾，由于我们下学期申请中国同屋的CIEE学生有限，经过面试，我们未能通过你的CIEE同屋申请。希望理解！<br>
开学初如有变动、仍需中国同屋，我们会联系你的。";
				$content = htmlspecialchars_decode($content);
				foreach ($recruit_members as $recruit_member) $result=sp_send_email($emails, $subject, $content);
				$this->success( L('OPERATE_SUCCESS'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
			} else {
				$this->error( L('OPERATE_FAILED'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['interview_success'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			//面试通过创建兼职教师账号
			$recruit_members = $this->recruit_member_model->where("id in ($ids)")->select();
			foreach ($recruit_members as $recruit_member) {
				$user_data = array(
				 'user_login' => $recruit_member['phone'],
				 'user_pass' => sp_password( substr($recruit_member['phone'], -6) ),
				 'last_login_ip' => get_client_ip( 0, true ),
				 'create_time' => date( 'Y-m-d H:i:s' ),
				 'last_login_time' => date( 'Y-m-d H:i:s' ),
				 'user_status' => 1,//
				 'user_type' => 1,  //teacher
				 'teacher_join_time' => date( 'Y-m-d H:i:s' ),
				 'full_name' => $recruit_member['name'],
				 'user_email' => $recruit_member['email'],
				 //'smeta' => $recruit_member['smeta'],
				 'mobile' => $recruit_member['phone'],
				 'recruit_member_id' => $recruit_member['id']
				 );
				$user_id = $this->users_model->add($user_data);
				$this->role_user_model->add(array('role_id' => 13,'user_id' => $user_id)); 
			}
			$this->success( L('OPERATE_SUCCESS'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
		}
		if ( isset( $_POST['ids'] ) && $_GET['renew_pass'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$recruit_members = $this->recruit_member_model->where("id in ($ids)")->select();
			$subject = $recruit['recruit_name'];
			$content = '您的续住申请已通过';
			$content = htmlspecialchars_decode($content);
			foreach ($recruit_members as $recruit_member) {
				foreach ($recruit_term_year as $rty) {
					$recruit_member_term = $this->recruit_member_term_model->where(array('term_year' => $rty,'recruit_member_id' => $recruit_member['id']))->find();
					if ($recruit_member_term) {
						continue;
					} else {
						$this->recruit_member_term_model->add(array('term_year' => $rty,'recruit_member_id' => $recruit_member['id']));
					}
				}
				$result=sp_send_email($recruit_member['email'], $subject, $content);
			}
			$this->success( L('OPERATE_SUCCESS'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
		}
		if ( isset( $_POST['ids'] ) && $_GET['renew_nopass'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$recruit_members = $this->recruit_member_model->where("id in ($ids)")->select();
			$subject = $recruit['recruit_name'];
			$content = '非常感谢您对CIEE同屋项目感兴趣并提交续住申请表！但很遗憾，由于我们下学期申请中国同屋的CIEE学生有限，我们未能通过你的CIEE同屋申请。希望理解！<br>
开学初如有变动、仍需中国同屋，我们会联系你的。';
			$content = htmlspecialchars_decode($content);
			foreach ($recruit_members as $recruit_member) {
				foreach ($recruit_term_year as $rty) {
					$recruit_member_term = $this->recruit_member_term_model->where(array('term_year' => $rty,'recruit_member_id' => $recruit_member['id']))->find();
					if ($recruit_member_term) $this->recruit_member_term_model->where(array('term_year' => $rty,'recruit_member_id' => $recruit_member['id']))->delete();
				}
				$result=sp_send_email($recruit_member['email'], $subject, $content);
			}
			$this->success( L('OPERATE_SUCCESS'),U('recruit/apply_member',array('id' => $_GET['recruit_id'],'interview_status' => $_GET['interview_status'])) );
		}
	}
	
}