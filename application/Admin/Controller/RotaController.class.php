<?php
/**
 * @author Richard_Li
* 值班管理
* @date 2018年2月01日  上午09:37:28
*/
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class RotaController extends AdminbaseController {

	private $rota_model;
	private $user_model;
	private $rota_hour_model;
    private $recruit_member;

	function _initialize() {
		parent::_initialize();
		$this->rota_model = D( 'Rota' );
		$this->user_model = D( 'Users' );
		$this->rota_hour_model = D( 'Rota_hour' );
        $this->recruit_member = D( 'Recruit_member' );
	}
	
	/**
	 * 值班表信息
	 */
	public function index(){
		//搜索条件/翻页
		$year=I('year');//年
		$this->assign( 'year', $year );
		$month=I('month');//月
		$this->assign( 'month', $month );
		if (empty($year)||(empty($month)&&$month!=0)) {
			$year = isset($_GET["y"])?$_GET["y"]:date("Y");
			$month = isset($_GET["m"])?$_GET["m"]:date("m");
		}
		if ($month>12){//处理出现月份大于12的情况
			$month=1;
			$year++;
		}
		if ($month<1){//处理出现月份小于1的情况
			$month=12;
			$year--;
		}
		$days = date("t",mktime(0,0,0,$month,1,$year));//得到给定的月份应有的天数
		$dayofweek = date("w",mktime(0,0,0,$month,1,$year));//得到给定的月份的 1号 是星期几
		$dayofend = date("w",mktime(0,0,0,$month,$days,$year));//得到给定的月份的最后一天 是星期几
		$currentDate = $year.'年'.$month.'月份';//当前得到的日期信息
		//输出表头
		$table="<table class='table table-bordered table-list'><thead><tr align='center'><th colspan='7'>".$currentDate."</th></tr></thead>";
		$table .="<tbody><tr>";
		$table .="<td style='color:red'>星期日</td>";
		$table .="<td>星期一</td>";
		$table .="<td>星期二</td>";
		$table .="<td>星期三</td>";
		$table .="<td>星期四</td>";
		$table .="<td>星期五</td>";
		$table .="<td style='color:red'>星期六</td>";
		$table .="</tr>";
		//生成表格
		$nums=$dayofweek+1;
		for ($i=1;$i<=$dayofweek;$i++){//输出1号之前的空白日期
			$table.="<td> </td>";
		}
		for ($i=1;$i<=$days;$i++){
            $checkTime = strtotime($year."-".$month."-".$i);
            //取出值班表信息
            $where = array();
            $where['r.del_flg'] = 0;
            $where['r.date'] = array('eq',$checkTime);
            $list = $this->rota_model->alias('r')->field('r.id as rid, h.id as hid, u.id as uid, u.name as name, h.start_hour as start_hour, h.end_hour as end_hour, r.date as date')->join('__ROTA_HOUR__ h ON r.hour_id=h.id')->join('__RECRUIT_MEMBER__ u ON r.user_id=u.id')->where($where)->order("r.hour_id asc")->select();
		    //输出值班表信息
			$table.="<td style='text-align:center;valign:middle;'><font size='5'>".$i."</font><table style='width: 100%;'><tbody>";
			if (empty($list)){
				$table.="<tr><td class='tdtime'>8:30&nbsp;&nbsp;-&nbsp;&nbsp;12:30</td>
						<td class='tdpeople'>
						<a style='text-decoration:none' href='/index.php?g=Admin&m=Rota&a=add&hour_id=1&year=".$year."&month=".$month."&day=".$i."'>
						<font size='3' color='#FF6347'>未分配</font></a></td></tr>";
				$table.="<tr><td class='tdtime'>12:30&nbsp;&nbsp;-&nbsp;&nbsp;16:30</td>
						<td class='tdpeople'>
						<a style='text-decoration:none' href='/index.php?g=Admin&m=Rota&a=add&hour_id=2&year=".$year."&month=".$month."&day=".$i."'>
						<font size='3' color='#FF6347'>未分配</font></a></td></tr>";
			}else {
			    if (count($list,COUNT_NORMAL)<2) {
                    foreach ($list as $rota ){
                        if ($rota['hid'] == 1){
                            $table.="<tr><td class='tdtime'>8:30&nbsp;&nbsp;-&nbsp;&nbsp;12:30</td>
						            <td class='tdpeople'>
						            <a style='text-decoration:none' href='javascript:void(0);' onclick='promptBox(".$rota['rid'].")' data-toggle='modal' data-target='#myModal'>
						            <font size='2' color='#1E90FF'>".$rota['name']."</font></button></td></tr>";
                            $table.="<tr><td class='tdtime'>12:30&nbsp;&nbsp;-&nbsp;&nbsp;16:30</td>
						            <td class='tdpeople'>
						            <a style='text-decoration:none' href='/index.php?g=Admin&m=Rota&a=add&hour_id=2&year=".$year."&month=".$month."&day=".$i."'>
						            <font size='3' color='#FF6347'>未分配</font></a></td></tr>";

                        }elseif ($rota['hid'] == 2){
                            $table.="<tr><td class='tdtime'>8:30&nbsp;&nbsp;-&nbsp;&nbsp;12:30</td>
						            <td class='tdpeople'>
						            <a style='text-decoration:none' href='/index.php?g=Admin&m=Rota&a=add&hour_id=1&year=".$year."&month=".$month."&day=".$i."'>
						            <font size='3' color='#FF6347'>未分配</font></a></td></tr>";
                            $table.="<tr><td class='tdtime'>12:30&nbsp;&nbsp;-&nbsp;&nbsp;16:30</td>
						            <td class='tdpeople'>
						            <a style='text-decoration:none' href='javascript:void(0);' onclick='promptBox(".$rota['rid'].")' data-toggle='modal' data-target='#myModal'>
						            <font size='2' color='#1E90FF'>".$rota['name']."</font></a></td></tr>";
                        }
                    }
                }else{
                    foreach ($list as $rota){
                        if ($rota['hid'] == 1){
                            $table.="<tr><td class='tdtime'>8:30&nbsp;&nbsp;-&nbsp;&nbsp;12:30</td>
						            <td class='tdpeople'>
						            <a style='text-decoration:none' href='javascript:void(0);' onclick='promptBox(".$rota['rid'].")' data-toggle='modal' data-target='#myModal'>
						            <font size='2' color='#1E90FF'>".$rota['name']."</font></a></td></tr>";
                        }
                        if ($rota['hid'] == 2){
                            $table.="<tr><td class='tdtime'>12:30&nbsp;&nbsp;-&nbsp;&nbsp;16:30</td>
						            <td class='tdpeople'>
						            <a style='text-decoration:none' href='javascript:void(0);' onclick='promptBox(".$rota['rid'].")' data-toggle='modal' data-target='#myModal'>
						            <font size='2' color='#1E90FF'>".$rota['name']."</font></a></td></tr>";
                        }
                    }
                }
			}
			$table.="</tbody></table></td>";
			if ($nums%7==0){//换行处理：7个一行
				$table.="</tr><tr>";
			}
			$nums++;
		}
		//输出最后一天之后的空白日期
		if ($dayofend!=6 && $dayofend!=7){
			for ($i=5;$dayofend<=$i;$dayofend++){
				$table.="<td> </td>";
			}
		} elseif ($dayofend==7){
			for ($i=1;$i<=6;$i++){
				$table.="<td> </td>";
			}
		}
		$table.="</tbody></table>";
		$table.="<div class='table-actions' style='margin-left:95rem;'><a style='text-decoration:none' href='".U('rota/index',array('year'=>$year,'month'=>$month-1))."'>上一月</a>";
		$table.="&nbsp;&nbsp;&nbsp;&nbsp;<a style='text-decoration:none' href='".U('rota/index',array('year'=>$year,'month'=>$month+1))."'>下一月</a></div>";
		$this->assign( 'table', $table );
		$this->display();
	}
	//添加值班表
	function add() {
		if ( IS_POST ) {
			if (empty($_POST['user_id'])) $this->error('请选择值班人');
            $date = strtotime($_POST['currentDate']);
            $_POST['date'] = $date;
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$rota_id = $this->rota_model->add($_POST);
			if ($rota_id) {
				//记录日志
				LogController::log_record($rota_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$year=I('year');//年
			$month=I('month');//月
			$day=I('day');//日
            $hour_id=I('hour_id');//时间段
            //取出时间段
            $hr = array();
            $hr['id'] = $hour_id;
            $hr['del_flg'] = 0;
            $hour = $this->rota_hour_model->where($hr)->find();
			//选择负责人
			$where = array();
            $where['status'] = array('eq',1);
            $where['interview_status'] = array('eq',3);
			$user_list = $this->recruit_member->where($where)->select();
			$user_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
			foreach ($user_list as $user) {
				$user_html .= "<option value='".$user['id']."'>".$user['name']."</option>";
			}
            $currentDate = $year.' - '.$month.' - '.$day;
            $currentDateHid = $year.'-'.$month.'-'.$day;
			$currentTime = $hour['start_hour']." - ".$hour['end_hour'];
            $this->assign('hour_id',$hour_id);
            $this->assign('currentDate',$currentDate);
            $this->assign('currentDateHid',$currentDateHid);
            $this->assign('currentTime',$currentTime);
			$this->assign('user_html',$user_html);
			$this->display();
		}
	}
	//编辑值班表
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			if (empty($_POST['user_id'])) $this->error('请选择值班人');
			$_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->rota_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$where = array();
			$id = intval( I( 'get.id' ) );
			$where['r.id'] = $id;
			$where['r.del_flg'] = 0;
			$rota = $this->rota_model->alias('r')->field('r.id as rid, h.id as hid, u.id as uid, r.date as rdate, h.start_hour as start_hour, h.end_hour as end_hour, u.name as name')->join('__ROTA_HOUR__ h ON r.hour_id=h.id')->join('__RECRUIT_MEMBER__ u ON r.user_id=u.id')->where($where)->find();
            $date = date('Y-m-d',$rota['rdate']);
			//选择负责人
            $cwhere = array();
            $cwhere['status'] = array('eq',1);
            $cwhere['interview_status'] = array('eq',3);
            $cwhere['id'] = array('neq',$rota['uid']);
            $user_list = $this->recruit_member->where($cwhere)->select();
            $user_html = "<option value='0'>".L('PLEASE_SELECT')."</option>";
            foreach ($user_list as $user) {
                $user_html .= "<option value='".$user['id']."'>".$user['name']."</option>";
            }
			$this->assign('user_html',$user_html);
			$this->assign('date',$date);
            $this->assign('rota',$rota);
			$this->display();
		}
	}
	//删除值班表
	function delete() {
		if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['del_flg'] = 0;
			if ( $this->rota_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->rota_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['del_flg'] = 1;
			if ( $this->rota_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	}
	//值班时间段信息
	public function hour() {
		$where = array();
		$where['del_flg'] = 0;
		$count = $this->rota_hour_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->rota_hour_model->where($where)->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加值班时间段
	function hour_add() {
		if ( IS_POST ) {
			if (empty($_POST['start_hour'])) $this->error('请输入开始时间');
			if (empty($_POST['end_hour'])) $this->error('请输入结束时间');
			$_POST['create_time'] = date('Y-m-d H:i:s',time());
			$rota_hour_id = $this->rota_hour_model->add($_POST);
			if ($rota_hour_id) {
				//记录日志
				LogController::log_record($rota_hour_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	//编辑值班时间段
	function hour_edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			if (empty($_POST['start_hour'])) $this->error('请输入开始时间');
			if (empty($_POST['end_hour'])) $this->error('请输入结束时间');
			$_POST['update_time'] = date('Y-m-d H:i:s',time());
		
			$result = $this->rota_hour_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$rota = $this->rota_hour_model->find($id);
			$this->assign($rota);
			$this->display();
		}
	}
	//删除值班时间段
	function hour_delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['del_flg'] = 1;
			if ( $this->rota_hour_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);

				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['del_flg'] = 0;
			if ( $this->rota_hour_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->rota_hour_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['del_flg'] = 1;
			if ( $this->rota_hour_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	}
}