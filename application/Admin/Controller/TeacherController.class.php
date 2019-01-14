<?php
/** 
 * 后台教师管理
 * @author 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class TeacherController extends AdminbaseController {

	private $user_model;
	private $major_model;
	

	function _initialize() {
		parent::_initialize();

		$this->user_model = D( 'Users' );
		$this->major_model = D( 'TeacherMajor' );
	}
	
	//主导教师列表
	function index() {
		$where = array();
		
		//名称搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
		
		if ( !empty($keyword) ) {
			$where['first_name'] = array('like',"%$keyword%",'and');
			$where['last_name'] = array('like',"%$keyword%",'and');
			$where['_logic'] = 'or';
		}
		$where['ru.role_id'] = 3;
		$count = $this->user_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->user_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where($where)->order('u.id desc')->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//兼职教师列表
	function pt_teacher() {
		$where = array();
	
		//名称搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
	
		if ( !empty($keyword) ) {
			$where['full_name'] = array('like',"%$keyword%",'and');
		}
		$where['ru.role_id'] = 13;
		$count = $this->user_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->user_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where($where)->order('u.id desc')->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//离职
	function toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['user_status'] = 0;
			$data['teacher_leave_time'] = date('Y-m-d H:i:s',time());
			if ( $this->user_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('DIMISSION_SUCCESS') );
			} else {
				$this->error( L('DIMISSION_FAILED') );
			}
		}
	}
	
	//教师专业列表
	function major_list() {
		
		$where = array();
		$where['teacher_major_status'] = array('neq',2);
		$count = $this->major_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->major_model->where($where)->order(' listorder asc')->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加教师专业
	function major_add() {
		if ( IS_POST ) {
			if (empty($_POST['teacher_major_name'])) $this->error(L('TEACHER_MSG1'));
				
				
			$_POST['teacher_major_content'] = htmlspecialchars_decode($_POST['teacher_major_content']);
			$_POST['teacher_major_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
				
			$teacher_major_id = $this->major_model->add($_POST);
			if ($teacher_major_id) {
				//记录日志
				LogController::log_record($teacher_major_id,1);
	
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	//教师专业编辑
	function major_edit() {
		if(IS_POST) {
			$id = (int)$_POST['id'];
			if(empty($_POST['teacher_major_name'])) $this->error(L('TEACHER_MSG1'));
				
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['teacher_major_modify_time'] = date("Y-m-d H:i:s",time());
			$_POST['teacher_major_content'] = htmlspecialchars_decode($_POST['teacher_major_content']);
	
			$result=$this->major_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval(I('get.id'));
			$teacher_major = $this->major_model->where(array('id' => $id))->find();
			$this->assign($teacher_major);
			$this->display();
		}
	}
	//专业删除
	function major_delete() {
		if ( isset( $_POST['ids'] ) ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['teacher_major_status'] = 2;
			if ( $this->major_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['teacher_major_status'] = 1;
			if ( $this->major_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->major_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$data['teacher_major_status'] = 2;
			if ( $this->major_model->where(array('id' => $id))->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
	}
	//专业批量显示隐藏
	function major_toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['teacher_major_status'] = 1;
			if ( $this->major_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('DISPLAY_SUCCESS') );
			} else {
				$this->error( L('DISPLAY_FAILED') );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['teacher_major_status'] = 0;
			if ( $this->major_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('HIDE_SUCCESS') );
			} else {
				$this->error( L('HIDE_FAILED') );
			}
		}
	}
	//专业排序
	public function major_listorders() {
		$status = parent::_listorders( $this->major_model );
		if ( $status ) {
			$this->success( L('ORDER_UPDATE_SUCCESS') );
		} else {
			$this->error( L('ORDER_UPDATE_FAILED') );
		}
	}
	

}