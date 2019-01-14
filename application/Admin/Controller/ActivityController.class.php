<?php
/**
 * @author Richard_Li
 * 活动管理
 * @date 2017年8月22日  上午10:37:28
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ActivityController extends AdminbaseController {

	private $activity_model;
	private $user_model;
	private $line_plan_model;
	private $vehicle_model;
	private $lineplan_vehicle_relationship_model;
	private $activity_classify_model;
	private $activity_student_relationship;
	private $activity_email_model;
	private $activity_time_model;
	private $activity_address_model;
	private $email_template_model;
	
	private $studentCols = array(
			array( 20, 'First Name', 'FFFFFF' ),
			array( 20, 'Last Name', 'FFFFFF' ),
			array( 20, 'Tendency', 'FFFFFF' )
	);

	function _initialize() {
		parent::_initialize();

		$this->activity_model = D( 'Activity' );
		$this->user_model = D( 'Users' );
		$this->line_plan_model = D( 'Line_plan' );
		$this->vehicle_model = D( 'Vehicle' );
		$this->lineplan_vehicle_relationship_model = D( 'Lineplan_vehicle_relationship' );
		$this->activity_classify_model = D( 'Activity_classify' );
		$this->activity_student_relationship = D( 'Activity_student_relationship' );
		$this->activity_email_model = D( 'Activity_email' );
		$this->email_template_model = D( 'EmailTemplate' );
		$this->activity_time_model = D( 'ActivityTime' );
		$this->activity_address_model = D( 'ActivityAddress' );
	}
	//活动列表
	function index() {
		$where = array();
		//活动名称搜索
		$activity_name=I('activity_name');
		$this->assign( 'activity_name', $activity_name );
		
		$classify_id = (int)$_REQUEST['classify_id'];
		$term_year_sess = $_REQUEST['term_year_sess'];
		
		$this->getSel($classify_id, $term_year_sess);
		
		if ( !empty($activity_name) ) $where['activity_name'] = array('like',"%$activity_name%",'and');
		if ($classify_id) $where['classify_id'] = $classify_id;
		if ($term_year_sess) $where['term_year_sess'] = $term_year_sess;
		$where['activity_status'] = array('neq',2);
		
		$count = $this->activity_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->activity_model->where($where)->order("listorder asc,activity_modify_time desc")->limit( $page->firstRow, $page->listRows )->select();
		
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	private function getSel($classify_id,$term_year_sess) {
		$classifies = $this->activity_classify_model->where(array('classify_status' => 0))->order('listorder asc,id desc')->select();
		$classify_html = " <option value=''>".L('ACTIVITY_CATEGORY')."</option>";
		foreach ($classifies as $classify) {
			$classify_html .= "<option";
			if($classify['id'] == $classify_id) {
				$classify_html .= " selected";
			}
			$classify_html .= " value='".$classify['id']."'>".$classify['classify_name']."</option>";
		}
		$this->assign('classify_html',$classify_html);
			
		$where = array();
		$map = array();
		$where[] = "term != '' AND term is not null";
		$where[] = "year != '' AND year is not null";
		$where[] = "session != '' AND session is not null";
		$where[] = "program != '' AND program is not null";
		$where['_logic'] = "OR";
		$map['_complex'] = $where;
		$map['user_type'] = 2;
		$map['term_status'] = 1;
		$terms = $this->user_model->field('distinct term,year,session,program')->where($map)->order('year desc')->select();
		$term_html = " ";
		foreach ($terms as $term) {
			$term_year_sess_str = trim($term['year'])."-".trim($term['term'])."-".trim($term['session'])."-".trim($term['program']);
			$term_html .= "<option";
			foreach ($term_year_sess as $tys) {
				if($term_year_sess_str == $tys) {
					$term_html .= " selected";
				}
			}
			$term_html .= " value='".$term_year_sess_str."'>".$term_year_sess_str."</option>";
		}
		$this->assign('term_html',$term_html);
	}
	//添加活动
	function add() {
		if ( IS_POST ) {
			if (empty($_POST['term_year_sess'])) $this->error(L('ACTIVITY_MSG0'));
			if (empty($_POST['classify_id'])) $this->error(L('ACTIVITY_MSG2'));
			if (empty($_POST['admin_id'])) $this->error(L('ACTIVITY_MSG1'));
			//if (empty($_POST['activity_start_time'])) $this->error(L('ACTIVITY_MSG3'));
			//if (empty($_POST['activity_address'])) $this->error(L('ACTIVITY_MSG5'));
			
			$activity_times = array();
			foreach ($_POST['activity_start_time'] as $k => $activity_start_time){
				$activity_start_time = $activity_start_time;
				$activity_end_time = $_POST['activity_end_time'][$k];
			
				if ($activity_start_time ) {
					$activity_times[] = array('activity_start_time' => $activity_start_time,'activity_end_time' => $activity_end_time);
				}
				if ($activity_end_time && $activity_end_time < $activity_start_time) {
					$this->error(L('ACTIVITY_MSG4'));
				}
			}
			$activity_addresses = array();
			foreach ($_POST['activity_address'] as $k => $activity_address){
				$activity_address = $activity_address;
				$remark = $_POST['remark'][$k];
				if ($activity_address) $activity_addresses[] = array('activity_address' => $activity_address,'remark' => $remark);
			}
			
			$credit_flg = $_POST['credit_flg'];
			$activity_credit = $_POST['activity_credit'];
			if ($credit_flg == '1'){
				if (empty($activity_credit) || $activity_credit == '0') $this->error(L('ACTIVITY_MSG6'));
			}
			$_POST['term_year_sess'] = json_encode($_POST['term_year_sess']);
			$_POST['activity_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['activity_content'] = htmlspecialchars_decode($_POST['activity_content']);
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['qrcode'] = sp_asset_relative_url($_POST['qrcode']);
			$_POST['qrcode'] = json_encode($_POST['qrcode']);
			$_POST['status'] = 0;
			
			$activity_id = $this->activity_model->add($_POST);
			if ($activity_id) {
				foreach ($activity_times as $activity_time) {
					$activity_time['activity_id'] = $activity_id;
					$this->activity_time_model->add($activity_time);
				}
				foreach ($activity_addresses as $activity_address) {
					$activity_address['activity_id'] = $activity_id;
					$this->activity_address_model->add($activity_address);
				}
				//记录日志
				LogController::log_record($activity_id,1);
				
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			//选择负责人
			$activity_list = $this->user_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where(array('ru.role_id' => 3))->select();
			$activity_html = "<option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($activity_list as $activity) {
				$activity_html .= "<option value='".$activity['id']."'>".$activity['first_name']." ".$activity['last_name']."</option>";
			}
			$this->assign('activity_html',$activity_html);
			//选择活动分类
			$classify_list = $this->activity_classify_model->where(array('classify_status' => 0))->select();
			$classify_html = " <option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($classify_list as $clfy) {
				$classify_html .= "<option value='".$clfy['id']."'>".$clfy['classify_name']."</option>";
			}
			$this->assign('classify_html',$classify_html);
			//选择学季
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where[] = "session != '' AND session is not null";
			$where[] = "program != '' AND program is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$terms = $this->user_model->field('distinct term,year,session,program')->where($map)->order('year desc')->select();
			$term_html = " ";
			foreach ($terms as $term) {
				$term_year_sess_str = trim($term['year'])."-".trim($term['term'])."-".trim($term['session'])."-".trim($term['program']);
				$term_html .= "<option value='".$term_year_sess_str."'>".$term_year_sess_str."</option>";
			}
			$this->assign('term_html',$term_html);
			$this->display();
		}
	}
	//活动删除
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['activity_status'] = 2;
			if ( $this->activity_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['activity_status'] = 1;
			if ( $this->activity_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->activity_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['activity_status'] = 2;
			if ( $this->activity_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	}
	//编辑活动
	function edit() {
		if ( IS_POST ) {
			$activity_id = (int)$_POST['id'];

			if (empty($_POST['admin_id'])) $this->error(L('ACTIVITY_MSG1'));
			if (empty($_POST['classify_id'])) $this->error(L('ACTIVITY_MSG2'));
			//if (empty($_POST['activity_start_time'])) $this->error(L('ACTIVITY_MSG3'));
			//if (empty($_POST['activity_address'])) $this->error(L('ACTIVITY_MSG5'));
			//活动时间
			$activity_times = $this->activity_time_model->where(array('activity_id' => $activity_id))->select();
			$activity_time_ids = array();
			foreach ($activity_times as $activity_time) $activity_time_ids[] = $activity_time['id'];
			
			$activity_time_add = array();
			$activity_time_edit = array();
			$activity_time_del = array();
			$activity_time_edit_id = array();
			foreach ($_POST['activity_start_time'] as $k => $activity_start_time){
				$activity_time_id = (int)$_POST['activity_time_id'][$k];
				$activity_start_time = $activity_start_time;
				$activity_end_time = $_POST['activity_end_time'][$k];
			
				if ($activity_start_time) {
					if (in_array($activity_time_id, $activity_time_ids)){
						//修改
						$activity_time_edit[] = array('id' => $activity_time_id,'activity_id' => $activity_id,'activity_start_time' => $activity_start_time,'activity_end_time' => $activity_end_time);
						$activity_time_edit_id[] = $activity_time_id;
					} else {
						//新增
						$activity_time_add[] = array('activity_id' => $activity_id,'activity_start_time' => $activity_start_time,'activity_end_time' => $activity_end_time);
					}
				}
				if ( $activity_end_time && $activity_end_time < $activity_start_time) {
					$this->error(L('ACTIVITY_MSG4'));
				}
			}
			foreach ($activity_time_ids as $activity_time_id) {
				if (!in_array($activity_time_id, $activity_time_edit_id)) $activity_time_del[] = $activity_time_id;
			}
			//活动地点
			$activity_addresses = $this->activity_address_model->where(array('activity_id' => $activity_id))->select();
			$activity_address_ids = array();
			foreach ($activity_addresses as $activity_address) $activity_address_ids[] = $activity_address['id'];
				
			$activity_address_add = array();
			$activity_address_edit = array();
			$activity_address_del = array();
			$activity_address_edit_id = array();
			foreach ($_POST['activity_address'] as $k => $activity_address){
				$activity_address_id = (int)$_POST['activity_address_id'][$k];
				$activity_address = $activity_address;
				$remark = $_POST['remark'][$k];
					
				if ($activity_address) {
					if (in_array($activity_address_id, $activity_address_ids)){
						//修改
						$activity_address_edit[] = array('id' => $activity_address_id,'activity_id' => $activity_id,'activity_address' => $activity_address,'remark' => $remark);
						$activity_address_edit_id[] = $activity_address_id;
					} else {
						//新增
						$activity_address_add[] = array('activity_id' => $activity_id,'activity_address' => $activity_address,'remark' => $remark);
					}
				}
			}
			foreach ($activity_address_ids as $activity_address_id) {
				if (!in_array($activity_address_id, $activity_address_edit_id)) $activity_address_del[] = $activity_address_id;
			}
				
			$credit_flg = $_POST['credit_flg'];
			$activity_credit = $_POST['activity_credit'];
			if ($credit_flg == '1'){
				if (empty($activity_credit) || $activity_credit == '0') $this->error(L('ACTIVITY_MSG6'));
			}
			$_POST['term_year_sess'] = json_encode($_POST['term_year_sess']);
			$_POST['activity_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['activity_content'] = htmlspecialchars_decode($_POST['activity_content']);
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['qrcode'] = sp_asset_relative_url($_POST['qrcode']);
			$_POST['qrcode'] = json_encode($_POST['qrcode']);
	
			$result = $this->activity_model->where(array('id' => $activity_id))->save($_POST);
			if ($result) {
				foreach ($activity_time_add as $activity_time) $this->activity_time_model->add($activity_time);//新增活动时间
				foreach ($activity_time_edit as $activity_time) {//在原有活动时间上修改
					$activity_time_id = $activity_time['id'];
					unset($activity_time['id']);
					$this->activity_time_model->where(array('id' => $activity_time_id))->save($activity_time);
				}
				if ($activity_time_del) {//本来有的活动时间，在编辑过程中删除
					$this->activity_time_model->where("id in (".implode(',',$activity_time_del).")")->delete();
				}
				foreach ($activity_address_add as $activity_address) $this->activity_address_model->add($activity_address);//新增活动地点
				foreach ($activity_address_edit as $activity_address) {//在原有活动地点上修改
					$activity_address_id = $activity_address['id'];
					unset($activity_address['id']);
					$this->activity_address_model->where(array('id' => $activity_address_id))->save($activity_address);
				}
				if ($activity_address_del) {//本来有的活动地点，在编辑过程中删除
					$this->activity_address_model->where("id in (".implode(',',$activity_address_del).")")->delete();
				}
				//记录日志
				LogController::log_record($activity_id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$activity = $this->activity_model->find($id);
			$this->assign($activity);
			$activity_times = $this->activity_time_model->where(array('activity_id' => $activity['id']))->select();
			$this->assign('activity_times',$activity_times);
			$activity_addresses = $this->activity_address_model->where(array('activity_id' => $activity['id']))->select();
			$this->assign('activity_addresses',$activity_addresses);
			//选择负责人
			$activity_list = $this->user_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where(array('ru.role_id' => 3))->select();
			$activity_html = " <option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($activity_list as $acty) {
				$activity_html .= "<option";
				if($acty['id'] == $activity['admin_id']) {
					$activity_html .= " selected";
				}
				$activity_html .= " value='".$acty['id']."'>".$acty['first_name']." ".$acty['last_name']."</option>";
			}
			$this->assign('activity_html',$activity_html);
			//选择活动分类
			$classify_list = $this->activity_classify_model->where(array('classify_status' => 0))->select();
			$classify_html = " <option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($classify_list as $clfy) {
				$classify_html .= "<option";
				if($clfy['id'] == $activity['classify_id']) {
					$classify_html .= " selected";
				}
				$classify_html .= " value='".$clfy['id']."'>".$clfy['classify_name']."</option>";
			}
			$this->assign('classify_html',$classify_html);
			//选择学季
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where[] = "session != '' AND session is not null";
			$where[] = "program != '' AND program is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$terms = $this->user_model->field('distinct term,year,session,program')->where($map)->order('year desc')->select();
			$term_year_sess_arr = json_decode($activity['term_year_sess'],true);
			$term_html = " ";
			foreach ($terms as $term) {
				$term_year_sess_str = trim($term['year'])."-".trim($term['term'])."-".trim($term['session'])."-".trim($term['program']);
				$term_html .= "<option";
				foreach ($term_year_sess_arr as $term_year_sess) {
					if($term_year_sess_str == $term_year_sess) {
						$term_html .= " selected";
					}
				}
				$term_html .= " value='".$term_year_sess_str."'>".$term_year_sess_str."</option>";
			}
			$this->assign('term_html',$term_html);
			$this->display();
		}
	}
	// 活动推荐
	public function recommend(){
		if(isset($_POST['ids']) && $_GET["recommend"]){
			$ids = I('post.ids/a');
			if ( $this->activity_model->where(array('id'=>array('in',$ids)))->save(array('activity_recommended'=>3))!==false) {
				$this->success(L('RECOMMEND_SUCCESS'));
			} else {
				$this->error(L('RECOMMEND_FAILED'));
			}
		}
		if(isset($_POST['ids']) && $_GET["unrecommend"]){
			$ids = I('post.ids/a');
			if ( $this->activity_model->where(array('id'=>array('in',$ids)))->save(array('activity_recommended'=>1))!==false) {
				$this->success(L('UNRECOMMEND_SUCCESS'));
			} else {
				$this->error(L('UNRECOMMEND_FAILED'));
			}
		}
	}
	//活动排序
	public function listorders() {
		$status = parent::_listorders( $this->activity_model );
		if ( $status ) {
			$this->success(L('ORDER_UPDATE_SUCCESS'));
		} else {
			$this->error(L('ORDER_UPDATE_FAILED'));
		}
	}
	//活动批量显示隐藏
	function toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['activity_status'] = 1;
			if ( $this->activity_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success(L('DISPLAY_SUCCESS'));
			} else {
				$this->error(L('DISPLAY_FAILED'));
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['activity_status'] = 0;
			if ( $this->activity_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success(L('HIDE_SUCCESS'));
			} else {
				$this->error(L('HIDE_FAILED'));
			}
		}
	}
	// 结束活动
	function end(){
		$id = intval( I( 'get.id' ) );
		$data['status'] = 1;
		$activity = $this->activity_model->find($id);
		$credit_flg = $activity['credit_flg'];
		if ( $this->activity_model->where(array('id' => $id))->save($data) !== false ) {
			if ($credit_flg == 0) {
				$datas['status'] = 1;
				if ( $this->activity_student_relationship->where(array('activity_id' => $id))->save($datas) !== false ) {
					//记录日志
					LogController::log_record($id,3);
					$this->success(L('END_SUCCESS'));
				} else {
					$this->error(L('END_FAILED'));
				}
			} else {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('END_SUCCESS'));
			}
		} else {
			$this->error(L('END_FAILED'));
		}
	}
	// 发放学分
	function grant(){
		if ( IS_POST ) {
			$activity_id = (int)$_POST['id'];
			$activity_credit = (int)$_POST['activity_credit'];
			if ( isset( $_POST['ids'] ) ) {
				$ids = implode( ',',  $_POST['ids'] );
				if ( $this->user_model->where( "id in ($ids)" )->setInc('score',$activity_credit) !== false ) {
					$data['status'] = 1;
					if ( $this->activity_student_relationship->where( "student_id in ($ids)" )->save($data) !== false ) {
						LogController::log_record($activity_id,3);
						$this->success(L('GRANT_SUCCESS'));
					}else {
						$this->error(L('GRANT_FAILED'));
					}
				} else {
					$this->error(L('GRANT_FAILED'));
				}
			}
		}else{
			$id = intval( I( 'get.id' ) );
			$data['status'] = 1;
			$activity = $this->activity_model->find($id);
			$credit_flg = $activity['credit_flg'];
			$list = $this->activity_student_relationship->alias('r')->field('u.id as uid, r.*, u.*')
			->join('__USERS__ u ON r.student_id=u.id')
			->where( "r.activity_id = $id and r.status = 0" )->select();
			$this->assign( 'activity', $activity );
			$this->assign( 'list', $list );
			$this->display();
		}
	}
	//线路规划
	function line_plan() {
		$id = intval( I( 'get.id' ) );
		$this->assign( 'aid', $id );
		$where = array();
		$where['del_flg'] = array('neq',1);
		$where['activity_id'] = $id;
		$count = $this->line_plan_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->line_plan_model->where($where)->limit( $page->firstRow, $page->listRows )->order("create_time asc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加线路规划
	function line_plan_add() {
		if ( IS_POST ) {
			$flg =true;
			$_POST['activity_id'] = (int)$_POST['activity_id'];
			$_POST['del_flg'] = 0;
			$_POST['create_time'] = date('Y-m-d H:i:s',time());
			if ( $_POST['vehicle_flg'] == 1 ){
				//汽车
				if ( $_POST['flg'] == 0 ){
					$line_plan_id = $this->line_plan_model->add($_POST);
					$activity = $this->activity_model->find($_POST['activity_id']);
					if ( isset( $_POST['ids'] ) ) {
						foreach ($_POST['ids'] as $id ){
							$data['line_plan_id'] = $line_plan_id;
							$data['vehicle_id'] = $id;
							$data['activity_id'] = $_POST['activity_id'];
							$data['vehicle_status'] = 2;
							$data['start_time'] = $activity['activity_start_time'];
							$data['end_time'] = $activity['activity_end_time'];
							$relationship_id = $this->lineplan_vehicle_relationship_model->add($data);
							if(!$relationship_id){
								$flg =false;
								exit;
							}
						}
					}
				}
				//火车
				if ( $_POST['flg'] == 1 ){
					if(!empty($_FILES['train']['tmp_name'])){
						$uploadConfig = array(
								'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
								'rootPath' => './'.C( 'UPLOADPATH' ),
								'savePath' => './excel/activity/train/',
								'saveName' => array( 'uniqid', '' ),
								'exts' => array( 'xls', 'xlsx' ),
								'autoSub' => false
						);
						$upload = new \Think\Upload( $uploadConfig );
						$info = $upload->upload();
						if($info) {
							$_POST['train'] = $info['train']['savepath'].$info['train']['savename'];
							$_POST['train_name'] = $_FILES['train']['name'];
							$line_plan_id = $this->line_plan_model->add($_POST);
						}else{
							$this->error(L('UPLOAD_FAILED'));
						}
					} else {
						$line_plan_id = $this->line_plan_model->add($_POST);
					}
				}
				//飞机
				if ( $_POST['flg'] == 2 ){
					if(!empty($_FILES['airplan']['tmp_name'])){
						$uploadConfig = array(
								'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
								'rootPath' => './'.C( 'UPLOADPATH' ),
								'savePath' => './excel/activity/airplan/',
								'saveName' => array( 'uniqid', '' ),
								'exts' => array( 'xls', 'xlsx' ),
								'autoSub' => false
						);
						$upload = new \Think\Upload( $uploadConfig );
						$info = $upload->upload();
						if($info) {
							$_POST['airplan'] = $info['airplan']['savepath'].$info['airplan']['savename'];
							$_POST['airplan_name'] = $_FILES['airplan']['name'];
							$line_plan_id = $this->line_plan_model->add($_POST);
						}else{
							$this->error(L('UPLOAD_FAILED'));
						}
					} else {
						$line_plan_id = $this->line_plan_model->add($_POST);
					}
				}
			}else{
				$line_plan_id = $this->line_plan_model->add($_POST);
			}
			if ($line_plan_id && $flg) {
				//记录日志
				LogController::log_record($_POST['activity_id'],1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$this->assign( 'aid', $id );
			$activity = $this->activity_model->find($id);
			$people_limit = $activity['people_limit'];
			$this->assign( 'people_limit', $people_limit );
			
			$sizeList = $this->vehicle_model->query("select distinct car_size from ".C('DB_PREFIX')."vehicle where id not in(select vehicle_id from cmf_lineplan_vehicle_relationship where vehicle_status = 2) and car_type=0 and del_flg=0 order by car_size asc");
			$this->assign( 'sizeList', $sizeList );
			$vehicleList = $this->vehicle_model->query("select * from ".C('DB_PREFIX')."vehicle where id not in(select vehicle_id from cmf_lineplan_vehicle_relationship where vehicle_status = 2) and car_type=0 and del_flg=0 order by car_size asc");
			$this->assign( 'vehicleList', $vehicleList );
			
			$this->display();
		}
	}
	//删除线路规划
	function line_plan_delete() {
		
		if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['del_flg'] = 0;
			if ( $this->line_plan_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->line_plan_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['del_flg'] = 1;
			if ( $this->line_plan_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	}
	//编辑线路规划
	function line_plan_edit() {
		if ( IS_POST ) {
			$lid = (int)$_POST['id'];
			$linePlan = $this->line_plan_model->find($lid);
			$data['vehicle_status'] = 1;
			$this->lineplan_vehicle_relationship_model->where( "line_plan_id = ($lid)" )->save( $data );
			$flg =true;
			if ( $_POST['vehicle_flg'] == 1 ) {
				if ( $_POST['flg'] == 0 ) {
					$data['vehicle_flg'] = 1;
					$data['flg'] = 0;
					$this->line_plan_model->where(array('id' => $lid))->save($data);
					if(isset( $_POST['ids'] )){
						foreach ($_POST['ids'] as $vid ){
							if ( $this->lineplan_vehicle_relationship_model->where( "vehicle_id = ($vid) and line_plan_id = ($lid)" )->select() !== false ) {
								$data['vehicle_status'] = 2;
								if ( $this->lineplan_vehicle_relationship_model->where( "vehicle_id = ($vid) and line_plan_id = ($lid)" )->save($data) === false ) {
									$flg =false;
									exit;
								}
							} else {
								$data['line_plan_id'] = $lid;
								$data['vehicle_id'] = $vid;
								$data['activity_id'] = $linePlan['activity_id'];
								$data['vehicle_status'] = 2;
								$relationship_id = $this->lineplan_vehicle_relationship_model->add($data);
								if(!$relationship_id){
									$flg =false;
									exit;
								}
							}
						}
					}
				}
				if ( $_POST['flg'] == 1 ) {
					$data['vehicle_flg'] = 1;
					$data['flg'] = 1;
					if(!empty($_FILES['train']['tmp_name'])){
						$uploadConfig = array(
								'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
								'rootPath' => './'.C( 'UPLOADPATH' ),
								'savePath' => './excel/activity/train/',
								'saveName' => array( 'uniqid', '' ),
								'exts' => array( 'xls', 'xlsx' ),
								'autoSub' => false
						);
						$upload = new \Think\Upload( $uploadConfig );
						$info = $upload->upload();
						if($info) {
							$data['train'] = $info['train']['savepath'].$info['train']['savename'];
							$data['train_name'] = $_FILES['train']['name'];
						}else{
							$this->error(L('UPLOAD_FAILED'));
						}
					}
					$this->line_plan_model->where(array('id' => $lid))->save($data);
				} 
				if ( $_POST['flg'] == 2 ) {
					$data['vehicle_flg'] = 1;
					$data['flg'] = 2;
					if(!empty($_FILES['airplan']['tmp_name'])){
						$uploadConfig = array(
								'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
								'rootPath' => './'.C( 'UPLOADPATH' ),
								'savePath' => './excel/activity/airplan/',
								'saveName' => array( 'uniqid', '' ),
								'exts' => array( 'xls', 'xlsx' ),
								'autoSub' => false
						);
						$upload = new \Think\Upload( $uploadConfig );
						$info = $upload->upload();
						if($info) {
							$data['airplan'] = $info['airplan']['savepath'].$info['airplan']['savename'];
							$data['airplan_name'] = $_FILES['airplan']['name'];
						}else{
							$this->error(L('UPLOAD_FAILED'));
						}
					}
					$this->line_plan_model->where(array('id' => $lid))->save($data);
				}
			} else {
				$data['vehicle_flg'] = 0;
				$this->line_plan_model->where(array('id' => $lid))->save($data);
			}
			if ($flg) {
				//记录日志
				LogController::log_record($lid,1);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$aid = $_GET['aid'];
			$this->assign( 'aid', $aid );
			$activity = $this->activity_model->find($aid);
			$this->assign( 'activity', $activity );
			
			$id = $_GET['id'];
			$this->assign( 'id', $id );
			$line_plan = $this->line_plan_model->find($id );
			$this->assign( 'line_plan', $line_plan );
			
			$sizeListSelected = $this->vehicle_model->query("select distinct car_size from ".C('DB_PREFIX')."vehicle where id in(select vehicle_id from cmf_lineplan_vehicle_relationship where vehicle_status = 2 and line_plan_id = ".$id." and activity_id = ".$aid.") and car_type=0 and del_flg=0 order by car_size asc");
			$this->assign( 'sizeListSelected', $sizeListSelected );
			$vehicleListSelected = $this->vehicle_model->query("select * from ".C('DB_PREFIX')."vehicle where id in(select vehicle_id from cmf_lineplan_vehicle_relationship where vehicle_status = 2 and line_plan_id = ".$id." and activity_id = ".$aid.") and car_type=0 and del_flg=0 order by car_size asc");
			$this->assign( 'vehicleListSelected', $vehicleListSelected );
			
			$sizeList = $this->vehicle_model->query("select distinct car_size from ".C('DB_PREFIX')."vehicle where id not in(select vehicle_id from cmf_lineplan_vehicle_relationship where vehicle_status = 2) and car_type=0 and del_flg=0 order by car_size asc");
			$this->assign( 'sizeList', $sizeList );
			$vehicleList = $this->vehicle_model->query("select * from ".C('DB_PREFIX')."vehicle where id not in(select vehicle_id from cmf_lineplan_vehicle_relationship where vehicle_status = 2) and car_type=0 and del_flg=0 order by car_size asc");
			$this->assign( 'vehicleList', $vehicleList );
			
			$this->display();
		}
	}
	//活动分类列表
	function classify() {
		$where = array();
		//分类名称搜索
		$classify_name=I('classify_name');
		$this->assign( 'classify_name', $classify_name );
		if ( !empty($classify_name) ) {
			$where['classify_name'] = array('like',"%$classify_name%",'and');
		}
		$where['classify_status'] = array('neq',1);
		$count = $this->activity_classify_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->activity_classify_model->where($where)->limit( $page->firstRow, $page->listRows )->order("listorder asc")->select();
	
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加活动分类
	function classify_add() {
		if ( IS_POST ) {
			$_POST['classify_status'] = 0;
			$_POST['classify_content'] = htmlspecialchars_decode($_POST['classify_content']);
				
			$classify_id = $this->activity_classify_model->add($_POST);
			if ($classify_id) {
				//记录日志
				LogController::log_record($classify_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	//活动分类删除
	function classify_delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['classify_status'] = 1;
			if ( $this->activity_classify_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['classify_status'] = 0;
			if ( $this->activity_classify_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->activity_classify_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['classify_status'] = 1;
			if ( $this->activity_classify_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	}
	//编辑活动分类
	function classify_edit() {
		if ( IS_POST ) {
			$classify_id = (int)$_POST['id'];
	
			$_POST['classify_content'] = htmlspecialchars_decode($_POST['classify_content']);
	
			$result = $this->activity_classify_model->where(array('id' => $classify_id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($classify_id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$activity = $this->activity_classify_model->find($id);
			$this->assign($activity);
			$this->display();
		}
	}
	//分类排序
	public function classify_listorders() {
		$status = parent::_listorders( $this->activity_classify_model );
		if ( $status ) {
			$this->success(L('ORDER_UPDATE_SUCCESS'));
		} else {
			$this->error(L('ORDER_UPDATE_FAILED'));
		}
	}
	//某学生已报名活动列表
	function activity_signup_list() {
	
		$student_id = (int)I('get.student_id');
		$student = $this->user_model->where(array('id' => $student_id))->find();
	
		$student_activities = $this->activity_model
								->alias('a')
								->field('a.*,ac.classify_name,u.full_name')
								->join('__ACTIVITY_CLASSIFY__ ac ON ac.id=a.classify_id')
								->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.activity_id=a.id')
								->join('__USERS__ u ON u.id=a.admin_id')
								->where(array('asr.student_id' => $student_id,'a.status' => 0))
								->select();
		//已结束
		$old_student_activities = $this->activity_model
									->alias('a')
									->field('a.*,ac.classify_name,u.full_name')
									->join('__ACTIVITY_CLASSIFY__ ac ON ac.id=a.classify_id')
									->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.activity_id=a.id')
									->join('__USERS__ u ON u.id=a.admin_id')
									->where(array('asr.student_id' => $student_id,'a.status' => 1))
									->select();
	
		$this->assign( 'student', $student );
		$this->assign( 'student_activities', $student_activities );
		$this->assign( 'old_student_activities', $old_student_activities );
		$this->display();
	}
	//某活动已报名学生列表
	function allot_student_list() {
	
		$id = (int)I('get.id');
	
		$activity_students = $this->user_model
								->alias('u')
								->field('u.id,u.avatar,u.first_name,u.last_name,asr.activity_tendency')
								->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.student_id=u.id')
								->where(array('asr.activity_id' => $id))
								->order('u.first_name asc,u.last_name asc')
								->select();
	
		$activity = $this->activity_model->find($id);
		
		$this->assign( 'activity_students', $activity_students );
		$this->assign( $activity );
		$this->display();
	}
	//导出报名此活动的学生名单
	function student_export() {
	
		$id = (int)$_GET['id'];
	
		$activity = $this->activity_model->find($id);
		$students = $this->user_model->alias('u')->field('u.id,u.first_name,u.last_name,asr.activity_tendency')->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.student_id=u.id')->where(array('asr.activity_id' => $id))->order('u.first_name asc,u.last_name asc')->select();
	
		//导出
		set_time_limit(0);
	
		$xls_file_name = $activity['activity_name']."_Students_".date('Y-m-d',time());
		require_once 'today/excel/PHPExcel.php';
		$excel = new \PHPExcel();
		$excel->getProperties()->setCreator( 'CIEE' )
		->setLastModifiedBy( 'CIEE' )
		->setTitle( $xls_file_name );
		$sheet = $excel->setActiveSheetIndex( 0 );
		$sheet->getDefaultRowDimension()->setRowHeight( 15 );
	
		$rowIndex = 1;  //行
		foreach ( $students as $student ) {
			$colIndex = -1;//列
			$rowIndex++;
			$activity_tendency = json_decode($student['activity_tendency'],true);
			$tendency_str = "";
			foreach ($activity_tendency as $k => $at) {
				$i = $k+1;
				$tendency_str .= $i." : ".$at." ";
			}
			$colIndex++;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $student['first_name'] );  //姓名
			$colIndex++;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $student['last_name'] );
			$colIndex++;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $tendency_str );
	
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(array('font' => array( 'size' => 10,'name' => 'Arial' )));
		}
	
		//表头
		$colIndex = -1;
		$cols = 'studentCols';
		foreach ( $this->$cols as $col ) {
			$colIndex++;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1', $col[1] );
			$sheet->getColumnDimension( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ) )->setWidth( $col[0] );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->getStartColor()->setARGB( 'FF'.$col[2] );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getAlignment()->setWrapText( true );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->applyFromArray(
					array(
							'font' => array( 'bold' => true, 'size' => 10, 'name' => 'Arial' ),
							'alignment' => array( 'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER )
					)
					);
		}
		$sheet->getRowDimension(1)->setRowHeight( 18 );
	
		//$sheet->getStyle( 'B' )->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_TEXT );
	
		$sheet->setTitle( 'sheet1' );
		header( 'Content-Type: application/vnd.ms-excel' );
		header( "Content-Disposition: attachment;filename=$xls_file_name.xls" );
		header( 'Cache-Control: max-age=0' );
	
		$excelWriter = \PHPExcel_IOFactory::createWriter( $excel, 'Excel5' );
		$excelWriter->save('php://output');
	
		exit;
	}
    //邮件模板列表
    function email() {
        $where = array();
        //模板名搜索
        $name=I('name');
        $this->assign( 'name', $name );
        if ( !empty($name) ) {
            $where['name'] = array('like',"%$name%",'and');
        }
        $where['del_flg'] = array('neq',1);
        $count = $this->activity_email_model->where($where)->count();
        $page = $this->page($count, 20);
        $list = $this->activity_email_model->where($where)->limit( $page->firstRow, $page->listRows )->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
        $this->display();
    }
    //邮件模板添加
    function email_add() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './word/activity/email/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'doc', 'docx', 'dot', 'dotx', 'docm', 'dotm' ),
                'autoSub' => false
            );
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            if($info) {
                $_POST['address'] = $info['address']['savepath'].$info['address']['savename'];
                $_POST['name'] = $_FILES['address']['name'];
            }else{
                $this->error(L('UPLOAD_FAILED'));
            }
            $_POST['createtime'] = date('Y-m-d H:i:s',time());
            $email_id = $this->activity_email_model->add($_POST);
            if ($email_id) {
                //记录日志
                LogController::log_record($email_id,1);

                $this->success(L('ADD_SUCCESS'));
            } else {
                $this->error(L('ADD_FAILED'));
            }
        } else {
            $this->display();
        }
    }
    //邮件模板删除
    function email_delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            if ( $this->activity_email_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,3);

                $this->success(L('DELETE_SUCCESS'));
            } else {
                $this->error(L('DELETE_FAILED'));
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            if ( $this->activity_email_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //更改日志状态
                LogController::modify_log_type($log_id, 4);
                $this->success(L('RESTORE_SUCCESS'));
            } else {
                $this->error(L('RESTORE_FAILED'));
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->activity_email_model->where( "id in ($object)" )->delete() !== false ) {
                //更改日志状态
                LogController::modify_log_type($log_id, 5);
                $this->success(L('COMPLETE_DELETE_SUCCESS'));
            } else {
                $this->error(L('COMPLETE_DELETE_FAILED'));
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            if ( $this->activity_email_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success(L('DELETE_SUCCESS'));
            } else {
                $this->error(L('DELETE_FAILED'));
            }
        }
    }
    //邮件模板启用/停用
    function email_use() {
        $id = (int)$_GET['id'];
        $data['status'] = $_GET['status'];
        $data['updatetime'] = date('Y-m-d H:i:s',time());
        $result = $this->activity_email_model->where(array('id' => $id))->save($data);
        if ( $result ) {
            $this->success( L('ENABLED_SUCCESS') );
        } else {
            $this->error( L('ENABLED_FAILED') );
        }
    }
    //活动信息导入
    function ac_upload() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/activity/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['up_activity']['savepath'].$info['up_activity']['savename'];

            require_once 'today/excel/PHPExcel.php';
            require_once 'today/excel/PHPExcel/IOFactory.php';
            require_once 'today/excel/PHPExcel/Reader/Excel5.php';
            require_once 'today/excel/PHPExcel/Reader/Excel2007.php';

            $reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
            $obj = $reader->load( $file );

            $sheet = $obj->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $colCount = $sheet->getHighestColumn();
            $realRowCount = 0;
            $importCount = 0;

            $check = true;
            $where = array();
            $where['user_type'] = array('eq',2);
            $where['user_status'] = array('neq',0);
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $first_name = $sheet->getCell( 'A'.$i )->getValue();
                if(empty($first_name)){
                    $this->error('第'.$i.'行,first_name 不能为空');
                    $check = false;
                    break;
                }
                $last_name = $sheet->getCell( 'B'.$i )->getValue();
                if(empty($last_name)){
                    $this->error('第'.$i.'行,last_name 不能为空');
                    $check = false;
                    break;
                }
                $where['first_name'] = $first_name;
                $where['last_name'] = $last_name;
                $user = $this->user_model->field('id')->where($where)->find();
                $uid = $user['id'];
                if(empty($uid)){
                    $this->error('第'.$i.'行,找不到学生'.$first_name.$last_name);
                    $check = false;
                    break;
                }
                //关联活动1
                $af_name = $sheet->getCell( 'C'.$i )->getValue();
                $af = $this->activity_model->field('id')->where(array('activity_name' => $af_name))->find();
                $af_id = $af['id'];
                if(empty($af_id)){
                    $this->error('第'.$i.'行,找不到活动'.$af_name);
                    $check = false;
                    break;
                }
                //关联活动2
                $as_name = $sheet->getCell( 'D'.$i )->getValue();
                $as = $this->activity_model->field('id')->where(array('activity_name' => $as_name))->find();
                $as_id = $as['id'];
                if(empty($as_id)){
                    $this->error('第'.$i.'行,找不到活动'.$as_name);
                    $check = false;
                    break;
                }

                $realRowCount++;
                $importCount++;
                $info_add[] = array(
                    'activity_id' => $af_id, 'student_id' => $uid
                );
                $info_add[] = array(
                    'activity_id' => $as_id, 'student_id' => $uid
                );
            }
            if($check){
                foreach ($info_add as $asr_info) {
                    $this->activity_student_relationship->add($asr_info);
                }
                @unlink( $file );
                $this->success( '成功导入'.$importCount.'条活动信息记录', U( 'activity/index' ) );
            }
        } else {
            $this->display();
        }
    }
    //选择邮件模板
    function email_model() {
	    $email_id = $_POST['email_id'];
        $search = array();
        $search['id'] = $email_id;
        $email = $this->email_template_model->where($search)->find();
        $where = array();
        $where['email_status'] = 1;
        $where['id'] = array('neq',$email_id);;
        $list = $this->email_template_model->where($where)->select();
        $email_html = "<option value='".$email['id']."'>".$email['email_title']."</option>";
        foreach ($list as $eml) {
            $email_html .= "<option value='".$eml['id']."'>".$eml['email_title']."</option>";
        }
        $activity_id = (int)$_POST['activity_id'];
        $activity = $this->activity_model->find($activity_id);
        $activity_times = $this->activity_time_model->where(array('activity_id' => $activity_id))->order('activity_start_time asc')->select();
        $activity_addresses = $this->activity_address_model->where(array('activity_id' => $activity_id))->select();
        $activity_time_arr = array();
        $activity_address_arr = array();
        foreach ($activity_times as $activity_time) $activity_time_arr[] = $activity_time['activity_start_time'];
        foreach ($activity_addresses as $activity_address) $activity_address_arr[] = $activity_address['activity_address'];

        $ctt = str_replace("#activity_name#",$activity['activity_name'],$email['email_content']);
        $ctt = str_replace("#time#",implode(',', $activity_time_arr),$ctt);
        $ctt = str_replace("#place#",implode(',', $activity_address_arr),$ctt);
        $data['ttl'] =$email['email_title'];
        $data['ctt'] =htmlspecialchars_decode($ctt);
        $data['email_html'] =$email_html;
        $this->ajaxReturn($data);
    }
}