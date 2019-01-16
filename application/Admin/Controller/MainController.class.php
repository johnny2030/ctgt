<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MainController extends AdminbaseController {
	
    public function index(){
        redirect(U('admin/anchor/index'));
    }
}