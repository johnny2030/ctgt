<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AdController extends AdminbaseController{

    private $common_ad_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->common_ad_model = D("Common_ad");
	}
	
	// 广告列表
	public function index(){
        $where = array();
        $where['status'] = array('eq',0);
        $where['del_flg'] = array('eq',0);
        $count = $this->common_ad_model->where($where)->count();
        $page = $this->page($count, 20);
        $list = $this->common_ad_model->where($where)->limit( $page->firstRow, $page->listRows )->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
        $this->display();
	}
	
	// 广告添加
	public function add(){
        if ( IS_POST ) {
            //图片上传
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $_POST['photo'] = json_encode($_POST['smeta']);
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_ad_model->add($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($result,1);
                $this->success('添加广告成功！');
            } else {
                $this->error('添加广告失败！');
            }
        } else {
            $this->display();
        }
    }
	// 广告编辑
	public function edit(){
        if ( IS_POST ) {
            $id = (int)$_POST['id'];
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $_POST['photo'] = json_encode($_POST['smeta']);
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_ad_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($id,2);
                $this->success('编辑广告成功！');
            } else {
                $this->error('编辑广告失败！');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $ad = $this->common_ad_model->find($id);
            $this->assign($ad);
            $this->display();
        }
    }
	// 广告删除
	public function delete(){
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_ad_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            if ( $this->common_ad_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_ad_model->where( "id in ($object)" )->delete() !== false ) {
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
            if ( $this->common_ad_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }

    //广告使用
    function recommend() {
        $where = array();
        if(isset($_POST['ids'])){
            $ids = implode( ',', $_POST['ids'] );
            $recommend = intval( I( 'get.recommend' ) );
            if($recommend == 1){
                //判断是否设置已满
                $where['recommend'] = array('eq',1);
                $existed = $this->common_ad_model->where($where)->count();
                if ($existed>=3){
                    $this->error('广告位已满，只能设置三个广告位');
                }
                $check = 3-$existed;
                if (count($_POST['ids'])>$check) $this->error('您只能再设置'.$check.'个广告位');
                //判断是否已设置
                $where['id'] = array('in',$ids);
                $expired = $this->common_ad_model->where($where)->count();
                if (!empty($expired)){
                    $this->error('该广告已使用，请选择其他广告');
                }
            }
            $data['recommend'] = $recommend;
            if ( $this->common_ad_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,2);
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $recommend = intval( I( 'get.recommend' ) );
            if($recommend == 1){
                //判断是否设置已满
                $where['recommend'] = array('eq',1);
                $existed = $this->common_ad_model->where($where)->count();
                if ($existed>=3){
                    $this->error('广告位已满，只能设置三个广告位');
                }
                //判断是否已设置
                $where['id'] = array('eq',$id);
                $expired = $this->common_ad_model->where($where)->count();
                if (!empty($expired)){
                    $this->error('该广告已使用，请选择其他广告');
                }
            }
            $data['recommend'] = $recommend;
            if ( $this->common_ad_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,2);
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
    }
	
}