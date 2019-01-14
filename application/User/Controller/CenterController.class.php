<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class CenterController extends MemberbaseController {
	
	private $users_model;
	
	function _initialize(){
		parent::_initialize();
		$this->users_model = D('Users');
	}
	
	// 会员中心首页
	public function index() {
		$user_id = (int)I('get.id');
		if ($user_id) {
			$users = $this->users_model->find($user_id);
			$this->assign($users);
		} else {
			$this->check_login();
			$this->check_user();
			$this->assign($this->user);
		}
		$this->assign('user_id',$user_id);
		$this->display(':center');
	}
}
