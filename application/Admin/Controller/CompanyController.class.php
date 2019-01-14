<?php
/** 
 * 后台管理系统之实习公司管理
 * 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class CompanyController extends AdminbaseController {

	private $company_model;

	function _initialize() {
		parent::_initialize();

		$this->company_model = D( 'Company' );
	}
	//实习公司列表
	function index() {
		
		$where = array();
		//实习公司名称搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
		
		if ( !empty($keyword) ) {
			$where['company_name'] = array('like',"%$keyword%",'and');
		}
		$where['company_status'] = array('neq',2);
		$count = $this->company_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->company_model->where($where)->limit( $page->firstRow, $page->listRows )->order("listorder asc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加实习公司
	function add() {
		if ( IS_POST ) {
			if (empty($_POST['company_name'])) $this->error('请填写实习公司名称');
			
			$_POST['company_content'] = htmlspecialchars_decode($_POST['company_content']);
			$_POST['company_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			
			
			$company_id = $this->company_model->add($_POST);
			if ($company_id) {
				
				//记录日志
				LogController::log_record($company_id,1);
				
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！');
			}
		} else {
			$this->display();
		}
	}
	//编辑实习公司
	function edit() {
		if ( IS_POST ) {
			$company_id = (int)$_POST['id'];
			
			if (empty($_POST['company_name'])) $this->error('请填写实习公司名称');
			
			
			$_POST['company_content'] = htmlspecialchars_decode($_POST['company_content']);
			$_POST['company_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
				
			$result = $this->company_model->where(array('id' => $company_id))->save($_POST);
			if ($result) {
				
				//记录日志
				LogController::log_record($assessment_id,2);
				
				$this->success('修改成功！');
			} else {
				$this->error('修改失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$company = $this->company_model->find($id);
			$this->assign($company);
			
			$this->display();
		}
	}
	
	//删除实习公司
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['company_status'] = 2;
			if ( $this->company_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
				
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['company_status'] = 1;
			if ( $this->company_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( '恢复成功！' );
			} else {
				$this->error( '恢复失败！' );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->company_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( '彻底删除成功！' );
			} else {
				$this->error( '彻底删除失败！' );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['company_status'] = 2;
			if ( $this->company_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		}
		
		
	}
	//实习公司批量显示隐藏
	function toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['company_status'] = 1;
			if ( $this->company_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( '显示成功！' );
			} else {
				$this->error( '显示失败！' );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['company_status'] = 0;
			if ( $this->company_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( '隐藏成功！' );
			} else {
				$this->error( '隐藏失败！' );
			}
		}
	}
	
	//实习公司排序
	public function listorders() {
		$status = parent::_listorders( $this->company_model );
		if ( $status ) {
			$this->success( '排序更新成功！' );
		} else {
			$this->error( '排序更新失败！' );
		}
	}
	

}