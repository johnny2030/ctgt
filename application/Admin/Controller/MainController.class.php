<?php
/**
 *首页
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MainController extends AdminbaseController {
	
	function _initialize() {
		parent::_initialize();
	}
	//多语言切换
	public function lang() {
		if (IS_AJAX){
			//$configs = include 'application/Common/Conf/config.php';
			$lang = I('l');
			$configs['DEFAULT_LANG'] = $lang;
			sp_set_dynamic_config($configs);
			$this->ajaxReturn();
		}
	}
	//首页信息
	public function index() {
		redirect(U('admin/student/index'));
	}
	
}