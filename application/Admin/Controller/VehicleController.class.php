<?php
/**
 * @author Richard_Li
 * 车库管理
 * @date 2017年9月6日  上午11:10:28
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class VehicleController extends AdminbaseController {

	private $vehicle_model;

	function _initialize() {
		parent::_initialize();
		$this->vehicle_model = D( 'Vehicle' );
	}
	//车库列表
	function index() {
		$where = array();
		//车牌号码搜索
		$car_number=I('car_number');
		$this->assign( 'car_number', $car_number );
		if ( !empty($car_number) ) {
			$where['car_number'] = array('like',"%$car_number%",'and');
		}
		$where['del_flg'] = array('neq',1);
		$count = $this->vehicle_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->vehicle_model->where($where)->limit( $page->firstRow, $page->listRows )->order("create_time desc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加车辆
	function add() {
		if ( IS_POST ) {
			$_POST['car_size'] = (int)$_POST['car_size'];
			$_POST['del_flg'] = 0;
			$_POST['create_time'] = date('Y-m-d H:i:s',time());
			$vehicle_id = $this->vehicle_model->add($_POST);
			if ($vehicle_id) {
				//记录日志
				LogController::log_record($vehicle_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	//删除车辆
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['del_flg'] = 1;
			if ( $this->vehicle_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['del_flg'] = 0;
			if ( $this->vehicle_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->vehicle_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['del_flg'] = 1;
			if ( $this->vehicle_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
	}
	//编辑车辆
	function edit() {
		if ( IS_POST ) {
			$_POST['car_size'] = (int)$_POST['car_size'];
			$vehicle_id = (int)$_POST['id'];
			$result = $this->vehicle_model->where(array('id' => $vehicle_id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($vehicle_id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$vehicle = $this->vehicle_model->find($id);
			$this->assign($vehicle);
			$this->display();
		}
	}
	//车辆启用/停运
	function toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['start'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['car_type'] = 0;
			if ( $this->vehicle_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('ENABLED_SUCCESS') );
			} else {
				$this->error( L('ENABLED_FAILED') );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['stop'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['car_type'] = 1;
			if ( $this->vehicle_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('OFFSTREAM_SUCCESS') );
			} else {
				$this->error( L('OFFSTREAM_FAILED') );
			}
		}
	}
}