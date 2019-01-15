<?php
/**
 * 货品管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class GoodsController extends AdminbaseController {

    private $common_category_model;
    private $common_spec_model;
    private $common_goods_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_spec_model = D( 'Common_spec' );
        $this->common_category_model = D( 'Common_category' );
        $this->common_goods_model = D( 'Common_goods' );
	}
	//货品信息列表
	function index() {
		$where = array();
		//货品名
		$name=I('name');
		$this->assign( 'name', $name );
		if ( $name ) $where['g.name'] = array('like',"%$name%");
        $where['g.del_flg'] = array('eq',0);
		$count = $this->common_goods_model->alias('g')->field('g.*,s.name as sname, c.name as cname')->join('__COMMON_SPEC__ s ON g.sid=s.id')->join('__COMMON_CATEGORY__ c ON g.cid=c.id')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_goods_model->alias('g')->field('g.*,s.name as sname, c.name as cname')->join('__COMMON_SPEC__ s ON g.sid=s.id')->join('__COMMON_CATEGORY__ c ON g.cid=c.id')->where($where)->limit( $page->firstRow, $page->listRows )->order("g.create_time desc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加
	function add() {
		if ( IS_POST ) {
            //图片上传
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $_POST['photo'] = json_encode($_POST['smeta']);
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_goods_model->add($_POST);
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
            $spec = $this->common_spec_model->where($where)->order("create_time desc")->select();
            $category = $this->common_category_model->where($where)->order("create_time desc")->select();
            $this->assign( 'spec', $spec );
            $this->assign( 'category', $category );
			$this->display();
		}
	}
	//编辑
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $_POST['photo'] = json_encode($_POST['smeta']);
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_goods_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$goods = $this->common_goods_model->find($id);
            $where = array();
            $where['del_flg'] = array('eq',0);
            $spec = $this->common_spec_model->where($where)->order("create_time desc")->select();
            $category = $this->common_category_model->where($where)->order("create_time desc")->select();
            $this->assign( 'spec', $spec );
            $this->assign( 'category', $category );
			$this->assign($goods);
			$this->display();
		}
	}
    //删除
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_goods_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            if ( $this->common_goods_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_goods_model->where( "id in ($object)" )->delete() !== false ) {
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
            if ( $this->common_goods_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
}