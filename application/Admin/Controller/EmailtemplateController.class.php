<?php
/** 
 * 后台管理系统之邮件模板
 * 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class EmailtemplateController extends AdminbaseController {

	private $email_template_model;
	private $email_conf_model;

	function _initialize() {
		parent::_initialize();

		$this->email_template_model = D( 'EmailTemplate' );
		$this->email_conf_model = D( 'EmailConf' );
	}
	//邮件模板列表
	function index() {
		
		$where = array();
		//邮件标题搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
		
		if ( !empty($keyword) ) {
			$where['email_title'] = array('like',"%$keyword%");
		}
		$where['email_status'] = array('neq',2);
		$count = $this->email_template_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->email_template_model->where($where)->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加邮件模板
	function add() {
		if ( IS_POST ) {
			if (empty($_POST['email_title'])) $this->error(L('EMAIL_TITLE_NOT_NULL'));
			if (empty($_POST['email_content'])) $this->error(L('EMAIL_CONTENT_NOT_NULL'));
			
			$_POST['email_content'] = htmlspecialchars_decode($_POST['email_content']);
			
			$email_template_id = $this->email_template_model->add($_POST);
			if ($email_template_id) {
				
				//记录日志
				LogController::log_record($email_template_id,1);
				
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	//编辑邮件模板
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			
			if (empty($_POST['email_title'])) $this->error(L('EMAIL_TITLE_NOT_NULL'));
			if (empty($_POST['email_content'])) $this->error(L('EMAIL_CONTENT_NOT_NULL'));
			
			$_POST['email_content'] = htmlspecialchars_decode($_POST['email_content']);
			unset($_POST['id']);
			
			$result = $this->email_template_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$email_template = $this->email_template_model->find($id);
			$this->assign($email_template);
			
			$this->display();
		}
	}
	//邮件配置信息
	function email_conf() {
		$admin_id = sp_get_current_admin_id();
		
		if ( IS_POST ) {
			if (empty($_POST['email_sender'])) $this->error(L('EMAIL_SENDER_NOT_NULL'));
			if (empty($_POST['email_address'])) $this->error(L('EMAIL_ADDRESS_NOT_NULL'));
			if (empty($_POST['email_smtp'])) $this->error(L('EMAIL_SMTP_NOT_NULL'));
			if (empty($_POST['email_smtp_port'])) $this->error(L('EMAIL_SMTP_PORT_NOT_NULL'));
			if (empty($_POST['email_login_name'])) $this->error(L('EMAIL_LOGIN_NAME_NOT_NULL'));
			if (empty($_POST['email_password'])) $this->error(L('EMAIL_PASSWORD_NOT_NULL'));
			
			$_POST['admin_id'] = $admin_id;
			$email_conf = $this->email_conf_model->where(array('admin_id' => $admin_id))->find();
			if ($email_conf) {
				$result = $this->email_conf_model->where(array('admin_id' => $admin_id))->save($_POST);
			} else {
				$result = $this->email_conf_model->add($_POST);
			}
			if ($result) {
	
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$email_conf = $this->email_conf_model->where(array('admin_id' => $admin_id))->find();
			$this->assign($email_conf);
			$this->display();
		}
	}
	//邮件模板删除
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['email_status'] = 2;
			if ( $this->email_template_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
				
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['email_status'] = 1;
			if ( $this->email_template_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->email_template_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['email_status'] = 2;
			if ( $this->email_template_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
		
		
	}
	//邮件模板批量显示隐藏
	function toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['email_status'] = 1;
			if ( $this->email_template_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('DISPLAY_SUCCESS') );
			} else {
				$this->error( L('DISPLAY_FAILED') );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['email_status'] = 0;
			if ( $this->email_template_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('HIDE_SUCCESS') );
			} else {
				$this->error( L('HIDE_FAILED') );
			}
		}
	}
	

}