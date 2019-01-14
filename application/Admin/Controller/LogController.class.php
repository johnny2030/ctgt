<?php
/**
 * 系统操作日志记录
 * 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class LogController extends AdminbaseController{
    
	private $log_model;
	private $menu_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->log_model = D("CommonActionLog");
		$this->menu_model = D("Menu");
	}
	
	// 日志列表
	public function index(){
		$keyword = $_POST['keyword'];
		$this->assign('keyword',$keyword);
		$where = array();
		if ($keyword) $where['u.user_login'] = array('like',"%$keyword%");
		
		$count = $this->log_model->alias('l')->join("__USERS__ u ON l.user=u.id")->join("__MENU__ m ON l.menuid=m.id")->where($where)->count();
		$page = $this->page($count,20);
		$logs = $this->log_model
				->alias('l')
				->field("l.*,u.user_login,m.name menu_name,m.app menu_app,m.model menu_model,m.action menu_action")
				->join("__USERS__ u ON l.user=u.id")->join("__MENU__ m ON l.menuid=m.id")
				->where($where)
				->order('l.last_time desc')
				->limit($page->firstRow,$page->listRows)
				->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign('logs',$logs);
		$this->display();
	}
	
	// 删除日志
	public function delete(){
		$ids = implode( ',', $_POST['ids'] );
		if ( $this->log_model->where( "id in ($ids)" )->delete() !== false ) {
			$this->success( L('DELETE_SUCCESS') );
		} else {
			$this->error( L('DELETE_FAILED') );
		}
	}
	/**
	 * 记录日志
	 * 开发者可以自由控制，对于没有必要做的检查可以不做，以减少服务器压力
	 * @param number $object 访问对象的id
	 * @param $type 日志类型1：新增，2：更新，3：删除
	 */
	public static function log_record($object="",$type) {
		$action=MODULE_NAME."-".CONTROLLER_NAME."-".ACTION_NAME;
		$menu = D('Menu')->where(array('app' => MODULE_NAME,'model' => CONTROLLER_NAME,'action' => ACTION_NAME))->find();
		$adminid=get_current_admin_id();
	
		$ip=get_client_ip(0,true);//修复ip获取
		$time=time();
	
		$data=array("user"=>$adminid,"action"=>$action,"object"=>$object,'ip' => $ip,'last_time' => $time,'type' => $type,'menuid' => $menu['id']);
		
		if (D('CommonActionLog')->add($data)) return true;
		else return false;
		
	}
	/**
	 * 更改状态
	 * @param $id 日志id
	 * @param $type 日志类型4：已恢复，5:已彻底删除
	 */
	public static function modify_log_type($id,$type) {
		if (D('CommonActionLog')->where(array('id' => $id))->save(array('type' => $type))) return true;
		else return false;
	}
	
}