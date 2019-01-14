<?php
/** 
 * 后台教师管理
 * @author 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class PartTimeTeacherController extends AdminbaseController {

	private $user_model;
	private $major_model;
	private $part_time_teacher_model;

	function _initialize() {
		parent::_initialize();
		$this->major_model = D( 'TeacherMajor' );
		$this->part_time_teacher_model = D( 'PartTimeTeacher' );
	}
	
	//兼职教师列表
	function index() {
		$where = array();
		//名称搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
		
		if ( !empty($keyword) ) {
			$where['first_name'] = array('like',"%$keyword%",'and');
			$where['last_name'] = array('like',"%$keyword%",'and');
			$where['_logic'] = 'or';
		}
		$count = $this->part_time_teacher_model->alias('ptt')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->part_time_teacher_model->alias('ptt')->where($where)->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	
	//添加教师
	function add() {
		if ( IS_POST ) {
			$_POST['status'] = 1;
			$part_time_teacher_id = $this->part_time_teacher_model->add($_POST);
			if ($part_time_teacher_id) {
				//记录日志
				LogController::log_record($part_time_teacher,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
	
	//批量导入教师信息
	function upload() {
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
			require_once 'today/excel/PHPExcel.php';
	
			$reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
			$obj = $reader->load( $file );
			$sheet = $obj->getSheet(0);
			$rowCount = $sheet->getHighestRow();
			$colCount = $sheet->getHighestColumn();
			$realRowCount = 0;
			$importCount = 0;
			//新增教师
			$part_time_teacher_info_add = array();
			for ( $i = 2; $i <= $rowCount; $i++ ) {
				$first_name = $sheet->getCell( 'A'.$i )->getValue();
				if (empty($first_name)) $this->error('第'.$i.'行First Name为空');
				$last_name = $sheet->getCell( 'B'.$i )->getValue();
				if (empty($last_name)) $this->error('第'.$i.'行Last Name为空');
				$mobile_phone = $sheet->getCell( 'C'.$i )->getValue();
				if (empty($mobile_phone)) $this->error('第'.$i.'行Mobile Phone为空');
				$teacher_email = $sheet->getCell( 'D'.$i )->getValue();
				if (empty($teacher_email)) $this->error('第'.$i.'行Teacher Email为空');
				$teacher_college = $sheet->getCell( 'E'.$i )->getValue();
				if (empty($teacher_college)) $this->error('第'.$i.'行Teacher College为空');
				$gender = $sheet->getCell( 'F'.$i )->getValue();
				if (empty($gender)) $this->error('第'.$i.'行Gender为空');
				$year_of_enrollment = $sheet->getCell( 'G'.$i )->getValue();
				if (empty($year_of_enrollment)) $this->error('第'.$i.'行Year Of Enrollment为空');
				$teaching_time = $sheet->getCell( 'H'.$i )->getValue();
				if (empty($teaching_time)) $this->error('第'.$i.'行Teaching Time为空');
				$teacher_address = $sheet->getCell( 'I'.$i )->getValue();
				if (empty($teacher_address)) $this->error('第'.$i.'行Teacher Address为空');
				$date_of_birth = $sheet->getCell( 'J'.$i )->getValue();
				if (empty($date_of_birth)) $this->error('第'.$i.'行Date Of Birth为空');
				$native_place = $sheet->getCell( 'K'.$i )->getValue();
				if (empty($native_place)) $this->error('第'.$i.'行Native Place为空');
				$country_of_citizenship = $sheet->getCell( 'L'.$i )->getValue();
				if (empty($country_of_citizenship)) $this->error('第'.$i.'行Country Of Citizenship为空');
				$full_name = $first_name." ".$last_name;
	
				$realRowCount++;
				$importCount++;
				$part_time_teacher_info_add[] = array( 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time()), 'first_name' => $first_name, 'last_name' => $last_name, 
						'mobile_phone' => $mobile_phone, 'teacher_email' => $teacher_email, 'teacher_college' => $teacher_college,
						'year_of_enrollment' => $year_of_enrollment, 'teaching_time' => $teaching_time, 'teacher_address' => $teacher_address,
						'date_of_birth' => $date_of_birth, 'native_place' => $native_place, 'gender' => $gender, 'country_of_citizenship' => $country_of_citizenship
				);
			}
			foreach ($part_time_teacher_info_add as $part_time_teacher_info) {
				$this->part_time_teacher_model->add($part_time_teacher_info);//新增教师
			}
			@unlink( $file );
			$this->success( '成功导入'.$importCount.'条教师记录', U( 'parttimeteacher/index' ) );
		} else {
			$this->display();
		}
	}
	
	 
	//编辑兼职教师信息
	function edit() {
		if ( IS_POST ) {
			$part_time_teacher_id = (int)$_POST['id'];
			$result = $this->part_time_teacher_model->where(array('id' => $part_time_teacher_id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($part_time_teacher_id,2);
	
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$part_time_teacher = $this->part_time_teacher_model->find($id);
			$this->assign($part_time_teacher);
			$this->display();
		}
	}
	
	
	//兼职教师列表
	function moredetails() {
		$where = array();
		//名称搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
	
		if ( !empty($keyword) ) {
			$where['first_name'] = array('like',"%$keyword%",'and');
			$where['last_name'] = array('like',"%$keyword%",'and');
			$where['_logic'] = 'or';
		}
		$count = $this->part_time_teacher_model->alias('ptt')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->part_time_teacher_model->alias('ptt')->where($where)->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	
	
	

}