<?php
/**
 * @author Richard_Li
 * 学生管理
 * @date 2017年8月24日  上午10:52:58
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class StudentController extends AdminbaseController {

	private $users_model;
	private $course_model;
	private $course_student_model;
	private $activity_model;
	private $activity_student_model;
	private $redundancy_field_model;
	private $role_user_model;
	

	function _initialize() {
		parent::_initialize();

		$this->users_model = D( 'Users' );
		$this->course_model = D( 'Course' );
		$this->course_student_model = D( 'CourseStudentRelationship' );
		$this->activity_model = D( 'Activity' );
		$this->activity_student_model = D( 'ActivityStudentRelationship' );
		$this->redundancy_field_model = D( 'RedundancyField' );
		$this->role_user_model = D( 'RoleUser' );
	}
	/**
	 * 学生信息列表
	 * @param Array $year_val 根据year条件多重检索
	 * @param Array $term_val 根据term条件多重检索
	 * @param Array $sess_val 根据sess条件多重检索
	 * @param Array $program_val 根据program条件多重检索
	 */
	function index() {
		
		$where = array();
		
		//搜索
		$name = trim(I('request.name'));
		$this->assign( 'name', $name );
		if (!empty($name)) {
			$where['u.first_name|u.last_name|u.full_name|u.chinese_name|u.rd_field_2'] = array('like',"%$name%");
		}
		
		$year_val = $_REQUEST['year'];
		$term_val = $_REQUEST['term'];
		$sess_val = $_REQUEST['session'];
		$program_val = $_REQUEST['program'];
		$gender_val = trim($_REQUEST['gender']);
		$course_id = $_REQUEST['course'];
		$activity_id = $_REQUEST['activity'];
		$term_status = $_REQUEST['term_status'];
		$field_cols = explode(',', $_REQUEST['cols_str']);
		
		$this->assign('year_val',$year_val);
		$this->assign('term_val',$term_val);
		$this->assign('sess_val',$sess_val);
		$this->assign('program_val',$program_val);
		$this->assign('gender_val',$gender_val);
		$this->assign('course_id',$course_id);
		$this->assign('activity_id',$activity_id);
		$this->assign('term_status',$term_status);
		$this->assign('field_cols',$field_cols);
		
		$this->getSelect($year_val, $term_val, $sess_val, $program_val, $gender_val, $course_id, $activity_id ,$term_status == null ? 1 : $term_status);
		
		$where['u.user_type'] = array('eq',2);
		$where['u.user_status'] = array('neq',0);
		if ($term_status == null) {
			$where['u.term_status'] = 1;
			$this->assign('term_status',1);
		} else {
			$where['u.term_status'] = $term_status;
			$this->assign('term_status',$term_status);
		}
		if ($year_val) $where['u.year'] = array('in',implode(',', $year_val));
		if ($term_val) {
			$where['u.term'] = array();
			foreach ($term_val as $t) {
				array_push($where['u.term'], array('eq',$t));
			}
			array_push($where['u.term'], 'or');
		}
		if ($sess_val) {
			$where['u.session'] = array();
			foreach ($sess_val as $s) {
				array_push($where['u.session'], array('eq',$s));
			}
			array_push($where['u.session'], 'or');
		}
		if ($program_val) {
			$where['u.program'] = array();
			foreach ($program_val as $p) {
				array_push($where['u.program'], array('eq',$p));
			}
			array_push($where['u.program'], 'or');
		}
		if ($gender_val) $where['u.gender'] = $gender_val;
		if ($course_id && empty($activity_id)) {
			$where['cs.course_id'] = $course_id;
			$where['cs.course_student_status'] = 1;
			$count = $this->users_model->alias('u')->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.student_id=u.id')->where($where)->count();
			$page = $this->page($count, 20);
			if ($_REQUEST['cmd'] == 'export' || $_REQUEST['cmd'] == 'export_course') {
				//$list = $this->users_model->alias('u')->field('u.*,left(u.first_name,1) as fn,left(u.last_name,1) as ln')->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.student_id=u.id')->where($where)->order('fn asc,ln asc')->select();
				$list = $this->users_model->alias('u')->field('u.*')->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.student_id=u.id')->where($where)->order('u.first_name asc,u.last_name asc')->select();
			} else {
				$list = $this->users_model->alias('u')->field('u.*')->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.student_id=u.id')->where($where)->order('u.first_name asc,u.last_name asc')->limit( $page->firstRow, $page->listRows )->select();
			}
			$this->assign("page", $page->show('Admin'));
		} elseif (empty($course_id) && $activity_id){
			$where['asr.activity_id'] = $activity_id;
			$count = $this->users_model->alias('u')->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.student_id=u.id')->where($where)->count();
			$page = $this->page($count, 20);
			if ($_REQUEST['cmd'] == 'export' || $_REQUEST['cmd'] == 'export_course') {
				$list = $this->users_model->alias('u')->field('u.*')->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.student_id=u.id')->where($where)->order('u.first_name asc,u.last_name asc')->select();
			} else {
				$list = $this->users_model->alias('u')->field('u.*')->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.student_id=u.id')->where($where)->order('u.first_name asc,u.last_name asc')->limit( $page->firstRow, $page->listRows )->select();
			}
			$this->assign("page", $page->show('Admin'));
		} elseif ($course_id && $activity_id){
			$course_student_ids = array();
			$activity_student_ids = array();
			$course_students = $this->course_student_model->where(array('course_id' => $course_id,'course_student_status' => 1))->select();
			foreach ($course_students as $course_student) $course_student_ids[] = $course_student['student_id'];
			$activity_students = $this->activity_student_model->where(array('activity_id' => $activity_id))->select();
			foreach ($activity_students as $activity_student) $activity_student_ids[] = $activity_student['student_id'];
				
			$the_same_ids = array_intersect($course_student_ids, $activity_student_ids);
			$student_ids_str = implode(',', $the_same_ids);
			$where['id'] = array('in',$student_ids_str);
				
			$count = $this->users_model->alias('u')->where($where)->count();
			$page = $this->page($count, 20);
			if ($_REQUEST['cmd'] == 'export' || $_REQUEST['cmd'] == 'export_course') {
				$list = $this->users_model->alias('u')->field('u.*')->where($where)->order('u.first_name asc,u.last_name asc')->select();
			} else {
				$list = $this->users_model->alias('u')->field('u.*')->where($where)->order('u.first_name asc,u.last_name asc')->limit( $page->firstRow, $page->listRows )->select();
			}
			$this->assign("page", $page->show('Admin'));
		} else {
			$count = $this->users_model->alias('u')->where($where)->count();
			$page = $this->page($count, 20);
			if ($_REQUEST['cmd'] == 'export' || $_REQUEST['cmd'] == 'export_course') {
				$list = $this->users_model->alias('u')->field('u.*')->where($where)->order('u.first_name asc,u.last_name asc')->select();
			} else {
				$list = $this->users_model->alias('u')->field('u.*')->where($where)->order('u.first_name asc,u.last_name asc')->limit( $page->firstRow, $page->listRows )->select();
			}
			$this->assign("page", $page->show('Admin'));
		}
		
		$this->assign( 'list', $list );
		
		//冗余字段
		$rd_fields = $this->redundancy_field_model->select();
		$this->assign('rd_fields',$rd_fields);
		
		if ( $_REQUEST['cmd'] == 'export' ) {
			$cols = array();
			if (count($field_cols) == 2) {
				$cols = array(
					array( 20, 'First Name', 'FFFFFF' ),//1
					array( 20, 'Last Name', 'FFFFFF' ),//2
					array( 20, 'Chinese Name', 'FFFFFF' ),//3
					array( 20, 'Chinese level', 'FFFFFF' ),//4
					array( 20, 'Wechat', 'FFFFFF' ),//5
					array( 20, 'Phone', 'FFFFFF' ),//6
					array( 20, 'Program', 'FFFFFF' ),//7
					array( 20, 'Program Location', 'FFFFFF' ),//8
					array( 20, 'Program Short Name', 'FFFFFF' ),//9
					array( 20, 'Location City', 'FFFFFF' ),//10
					array( 20, 'Location Country', 'FFFFFF' ),//11
					array( 20, 'Term', 'FFFFFF' ),//12
					array( 20, 'Year', 'FFFFFF' ),//13
					array( 20, 'Session', 'FFFFFF' ),//14
					array( 20, 'Institution', 'FFFFFF' ),//15
					array( 20, 'Student Email', 'FFFFFF' ),//16
					array( 20, 'Gender', 'FFFFFF' ),//17
					array( 20, 'Date Of Birth', 'FFFFFF' ),//18
					array( 20, 'City Of Birth', 'FFFFFF' ),//19
					array( 20, 'Country Of Birth', 'FFFFFF' ),//20
					array( 20, 'State Of Birth', 'FFFFFF' ),//21
					array( 20, 'Country Of Citizenship', 'FFFFFF' ),//22
					array( 20, 'Ethnicity', 'FFFFFF' ),//23
					array( 20, 'Passport Number', 'FFFFFF' ),//24
					array( 20, 'Issue Date', 'FFFFFF' ),//25
					array( 20, 'Expiration Date', 'FFFFFF' ),//26
					array( 20, 'Issuing Country', 'FFFFFF' ),//27
					array( 20, 'Issuing City', 'FFFFFF' ),//28
					array( 20, 'Name On Passport', 'FFFFFF' ),//29
					array( 20, 'Primary Advisor', 'FFFFFF' ),//30
					array( 20, 'Advisor Email', 'FFFFFF' ),//31
					array( 20, 'Advisor Daytime Phone', 'FFFFFF' ),//32
					array( 20, 'Advisor Evening Phone', 'FFFFFF' ),//33
					array( 20, 'Advisor Mobile Phone', 'FFFFFF' ),//34
					array( 20, 'Onsite Address Line 1', 'FFFFFF' ),//35
					array( 20, 'Onsite Address Line 2', 'FFFFFF' ),//36
					array( 20, 'Onsite Address Line 3', 'FFFFFF' ),//37
					array( 20, 'Onsite Room Name', 'FFFFFF' ),//38
					array( 20, 'Onsite Room Number', 'FFFFFF' ),//39
					array( 20, 'Onsite City', 'FFFFFF' ),//40
					array( 20, 'Onsite Postal Code', 'FFFFFF' ),//41
					array( 20, 'Onsite Country', 'FFFFFF' ),//42
					array( 20, 'Primary Emergency Contact First Name', 'FFFFFF' ),//43
					array( 20, 'Primary Emergency Contact Last Name', 'FFFFFF' ),//44
					array( 20, 'Primary Emergency Contact Relationship', 'FFFFFF' ),//45
					array( 20, 'Primary Emergency Contact Email', 'FFFFFF' ),//46
					array( 20, 'Primary Emergency Contact Daytime Phone', 'FFFFFF' ),//47
					array( 20, 'Primary Emergency Contact Evening Phone', 'FFFFFF' ),//48
					array( 20, 'Primary Emergency Contact Mobile Phone', 'FFFFFF' ),//49
					array( 20, 'Alternate Emergency Contact First Name', 'FFFFFF' ),//50
					array( 20, 'Alternate Emergency Contact Last Name', 'FFFFFF' ),//51
					array( 20, 'Alternate Emergency Contact Relationship', 'FFFFFF' ),//52
					array( 20, 'Alternate Emergency Contact Email', 'FFFFFF' ),//53
					array( 20, 'Alternate Emergency Contact Daytime Phone', 'FFFFFF' ),//54
					array( 20, 'Alternate Emergency Contact Evening Phone', 'FFFFFF' ),//55
					array( 20, 'Alternate Emergency Contact Mobile Phone', 'FFFFFF' ),//56
					array( 20, 'Primary Emergency Contact', 'FFFFFF' ),//57
					array( 20, 'Secondary Emergency Contact', 'FFFFFF' ),//58
					array( 20, 'Health, Safety and Security Officer', 'FFFFFF' ),//59
					array( 20, 'Program Type', 'FFFFFF' ),//60
					array( 20, 'Permanent Address Line 1', 'FFFFFF' ),//61
					array( 20, 'Permanent Address Line 2', 'FFFFFF' ),//62
					array( 20, 'Permanent Address Line 3', 'FFFFFF' ),//63
					array( 20, 'Permanent City', 'FFFFFF' ),//64
					array( 20, 'Permanent State', 'FFFFFF' ),//65
					array( 20, 'Permanent Postal Code', 'FFFFFF' ),//66
					array( 20, 'Permanent Country', 'FFFFFF' ),//67
					array( 20, 'Permanent Daytime Phone', 'FFFFFF' ),//68
					array( 20, 'Permanent Evening Phone', 'FFFFFF' ),//69
					array( 20, 'Permanent Mobile Phone', 'FFFFFF' ),//70
					array( 20, 'Alternate Address Line 1', 'FFFFFF' ),//71
					array( 20, 'Alternate Address Line 2', 'FFFFFF' ),//72
					array( 20, 'Alternate Address Line 3', 'FFFFFF' ),//73
					array( 20, 'Alternate City', 'FFFFFF' ),//74
					array( 20, 'Alternate State', 'FFFFFF' ),//75
					array( 20, 'Alternate Postal Code', 'FFFFFF' ),//76
					array( 20, 'Alternate Country', 'FFFFFF' ),//77
					array( 20, 'Alternate Daytime Phone', 'FFFFFF' ),//78
					array( 20, 'Alternate Evening Phone', 'FFFFFF' ),//79
					array( 20, 'Alternate Mobile Phone', 'FFFFFF' ),//80
					array( 20, 'Alternate Start Date', 'FFFFFF' ),//81
					array( 20, 'Alternate End Date', 'FFFFFF' ),//82
					array( 20, 'Rd Field 1', 'FFFFFF' ),
					array( 20, 'Rd Field 2', 'FFFFFF' ),
					array( 20, 'Rd Field 3', 'FFFFFF' ),
					array( 20, 'Rd Field 4', 'FFFFFF' ),
					array( 20, 'Rd Field 5', 'FFFFFF' )
				);
			} else {
				foreach ($field_cols as $k => $field_col) {
					$cols[] = array(20,$field_cols[$k],'FFFFFF');
				}
			}
			
			//导出
			set_time_limit(0);
		
			$xls_file_name = 'Master Student Information '.date('Y-m-d',time());
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
		
			$rowIndex = 1;  //行
		
			if ( count( $list ) > 0 ) {
				foreach ( $list as $user ) {
					$colIndex = -1;//列
					$rowIndex++;
					$chinese_level_arr = array();
					$chinese_levels = $this->course_model->alias('c')->field('c.course_name')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.course_id=c.id')->where(array('csr.student_id' => $user['id'],'csr.course_student_status' => 1,'c.course_status' => array('neq',2),'c.course_type' => 2))->select();
					foreach ($chinese_levels as $chinese_level) $chinese_level_arr[] = $chinese_level['course_name'];
					$chinese_level_str = implode(',', $chinese_level_arr);
					foreach ($cols as $col) {
						$colIndex++;
						$field_name = $this->check_name(trim($col[1]));
						if ($field_name == "chinese_level") {
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_level_str.' ' ); 
						} else {
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user[$field_name].' ' );
						}
						$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
								'font' => array( 'size' => 10,'name' => 'Arial' )
							)
						);
					}
				}
			}
		
			$rowIndex = 1;//第一行字段名
			$colIndex = -1;//列
			foreach ($cols as $col) {
				$colIndex++;
				//第一行
				if ($col[1] == "Rd Field 1") {
					$rd_field = $this->redundancy_field_model->find(1);
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $rd_field['field_key'] );
				} elseif ($col[1] == "Rd Field 2") {
					$rd_field = $this->redundancy_field_model->find(2);
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $rd_field['field_key'] );
				} elseif ($col[1] == "Rd Field 3") {
					$rd_field = $this->redundancy_field_model->find(3);
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $rd_field['field_key'] );
				} elseif ($col[1] == "Rd Field 4") {
					$rd_field = $this->redundancy_field_model->find(4);
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $rd_field['field_key'] );
				} elseif ($col[1] == "Rd Field 5") {
					$rd_field = $this->redundancy_field_model->find(5);
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $rd_field['field_key'] );
				} else {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $col[1] );
				}
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
		if ( $_REQUEST['cmd'] == 'export_course' ) {
			$course_cols = array(
					array( 20, 'First Name', 'FFFFFF' ),//1
					array( 20, 'Last Name', 'FFFFFF' ),//2
					array( 20, 'Course1', 'FFFFFF' ),//3
					array( 20, 'Course2', 'FFFFFF' ),//4
					array( 20, 'Course3', 'FFFFFF' ),//5
					array( 20, 'Course4', 'FFFFFF' ),//6
					array( 20, 'Course5', 'FFFFFF' ),//7
					array( 20, 'Course6', 'FFFFFF' ),//8
					array( 20, 'Course7', 'FFFFFF' ),//9
					array( 20, 'Course8', 'FFFFFF' ),//10
					array( 20, 'Course9', 'FFFFFF' ),//11
					array( 20, 'Course10', 'FFFFFF' )//12
			);
				
			//导出
			set_time_limit(0);
		
			$xls_file_name = 'Master Student Course Information '.date('Y-m-d',time());
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
		
			$rowIndex = 1;  //行
		
			if ( count( $list ) > 0 ) {
				foreach ( $list as $user ) {
					$colIndex = -1;//列
					$rowIndex++;
					$student_courses = $this->course_model
											->alias('c')
											->field('c.*')
											->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.course_id=c.id')
											->where(array('cs.student_id' => $user['id'],'cs.course_student_status' => 1))
											->select();
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user['first_name'] );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user['last_name'] );
					foreach ($student_courses as $student_course) {
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $student_course['course_name'] );
					}
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial' )
							)
					);
				}
			}
		
			$rowIndex = 1;//第一行字段名
			$colIndex = -1;//列
			foreach ($course_cols as $col) {
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
	private function getSelect($year_val,$term_val,$sess_val,$program_val,$gender_val,$course_id,$activity_id,$term_status) {
		//Year
		$years = $this->users_model->field('distinct year')->where(array('user_type' => 2,'term_status' => $term_status))->order('year desc')->select();
		$year_html = " ";
		foreach ($years as $year) {
			if ($year['year']) {
				$year_html .= "<option";
				foreach ($year_val as $y) {
					if($year['year'] == $y) {
						$year_html .= " selected";
					}
				}
				$year_html .= " value='".$year['year']."'>".$year['year']."</option>";
			}
		}
		$this->assign('year_html',$year_html);
	
		//Term
		$terms = $this->users_model->field('distinct term')->where(array('user_type' => 2,'term_status' => $term_status))->select();
		$term_html = " ";
		foreach ($terms as $term) {
			if ($term['term']) {
				$term_html .= "<option";
				foreach ($term_val as $t) {
					if($term['term'] == $t) {
						$term_html .= " selected";
					}
				}
				$term_html .= " value='".$term['term']."'>".$term['term']."</option>";
			}
		}
		$this->assign('term_html',$term_html);
	
		//Session
		$sesses = $this->users_model->field('distinct session')->where(array('user_type' => 2,'term_status' => $term_status))->select();
		$sess_html = " ";
		foreach ($sesses as $sess) {
			if ($sess['session']) {
				$sess_html .= "<option";
				foreach ($sess_val as $s) {
					if($sess['session'] == $s) {
						$sess_html .= " selected";
					}
				}
				$sess_html .= " value='".$sess['session']."'>".$sess['session']."</option>";
			}
		}
		$this->assign('sess_html',$sess_html);
	
		//Program
		$programs = $this->users_model->field('distinct program,term_status')->where(array('user_type' => 2,'term_status' => $term_status))->select();
		$program_html = " ";
		foreach ($programs as $program) {
			if ($program['program']) {
				$program_html .= "<option";
				foreach ($program_val as $p) {
					if($program['program'] == $p) {
						$program_html .= " selected";
					}
				}
				$program_html .= " value='".$program['program']."'>".$program['program']."</option>";
			}
		}
		$this->assign('program_html',$program_html);
		//Gender
		$genders = $this->users_model->field('distinct gender')->where(array('user_type' => 2))->select();
		$gender_html = " <option value=''>Gender</option>";
		foreach ($genders as $gender) {
			if($gender['gender']) {
				$gender_html .= "<option";
				if($gender['gender'] == $gender_val) {
					$gender_html .= " selected";
				}
				$gender_html .= " value='".$gender['gender']."'>".$gender['gender']."</option>";
			}
		}
		$this->assign('gender_html',$gender_html);
		//Course
		$courses = $this->course_model->field('id,course_name,course_code')->where(array('course_status' => array('eq',1)))->select();
		$course_html = " <option value=''>Course</option>";
		foreach ($courses as $course) {
			$course_html .= "<option";
			if($course['id'] == $course_id) {
				$course_html .= " selected";
			}
			$course_html .= " value='".$course['id']."'>".$course['course_name']."</option>";
		}
		$this->assign('course_html',$course_html);
		//Activity
		$activities = $this->activity_model->field('id,activity_name')->where(array('activity_status' => array('neq',2)))->select();
		$activity_html = " <option value=''>Activity</option>";
		foreach ($activities as $activity) {
			$activity_html .= "<option";
			if($activity['id'] == $activity_id) {
				$activity_html .= " selected";
			}
			$activity_html .= " value='".$activity['id']."'>".$activity['activity_name']."</option>";
		}
		$this->assign('activity_html',$activity_html);
		
	}
	
	// 选择课程
	public function select_course(){
		$where = array();
		$id = I('get.id');
		$teacher_id = sp_get_current_admin_id();
		//获取当前用户管理权限id
		$roles = $this->role_user_model->where(array('user_id' => $teacher_id))->select();
		$role_id = array();
		foreach ($roles as $role) $role_id[] = $role['role_id'];
		$student = $this->users_model->find($id);
		
		$where['csr.student_id'] = $id;
		$where['csr.course_student_status'] = 1;
		$where['c.course_status'] = 1;
		//staff,教师
		if (!in_array(1, $role_id) && !in_array(12, $role_id)) {
			if (in_array(3, $role_id) && !in_array(13, $role_id)) {
				$where['c.headteacher_id'] = $teacher_id;
			} elseif (!in_array(3, $role_id) && in_array(13, $role_id)){
				$where[] = "FIND_IN_SET(".$teacher_id.",c.parttimeteacher_id)";
			} elseif (in_array(3, $role_id) && in_array(13, $role_id)) {
				$where[] = "c.headteacher_id=".$teacher_id." OR FIND_IN_SET(".$teacher_id.",c.parttimeteacher_id)";
			} else {
				
			}
		}
		$select_courses = $this->course_model
								->alias('c')
								->field('c.id,c.course_name,c.course_code')
								->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.course_id=c.id')
								->where($where)
								->select();
			
		$select_course_html = " <option value=''>已报名课程</option>";
		foreach ($select_courses as $select_course) {
			$select_course_html .= "<option value='".$select_course['id']."'>".$select_course['course_name']."(".$select_course['course_code'].")</option>";
		}
		$this->assign('student_id',$id);
		$this->assign('select_course_html',$select_course_html);
		$this->display();
	}
	
	//异步修改数据
	function async_edit() {
		if (IS_AJAX){
			$param_key = I('param_key');
			$param_value = I('param_value');
			$user_id = (int)I('user_id');
			$user = $this->users_model->find($user_id);
			$save_data = array();
			if ($param_key == "first_name" || $param_key == "last_name") {
				if ($param_key == "first_name") {
					$user_login = strtolower(trim($param_value)).strtolower(trim($user['last_name']));
					$full_name = trim($param_value)." ".trim($user['last_name']);
				}
				if ($param_key == "last_name") {
					$user_login = strtolower(trim($user['first_name'])).strtolower(trim($param_value));
					$full_name = trim($user['first_name'])." ".trim($param_value);
				}
				$user_pass = sp_password($user_login);
				$save_data = array('user_login' => $user_login,'user_pass' => $user_pass,'full_name' => $full_name,$param_key => trim($param_value));
			} else {
				$save_data = array($param_key => trim($param_value));
			}
			
			$this->users_model->where(array('id' => $user_id))->save($save_data);
			$data = " ".$param_value;
			$this->ajaxReturn($data);
		}
	}
	//异步修改数据
	function field_async_edit() {
		if (IS_AJAX){
			$param_key = I('param_key');
			$param_value = I('param_value');
			$field_id = (int)I('field_id');
			$this->redundancy_field_model->where(array('id' => $field_id))->save(array($param_key => trim($param_value)));
			$data = " ".$param_value;
			$this->ajaxReturn($data);
		}
	}
	//批量导入学生信息
	function student_upload() {
		if ( IS_POST ) {
			$uploadConfig = array(
					'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
					'rootPath' => './'.C( 'UPLOADPATH' ),
					'savePath' => './excel/',
					'saveName' => array( 'uniqid', '' ),
					'exts' => array( 'xls', 'xlsx' ),
					'autoSub' => false
			);
			$upload = new \Think\Upload( $uploadConfig );
			$info = $upload->upload();
			$file = './'.C( 'UPLOADPATH' ).$info['file']['savepath'].$info['file']['savename'];
	
			require_once 'today/excel/PHPExcel.php';
			require_once 'today/excel/PHPExcel/IOFactory.php';
			require_once 'today/excel/PHPExcel/Reader/Excel5.php';
			require_once 'today/excel/PHPExcel/Reader/Excel2007.php';
	
			$reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
			$obj = $reader->load( $file );
			$sheet = $obj->getSheet(0);
			$rowCount = $sheet->getHighestRow();
			$colCount = $sheet->getHighestColumn();
			$realRowCount = 0;
			$importCount = 0;
			
			//数据库中已有学生的全名
			$database_students_fullname = array();
			//查询出数据库中已有的学生
			$database_students = $this->users_model->where(array('user_type' => 2))->select();
			foreach ($database_students as $database_student) $database_students_fullname[] = $database_student['full_name'];
			//上传表格中所拥有但是数据库中尚未拥有的学生需要新增到数据库
			$table_students_add = array();
			//上传表格中所拥有并且数据库中也已有的学生需要修改的数据
			$table_students_edit = array();
			
			//得到表格的头，并将其转化为数据库中字段名A1,B1固定不变
			$key_td_a = $this->check_name($sheet->getCell( 'A1' )->getValue());//A,first_name
			$key_td_b = $this->check_name($sheet->getCell( 'B1' )->getValue());//B,last_name
			$key_td_c = $this->check_name($sheet->getCell( 'C1' )->getValue());
			$key_td_d = $this->check_name($sheet->getCell( 'D1' )->getValue());
			$key_td_e = $this->check_name($sheet->getCell( 'E1' )->getValue());
			$key_td_f = $this->check_name($sheet->getCell( 'F1' )->getValue());
			$key_td_g = $this->check_name($sheet->getCell( 'G1' )->getValue());
			$key_td_h = $this->check_name($sheet->getCell( 'H1' )->getValue());
			$key_td_i = $this->check_name($sheet->getCell( 'I1' )->getValue());
			$key_td_j = $this->check_name($sheet->getCell( 'J1' )->getValue());
			$key_td_k = $this->check_name($sheet->getCell( 'K1' )->getValue());
			$key_td_l = $this->check_name($sheet->getCell( 'L1' )->getValue());
			$key_td_m = $this->check_name($sheet->getCell( 'M1' )->getValue());
			$key_td_n = $this->check_name($sheet->getCell( 'N1' )->getValue());
			$key_td_o = $this->check_name($sheet->getCell( 'O1' )->getValue());
			$key_td_p = $this->check_name($sheet->getCell( 'P1' )->getValue());
			$key_td_q = $this->check_name($sheet->getCell( 'Q1' )->getValue());
			$key_td_r = $this->check_name($sheet->getCell( 'R1' )->getValue());
			$key_td_s = $this->check_name($sheet->getCell( 'S1' )->getValue());
			$key_td_t = $this->check_name($sheet->getCell( 'T1' )->getValue());
			$key_td_u = $this->check_name($sheet->getCell( 'U1' )->getValue());
			$key_td_v = $this->check_name($sheet->getCell( 'V1' )->getValue());
			$key_td_w = $this->check_name($sheet->getCell( 'W1' )->getValue());
			$key_td_x = $this->check_name($sheet->getCell( 'X1' )->getValue());
			$key_td_y = $this->check_name($sheet->getCell( 'Y1' )->getValue());
			$key_td_z = $this->check_name($sheet->getCell( 'Z1' )->getValue());
			$key_td_aa = $this->check_name($sheet->getCell( 'AA1' )->getValue());
			$key_td_ab = $this->check_name($sheet->getCell( 'AB1' )->getValue());
			$key_td_ac = $this->check_name($sheet->getCell( 'AC1' )->getValue());
			$key_td_ad = $this->check_name($sheet->getCell( 'AD1' )->getValue());
			$key_td_ae = $this->check_name($sheet->getCell( 'AE1' )->getValue());
			$key_td_af = $this->check_name($sheet->getCell( 'AF1' )->getValue());
			$key_td_ag = $this->check_name($sheet->getCell( 'AG1' )->getValue());
			$key_td_ah = $this->check_name($sheet->getCell( 'AH1' )->getValue());
			$key_td_ai = $this->check_name($sheet->getCell( 'AI1' )->getValue());
			$key_td_aj = $this->check_name($sheet->getCell( 'AJ1' )->getValue());
			$key_td_ak = $this->check_name($sheet->getCell( 'AK1' )->getValue());
			$key_td_al = $this->check_name($sheet->getCell( 'AL1' )->getValue());
			$key_td_am = $this->check_name($sheet->getCell( 'AM1' )->getValue());
			$key_td_an = $this->check_name($sheet->getCell( 'AN1' )->getValue());
			$key_td_ao = $this->check_name($sheet->getCell( 'AO1' )->getValue());
			$key_td_ap = $this->check_name($sheet->getCell( 'AP1' )->getValue());
			$key_td_aq = $this->check_name($sheet->getCell( 'AQ1' )->getValue());
			$key_td_ar = $this->check_name($sheet->getCell( 'AR1' )->getValue());
			$key_td_as = $this->check_name($sheet->getCell( 'AS1' )->getValue());
			$key_td_at = $this->check_name($sheet->getCell( 'AT1' )->getValue());
			$key_td_au = $this->check_name($sheet->getCell( 'AU1' )->getValue());
			$key_td_av = $this->check_name($sheet->getCell( 'AV1' )->getValue());
			$key_td_aw = $this->check_name($sheet->getCell( 'AW1' )->getValue());
			$key_td_ax = $this->check_name($sheet->getCell( 'AX1' )->getValue());
			$key_td_ay = $this->check_name($sheet->getCell( 'AY1' )->getValue());
			$key_td_az = $this->check_name($sheet->getCell( 'AZ1' )->getValue());
			$key_td_ba = $this->check_name($sheet->getCell( 'BA1' )->getValue());
			$key_td_bb = $this->check_name($sheet->getCell( 'BB1' )->getValue());
			$key_td_bc = $this->check_name($sheet->getCell( 'BC1' )->getValue());
			$key_td_bd = $this->check_name($sheet->getCell( 'BD1' )->getValue());
			$key_td_be = $this->check_name($sheet->getCell( 'BE1' )->getValue());
			$key_td_bf = $this->check_name($sheet->getCell( 'BF1' )->getValue());
			$key_td_bg = $this->check_name($sheet->getCell( 'BG1' )->getValue());
			$key_td_bh = $this->check_name($sheet->getCell( 'BH1' )->getValue());
			$key_td_bi = $this->check_name($sheet->getCell( 'BI1' )->getValue());
			$key_td_bj = $this->check_name($sheet->getCell( 'BJ1' )->getValue());
			$key_td_bk = $this->check_name($sheet->getCell( 'BK1' )->getValue());
			$key_td_bl = $this->check_name($sheet->getCell( 'BL1' )->getValue());
			$key_td_bm = $this->check_name($sheet->getCell( 'BM1' )->getValue());
			$key_td_bn = $this->check_name($sheet->getCell( 'BN1' )->getValue());
			$key_td_bo = $this->check_name($sheet->getCell( 'BO1' )->getValue());
			$key_td_bp = $this->check_name($sheet->getCell( 'BP1' )->getValue());
			$key_td_bq = $this->check_name($sheet->getCell( 'BQ1' )->getValue());
			$key_td_br = $this->check_name($sheet->getCell( 'BR1' )->getValue());
			$key_td_bs = $this->check_name($sheet->getCell( 'BS1' )->getValue());
			$key_td_bt = $this->check_name($sheet->getCell( 'BT1' )->getValue());
			$key_td_bu = $this->check_name($sheet->getCell( 'BU1' )->getValue());
			$key_td_bv = $this->check_name($sheet->getCell( 'BV1' )->getValue());
			$key_td_bw = $this->check_name($sheet->getCell( 'BW1' )->getValue());
			$key_td_bx = $this->check_name($sheet->getCell( 'BX1' )->getValue());
			$key_td_by = $this->check_name($sheet->getCell( 'BY1' )->getValue());
			$key_td_bz = $this->check_name($sheet->getCell( 'BZ1' )->getValue());
			$key_td_ca = $this->check_name($sheet->getCell( 'CA1' )->getValue());
			$key_td_cb = $this->check_name($sheet->getCell( 'CB1' )->getValue());
			$key_td_cc = $this->check_name($sheet->getCell( 'CC1' )->getValue());
			$key_td_cd = $this->check_name($sheet->getCell( 'CD1' )->getValue());
			$key_td_ce = $this->check_name($sheet->getCell( 'CE1' )->getValue());
			$key_td_cf = $this->check_name($sheet->getCell( 'CF1' )->getValue());
			$key_td_cg = $this->check_name($sheet->getCell( 'CG1' )->getValue());
			$key_td_ch = $this->check_name($sheet->getCell( 'CH1' )->getValue());
			$key_td_ci = $this->check_name($sheet->getCell( 'CI1' )->getValue());
			//还可添加冗余字段
			for ( $i = 2; $i <= $rowCount; $i++ ) {
				//first_name和last_name不能为空,A,B两列固定为first_name和last_name
				$value_td_a = trim($sheet->getCell( 'A'.$i )->getValue());
				$value_td_b = trim($sheet->getCell( 'B'.$i )->getValue());
				if (empty($value_td_a) || empty($value_td_b)) continue;
				//其他列字段，可以随意交换位置
				$value_td_c = trim($sheet->getCell( 'C'.$i )->getValue());
				$value_td_d = trim($sheet->getCell( 'D'.$i )->getValue());
				$value_td_e = trim($sheet->getCell( 'E'.$i )->getValue());
				$value_td_f = trim($sheet->getCell( 'F'.$i )->getValue());
				$value_td_g = trim($sheet->getCell( 'G'.$i )->getValue());
				$value_td_h = trim($sheet->getCell( 'H'.$i )->getValue());
				$value_td_i = trim($sheet->getCell( 'I'.$i )->getValue());
				$value_td_j = trim($sheet->getCell( 'J'.$i )->getValue());
				$value_td_k = trim($sheet->getCell( 'K'.$i )->getValue());
				$value_td_l = trim($sheet->getCell( 'L'.$i )->getValue());
				$value_td_m = trim($sheet->getCell( 'M'.$i )->getValue());
				$value_td_n = trim($sheet->getCell( 'N'.$i )->getValue());
				$value_td_o = trim($sheet->getCell( 'O'.$i )->getValue());
				$value_td_p = trim($sheet->getCell( 'P'.$i )->getValue());
				$value_td_q = trim($sheet->getCell( 'Q'.$i )->getValue());
				$value_td_r = trim($sheet->getCell( 'R'.$i )->getValue());
				$value_td_s = trim($sheet->getCell( 'S'.$i )->getValue());
				$value_td_t = trim($sheet->getCell( 'T'.$i )->getValue());
				$value_td_u = trim($sheet->getCell( 'U'.$i )->getValue());
				$value_td_v = trim($sheet->getCell( 'V'.$i )->getValue());
				$value_td_w = trim($sheet->getCell( 'W'.$i )->getValue());
				$value_td_x = trim($sheet->getCell( 'X'.$i )->getValue());
				$value_td_y = trim($sheet->getCell( 'Y'.$i )->getValue());
				$value_td_z = trim($sheet->getCell( 'Z'.$i )->getValue());
				$value_td_aa = trim($sheet->getCell( 'AA'.$i )->getValue());
				$value_td_ab = trim($sheet->getCell( 'AB'.$i )->getValue());
				$value_td_ac = trim($sheet->getCell( 'AC'.$i )->getValue());
				$value_td_ad = trim($sheet->getCell( 'AD'.$i )->getValue());
				$value_td_ae = trim($sheet->getCell( 'AE'.$i )->getValue());
				$value_td_af = trim($sheet->getCell( 'AF'.$i )->getValue());
				$value_td_ag = trim($sheet->getCell( 'AG'.$i )->getValue());
				$value_td_ah = trim($sheet->getCell( 'AH'.$i )->getValue());
				$value_td_ai = trim($sheet->getCell( 'AI'.$i )->getValue());
				$value_td_aj = trim($sheet->getCell( 'AJ'.$i )->getValue());
				$value_td_ak = trim($sheet->getCell( 'AK'.$i )->getValue());
				$value_td_al = trim($sheet->getCell( 'AL'.$i )->getValue());
				$value_td_am = trim($sheet->getCell( 'AM'.$i )->getValue());
				$value_td_an = trim($sheet->getCell( 'AN'.$i )->getValue());
				$value_td_ao = trim($sheet->getCell( 'AO'.$i )->getValue());
				$value_td_ap = trim($sheet->getCell( 'AP'.$i )->getValue());
				$value_td_aq = trim($sheet->getCell( 'AQ'.$i )->getValue());
				$value_td_ar = trim($sheet->getCell( 'AR'.$i )->getValue());
				$value_td_as = trim($sheet->getCell( 'AS'.$i )->getValue());
				$value_td_at = trim($sheet->getCell( 'AT'.$i )->getValue());
				$value_td_au = trim($sheet->getCell( 'AU'.$i )->getValue());
				$value_td_av = trim($sheet->getCell( 'AV'.$i )->getValue());
				$value_td_aw = trim($sheet->getCell( 'AW'.$i )->getValue());
				$value_td_ax = trim($sheet->getCell( 'AX'.$i )->getValue());
				$value_td_ay = trim($sheet->getCell( 'AY'.$i )->getValue());
				$value_td_az = trim($sheet->getCell( 'AZ'.$i )->getValue());
				$value_td_ba = trim($sheet->getCell( 'BA'.$i )->getValue());
				$value_td_bb = trim($sheet->getCell( 'BB'.$i )->getValue());
				$value_td_bc = trim($sheet->getCell( 'BC'.$i )->getValue());
				$value_td_bd = trim($sheet->getCell( 'BD'.$i )->getValue());
				$value_td_be = trim($sheet->getCell( 'BE'.$i )->getValue());
				$value_td_bf = trim($sheet->getCell( 'BF'.$i )->getValue());
				$value_td_bg = trim($sheet->getCell( 'BG'.$i )->getValue());
				$value_td_bh = trim($sheet->getCell( 'BH'.$i )->getValue());
				$value_td_bi = trim($sheet->getCell( 'BI'.$i )->getValue());
				$value_td_bj = trim($sheet->getCell( 'BJ'.$i )->getValue());
				$value_td_bk = trim($sheet->getCell( 'BK'.$i )->getValue());
				$value_td_bl = trim($sheet->getCell( 'BL'.$i )->getValue());
				$value_td_bm = trim($sheet->getCell( 'BM'.$i )->getValue());
				$value_td_bn = trim($sheet->getCell( 'BN'.$i )->getValue());
				$value_td_bo = trim($sheet->getCell( 'BO'.$i )->getValue());
				$value_td_bp = trim($sheet->getCell( 'BP'.$i )->getValue());
				$value_td_bq = trim($sheet->getCell( 'BQ'.$i )->getValue());
				$value_td_br = trim($sheet->getCell( 'BR'.$i )->getValue());
				$value_td_bs = trim($sheet->getCell( 'BS'.$i )->getValue());
				$value_td_bt = trim($sheet->getCell( 'BT'.$i )->getValue());
				$value_td_bv = trim($sheet->getCell( 'BV'.$i )->getValue());
				$value_td_bw = trim($sheet->getCell( 'BW'.$i )->getValue());
				$value_td_bx = trim($sheet->getCell( 'BX'.$i )->getValue());
				$value_td_by = trim($sheet->getCell( 'BY'.$i )->getValue());
				$value_td_bz = trim($sheet->getCell( 'BZ'.$i )->getValue());
				$value_td_ca = trim($sheet->getCell( 'CA'.$i )->getValue());
				$value_td_cb = trim($sheet->getCell( 'CB'.$i )->getValue());
				$value_td_cc = trim($sheet->getCell( 'CC'.$i )->getValue());
				$value_td_cd = trim($sheet->getCell( 'CD'.$i )->getValue());
				$value_td_ce = trim($sheet->getCell( 'CE'.$i )->getValue());
				$value_td_cf = trim($sheet->getCell( 'CF'.$i )->getValue());
				$value_td_cg = trim($sheet->getCell( 'CG'.$i )->getValue());
				$value_td_ch = trim($sheet->getCell( 'CH'.$i )->getValue());
				$value_td_ci = trim($sheet->getCell( 'CI'.$i )->getValue());
				$realRowCount++;
				$importCount++;
				
				$full_name = $value_td_a." ".$value_td_b;
				$user_login = strtolower(trim($value_td_a)).strtolower(trim($value_td_b));
				if (!in_array($full_name, $database_students_fullname)) {
					$table_students_add[] = array(
							'user_login' => $user_login,//登录名设置为full_name
							'user_pass' => sp_password($user_login),//密码设置为full_name
							'create_time' => date('Y-m-d H:i:s',time()),
							'user_status' => 1,//账户状态可用
							'user_type' =>2,//用户状态为学生
							'term_status' =>1,//学季状态
							'full_name' => $full_name,
							$key_td_a => $value_td_a,
							$key_td_b => $value_td_b,
							$key_td_c => $value_td_c,
							$key_td_d => $value_td_d,
							$key_td_e => $value_td_e,
							$key_td_f => $value_td_f,
							$key_td_g => $value_td_g,
							$key_td_h => $value_td_h,
							$key_td_i => $value_td_i,
							$key_td_j => $value_td_j,
							$key_td_k => $value_td_k,
							$key_td_l => $value_td_l,
							$key_td_m => $value_td_m,
							$key_td_n => $value_td_n,
							$key_td_o => $value_td_o,
							$key_td_p => $value_td_p,
							$key_td_q => $value_td_q,
							$key_td_r => $value_td_r,
							$key_td_s => $value_td_s,
							$key_td_t => $value_td_t,
							$key_td_u => $value_td_u,
							$key_td_v => $value_td_v,
							$key_td_w => $value_td_w,
							$key_td_x => $value_td_x,
							$key_td_y => $value_td_y,
							$key_td_z => $value_td_z,
							$key_td_aa => $value_td_aa,
							$key_td_ab => $value_td_ab,
							$key_td_ac => $value_td_ac,
							$key_td_ad => $value_td_ad,
							$key_td_ae => $value_td_ae,
							$key_td_af => $value_td_af,
							$key_td_ag => $value_td_ag,
							$key_td_ah => $value_td_ah,
							$key_td_ai => $value_td_ai,
							$key_td_aj => $value_td_aj,
							$key_td_ak => $value_td_ak,
							$key_td_al => $value_td_al,
							$key_td_am => $value_td_am,
							$key_td_an => $value_td_an,
							$key_td_ao => $value_td_ao,
							$key_td_ap => $value_td_ap,
							$key_td_aq => $value_td_aq,
							$key_td_ar => $value_td_ar,
							$key_td_as => $value_td_as,
							$key_td_at => $value_td_at,
							$key_td_au => $value_td_au,
							$key_td_av => $value_td_av,
							$key_td_aw => $value_td_aw,
							$key_td_ax => $value_td_ax,
							$key_td_ay => $value_td_ay,
							$key_td_az => $value_td_az,
							$key_td_ba => $value_td_ba,
							$key_td_bb => $value_td_bb,
							$key_td_bc => $value_td_bc,
							$key_td_bd => $value_td_bd,
							$key_td_be => $value_td_be,
							$key_td_bf => $value_td_bf,
							$key_td_bg => $value_td_bg,
							$key_td_bh => $value_td_bh,
							$key_td_bi => $value_td_bi,
							$key_td_bj => $value_td_bj,
							$key_td_bk => $value_td_bk,
							$key_td_bl => $value_td_bl,
							$key_td_bm => $value_td_bm,
							$key_td_bn => $value_td_bn,
							$key_td_bo => $value_td_bo,
							$key_td_bp => $value_td_bp,
							$key_td_bq => $value_td_bq,
							$key_td_br => $value_td_br,
							$key_td_bs => $value_td_bs,
							$key_td_bt => $value_td_bt,
							$key_td_bu => $value_td_bu,
							$key_td_bv => $value_td_bv,
							$key_td_bw => $value_td_bw,
							$key_td_bx => $value_td_bx,
							$key_td_by => $value_td_by,
							$key_td_bz => $value_td_bz,
							$key_td_ca => $value_td_ca,
							$key_td_cb => $value_td_cb,
							$key_td_cc => $value_td_cc,
							$key_td_cd => $value_td_cd,
							$key_td_ce => $value_td_ce,
							$key_td_cf => $value_td_cf,
							$key_td_cg => $value_td_cg,
							$key_td_ch => $value_td_ch,
							$key_td_ci => $value_td_ci
					);
				} else {
					$table_students_edit[] = array(
							'full_name' => $full_name,
							$key_td_c => $value_td_c,
							$key_td_d => $value_td_d,
							$key_td_e => $value_td_e,
							$key_td_f => $value_td_f,
							$key_td_g => $value_td_g,
							$key_td_h => $value_td_h,
							$key_td_i => $value_td_i,
							$key_td_j => $value_td_j,
							$key_td_k => $value_td_k,
							$key_td_l => $value_td_l,
							$key_td_m => $value_td_m,
							$key_td_n => $value_td_n,
							$key_td_o => $value_td_o,
							$key_td_p => $value_td_p,
							$key_td_q => $value_td_q,
							$key_td_r => $value_td_r,
							$key_td_s => $value_td_s,
							$key_td_t => $value_td_t,
							$key_td_u => $value_td_u,
							$key_td_v => $value_td_v,
							$key_td_w => $value_td_w,
							$key_td_x => $value_td_x,
							$key_td_y => $value_td_y,
							$key_td_z => $value_td_z,
							$key_td_aa => $value_td_aa,
							$key_td_ab => $value_td_ab,
							$key_td_ac => $value_td_ac,
							$key_td_ad => $value_td_ad,
							$key_td_ae => $value_td_ae,
							$key_td_af => $value_td_af,
							$key_td_ag => $value_td_ag,
							$key_td_ah => $value_td_ah,
							$key_td_ai => $value_td_ai,
							$key_td_aj => $value_td_aj,
							$key_td_ak => $value_td_ak,
							$key_td_al => $value_td_al,
							$key_td_am => $value_td_am,
							$key_td_an => $value_td_an,
							$key_td_ao => $value_td_ao,
							$key_td_ap => $value_td_ap,
							$key_td_aq => $value_td_aq,
							$key_td_ar => $value_td_ar,
							$key_td_as => $value_td_as,
							$key_td_at => $value_td_at,
							$key_td_au => $value_td_au,
							$key_td_av => $value_td_av,
							$key_td_aw => $value_td_aw,
							$key_td_ax => $value_td_ax,
							$key_td_ay => $value_td_ay,
							$key_td_az => $value_td_az,
							$key_td_ba => $value_td_ba,
							$key_td_bb => $value_td_bb,
							$key_td_bc => $value_td_bc,
							$key_td_bd => $value_td_bd,
							$key_td_be => $value_td_be,
							$key_td_bf => $value_td_bf,
							$key_td_bg => $value_td_bg,
							$key_td_bh => $value_td_bh,
							$key_td_bi => $value_td_bi,
							$key_td_bj => $value_td_bj,
							$key_td_bk => $value_td_bk,
							$key_td_bl => $value_td_bl,
							$key_td_bm => $value_td_bm,
							$key_td_bn => $value_td_bn,
							$key_td_bo => $value_td_bo,
							$key_td_bp => $value_td_bp,
							$key_td_bq => $value_td_bq,
							$key_td_br => $value_td_br,
							$key_td_bs => $value_td_bs,
							$key_td_bt => $value_td_bt,
							$key_td_bu => $value_td_bu,
							$key_td_bv => $value_td_bv,
							$key_td_bw => $value_td_bw,
							$key_td_bx => $value_td_bx,
							$key_td_by => $value_td_by,
							$key_td_bz => $value_td_bz,
							$key_td_ca => $value_td_ca,
							$key_td_cb => $value_td_cb,
							$key_td_cc => $value_td_cc,
							$key_td_cd => $value_td_cd,
							$key_td_ce => $value_td_ce,
							$key_td_cf => $value_td_cf,
							$key_td_cg => $value_td_cg,
							$key_td_ch => $value_td_ch,
							$key_td_ci => $value_td_ci
					);
				}
				
			}
			foreach ($table_students_add as $table_student) {
				$this->users_model->add($table_student);//新增学生
			}
			foreach ($table_students_edit as $table_student) {
				$full_name = $table_student['full_name'];
				unset($table_student['full_name']);
				$this->users_model->where(array('full_name' => $full_name))->save($table_student);
			}
			@unlink( $file );
			$this->success( '成功导入'.$importCount.'条学生记录', U( 'student/index' ) );
		} else {
			$this->display();
		}
	}
	
	//上传学生头像
	function photos_upload() {
		if ( IS_POST ) {
			//头像照片
			if(!empty($_POST['avatar_photos_alt']) && !empty($_POST['avatar_photos_url'])){
				foreach ($_POST['avatar_photos_url'] as $key => $url){
					$avatar_photo_url = sp_asset_relative_url($url);
					$avatar_photo_alt = $_POST['avatar_photos_alt'][$key];
					$full_name = explode('.', $avatar_photo_alt);
					$_POST['avatar'] = array("url" => $avatar_photo_url,"alt" => $avatar_photo_alt);
					$avatar = json_encode($_POST['avatar']);
					$this->users_model->where(array('full_name' => $full_name[0]))->save(array('avatar' => $avatar));
				}
			}
			$this->success(L('UPLOAD_SUCCESS'));
		} else {
			$this->display();
		}
	}
	//上传签证和护照
	function pv_upload() {
		if ( IS_POST ) {
			$user_id = (int)$_POST['id'];
			//签证照片
			if(!empty($_POST['visa_photos_alt']) && !empty($_POST['visa_photos_url'])){
				foreach ($_POST['visa_photos_url'] as $key => $url){
					$visa_photo_url = sp_asset_relative_url($url);
					$_POST['passport_visa']['photos']['visa'][]=array("url" => $visa_photo_url,"alt" => $_POST['visa_photos_alt'][$key]);
				}
			}
			//护照照片
			if(!empty($_POST['passport_photos_alt']) && !empty($_POST['passport_photos_url'])){
				foreach ($_POST['passport_photos_url'] as $key => $url){
					$passport_photo_url = sp_asset_relative_url($url);
					$_POST['passport_visa']['photos']['passport'][]=array("url" => $passport_photo_url,"alt" => $_POST['passport_photos_alt'][$key]);
				}
			}
			$_POST['passport_visa'] = json_encode($_POST['passport_visa']);
			unset($_POST['id']);
			$result = $this->users_model->where(array('id' => $user_id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($user_id,2);
		
				$this->success(L('UPLOAD_SUCCESS'));
			} else {
				$this->error(L('UPLOAD_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$user = $this->users_model->find($id);
			$this->assign($user);
				
			$this->display();
		}
	}
	//添加学生信息
	function add() {
		if ( IS_POST ) {
			$_POST['user_login'] = strtolower(trim($_POST['first_name'])).strtolower(trim($_POST['last_name']));
			$_POST['user_pass'] = sp_password($_POST['user_login']);
			$_POST['full_name'] = trim($_POST['first_name'])." ".trim($_POST['last_name']);
			$_POST['user_status'] = 1;
			$_POST['user_type'] = 2;
			$_POST['term_status'] = 1;
			$user_id = $this->users_model->add($_POST);
			if ($user_id) {
				//记录日志
				LogController::log_record($user_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$rd_fields = $this->redundancy_field_model->select();
			$this->assign('rd_fields',$rd_fields);
			$this->display();
		}
	}
	//添加冗余字段
	function field_add() {
		if ( IS_POST ) {
			$field_id = $this->redundancy_field_model->add($_POST);
			if ($field_id) {
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	
	//删除学生信息
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['user_status'] = 0;
			if ( $this->users_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['user_status'] = 1;
			if ( $this->users_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->users_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['user_status'] = 0;
			if ( $this->users_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
	}
	
	//编辑学生信息
	function edit() {
		if ( IS_POST ) {
			$user_id = (int)$_POST['id'];
			$_POST['user_login'] = strtolower(trim($_POST['first_name'])).strtolower(trim($_POST['last_name']));
			$_POST['user_pass'] = sp_password($_POST['user_login']);
			$_POST['full_name'] = trim($_POST['first_name'])." ".trim($_POST['last_name']);
			$result = $this->users_model->where(array('id' => $user_id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($user_id,2);

				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$users = $this->users_model->find($id);
			$this->assign($users);
			
			$rd_fields = $this->redundancy_field_model->select();
			$this->assign('rd_fields',$rd_fields);
			$this->display();
		}
	}
	
	//分配班级
	function class_allot() {
		if ( IS_POST ) {
			$_POST['user_id'] = (int)$_POST['passport_number'];
			$_POST['user_type'] = 2;
			$class_user = $this->class_user_model->where(array('user_id' => $_POST['user_id']))->find();
			$result = $this->class_user_model;
			if ($class_user) {
				$result->where(array('user_id' => $_POST['user_id']))->save($_POST);
			} else {
				$result->add($_POST);
			}
			
			if ($result) {
				$this->success(L('ALLOT_SUCCESS'));
			} else {
				$this->error(L('ALLOT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$user = $this->users_model->find($id);
			$this->assign('user',$user);
			
			$sql = "select c.*,(select count(*) from ".C('DB_PREFIX')."class_user_relationship cu where cu.class_id=c.id and cu.user_type=2) as student_count from ".C('DB_PREFIX')."class c where class_status=1";
			$classes = $this->class_model->query($sql);
			$this->assign('classes',$classes);
			
			$this->display();
		}
	}
	//分配课程
	function course_allot() {
		if ( IS_POST ) {
			$student_id = (int)$_POST['student_id'];
				
			//数据库中此学生已经分配过的原有课程id
			$student_course_ids = array();
			$student_courses = $this->course_student_model->where(array('student_id' => $student_id))->select();
			foreach ($student_courses as $student_course) $student_course_ids[] = $student_course['course_id'];
			//新增课程
			$student_course_add = array();
			//编辑课程
			$student_course_edit = array();
			//不用变动的课程id
			$student_course_edit_ids = array();
			//历史课程id
			$student_course_modify_ids = array();
			//if ($_POST['course_ids']) {
				foreach ($_POST['course_ids'] as $course_id) {
					$course_id = (int)$course_id;
					$student_course = $this->course_student_model->where(array('course_id' => $course_id,'student_id' => $student_id))->find();
					if (empty($student_course)) $student_course_add[] = array('student_id' => $student_id,'course_id' => $course_id,'course_student_status' => 1);
					if (in_array($course_id, $student_course_ids)) $student_course_edit_ids[] = $course_id;
				}
				if ($student_course_edit_ids) {
					foreach ($student_course_edit_ids as $student_course_edit_id) {
						$this->course_student_model->where(array('course_id' => $student_course_edit_id,'student_id' => $student_id))->save(array('course_student_status' => 1));//新增
					}
					
				}
				foreach ($student_course_ids as $student_course_id) {
					if (!in_array($student_course_id, $student_course_edit_ids)) $student_course_modify_ids[] = $student_course_id;
				}
				foreach ($student_course_add as $student_course) {
					$this->course_student_model->add($student_course);//新增
				}
				if ($student_course_modify_ids) {
					$sql_del_ids = implode(',', $student_course_modify_ids);
					$this->course_student_model->where(array('course_id' => array('in',$sql_del_ids),'student_id' => $student_id))->save(array('course_student_status' => 0));//修改变动的课程状态
				}
				$this->success(L('ALLOT_SUCCESS'));
			//} else {
			//	$this->error(L('ALLOT_FAILED'));
			//}
		} else {
			$student_id = (int)I('get.student_id');
			$student = $this->users_model->where(array('id' => $student_id))->find();
			
			$term = trim($student['term']) ;
			$year = trim($student['year']) ;
			$sess = trim($student['session']) ;
			$program = trim($student['program']) ;
			
			$term_year_sess = $year."-".$term."-".$sess."-".$program;
			//专业课	
			$speciality_courses = $this->course_model
							->alias('c')
							->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
							->where(array('tc.term_year_sess' => $term_year_sess,'c.course_status' => 1,'c.course_type' => 1))
							->order('c.course_name asc')
							->select();
			//中文课
			$chinese_courses = $this->course_model
							->alias('c')
							->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
							->where(array('tc.term_year_sess' => $term_year_sess,'c.course_status' => 1,'c.course_type' => 2))
							->order('c.course_name asc')
							->select();
			
			$this->assign('speciality_courses',$speciality_courses);
			$this->assign('chinese_courses',$chinese_courses);
			$this->assign('student',$student);
			$this->assign('term',$term);
			$this->assign('year',$year);
			$this->assign('sess',$sess);
			$this->assign('program',$program);
			$this->assign('term_year_sess',$term_year_sess);
			$this->display();
		}
	}
	//某学生已分配课程列表
	function course_allot_list() {
	
		$student_id = (int)I('get.student_id');
		$student = $this->users_model->where(array('id' => $student_id))->find();
	
		$student_courses = $this->course_model
								->alias('c')
								->field('c.*')
								->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.course_id=c.id')
								->where(array('cs.student_id' => $student_id,'cs.course_student_status' => 1))
								->select();
		//历史记录
		$old_student_courses = $this->course_model
								->alias('c')
								->field('c.*')
								->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.course_id=c.id')
								->where(array('cs.student_id' => $student_id,'cs.course_student_status' => 0))
								->select();
	
		$this->assign( 'student', $student );
		$this->assign( 'student_courses', $student_courses );
		$this->assign( 'old_student_courses', $old_student_courses );
		$this->display();
	}
	function activity_list() {
	
		$student_id = (int)I('get.student_id');
		$student = $this->users_model->where(array('id' => $student_id))->find();
	
		$student_activities = $this->activity_model
								->alias('a')
								->field('a.*,ac.classify_name,u.full_name,asr.activity_tendency')
								->join('__ACTIVITY_STUDENT_RELATIONSHIP__ asr ON asr.activity_id=a.id')
								->join('__ACTIVITY_CLASSIFY__ ac ON ac.id=a.classify_id')
								->join('__USERS__ u ON u.id=a.admin_id')
								->where(array('asr.student_id' => $student_id))
								->select();
	
		$this->assign( 'student', $student );
		$this->assign( 'student_activities', $student_activities );
		$this->display();
	}
	function check_name($table_name){
		$table_name = strtolower(trim($table_name));
		//$table_name = str_replace(":","",$table_name);
		//$table_name = str_replace("/","",$table_name);
		$table_name = str_replace(" ","_",$table_name);
		$table_name = str_replace(",","_",$table_name);
		return $table_name;
	}
	//year-term单选下拉框
	public static function term_years($term_year_val="") {
		//学季
		$where = array();
		$map = array();
		$where[] = "term != '' AND term is not null";
		$where[] = "year != '' AND year is not null";
		$where['_logic'] = "OR";
		$map['_complex'] = $where;
		$map['user_type'] = 2;
		$map['term_status'] = 1;
		$term_years = D('Users')->field('distinct term,year')->where($map)->order('year desc')->select();
		$term_year_html = " <option value=''>Year-Term</option>";
		foreach ($term_years as $term_year) {
			$term_year_str = trim($term_year['year'])."-".trim($term_year['term']);
			$term_year_html .= "<option";
			if ($term_year_val == $term_year_str) {
				$term_year_html .= " selected";
			}
			$term_year_html .= " value='".$term_year_str."'>".$term_year_str."</option>";
		}
		return $term_year_html;
	}
}