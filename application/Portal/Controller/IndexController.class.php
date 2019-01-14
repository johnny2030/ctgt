<?php

// +----------------------------------------------------------------------
// | CCDC
// +----------------------------------------------------------------------
// | Author: 11K <likun_19911227@163.com>
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 首页
 */
class IndexController extends HomebaseController {
	
	//首页
	public function index() {
		if(sp_is_user_login()){ //已经登录时直接跳到首页
			$this->display(":index");
		} else {
			redirect(U('user/login/index'));
		}
	}

}


