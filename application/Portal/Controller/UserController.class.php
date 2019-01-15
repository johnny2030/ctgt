<?php

namespace Portal\Controller;
use Common\Controller\HomebaseController;

class UserController extends HomebaseController {

    private $common_user_model;
    private $common_order_model;
    private $common_stock_model;
    private $common_category_model;
    private $common_spec_model;
    private $common_suggest_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_order_model = D( 'Common_order' );
        $this->common_stock_model = D( 'Common_stock' );
        $this->common_spec_model = D( 'Common_spec' );
        $this->common_category_model = D( 'Common_category' );
        $this->common_suggest_model = D( 'Common_suggest' );

	}
    //个人中心首页
    public function index() {
        $id = session('login_id');

        $ym = date('Y/m',time());
        $day = date('d',time());
        $this->assign( 'ym', $ym );
        $this->assign( 'day', $day );

        $user = $this->common_user_model->find($id);
        $this->assign( 'user', $user );
        $this->display('/personal');
    }
    public function personal_edit(){
        if ( IS_POST ) {
            $id = (int)session('login_id');
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                R('User/index');
            } else {
                $this->error('保存失败！');
            }
        } else {
            $ym = date('Y/m',time());
            $day = date('d',time());
            $this->assign( 'ym', $ym );
            $this->assign( 'day', $day );

            $id = session('login_id');
            $user = $this->common_user_model->find($id);
            $this->assign( 'user', $user );
            $this->display('/personal_edit');
        }
    }
    public function order() {
        $id = session('login_id');

        $ym = date('Y/m',time());
        $day = date('d',time());
        $this->assign( 'ym', $ym );
        $this->assign( 'day', $day );

        $where = array();
        $where['payer'] = array('eq',$id);
        $where['del_flg'] = array('eq',0);
        $order_list = $this->common_order_model->where($where)->select();
        $this->assign( 'order_list', $order_list );
        $this->display('/order');
    }
    public function stock() {
        $id = session('login_id');

        $ym = date('Y/m',time());
        $day = date('d',time());
        $this->assign( 'ym', $ym );
        $this->assign( 'day', $day );

        $where = array();
        $where['k.user_id'] = array('eq',$id);
        $where['k.del_flg'] = array('eq',0);
        $stock_list = $this->common_stock_model->alias('k')->field('k.*,s.name as sname, c.name as cname')->join('__COMMON_SPEC__ s ON k.sid=s.id')->join('__COMMON_CATEGORY__ c ON k.cid=c.id')->where($where)->order("k.create_time desc")->select();
        $this->assign( 'stock_list', $stock_list );
        $this->display('/stock');
    }
    public function stock_apply(){
        $id = session('login_id');
        if ( IS_POST ) {
            $_POST['user_id'] = $id;
            $_POST['status'] = '申请备货';
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_stock_model->add($_POST);
            if ($result) {
                R('User/stock');
            } else {
                $this->error('添加失败！');
            }
        }else{
            $ym = date('Y/m',time());
            $day = date('d',time());
            $this->assign( 'ym', $ym );
            $this->assign( 'day', $day );

            $where = array();
            $where['user_id'] = array(['eq',0],['eq',$id],'or');
            $where['del_flg']=0;
            $spec = $this->common_spec_model->where($where)->order("create_time desc")->select();
            $category = $this->common_category_model->where($where)->order("create_time desc")->select();
            $this->assign( 'spec', $spec );
            $this->assign( 'category', $category );
            $this->display('/stock_apply');
        }
    }
    public function suggest(){
        $id = session('login_id');

        $ym = date('Y/m',time());
        $day = date('d',time());
        $this->assign( 'ym', $ym );
        $this->assign( 'day', $day );

        $where = array();
        $where['user_id'] = array('eq',$id);
        $where['del_flg'] = array('eq',0);
        $suggest_list = $this->common_suggest_model->where($where)->order("create_time desc")->select();
        $this->assign( 'suggest_list', $suggest_list );
        $this->display('/suggest');
    }
    public function suggest_add(){
        $id = session('login_id');
        if (empty($id)) $this->error('请先登录！');
        $_POST['user_id'] = $id;
        $_POST['status'] = '未处理';
        $_POST['create_time'] = date('Y-m-d H:i:s',time());
        $result = $this->common_suggest_model->add($_POST);
        if ($result) {
            R('User/suggest');
        } else {
            $this->error('添加失败！');
        }
    }

}