<?php
/** 
 * 后台管理系统之工资管理
 * @author 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class WageController extends AdminbaseController {

	private $wage_scale_model;
	private $wage_member_model;
	private $users_model;

	function _initialize() {
		parent::_initialize();

		$this->wage_scale_model = D( 'WageScale' );
		$this->wage_member_model = D( 'WageMember' );
		$this->users_model = D( 'Users' );
	}
	//工资记录列表
	function index() {
		
		$where = array();
		$year_month = $_REQUEST['year_month'];
		$keyword = $_REQUEST['keyword'];
		$this->assign( 'year_month', $year_month );
		$this->assign( 'keyword', $keyword );

		if ($year_month) $where['wm.year_month'] = $year_month;
		if ($keyword) $where['wm.name'] = array('like',"%$keyword%");
		$where['wm.status'] = 1;
		
		$count = $this->wage_member_model
					->alias('wm')
					->field('wm.*,ws.wage_scale')
					->join('__WAGE_SCALE__ ws ON ws.id=wm.wage_scale_id')
					->where($where)
					->count();
		$page = $this->page($count, 15);
		$list = $this->wage_member_model
					->alias('wm')
					->field('wm.*,ws.wage_scale')
					->join('__WAGE_SCALE__ ws ON ws.id=wm.wage_scale_id')
					->where($where)
					->limit( $page->firstRow, $page->listRows )
					->order("wm.year_month desc")
					->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		
		if ( $_REQUEST['cmd'] == 'export' ) {
			
			$wages = $this->wage_member_model
						->alias('wm')
						->field('wm.*,ws.wage_scale')
						->join('__WAGE_SCALE__ ws ON ws.id=wm.wage_scale_id')
						->where($where)
						->order("wm.year_month desc")
						->select();
			
			$cols = array(
					array( 20, '姓名', 'FFFFFF' ),//1
					array( 20, '课时数', 'FFFFFF' ),//2
					array( 20, '工资标准', 'FFFFFF' ),//3
					array( 20, '工资总数', 'FFFFFF' ),//4
					array( 20, '学号', 'FFFFFF' ),//5
					array( 20, '卡号', 'FFFFFF' ),
					array( 20, '收款类型', 'FFFFFF' ),
					array( 20, '年级组负责人', 'FFFFFF' )
			);
				
			//导出
			set_time_limit(0);
		
			$xls_file_name = 'CIEE中文课课时费'.date('Y-m-d',time());
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
		
			$rowIndex = 1;  //行
		
			if ( count( $wages ) > 0 ) {
				foreach ( $wages as $wage ) {
					$colIndex = -1;//列
					$rowIndex++;
					if ($wage['teacher_status'] == 0) {
						$class_hour = $wage['class_hour'];
						$total_wage = sprintf("%.2f",$wage['class_hour']*$wage['wage_scale']);
					}
					if ($wage['teacher_status'] == 1) {
						$class_hour = $wage['class_hour']*1.5;
						$total_wage = sprintf("%.2f",$wage['class_hour']*$wage['wage_scale']*1.5);
					}
					$teacher = $this->users_model->field('full_name')->find($wage['leader_id']);
					
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $wage['teacher_name'] );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $class_hour );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $wage['wage_scale'] );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $total_wage );
					if ($wage['type'] == 1) {
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $wage['teacher_student_no'].' ' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $wage['teacher_card_no'].' ' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '本人' );
					}
					if ($wage['type'] == 2) {
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $wage['receiver_student_no'].' ' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $wage['receiver_card_no'].' ' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '代收( '.$wage['receiver'].' )' );
					}
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $teacher['full_name'] );
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial' )
							)
							);
				}
			}
		
			$rowIndex = 1;//第一行字段名
			$colIndex = -1;//列
			foreach ($cols as $col) {
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $col[1] );
				$sheet->getColumnDimension( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ) )->setWidth( $col[0] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getFill()->getStartColor()->setARGB( 'FF'.$col[2] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getTop()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getRight()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getBottom()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getLeft()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getTop()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getRight()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getBottom()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getLeft()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getAlignment()->setWrapText( false );//自动换行
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'bold' => true, 'size' => 10,'name' => 'Arial' ),
								'alignment' => array( 'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER )
						)
				);
			}
			$sheet->getRowDimension(1)->setRowHeight( 18 );
				
			//$sheet->getStyle( 'B' )->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_TEXT );
		
			$sheet->setTitle( 'sheet1' );
			header( 'Content-Type: application/vnd.ms-excel' );
			header( "Content-Disposition: attachment;filename=$xls_file_name.xls" );
			header( 'Cache-Control: max-age=0' );
		
			$excelWriter = \PHPExcel_IOFactory::createWriter( $excel, 'Excel5' );
			$excelWriter->save('php://output');
		
			exit;
		}
		if ( $_REQUEST['cmd'] == 'fee' ) {
			$wages = $this->wage_member_model
						->alias('wm')
						->field('wm.*,ws.wage_scale')
						->join('__WAGE_SCALE__ ws ON ws.id=wm.wage_scale_id')
						->where($where)
						->order("wm.year_month desc")
						->select();
			
			$where['wm.type'] = 1;
			$teacher_student_cards = $this->wage_member_model->alias('wm')->field('distinct wm.teacher_name,wm.teacher_student_no,wm.teacher_card_no')->where($where)->select();
			$where['wm.type'] = 2;
			$receiver_student_cards = $this->wage_member_model->alias('wm')->field('distinct wm.receiver,wm.receiver_student_no,wm.receiver_card_no')->where($where)->select();
			
			$tsc_arr = array();
			foreach ($teacher_student_cards as $teacher_student_card) $tsc_arr[] = array('name' => $teacher_student_card['teacher_name'],'student_no' => $teacher_student_card['teacher_student_no'],'card_no' => $teacher_student_card['teacher_card_no']);
			$rsc_arr = array();
			foreach ($receiver_student_cards as $receiver_student_card) $rsc_arr[] = array('name' => $receiver_student_card['receiver'],'student_no' => $receiver_student_card['receiver_student_no'],'card_no' => $receiver_student_card['receiver_card_no']);
			
			//多数组合并
			$student_cards_arr = array_merge($tsc_arr, $rsc_arr);
			//去重
			$student_cards = $this->more_array_unique($student_cards_arr);
			
			$data = array();
			foreach ($student_cards as $student_card) {
				$total_wage = 0;
				foreach ($wages as $wage) {
					if ($wage['type'] == 1) {
						if ($wage['teacher_name'] == $student_card['name'] && $wage['teacher_student_no'] == $student_card['student_no'] && $wage['teacher_card_no'] == $student_card['card_no']) {
							if ($wage['teacher_status'] == 0) $total_wage += sprintf("%.2f",$wage['class_hour']*$wage['wage_scale']);
							if ($wage['teacher_status'] == 1) $total_wage += sprintf("%.2f",$wage['class_hour']*$wage['wage_scale']*1.5);
						}
					}
					if ($wage['type'] == 2) {
						if ($wage['receiver'] == $student_card['name'] && $wage['receiver_student_no'] == $student_card['student_no'] && $wage['receiver_card_no'] == $student_card['card_no']) {
							if ($wage['teacher_status'] == 0) $total_wage += sprintf("%.2f",$wage['class_hour']*$wage['wage_scale']);
							if ($wage['teacher_status'] == 1) $total_wage += sprintf("%.2f",$wage['class_hour']*$wage['wage_scale']*1.5);
						}
					}
				}
				$data[] = array('name' => $student_card['name'],'student_no' => $student_card['student_no'],'card_no' => $student_card['card_no'],'total_wage' => $total_wage);
			}
			
			$cols = array(
					array( 20, '姓名', 'FFFFFF' ),
					array( 20, '学号', 'FFFFFF' ),
					array( 20, '卡号', 'FFFFFF' ),
					array( 20, '申报费用', 'FFFFFF' )
			);
		
			//导出
			set_time_limit(0);
		
			$xls_file_name = 'CIEE中文课申报费用'.date('Y-m-d',time());
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
		
			$rowIndex = 1;  //行
		
			if ( count( $data ) > 0 ) {
				foreach ( $data as $vo ) {
					$colIndex = -1;//列
					$rowIndex++;
						
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $vo['name'].' ' );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $vo['student_no'].' ' );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $vo['card_no'].' ' );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $vo['total_wage'] );
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial' )
							)
							);
				}
			}
		
			$rowIndex = 1;//第一行字段名
			$colIndex = -1;//列
			foreach ($cols as $col) {
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $col[1] );
				$sheet->getColumnDimension( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ) )->setWidth( $col[0] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getFill()->getStartColor()->setARGB( 'FF'.$col[2] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getTop()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getRight()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getBottom()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getLeft()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getTop()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getRight()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getBottom()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getBorders()->getLeft()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->getAlignment()->setWrapText( false );//自动换行
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'bold' => true, 'size' => 10,'name' => 'Arial' ),
								'alignment' => array( 'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER )
						)
						);
			}
			$sheet->getRowDimension(1)->setRowHeight( 18 );
		
			//$sheet->getStyle( 'B' )->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_TEXT );
		
			$sheet->setTitle( 'sheet1' );
			header( 'Content-Type: application/vnd.ms-excel' );
			header( "Content-Disposition: attachment;filename=$xls_file_name.xls" );
			header( 'Cache-Control: max-age=0' );
		
			$excelWriter = \PHPExcel_IOFactory::createWriter( $excel, 'Excel5' );
			$excelWriter->save('php://output');
		
			exit;
		}
		
		$this->display();
		
	}
	//添加工资记录
	function add() {
		if ( IS_POST ) {
			$wage_member_id = $this->wage_member_model->add($_POST);
			if ($wage_member_id) {
				//记录日志
				LogController::log_record($wage_member_id,1);
				
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			//登录者信息
			$user = $this->users_model->field('full_name')->find(sp_get_current_admin_id());
			//选择工资标准
			$where = array();
			$where['status'] = 1;
			$wage_scales = $this->wage_scale_model->where($where)->order('wage_scale asc')->select();
			$wage_scale_html = " <option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($wage_scales as $wage_scale) {
				$wage_scale_html .= "<option value='".$wage_scale['id']."'>".$wage_scale['wage_scale']."</option>";
			}
			
			$leaders = $this->users_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where(array('ru.role_id' => 3))->order('full_name asc')->select();
			$leader_html = " <option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($leaders as $leader) {
				$leader_html .= "<option value='".$leader['id']."'>".$leader['full_name']."</option>";
			}
			
			$this->assign('user',$user);
			$this->assign('wage_scale_html',$wage_scale_html);
			$this->assign('leader_html',$leader_html);
			$this->display();
		}
	}
	//编辑工资记录
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			$result = $this->wage_member_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = (int)$_GET['id'];
			$wage_member = $this->wage_member_model->find($id);
			$this->assign($wage_member);
			
			$where = array();
			$where['status'] = 1;
			$wage_scales = $this->wage_scale_model->where($where)->order('wage_scale asc')->select();
			$wage_scale_html = " <option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($wage_scales as $wage_scale) {
				$wage_scale_html .= "<option";
				if($wage_scale['id'] == $wage_member['wage_scale_id']) {
					$wage_scale_html .= " selected";
				}
				$wage_scale_html .= " value='".$wage_scale['id']."'>".$wage_scale['wage_scale']."</option>";
			}
			$this->assign('wage_scale_html',$wage_scale_html);
			
			$leaders = $this->users_model->alias('u')->join('__ROLE_USER__ ru ON ru.user_id=u.id')->where(array('ru.role_id' => 3))->order('full_name asc')->select();
			$leader_html = " <option value=''>".L('PLEASE_SELECT')."</option>";
			foreach ($leaders as $leader) {
				$leader_html .= "<option";
				if($leader['id'] == $wage_member['leader_id']) {
					$leader_html .= " selected";
				}
				$leader_html .= " value='".$leader['id']."'>".$leader['full_name']."</option>";
			}
			$this->assign('leader_html',$leader_html);
			
			$this->display();
		}
	}
	// 工资记录批量复制
	function copy(){
		if(isset($_POST['ids'])){
			foreach ($_POST['ids'] as $id){
				//复制课程表中的数据
				$find_wage = $this->wage_member_model
								->field('wage_scale_id,leader_id,teacher_name,teacher_student_no,teacher_card_no,receiver,receiver_student_no,receiver_card_no,type,class_hour')
								->where(array('id'=>$id))
								->find();
				if($find_wage){
					$find_wage['year_month'] = date('Y-m');
					$find_wage['status'] = 1;
					$wage_id = $this->wage_member_model->add($find_wage);
				}
			}
			$this->success("复制成功！");
		}
	}
	
	//获取教师信息
	function getTeacher() {
		if (IS_AJAX){
			$type = $_POST['type'];
			
			//当前登录后台用户的id
			$user_id = sp_get_current_admin_id();
			$teacher = $this->users_model->find($user_id);
			$data = array();
			$data['teacher_student_no'] = $teacher['teacher_student_no'];
			$data['teacher_card_no'] = $teacher['teacher_card_no'];
			$data['receiver'] = $teacher['receiver'];
			$data['receiver_student_no'] = $teacher['receiver_student_no'];
			$data['receiver_card_no'] = $teacher['receiver_card_no'];
			
			$this->ajaxReturn($data);
		}
	}
	//删除工资记录
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['status'] = 2;
			if ( $this->wage_member_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
				
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['status'] = 1;
			if ( $this->wage_member_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->wage_member_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['status'] = 2;
			if ( $this->wage_member_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
	}

	//工资标准列表
	function scale() {
	
		$where = array();
		$where['status'] = 1;
	
		$count = $this->wage_scale_model->where($where)->count();
		$page = $this->page($count, 15);
		$list = $this->wage_scale_model->where($where)->limit( $page->firstRow, $page->listRows )->order("wage_scale asc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加工资标准
	function add_scale() {
		if ( IS_POST ) {
			if (empty($_POST['wage_scale'])) $this->error(L('工资标准不能为空'));
				
			$wage_scale_id = $this->wage_scale_model->add($_POST);
			if ($wage_scale_id) {
				//记录日志
				LogController::log_record($wage_scale_id,1);
	
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	//编辑工资标准
	function edit_scale() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			if (empty($_POST['wage_scale'])) $this->error(L('工资标准不能为空'));
	
			$result = $this->wage_scale_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
	
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = (int)$_GET['id'];
			$wage_scale = $this->wage_scale_model->find($id);
			$this->assign($wage_scale);
				
			$this->display();
		}
	}
	//删除工资标准
	function delete_scale() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['status'] = 2;
			if ( $this->wage_scale_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['status'] = 1;
			if ( $this->wage_scale_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->wage_scale_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['status'] = 2;
			if ( $this->wage_scale_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
	
	
	}
	//数组去重
	function more_array_unique($arr=array()){
		foreach($arr[0] as $k => $v){
			$arr_inner_key[]= $k;   //先把二维数组中的内层数组的键值记录在在一维数组中
		}
		foreach ($arr as $k => $v){
			$v =join(",",$v);    //降维 用implode()也行
			$temp[$k] =$v;      //保留原来的键值 $temp[]即为不保留原来键值
		}
		$temp =array_unique($temp);    //去重：去掉重复的字符串
		foreach ($temp as $k => $v){
			$a = explode(",",$v);   //拆分后的重组 如：Array( [0] => james [1] => 30 )
			$arr_after[$k]= array_combine($arr_inner_key,$a);  //将原来的键与值重新合并
		}
		return $arr_after;
	}
}