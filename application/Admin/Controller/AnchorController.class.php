<?php
/**
 * 主播管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AnchorController extends AdminbaseController {

    private $common_anchor_model;
    private $common_agent_model;
    private $common_live_model;
	
	function _initialize() {
		parent::_initialize();

		$this->common_anchor_model = D( 'Common_anchor' );
        $this->common_agent_model = D( 'Common_agent' );
        $this->common_live_model = D( 'Common_live' );
	}
	//主播信息列表
	function index() {
        $role_id = session('role_id');
        $admin_id = session('ADMIN_ID');
        $room_id=I('room_id');
        $where = array();
        if ( $room_id ){
            $where['a.room_id'] = array('like',"%$room_id%");
            $this->assign( 'room_id', $room_id );
        }
	    if ($role_id == 2){
            $where['a.agent_id'] = array('eq',$admin_id);

        }
        $where['a.del_flg'] = array('eq',0);
		$count = $this->common_anchor_model->alias('a')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_anchor_model->alias('a')->field('a.*,g.name as gname')->join('__COMMON_AGENT__ g ON a.agent_id=g.login_id')->where($where)->limit( $page->firstRow, $page->listRows )->order("a.create_time desc")->select();
		$this->assign("page", $page->show('Admin'));
        $this->assign( 'role_id', $role_id );
		$this->assign( 'list', $list );
		$this->display();
	}

	//添加主播信息
	function add() {
		if ( IS_POST ) {
            $admin_id = session('ADMIN_ID');
            $role_id = session('role_id');
            if ($role_id == 2){
                $_POST['agent_id'] = $admin_id;
            }
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_anchor_model->add($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($result,1);
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！');
			}
		} else {
            $role_id = session('role_id');
            if ($role_id != 2){
                $list = $this->common_agent_model->alias('a')->field('a.name as name,a.login_id as login_id,u.user_login as user_login')->join('__USERS__ u ON u.id=a.login_id')->order("a.create_time desc")->select();
                $this->assign( 'list', $list );
            }
            $this->assign( 'role_id', $role_id );
			$this->display();
		}
	}
	//编辑主播信息
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_anchor_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
            $role_id = session('role_id');
			$anchor = $this->common_anchor_model->find($id);
			$this->assign($anchor);

            if ($role_id != 2){
                $list = $this->common_agent_model->alias('a')->field('a.name as name,a.login_id as login_id,u.user_login as user_login')->join('__USERS__ u ON u.id=a.login_id')->order("a.create_time desc")->select();
                $this->assign( 'list', $list );
            }
            $this->assign( 'role_id', $role_id );
			$this->display();
		}
	}
    //删除主播信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_anchor_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            if ( $this->common_anchor_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_anchor_model->where( "id in ($object)" )->delete() !== false ) {
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
            if ( $this->common_anchor_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
    //开播时长与业绩
    function live(){
        $role_id = session('role_id');
        $id = intval( I( 'get.id' ) );
        $start_time=I('start_time');
        $end_time=I('end_time');
        //主播信息
        $anchor = $this->common_anchor_model->find($id);
        $this->assign('anchor',$anchor);
        //时长与业绩
        $where = array();
        if ($start_time) $where['time'] = array('egt',$start_time);$this->assign('start_time',$start_time);
        if ($end_time) $where['time'] = array('elt',$end_time);$this->assign('end_time',$end_time);
        $where['user_id'] = array('eq',$id);
        $where['del_flg'] = array('eq',0);
        //总数
        $sumDay = $this->common_live_model->where($where)->sum('day');
        if (empty($sumDay)) $sumDay = 0;
        $sumHour = $this->common_live_model->where($where)->sum('hour');
        if (empty($sumHour)) $sumHour = 0;
        $sumMinute = $this->common_live_model->where($where)->sum('minute');
        if (empty($sumMinute)) $sumMinute = 0;
        $sumMoney = $this->common_live_model->where($where)->sum('money');
        if (empty($sumMoney)) $sumMoney = 0;
        $this->assign('sumDay',$sumDay);
        $this->assign('sumHour',$sumHour);
        $this->assign('sumMinute',$sumMinute);
        $this->assign('sumMoney',$sumMoney);

        $count = $this->common_live_model->where($where)->count();
        $page = $this->page($count, 10);
        $list = $this->common_live_model->where($where)->limit( $page->firstRow, $page->listRows )->order("time desc")->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
        $this->assign( 'role_id', $role_id );
        $this->display();
    }
    //添加时长与业绩
    function live_add() {
        if ( IS_POST ) {
            $_POST['time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_live_model->add($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($result,1);
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            //主播信息
            $anchor = $this->common_anchor_model->find($id);
            $this->assign('anchor',$anchor);
            $this->display();
        }
    }
    //编辑时长与业绩
    function live_edit() {
        if ( IS_POST ) {
            $_POST['time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_live_model->add($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($result,1);
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $anchor = $this->common_live_model->find($id);
            $this->assign($anchor);
            $this->display();
        }
    }
    //导入主播信息
    function upload() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/anchor/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['anchor_file']['savepath'].$info['anchor_file']['savename'];

            require_once 'today/excel/PHPExcel.php';
            require_once 'today/excel/PHPExcel/IOFactory.php';
            require_once 'today/excel/PHPExcel/Reader/Excel5.php';
            require_once 'today/excel/PHPExcel/Reader/Excel2007.php';

            $reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
            $obj = $reader->load( $file );
            $sheet = $obj->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $anchor_info_add = array();
            $time = date('Y-m-d H:i:s',time());
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $name = $sheet->getCell( 'A'.$i )->getValue();
                $phone = $sheet->getCell( 'B'.$i )->getValue();
                $platform = $sheet->getCell( 'C'.$i )->getValue();
                $room_id = $sheet->getCell( 'D'.$i )->getValue();
                $nickname = $sheet->getCell( 'E'.$i )->getValue();
                $address = $sheet->getCell( 'F'.$i )->getValue();
                $id_card = $sheet->getCell( 'G'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $anchor_info_add[] = array(
                    "name" => $name, "phone" => $phone, "platform" => $platform, "room_id" => $room_id, "nickname" => $nickname, "address" => $address, "id_card" => $id_card, "create_time" => $time
                );
            }
            foreach ($anchor_info_add as $table_anchor) {
                $this->common_anchor_model->add($table_anchor);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条记录', U( 'anchor/index' ) );
        } else {
            $this->display();
        }
    }
    //图片上传
/*$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
$_POST['smeta'] = json_encode($_POST['smeta']);

$_POST['expert_details'] = htmlspecialchars_decode($_POST['expert_details']);
$_POST['pubdate'] = date('Y-m-d H:i:s',time());*/
}