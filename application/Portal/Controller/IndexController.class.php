<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController; 

class IndexController extends HomebaseController {

    private $common_user_model;

    public function _initialize() {
        parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
    }

    //首页
	public function index() {
        $login_id = session('login_id');
        $login_user = session('login_user');
        $this->assign( 'login_id', $login_id );
        $this->assign( 'login_user', $login_user );
        $this->display("/index");
    }
    //登录
    public function login(){
        if ( IS_POST ) {
            $where = array();
            $where['user'] = $_POST['user'];
            $where['password'] = $_POST['password'];
            $result = $this->common_user_model->where($where)->find();
            if ($result) {
                session('login_id',$result['id']);
                session('login_user',$result['user']);
                R('Index/index');
            } else {
                $this->error('登录失败！');
            }
        } else {
            $login_id = session('login_id');
            if (empty($login_id)){
                $this->display('/index');
            }else{
                R('Index/index');
            }
        }
    }
    //注册
    public function register(){
        if ( IS_POST ) {
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->add($_POST);
            if ($result) {
                session('login_id',$result);
                session('login_user',$_POST['user']);
                R('Index/index');
            } else {
                $this->error('注册失败！');
            }
        } else {
            $login_id = session('login_id');
            if (empty($login_id)){
                $this->display('/index');
            }else{
                R('Index/index');
            }
        }
    }

}


