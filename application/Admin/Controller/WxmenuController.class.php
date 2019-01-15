<?php
/**
 * 微信菜单
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class WxmenuController extends AdminbaseController {

	private $wxmenu_model;
	
	function _initialize() {
		parent::_initialize();

		$this->wxmenu_model = D( 'Wxmenu' );
	}
	//微信菜单列表
	function index() {
		$where = array();
		//姓名
		$name=I('name');
		$this->assign( 'name', $name );
		if ( $name ) $where['name'] = array('like',"%$name%");

        $where['second'] = array('eq',0);
        $where['del_flg'] = array('eq',0);
		$count = $this->wxmenu_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->wxmenu_model->where($where)->limit( $page->firstRow, $page->listRows )->order("listorder is null,listorder asc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}

	//添加微信菜单
	function add() {
		if ( IS_POST ) {
            $where = array();
            $where['status'] = array('eq',0);
            $where['del_flg'] = array('eq',0);
		    if (empty($_POST['first'])){
                $first = $this->wxmenu_model->where($where)->max('first');
                if (empty($first)){
                    $_POST['first'] = 1;
                }else{
                    $_POST['first'] = $first+1;
                }
            }else{
                $where['first'] = array('eq',$_POST['first']);
                $second = $this->wxmenu_model->where($where)->max('second');
                if (empty($second)){
                    $_POST['second'] = 1;
                }else{
                    $_POST['second'] = $second+1;
                }
            }
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->wxmenu_model->add($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($result,1);
				$this->success('添加微信菜单成功！');
			} else {
				$this->error('添加微信菜单失败！');
			}
		} else {
			$this->display();
		}
	}
	//编辑微信菜单
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->wxmenu_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑微信菜单成功！');
			} else {
				$this->error('编辑微信菜单失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
            $wxmenu = $this->wxmenu_model->find($id);
			$this->assign($wxmenu);
			$this->display();
		}
	}
    //删除微信菜单
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $where = array();
            $where['status'] = array('eq',1);
            $where['id'] = array('in',$ids);
            $result = $this->wxmenu_model->where($where)->select();
            if(empty($result)){
                $data['del_flg'] = 1;
                $data['update_time'] = date('Y-m-d H:i:s',time());
                if ( $this->wxmenu_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                    //记录日志
                    LogController::log_record($ids,3);
                    $this->success('删除成功');
                } else {
                    $this->error('删除失败');
                }
            }else{
                $this->error('选择菜单中存在正在使用的菜单，请先停用！');
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->wxmenu_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->wxmenu_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $where = array();
            $where['status'] = array('eq',1);
            $where['id'] = array('eq',$id);
            $result = $this->wxmenu_model->where($where)->select();
            if(empty($result)){
                $data['del_flg'] = 1;
                $data['update_time'] = date('Y-m-d H:i:s',time());
                if ( $this->wxmenu_model->where(array('id' => $id))->save($data) !== false ) {
                    //记录日志
                    LogController::log_record($id,3);
                    $this->success('删除成功');
                } else {
                    $this->error('删除失败');
                }
            }else{
                $this->error('当前菜单正在使用中，请先停用！');
            }
        }
    }
    //微信菜单排序
    public function listorders() {
        $status = parent::_listorders( $this->wxmenu_model );
        if ( $status ) {
            $this->success('排序成功');
        } else {
            $this->error('排序失败');
        }
    }
    //微信菜单使用
    function toggle() {
        require_once 'today/class.today.php';
        require_once 'today/Wechat.php';
        $wechat = new \Wechat( $this );
        if ( isset( $_POST['ids'] )){
            /*$where = array();*/
            $status = $_GET['status'];
            $num = count($_POST['ids']);
            $ids = implode( ',',  $_POST['ids'] );
            //使用
            if ($status == 1){
                /*$where['status'] = array('eq',1);
                $where['second'] = array('eq',0);
                $where['del_flg'] = array('eq',0);
                $count = $this->wxmenu_model->where($where)->count();
                if($count == 3) $this->error('启用菜单已满，最多可以启用三个菜单！');*/
                if ($num>3) $this->error('最多可以启用三个菜单！');
                /*//判断是否已设置
                $where['id'] = array('in',$ids);
                $expired = $this->wxmenu_model->where($where)->count();
                if (!empty($expired)) $this->error('选择菜单已经启用，请选择其他菜单！');*/
                $menu_list = $this->wxmenu_model->where( "id in ($ids)" )->select();
                $data='{ "button":[ ';
                $count = count($menu_list);
                foreach ( $menu_list as $key => $item){
                    $swhere['first'] = $item['first'];
                    $swhere['second'] = array('neq',0);
                    $swhere['del_flg'] = array('eq',0);
                    $sub_list = $this->wxmenu_model->where($swhere)->select();
                    if($key+1 == $count){
                        if(empty($sub_list)){
                            $data .=  '{
                            "type":"view",  
                            "name":"'.$item['name'].'",  
                            "url":"'.$item['url'].'"  
                            }';
                        }else{
                            $data .=  '{  
                        "name":"'.$item['name'].'",  
                        "sub_button":[  ';
                            $sub_count = count($sub_list);
                            foreach ($sub_list as $sub_key => $sub){
                                if($sub_key+1 == $sub_count){
                                    $data .='
                            {      
                                "type":"view",  
                                "name":"'.$sub['name'].'",  
                                "url":"'.$sub['url'].'"  
                            }';
                                }else{
                                    $data .='
                            {      
                                "type":"view",  
                                "name":"'.$sub['name'].'",  
                                "url":"'.$sub['url'].'"  
                            },';
                                }
                                $ids .= ','.$sub['id'];
                            }
                            $data .=']  }';
                        }
                    }else{
                        if(empty($sub_list)){
                            $data .=  '{
                            "type":"view",  
                            "name":"'.$item['name'].'",  
                            "url":"'.$item['url'].'"  
                            },';
                        }else{
                            $data .=  '{  
                        "name":"'.$item['name'].'",  
                        "sub_button":[  ';
                            $sub_count = count($sub_list);
                            foreach ($sub_list as $sub_key => $sub){
                                if($sub_key+1 == $sub_count){
                                    $data .='
                            {      
                                "type":"view",  
                                "name":"'.$sub['name'].'",  
                                "url":"'.$sub['url'].'"  
                            }';
                                }else{
                                    $data .='
                            {      
                                "type":"view",  
                                "name":"'.$sub['name'].'",  
                                "url":"'.$sub['url'].'"  
                            },';
                                }
                                $ids .= ','.$sub['id'];
                            }
                            $data .=']  },';
                        }
                    }
                }
                $data .=' ]  }';
                $menuCreate = $wechat->menuCreate($data);
                $result = substr($menuCreate,11,1);
                if ($result == 0){
                    $menu_d['status'] = $status;
                    if ( $this->wxmenu_model->where( "id in ($ids)" )->save( $menu_d ) !== false ) {
                        $this->success('微信菜单启用成功');
                    } else {
                        $this->error('微信菜单启用失败');
                    }
                }else{
                    $this->error('微信菜单启用失败');
                }
            }else{
                $data['status'] = $status;
                if ( $this->wxmenu_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                    $this->success('微信菜单成功');
                } else {
                    $this->error('微信菜单失败');
                }
            }
        }
    }
    //微信子菜单
    function submenu() {
        $id = intval( I( 'get.id' ) );
        $wxmenu = $this->wxmenu_model->find($id);
        $where = array();
        $where['first'] = array('eq',$wxmenu['first']);
        $where['second'] = array('neq',0);
        $where['del_flg'] = array('eq',0);
        $list = $this->wxmenu_model->where($where)->order("listorder asc")->select();
        $this->assign( 'id', $id );
        $this->assign( 'list', $list );
        $this->display();
    }
    //添加微信子菜单
    function sub_add() {
        if ( IS_POST ) {
            $id = (int)$_POST['id'];
            $wxmenu = $this->wxmenu_model->find($id);
            $where = array();
            $where['first'] = array('eq',$wxmenu['first']);
            $where['second'] = array('neq',0);
            $where['status'] = array('eq',0);
            $where['del_flg'] = array('eq',0);
            $second = $this->wxmenu_model->where($where)->max('second');
            if (empty($second)){
                $data['second'] = 1;
            }else{
                $data['second'] = $second+1;
            }
            $data['first'] = $wxmenu['first'];
            $data['name'] = $_POST['name'];
            $data['url'] = $_POST['url'];
            $data['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->wxmenu_model->add($data);
            if ($result) {
                //记录日志
                LogController::log_record($result,1);
                $this->success('添加微信子菜单成功！');
            } else {
                $this->error('添加微信子菜单失败！');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $this->assign( 'id', $id );
            $this->display();
        }
    }
    //编辑微信子菜单
    function sub_edit() {
        if ( IS_POST ) {
            $id = (int)$_POST['id'];
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->wxmenu_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($id,2);
                $this->success('编辑微信菜单成功！');
            } else {
                $this->error('编辑微信菜单失败！');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $fid = intval( I( 'get.fid' ) );
            $wxmenu = $this->wxmenu_model->find($id);
            $this->assign('fid',$fid);
            $this->assign($wxmenu);
            $this->display();
        }
    }
}