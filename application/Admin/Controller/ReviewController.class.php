<?php

/*
 * 后台审核管理
 * 1xd
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ReviewController extends AdminbaseController {
	private $review_model;
	private $users_model;
	function _initialize() {
		parent::_initialize();
		$this->review_model = D( 'Review' );
		$this->users_model = D( 'Users' );
	}
	
	
	//审核列表
	function index() {
		$where = array();
		//审核事件搜索
		$review_title=I('request.review_title');
		$this->assign( 'review_title', $review_title );
		if ( !empty($review_title) ) {
			$where['review_title'] = array('like',"%$review_title%");
		}
		$count = $this->review_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->review_model
		->alias('s')
		->field('s.*,us.user_login')
		->join('__USERS__ us ON s.proposer_id=us.id')
		
		->where($where)->limit( $page->firstRow, $page->listRows )->order("s.review_createtime desc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	
	// 添加申请
	function add() {
		if (IS_POST) {
			$_POST['review_title'] = $_POST['review_title'];
			$_POST['proposer_id'] = sp_get_current_admin_id ();
			$_POST['review_createtime'] = date( 'Y-m-d H:i:s', time () );
			$_POST['review_msg'] = htmlspecialchars_decode($_POST['review_msg']);
			$result = $this->review_model->add ( $_POST );
			if ($result) {
				//记录日志
				LogController::log_record($result,1);
				$this->success ( L('ADD_SUCCESS') );
			} else {
				$this->error ( L('ADD_FAILED') );
			}
		} else {
			$this->display ();
		}
	}
	//编辑
	function review() {
		if ( IS_POST ) {
			$id = ( int ) $_POST['id'];
			$_POST['admin_id'] = sp_get_current_admin_id ();
			$_POST['review_msg'] = htmlspecialchars_decode($_POST['review_msg']);
			$_POST['review_createtime'] = date('Y-m-d H:i:s',time());
			$result = $this->review_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($result,2);
				$this->success(L('REVIEW_SUCCESS'));
			} else {
				$this->error(L('REVIEW_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$review = $this->review_model->find($id);
			$this->assign($review);
			$this->display();
		}
	}
	
	// 审核删除
	public function delete(){
		if(isset($_POST['ids'])){
	
		}else{
			$id = I("get.id",0,'intval');
			if ($this->review_model->delete($id)!==false) {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	
	}
	
	
}