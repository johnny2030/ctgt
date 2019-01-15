<?php
/**
 * 订单管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class OrderController extends AdminbaseController {

    private $common_category_model;
    private $common_spec_model;
    private $common_goods_model;
    private $common_user_model;
    private $common_order_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_category_model = D( 'Common_category' );
        $this->common_spec_model = D( 'Common_spec' );
        $this->common_goods_model = D( 'Common_goods' );
        $this->common_user_model = D( 'Common_user' );
        $this->common_order_model = D( 'Common_order' );
	}
	//订单信息列表
	function index() {
		$where = array();
		$name=I('name');
        $pay_id=I('pay_id');
        if ( $pay_id ) {
            $where['o.pay_id'] = array('eq',$pay_id);
            $this->assign( 'pay_id', $pay_id );
        }
        if ( $name ){
            $where['cu.company_name|cu.name'] = array('like',"%$name%");
            $this->assign( 'name', $name );
        }
        $where['o.del_flg'] = array('eq',0);
        $count = $this->common_order_model->alias('o')->field('o.*,g.name as gname,cu.name as cuname,cu.company_name as company_name,s.name as sname,c.name as cname')->join('__COMMON_GOODS__ g ON o.gid=g.id')->join('__COMMON_USER__ cu ON o.payer=cu.id')->join('__COMMON_SPEC__ s ON o.sid=s.id')->join('__COMMON_CATEGORY__ c ON o.cid=c.id')->where($where)->count();
        $page = $this->page($count, 20);
        $list = $this->common_order_model->alias('o')->field('o.*,g.name as gname,cu.name as cuname,cu.company_name as company_name,s.name as sname,c.name as cname')->join('__COMMON_GOODS__ g ON o.gid=g.id')->join('__COMMON_USER__ cu ON o.payer=cu.id')->join('__COMMON_SPEC__ s ON o.sid=s.id')->join('__COMMON_CATEGORY__ c ON o.cid=c.id')->where($where)->limit( $page->firstRow, $page->listRows )->order("o.create_time desc")->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
		$this->display();
	}
    //添加
    function add() {
        if ( IS_POST ) {
            if (!empty($_POST['payer1'])){
                $_POST['payer'] = $_POST['payer1'];
            }
            if (empty($_POST['payer'])&&empty($_POST['payer1'])&&!empty($_POST['name'])){
                $data = array();
                $data['name'] = $_POST['name'];
                $data['phone'] = $_POST['phone'];
                if (!empty($_POST['company_name'])){
                    $data['company_name'] = $_POST['company_name'];
                    $data['company_phone'] = $_POST['company_phone'];
                }
                $data['create_time'] = date('Y-m-d H:i:s',time());
                $result = $this->common_user_model->add($data);
                $_POST['payer'] = $result;
            }
            $_POST['pay_id'] = $this->salt('8');
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_order_model->add($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($result,1);
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $where = array();
            $where['del_flg'] = array('eq',0);
            $goods = $this->common_goods_model->where($where)->order("create_time desc")->select();
            $spec = $this->common_spec_model->where($where)->order("create_time desc")->select();
            $category = $this->common_category_model->where($where)->order("create_time desc")->select();
            $where['company_name'] = array('neq','');
            $company = $this->common_user_model->where($where)->order("create_time desc")->select();
            $where = array();
            $where['del_flg'] = array('eq',0);
            $where['company_name'] = array('eq','');
            $customer = $this->common_user_model->where($where)->order("create_time desc")->select();
            $this->assign( 'company', $company );
            $this->assign( 'customer', $customer );
            $this->assign( 'goods', $goods );
            $this->assign( 'spec', $spec );
            $this->assign( 'category', $category );
            $this->display();
        }
    }
    //删除订单信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_order_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_order_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_order_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_order_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
    /**
     * 随机取出字符串
     * @param  int $strlen 字符串位数
     * @return string
     */
    public function salt($strlen){
        $str  = "1234567890";
        $salt = '';
        $_len = strlen($str)-1;
        for ($i = 0; $i < $strlen; $i++) {
            $salt .= $str[mt_rand(0,$_len)];
        }
        return $salt;
    }
}