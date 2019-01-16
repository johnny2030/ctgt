<?php
/**
 * 经纪人管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AgentController extends AdminbaseController {

    private $common_agent_model;
    private $role_user_model;
    private $users_model;

	function _initialize() {
		parent::_initialize();

        $this->common_agent_model = D( 'Common_agent' );
        $this->role_user_model = D( 'Role_user' );
        $this->users_model = D( 'Users' );

	}
	//经纪人信息列表
	function index() {
        $name=I('name');
        $where = array();
        if ( $name ){
            $where['a.name'] = array('like',"%$name%");
            $this->assign( 'name', $name );
        }
        $where['a.del_flg'] = array('eq',0);
        $count = $this->common_agent_model->alias('a')->where($where)->count();
        $page = $this->page($count, 20);
        $list = $this->common_agent_model->alias('a')->field('a.*,u.user_login as user_login')->join('__USERS__ u ON u.id=a.login_id','left')->limit( $page->firstRow, $page->listRows )->order("a.create_time desc")->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
        $this->display();
	}

	//添加经纪人信息
	function add() {
		if ( IS_POST ) {
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_agent_model->add($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($result,1);
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！');
			}
		} else {
		    //获取所有关联登录账号
            $where = array();
            $where['del_flg'] = array('eq',0);
            $logins = $this->common_agent_model->field('login_id')->where($where)->select();
            $where = array();
            $where['r.role_id'] = array('eq',2);
            if ($logins){
                $login = array();
                foreach ($logins as $k => $v) {
                    array_push($login,$v['login_id']);
                }
                $where['r.user_id'] = array('not in',$login);
            }
            $list = $this->role_user_model->alias('r')->field('r.*,u.user_login as user_login')->join('__USERS__ u ON u.id=r.user_id')->where($where)->order("u.create_time desc")->select();
            $this->assign( 'list', $list );
            $this->display();
		}
	}
	//编辑经纪人信息
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_agent_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
            $where = array();
            $where['a.id'] = array('eq',$id);
			$agent = $this->common_agent_model->alias('a')->field('a.*,u.user_login as user_login')->join('__USERS__ u ON u.id=a.login_id','left')->where($where)->find();
			$this->assign($agent);

            //获取所有关联登录账号
            $where = array();
            $where['del_flg'] = array('eq',0);
            $logins = $this->common_agent_model->field('login_id')->where($where)->select();
            $where = array();
            $where['r.role_id'] = array('eq',2);
            if ($logins){
                $login = array();
                foreach ($logins as $k => $v) {
                    array_push($login,$v['login_id']);
                }
                $where['r.user_id'] = array('not in',$login);
            }
            $list = $this->role_user_model->alias('r')->field('r.*,u.user_login as user_login')->join('__USERS__ u ON u.id=r.user_id')->where($where)->order("u.create_time desc")->select();
            $this->assign( 'list', $list );
			$this->display();
		}
	}
    //删除经纪人信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_agent_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            if ( $this->common_agent_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_agent_model->where( "id in ($object)" )->delete() !== false ) {
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
            if ( $this->common_agent_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
}