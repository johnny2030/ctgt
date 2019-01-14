<?php
/** 
 * 后台课程&学季
 * 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AddressController extends AdminbaseController {

	
	private $address_model;
	

	function _initialize() {
		parent::_initialize();
		$this->address_model = D( 'Address' );
		
	}
		
	function index() {
		$where = array();
		$where['address_status'] = array('neq',2,'and');
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
		if ( !empty($keyword) ) {
			$where['address_name'] = array('like',"%$keyword%");
			
		}
		
		$count = $this->address_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->address_model->where($where)->limit( $page->firstRow, $page->listRows )->order("listorder asc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
		}
		//添加地点
		function add() {
			if ( IS_POST ) {
				//$_POST['cid'] = (int)$_POST['cid'];
				$address_name = array();
				$_POST['userid'] = sp_get_current_admin_id();
				$address_id = $this->address_model->add($_POST);
				if (1 == 1) {
					//记录日志
					LogController::log_record($address_id,1);
		
					$this->success(L('ADD_SUCCESS'));
				} else {
					$this->error(L('ADD_FAILED'));
				}
			} else {
				
				$this->assign('address_html',$address_html);
				$this->display();
			}
		}
		
		//删除地点
		function delete() {
			if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
				$ids = implode( ',', $_POST['ids'] );
				$data['address_status'] = 2;
				if ( $this->address_model->where( "id in ($ids)" )->save( $data ) !== false ) {
					//记录日志
					LogController::log_record($ids,3);
		
					$this->success( L('DELETE_SUCCESS') );
				} else {
					$this->error( L('DELETE_FAILED') );
				}
			} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
				$object = $_GET['object'];
				$log_id = $_GET['id'];
				$data['address_status'] = 1;
				if ( $this->address_model->where( "id in ($object)" )->save( $data ) !== false ) {
					//更改日志状态
					LogController::modify_log_type($log_id, 4);
					$this->success( L('RESTORE_SUCCESS') );
				} else {
					$this->error( L('RESTORE_FAILED') );
				}
			} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
				$object = $_GET['object'];
				$log_id = $_GET['id'];
				if ( $this->address_model->where( "id in ($object)" )->delete() !== false ) {
					//更改日志状态
					LogController::modify_log_type($log_id, 5);
					$this->success( L('COMPLETE_DELETE_SUCCESS') );
				} else {
					$this->error( L('COMPLETE_DELETE_FAILED') );
				}
			} else {//单个逻辑删除
				$id = intval( I( 'get.id' ) );
				$data['address_status'] = 2;
				if ( $this->address_model->where(array('id' => $id))->save($data) !== false ) {
					//记录日志
					LogController::log_record($id,3);
					$this->success( L('DELETE_SUCCESS') );
				} else {
					$this->error( L('DELETE_FAILED') );
				}
			}
		
		
		}
		//编辑地点
		function edit() {
			if ( IS_POST ) {
				$address_id = (int)$_POST['id'];
					
				if (empty($_POST['address_name'])) $this->error('请填写地点名称');
				$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
				$_POST['smeta'] = json_encode($_POST['smeta']);
		
				$result = $this->address_model->where(array('id' => $address_id))->save($_POST);
				if ($result) {  
		
					//记录日志
					LogController::log_record($address_id,2);
		
					$this->success(L('EDIT_SUCCESS'));
				} else {
					$this->error(L('EDIT_FAILED'));
				}
			} else {
				$id = intval( I( 'get.id' ) );
				$address = $this->address_model->find($id);
				$this->assign($address);
					
				$this->display();
			}
		}
		
		//批量显示隐藏
		function toggle() {
			if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
				$ids = implode( ',',  $_POST['ids'] );
				$data['address_status'] = 1;
				if ( $this->address_model->where( "id in ($ids)" )->save( $data ) !== false ) {
					$this->success( L('DISPLAY_SUCCESS') );
				} else {
					$this->error( L('DISPLAY_FAILED') );
				}
			}
			if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
				$ids = implode( ',', $_POST['ids'] );
				$data['address_status'] = 0;
				if ( $this->address_model->where( "id in ($ids)" )->save( $data ) !== false ) {
					$this->success( L('HIDE_SUCCESS') );
				} else {
					$this->error( L('HIDE_FAILED') );
				}
			}
		}
		
	}