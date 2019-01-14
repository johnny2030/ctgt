<?php
/** 
 * 后台课程&学季
 * 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Org\Util\String;

class CourseController extends AdminbaseController {

	private $course_model;
	private $time_model;
	private $requirement_model;
	private $user_model;
	private $course_mentor_model;
	private $term_course_model;
	private $part_time_teacher_model;
	private $time_zones_model;
	
	private $studentCols = array(
			array( 20, 'First Name', 'FFFFFF' ),
			array( 20, 'Last Name', 'FFFFFF' )
	);
	
	private $courseCols = array(
			array( 20, '课程代码', 'FFFFFF' ),
			array( 20, '课程名称', 'FFFFFF' ),
			array( 20, '课程分类', 'FFFFFF' ),
			array( 20, '授课地点', 'FFFFFF' ),
			array( 20, '授课时间', 'FFFFFF' ),
			array( 20, '学生人数', 'FFFFFF' )
	);
	

	function _initialize() {
		parent::_initialize();

		$this->course_model = D( 'Course' );
		$this->time_model = D('CourseTime');
		$this->requirement_model = D('CourseRequirement');
		$this->user_model = D('Users');
		$this->course_mentor_model = D('CourseMentorRelationship');
		$this->term_course_model = D('TermCourseRelationship');
		$this->part_time_teacher_model = D('PartTimeTeacher');
		$this->time_zones_model = D('TimeZones');
	}
	//课程列表
	function index() {
		$where = array();
		
		//课程代码/名称/主导教师搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
		$course_type=I('request.course_type');
		$this->assign( 'course_type', $course_type );
		
		if ( !empty($keyword) ) $where['course_code|course_name'] = array('like',"%$keyword%");
		if ($course_type) $where['course_type'] = $course_type;
		
		$where['course_status'] = array('neq',2);
		$count = $this->course_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->course_model
				->where($where)
				->limit( $page->firstRow, $page->listRows )
				->order("listorder asc")
				->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加课程
	function add() {
		if ( IS_POST ) {
			//语言
			//$configs = include 'data/conf/config.php';
			//$lang = $configs['DEFAULT_LANG'];
			//授课时间
			
			/* $course_times = array();
			foreach ($_POST['course_start_time'] as $k => $course_start_time){
				$course_start_time = $course_start_time;
				$course_end_time = $_POST['course_end_time'][$k];
				
				if ($course_start_time) $course_times[] = array('course_start_time' => $course_start_time,'course_end_time' => $course_end_time);
				if ($course_end_time < $course_start_time) {
					$this->error(L('COURSE_MSG1'));
				}
			} */
			$courses = $this->course_model->where(array('course_status' => array('neq',2)))->select();
			foreach ($courses as $course) {
				if (trim($_POST['course_code']) == trim($course['course_code'])) $this->error('此课程代码已经存在');
				if (trim($_POST['course_name']) == trim($course['course_name'])) $this->error('此课程名称已经存在');
			}
			//授课时间
			$course_times = array();
			$index = 0;
			foreach ($_POST['course_week'] as $k => $course_week){
				$course_week = $course_week;
				$time_zone_count = (int)$_POST['time_zone_count'][$k];
				$time_zone_ids_arr = array();
				for ($i=$index; $i<$index+$time_zone_count; $i++) $time_zone_ids_arr[] = (int)($_POST['timezones_id'][$i]);
				$index += $time_zone_count;
			
				$time_zone_ids = implode(',',$time_zone_ids_arr);
			
				if ($course_week || $time_zone_ids) $course_times[] = array('time_zone_ids' => $time_zone_ids,'course_week' => $course_week);
			}
			//Course Requirement Key
			$course_requirements = array();
			if (implode('', $_POST['requirement_description']) && implode('', $_POST['requirement_key']) && implode('', $_POST['requirement_grade_percent']) && implode('', $_POST['requirement_type']) && implode('', $_POST['requirement_points_system'])) {
				foreach ($_POST['requirement_description'] as $k => $requirement_description) {
					$requirement_description = trim($requirement_description);
					$requirement_key = trim($_POST['requirement_key'][$k]);
					$requirement_grade_percent = (int)$_POST['requirement_grade_percent'][$k];
					$requirement_type = (int)$_POST['requirement_type'][$k];
					$requirement_points_system = (int)$_POST['requirement_points_system'][$k];
					if ($requirement_description && $requirement_key && $requirement_grade_percent && $requirement_type && $requirement_points_system) {
						$course_requirements[] = array('requirement_description' => $requirement_description,'requirement_key' => $requirement_key,'requirement_grade_percent' => $requirement_grade_percent,'requirement_type' => $requirement_type,'requirement_points_system' => $requirement_points_system);
					} else {
						$this->error('请查看课程要求每一项是否填写或选择');
					}
				}
			}
			
			if ($_POST['select_style'] == 1) {
				if (empty($_POST['headteacher_id'])) {
					$this->error(L('COURSE_MSG2'));
				} else {
					$_POST['headteacher_name'] = "";
				}
			}
			if ($_POST['select_style'] == 2) {
				if (empty($_POST['headteacher_name'])) {
					$this->error(L('COURSE_MSG2'));
				} else {
					$_POST['headteacher_id'] = 0;
				}
			}
			unset($_POST['select_style']);
			
			$_POST['course_content'] = htmlspecialchars_decode($_POST['course_content']);
			$_POST['course_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['parttimeteacher_id'] = implode(',', $_POST['parttimeteacher_id']);
			//$_POST['timezones_id'] = implode(',', $_POST['timezones_id']);
			
			$course_id = $this->course_model->add($_POST);
			if ($course_id) {
				foreach ($course_times as $course_time) {
					$course_time['course_id'] = $course_id;
					$this->time_model->add($course_time);
				}
				if ($course_requirements) {
					foreach ($course_requirements as $course_requirement) {
						$course_requirement['course_id'] = $course_id;
						$this->requirement_model->add($course_requirement);
					}
				}
				/* foreach ($course_times as $course_time) {
					$course_time['course_id'] = $course_id;
					$this->time_model->add($course_time);
				}
				foreach ($_POST['mentor_id'] as $mentor_id) {
					$this->course_mentor_model->add(array('course_id' => $course_id,'mentor_id' => $mentor_id));
				} */
				//记录日志
				LogController::log_record($course_id,1);
				
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			
			$lead_teachers = $this->user_model
						->alias('u')
						->field('u.id as teacher_id,u.full_name')
						->join('__ROLE_USER__ ru ON ru.user_id=u.id')
						->where(array('ru.role_id' => 3,'u.user_status' => 1))
						->select();
			$this->assign('lead_teachers',$lead_teachers);
			
			$part_time_teachers = $this->user_model
								->alias('u')
								->field('u.id as teacher_id,u.full_name')
								->join('__ROLE_USER__ ru ON ru.user_id=u.id')
								->where(array('ru.role_id' => 13,'u.user_status' => 1))
								->select();
			$this->assign('part_time_teachers',$part_time_teachers);
			
			$time_zones = $this->time_zones_model->where(array('time_status' =>1))->order('time_zones asc')->select();
			$this->assign('time_zones',$time_zones);
			
			$this->display();
		}
	}
	//编辑课程
	function edit() {
		if ( IS_POST ) {
			$course_id = (int)$_REQUEST['id'];
			
			//授课时间
			/* $course_times = $this->time_model->where(array('course_id' => $course_id))->select();
			$course_time_ids = array();
			foreach ($course_times as $course_time) $course_time_ids[] = $course_time['id']; 
				
			$course_time_add = array();
			$course_time_edit = array();
			$course_time_del = array();
			$course_time_edit_id = array();
			foreach ($_POST['course_start_time'] as $k => $course_start_time){
				$course_time_id = (int)$_POST['course_time_id'][$k];
				$course_start_time = $course_start_time;
				$course_end_time = $_POST['course_end_time'][$k];
				
				if ($course_start_time) {
					if (in_array($course_time_id, $course_time_ids)){
						//修改
						$course_time_edit[] = array('id' => $course_time_id,'course_id' => $course_id,'course_start_time' => $course_start_time,'course_end_time' => $course_end_time);		
						$course_time_edit_id[] = $course_time_id;
					} else {
						//新增
						$course_time_add[] = array('course_id' => $course_id,'course_start_time' => $course_start_time,'course_end_time' => $course_end_time);
					}
				}
				if ($course_end_time < $course_start_time) {
					$this->error(L('COURSE_MSG1'));
				}
			}
			foreach ($course_time_ids as $course_time_id) {
				if (!in_array($course_time_id, $course_time_edit_id)) $course_time_del[] = $course_time_id;
			} */
			
			$courses = $this->course_model->where(array('course_status' => array('neq',2)))->select();
			foreach ($courses as $course) {
				if ($course['id'] != $course_id) {
					if (trim($_POST['course_name']) == trim($course['course_name'])) $this->error('此课程名称已经存在');
				}
			}
			//授课时间
			$course_times = $this->time_model->where(array('course_id' => $course_id))->select();
			$course_time_ids = array();
			foreach ($course_times as $course_time) $course_time_ids[] = $course_time['id'];
			
			$course_time_add = array();
			$course_time_edit = array();
			$course_time_del_ids = array();
			$course_time_edit_ids = array();
			$index = 0;
			foreach ($_POST['course_week'] as $k => $course_week){
				$course_time_id = (int)$_POST['course_time_id'][$k];
				$course_week = $course_week;
				$time_zone_count = (int)$_POST['time_zone_count'][$k];
				$time_zone_ids_arr = array();
				for ($i=$index; $i<$index+$time_zone_count; $i++) $time_zone_ids_arr[] = (int)($_POST['timezones_id'][$i]);
				$index += $time_zone_count;
			
				$time_zone_ids = implode(',',$time_zone_ids_arr);
			
				if ($course_week || $time_zone_ids) {
					if (in_array($course_time_id, $course_time_ids)){
						//修改
						$course_time_edit[] = array('id' => $course_time_id,'course_id' => $course_id,'time_zone_ids' => $time_zone_ids,'course_week' => $course_week);
						$course_time_edit_ids[] = $course_time_id;
					} else {
						//新增
						$course_time_add[] = array('course_id' => $course_id,'time_zone_ids' => $time_zone_ids,'course_week' => $course_week);
					}
				}
			}
			foreach ($course_time_ids as $course_time_id) {
				if (!in_array($course_time_id, $course_time_edit_ids)) $course_time_del_ids[] = $course_time_id;
			}
			//Course Requirement Key
			$course_requirements = $this->requirement_model->where(array("course_id" => $course_id))->select();
			$course_requirement_ids = array();
			foreach ($course_requirements as $course_requirement) $course_requirement_ids[] = $course_requirement['id'];
			
			$course_requirement_add = array();
			$course_requirement_edit = array();
			$course_requirement_del = array();
			$course_requirement_edit_id = array();
			if (implode('', $_POST['requirement_description']) && implode('', $_POST['requirement_key']) && implode('', $_POST['requirement_grade_percent']) && implode('', $_POST['requirement_type']) && implode('', $_POST['requirement_points_system'])) {
				foreach ($_POST['requirement_description'] as $k => $requirement_description) {
					$requirement_description = trim($requirement_description);
					$requirement_key = trim($_POST['requirement_key'][$k]);
					$requirement_grade_percent = (int)$_POST['requirement_grade_percent'][$k];
					$requirement_id = (int)$_POST['requirement_id'][$k];
					$requirement_type = (int)$_POST['requirement_type'][$k];
					$requirement_points_system = (int)$_POST['requirement_points_system'][$k];
					if ($requirement_description && $requirement_key && $requirement_grade_percent && $requirement_type && $requirement_points_system) {
						if (in_array($requirement_id, $course_requirement_ids)) {
							//edit
							$course_requirement_edit[] = array('id' => $requirement_id,'course_id' => $course_id,'requirement_description' => $requirement_description,'requirement_key' => $requirement_key,'requirement_grade_percent' => $requirement_grade_percent,'requirement_type' => $requirement_type,'requirement_points_system' => $requirement_points_system);
							$course_requirement_edit_id[] = $requirement_id;
						} else {
							//add
							$course_requirement_add[] = array('course_id' =>$course_id,'requirement_description' => $requirement_description,'requirement_key' => $requirement_key,'requirement_grade_percent' => $requirement_grade_percent,'requirement_type' => $requirement_type,'requirement_points_system' => $requirement_points_system);
						}
					} else {
						$this->error('请查看课程要求每一项是否填写或选择');
					}
				}
				foreach ($course_requirement_ids as $course_requirement_id) {
					if (!in_array($course_requirement_id, $course_requirement_edit_id)) $course_requirement_del[] = $course_requirement_id;
				}
			} else {
				$this->requirement_model->where(array('course_id' => $course_id))->delete();
			}
			//Course Mentor
			/* $course_mentors = $this->course_mentor_model->where(array("course_id" => $course_id))->select();
			$mentor_ids = array();
			foreach ($course_mentors as $course_mentor) $mentor_ids[] = $course_mentor['mentor_id'];
				
			$course_mentor_add = array();
			$course_mentor_edit = array();
			$course_mentor_del = array();
			$course_mentor_edit_ids = array();
			foreach ($_POST['mentor_id'] as $mentor_id) {
				if ($_POST['mentor_id']) {
					if (in_array($mentor_id, $mentor_ids)) {
						//edit
						$course_mentor_edit_ids[] = $mentor_id;
					} else {
						//add
						$course_mentor_add[] = array('course_id' =>$course_id,'mentor_id' => $mentor_id);
					}
				}
			}
			foreach ($mentor_ids as $mentor_id) {
				if (!in_array($mentor_id, $course_mentor_edit_ids)) $course_mentor_del[] = $mentor_id;
			} */
			
			if ($_POST['select_style'] == 1) {
				if (empty($_POST['headteacher_id'])) {
					$this->error(L('COURSE_MSG2'));
				} else {
					$_POST['headteacher_name'] = "";
				}
			}
			if ($_POST['select_style'] == 2) {
				if (empty($_POST['headteacher_name'])) {
					$this->error(L('COURSE_MSG2'));
				} else {
					$_POST['headteacher_id'] = 0;
				}
			}
			unset($_POST['select_style']);
				
			$_POST['course_content'] = htmlspecialchars_decode($_POST['course_content']);
			$_POST['course_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['parttimeteacher_id'] = implode(',', $_POST['parttimeteacher_id']);
			//$_POST['timezones_id'] = implode(',', $_POST['timezones_id']);
				
			$result = $this->course_model->where(array('id' => $course_id))->save($_POST);
			if ($result) {
				/* foreach ($course_time_add as $course_time) $this->time_model->add($course_time);//新增授课时间
				foreach ($course_time_edit as $course_time) {//在原有授课时间上修改
					$course_time_id = $course_time['id'];
					unset($course_time['id']);
					$this->time_model->where(array('id' => $course_time_id))->save($course_time);
				}
				if ($course_time_del) {//本来有的授课时间，在编辑过程中删除
					$this->time_model->where("id in (".implode(',',$course_time_del).")")->delete();
				} */
				//授课时间
				foreach ($course_time_add as $course_time) $this->time_model->add($course_time);//新增
				foreach ($course_time_edit as $course_time) {//在原有上修改
					$course_time_id = $course_time['id'];
					unset($course_time['id']);
					$this->time_model->where(array('id' => $course_time_id))->save($course_time);
				}
				if ($course_time_del_ids) {//本来有的，在编辑过程中删除
					$this->time_model->where("id in (".implode(',',$course_time_del_ids).")")->delete();
				}
				
				if ($course_requirement_add) {
					foreach ($course_requirement_add as $course_requirement) $this->requirement_model->add($course_requirement);
				}
				if ($course_requirement_edit) {
					foreach ($course_requirement_edit as $course_requirement) {
						$course_requirement_id = $course_requirement['id'];
						unset($course_requirement['id']);
						$this->requirement_model->where(array('id' => $course_requirement_id))->save($course_requirement);
					}
				}
				if ($course_requirement_del) {
					$this->requirement_model->where("id in (".implode(',',$course_requirement_del).")")->delete();
				}
				
				/* foreach ($course_mentor_add as $course_mentor) $this->course_mentor_model->add($course_mentor);
				if ($course_mentor_del) {
					$this->course_mentor_model->where("mentor_id in (".implode(',',$course_mentor_del).")")->delete();
				} */
				//记录日志
				LogController::log_record($course_id,2);
				
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$course = $this->course_model->find($id);
			$this->assign($course);
			
			//授课时间
			/* $course_times = $this->time_model->where(array('course_id' => $course['id']))->select();
			$this->assign('course_times',$course_times); */
			//Course Requirement Key
			$course_requirements = $this->requirement_model->where(array('course_id' => $course['id']))->select();
			$this->assign('course_requirements',$course_requirements);
			
			$lead_teachers = $this->user_model
								->alias('u')
								->field('u.id as teacher_id,u.full_name')
								->join('__ROLE_USER__ ru ON ru.user_id=u.id')
								->where(array('ru.role_id' => 3,'u.user_status' => 1))
								->select();
			$this->assign('lead_teachers',$lead_teachers);
				
			$part_time_teachers = $this->user_model
									->alias('u')
									->field('u.id as teacher_id,u.full_name')
									->join('__ROLE_USER__ ru ON ru.user_id=u.id')
									->where(array('ru.role_id' => 13,'u.user_status' => 1))
									->select();
			$this->assign('part_time_teachers',$part_time_teachers);
			
			$course_times = $this->time_model->where(array('course_id' => $id))->select();
			$this->assign('course_times',$course_times);
			
			$time_zones = $this->time_zones_model->where(array('time_status' => 1))->order('time_zones asc')->select();
			$this->assign('time_zones',$time_zones);
				
			$this->display();
		}
	}
	//复制课程
	function copy_course() {
		$id = (int)$_GET['id'];
		
		//复制课程表中的数据
		$find_course = $this->course_model
							->field('headteacher_id,parttimeteacher_id,course_code,course_name,course_description,course_content,course_address,course_credit,smeta,course_academic_hour,course_type')
							->where(array('id'=>$id))
							->find();
		if($find_course){
			$find_course['course_modify_time'] = date('Y-m-d H:i:s');
			$find_course['course_status'] = 1;
			$course_id = $this->course_model->add($find_course);
			if ($course_id) {
				//复制关联表课程要求中的数据
				$find_course_requirements = $this->requirement_model->field('requirement_key,requirement_description,requirement_grade_percent,requirement_type,requirement_points_system')->where(array('course_id' => $id))->select();
				if ($find_course_requirements) {
					foreach ($find_course_requirements as $find_course_requirement) {
						$find_course_requirement['course_id'] = $course_id;
						$course_requirement_id = $this->requirement_model->add($find_course_requirement);
					}
				}
				//复制关联表授课时间中的数据
				$find_course_times = $this->time_model->field('time_zone_ids,course_week')->where(array('course_id' => $id))->select();
				if ($find_course_times) {
					foreach ($find_course_times as $find_course_time) {
						$find_course_time['course_id'] = $course_id;
						$course_time_id = $this->time_model->add($find_course_time);
					}
				}
				//记录日志
				LogController::log_record($course_id,1);
					
				$this->success(L('COPY_SUCCESS'),U('course/edit',array('id' => $course_id)));
			}else {
				$this->error(L('COPY_FAILED'));
			}
		}
	}
	//从数据库users表中检索term
	function term_search() {
		$where = array();
		$map = array();
		$where[] = "term != '' AND term is not null";
		$where[] = "year != '' AND year is not null";
		$where[] = "session != '' AND session is not null";
		$where[] = "program != '' AND program is not null";
		$where['_logic'] = "OR";
		$map['_complex'] = $where;
		$map['user_type'] = 2;
		$count = $this->user_model->field('distinct term,year,session,program,term_status')->where($map)->select();
		$page = $this->page(count($count), 10);
		$list = $this->user_model->field('distinct term,year,session,program,term_status')->where($map)->order('term_status desc,year desc')->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//roster
	function roster() {
		$where = array();
		$map = array();
		$where[] = "term != '' AND term is not null";
		$where[] = "year != '' AND year is not null";
		$where[] = "session != '' AND session is not null";
		$where['_logic'] = "OR";
		$map['_complex'] = $where;
		$map['user_type'] = 2;
		$count = $this->user_model->field('distinct term,year,session,term_status')->where($map)->select();
		$page = $this->page(count($count), 10);
		$list = $this->user_model->field('distinct term,year,session,term_status')->where($map)->order('term_status desc,year desc')->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//精确到session的课程列表
	function roster_list() {
	
		$where = array();
		$year = str_replace('*', '&', trim(I( 'get.year' ))) ;
		$term = str_replace('*', '&', trim(I( 'get.term' ))) ;
		$sess = str_replace('*', '&', trim(I( 'get.session' ))) ;
		if($year) $where['year'] = $year;
		if($term) $where['term'] = $term;
		if($sess) {
			$where['session'] = $sess;
			$term_year_sess = $year.'-'.$term.'-'.$sess;
		} else {
			$where['session'] = '';
			$term_year_sess = $year.'-'.$term;
		}
		$course_ids = array();
		$term_courses = $this->term_course_model->field('distinct course_id')->where($where)->select();
		foreach ($term_courses as $term_course) $course_ids[] = $term_course['course_id'];
		
		$courses = $this->course_model->where(array('id' => array('in',implode(',', $course_ids))))->order('course_code asc,course_name asc')->select();
	
		$this->assign( 'term', $term );
		$this->assign( 'year', $year );
		$this->assign( 'sess', $sess );
		$this->assign( 'courses', $courses );
		if ($_GET['cmd'] == 'export') {
			//导出
			set_time_limit(0);
				
			$xls_file_name = $term_year_sess."_Courses_".date('Y-m-d',time());
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )
			->setLastModifiedBy( 'CIEE' )
			->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
				
			$rowIndex = 1;  //行
			foreach ( $courses as $course ) {
				$course_times = $this->time_model->where(array('course_id' => $course['id']))->select();
				$course_time_arr = array();
				foreach ($course_times as $course_time) {
					$course_time_str = "";
					if ($course_time['course_week'] == 0) $course_time_str .= 'Weekdays';
					if ($course_time['course_week'] == 1) $course_time_str .= 'Monday';
					if ($course_time['course_week'] == 2) $course_time_str .= 'Tuesday';
					if ($course_time['course_week'] == 3) $course_time_str .= 'Wednesday';
					if ($course_time['course_week'] == 4) $course_time_str .= 'Thursday';
					if ($course_time['course_week'] == 5) $course_time_str .= 'Friday';
					$time_zones = $this->time_zones_model->where(array('id' => array('in',$course_time['time_zone_ids'])))->order('time_zones asc')->select();
					foreach ($time_zones as $time_zone) {
						$course_time_str .= ' '.$time_zone['time_zones'];
					}
					$course_time_arr[] = $course_time_str;
				}
		
				//报名学生人数
				$where1 = array();
				$where1['csr.course_id'] = $course['id'];
				$where1['csr.course_student_status'] = 1;
				if($year) $where1['u.year'] = $year;
				if($term) $where1['u.term'] = $term;
				if($sess) {
					$where1['u.session'] = $sess;
				} else {
					$where1['u.session'] = '';
				}
				$course_students = $this->user_model->alias('u')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.student_id=u.id')->where($where1)->select();
				$course_student_count = count($course_students);
				
				$colIndex = -1;//列
				$rowIndex++;
					
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course['course_code'] );
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course['course_name'] );
				if ($course['course_type'] == 1) {
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, L('COURSE_SPECIALIZED') );
				}
				if ($course['course_type'] == 2) {
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, L('COURSE_CHINESE') );
				}
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course['course_address'] );
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, implode(';', $course_time_arr) );
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course_student_count );
		
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(array('font' => array( 'size' => 10,'name' => 'Arial' )));
			}
				
			//表头
			$colIndex = -1;
			$cols = 'courseCols';
			foreach ( $this->$cols as $col ) {
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1', $col[1] );
				$sheet->getColumnDimension( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ) )->setWidth( $col[0] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->getStartColor()->setARGB( 'FF'.$col[2] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getAlignment()->setWrapText( true );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->applyFromArray(
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
	function ban() {
		$year = trim($_GET['year']);
		$term = trim($_GET['term']);
		$sess = trim($_GET['session']);
		$program = trim($_GET['program']);
		$where = array();
		if($year) $where['year'] = $year;
		if($term) $where['term'] = $term;
		if($sess) $where['session'] = $sess;
		if($program) $where['program'] = $program;
		$data['term_status'] = 0;
		if ( $this->user_model->where( $where )->save( $data ) ) {
			$this->success( '隐藏成功！' );
		} else {
			$this->error( '隐藏失败！' );
		}
	}
	
	function cancelban() {
		$year = trim($_GET['year']);
		$term = trim($_GET['term']);
		$sess = trim($_GET['session']);
		$program = trim($_GET['program']);
		$where = array();
		if($year) $where['year'] = $year;
		if($term) $where['term'] = $term;
		if($sess) $where['session'] = $sess;
		if($program) $where['program'] = $program;
		$data['term_status'] = 1;
		if ( $this->user_model->where( $where )->save( $data ) ) {
			$this->success( '显示成功！' );
		} else {
			$this->error( '显示失败！' );
		}
	}
	//分配课程
	function allot() {
		if ( IS_POST ) {
			
			$term = trim($_POST['term']);
			$year = trim($_POST['year']);
			$sess = trim($_POST['session']);
			$program = trim($_POST['program']);
			$term_year_sess = $year."-".$term."-".$sess."-".$program;
			
			$this->term_course_model->where(array('term_year_sess' => $term_year_sess))->delete();//删除该学季下所有课程id
			
			//新增课程id
			$term_course_add = array();
			if ($_POST['course_ids']) {
				foreach ($_POST['course_ids'] as $course_id) {
					$course_id = (int)$course_id;
					if ($sess) {
						$term_course_add[] = array('term_year_sess' => $term_year_sess,'course_id' => $course_id,'year' => $year,'term' => $term,'session' => $session,'program' => $program);
					} else {
						$term_course_add[] = array('term_year_sess' => $term_year_sess,'course_id' => $course_id,'year' => $year,'term' => $term,'program' => $program);
					}
				}
				foreach ($term_course_add as $term_course) {
					$this->term_course_model->add($term_course);//新增
				}
				$this->success(L('ALLOT_SUCCESS'));
			} else {
				$this->error(L('ALLOT_FAILED'));
			}
		} else {
			$term = trim($_GET['term']);
			$year = trim($_GET['year']);
			$sess = trim($_GET['session']);
			$program = trim($_GET['program']);
			
			$speciality_courses = $this->course_model->where(array('course_status' => 1,'course_type' => 1))->order('course_name asc')->select();
			$chinese_courses = $this->course_model->where(array('course_status' => 1,'course_type' => 2))->order('course_name asc')->select();
			$this->assign('speciality_courses',$speciality_courses);
			$this->assign('chinese_courses',$chinese_courses);
			
			$this->assign('term',$term);
			$this->assign('year',$year);
			$this->assign('sess',$sess);
			$this->assign('program',$program);
				
			$this->display();
		}
	}
	//某学季已分配课程列表
	function allot_list() {
	
		$term = str_replace('*', '&', trim(I( 'get.term' ))) ;
		$year = str_replace('*', '&', trim(I( 'get.year' ))) ;
		$sess = str_replace('*', '&', trim(I( 'get.session' ))) ;
		$program = str_replace('*', '&', trim(I( 'get.program' ))) ;
		$term_year_sess = $year."-".$term."-".$sess."-".$program;
		$term_courses = $this->course_model
						->alias('c')
						->field('c.id,c.course_name,c.course_code,c.course_type,c.course_address,c.timezones_id')
						->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
						->where(array('tc.term_year_sess' => $term_year_sess,'c.course_status' => 1))
						->order('c.course_code asc,c.course_name asc')
						->select();
		
		$this->assign( 'term', $term );
		$this->assign( 'year', $year );
		$this->assign( 'sess', $sess );
		$this->assign( 'program', $program );
		$this->assign( 'term_courses', $term_courses );
		$this->assign( 'term_year_sess', $term_year_sess );
		if ($_GET['cmd'] == 'export') {
			//导出
			set_time_limit(0);
			
			$xls_file_name = $term_year_sess."_Courses_".date('Y-m-d',time());
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )
			->setLastModifiedBy( 'CIEE' )
			->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
			
			$rowIndex = 1;  //行
			foreach ( $term_courses as $term_course ) {
				$course_times = $this->time_model->where(array('course_id' => $term_course['id']))->select();
				$course_time_arr = array();
				foreach ($course_times as $course_time) {
					$course_time_str = "";
					if ($course_time['course_week'] == 0) $course_time_str .= 'Weekdays';
					if ($course_time['course_week'] == 1) $course_time_str .= 'Monday';
					if ($course_time['course_week'] == 2) $course_time_str .= 'Tuesday';
					if ($course_time['course_week'] == 3) $course_time_str .= 'Wednesday';
					if ($course_time['course_week'] == 4) $course_time_str .= 'Thursday';
					if ($course_time['course_week'] == 5) $course_time_str .= 'Friday';
					$time_zones = $this->time_zones_model->where(array('id' => array('in',$course_time['time_zone_ids'])))->order('time_zones asc')->select();
					foreach ($time_zones as $time_zone) {
						$course_time_str .= ' '.$time_zone['time_zones'];
					}
					$course_time_arr[] = $course_time_str;
				}
				
				$colIndex = -1;//列
				$rowIndex++;
			
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $term_course['course_code'] );  
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $term_course['course_name'] );
				if ($term_course['course_type'] == 1) {
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, L('COURSE_SPECIALIZED') );
				}
				if ($term_course['course_type'] == 2) {
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, L('COURSE_CHINESE') );
				}
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $term_course['course_address'] );
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, implode(';', $course_time_arr) );
				
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(array('font' => array( 'size' => 10,'name' => 'Arial' )));
			}
			
			//表头
			$colIndex = -1;
			$cols = 'courseCols';
			foreach ( $this->$cols as $col ) {
				$colIndex++;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1', $col[1] );
				$sheet->getColumnDimension( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ) )->setWidth( $col[0] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->getStartColor()->setARGB( 'FF'.$col[2] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->getColor()->setARGB( 'FF000000' );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getAlignment()->setWrapText( true );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->applyFromArray(
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
	//某学季下课程已报名学生列表
	function allot_student_list() {
		$where = array();
		$id = (int)I('get.id');
	
		$term = str_replace('*', '&', trim(I( 'get.term' ))) ;
		$year = str_replace('*', '&', trim(I( 'get.year' ))) ;
		$sess = str_replace('*', '&', trim(I( 'get.session' ))) ;
		$program = str_replace('*', '&', trim(I( 'get.program' ))) ;
		$term_year_sess = $year."-".$term."-".$sess."-".$program;
		
		$where['csr.course_id'] = $id;
		$where['csr.course_student_status'] = 1;
		if ($term) $where['u.term'] = $term;
		if ($year) $where['u.year'] = $year;
		if ($sess) $where['u.session'] = $sess;
		if ($program) $where['u.program'] = $program;
		
		$course = $this->course_model->find($id);
		$course_students = $this->user_model->alias('u')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.student_id=u.id')->where($where)->select();
	
		$this->assign( 'term', $term );
		$this->assign( 'year', $year );
		$this->assign( 'sess', $sess );
		$this->assign( 'program', $program );
		$this->assign( 'term_year_sess', $term_year_sess );
		$this->assign( 'course_students', $course_students );
		$this->assign( $course );
		$this->display();
	}
	//导出报名此课程的学生名单
	function student_export() {
		$where = array();
		
		$id = (int)$_GET['id'];
		$term = trim($_GET['term']) ;
		$year = trim($_GET['year']);
		$sess = trim($_GET['session']) ;
		$program = trim($_GET['program']) ;
		$term_year_sess = $year."-".$term."-".$sess."-".$program;
		
		$where['csr.course_id'] = $id;
		$where['csr.course_student_status'] = 1;
		if ($term) $where['u.term'] = $term;
		if ($year) $where['u.year'] = $year;
		if ($sess) $where['u.session'] = $sess;
		if ($program) $where['u.program'] = $program;
		
		$course = $this->course_model->find($id);
		$students = $this->user_model->alias('u')->field('u.first_name,u.last_name')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.student_id=u.id')->where($where)->select();
	
		//导出
		set_time_limit(0);
	
		$xls_file_name = $course['course_name']."(".$course['course_code'].")_Students_".date('Y-m-d',time());
		require_once 'today/excel/PHPExcel.php';
		$excel = new \PHPExcel();
		$excel->getProperties()->setCreator( 'CIEE' )
		->setLastModifiedBy( 'CIEE' )
		->setTitle( $xls_file_name );
		$sheet = $excel->setActiveSheetIndex( 0 );
		$sheet->getDefaultRowDimension()->setRowHeight( 15 );
	
		$rowIndex = 1;  //行
		foreach ( $students as $student ) {
			$colIndex = -1;//列
			$rowIndex++;

			$colIndex++;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $student['first_name'] );  //姓名
			$colIndex++;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex,  $student['last_name'] );  

			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(array('font' => array( 'size' => 10,'name' => 'Arial' )));
		}
	
		//表头
		$colIndex = -1;
		$cols = 'studentCols';
		foreach ( $this->$cols as $col ) {
			$colIndex++;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1', $col[1] );
			$sheet->getColumnDimension( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ) )->setWidth( $col[0] );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getFill()->getStartColor()->setARGB( 'FF'.$col[2] );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getTop()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getRight()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getBottom()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getBorders()->getLeft()->getColor()->setARGB( 'FF000000' );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->getAlignment()->setWrapText( true );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).'1' )->applyFromArray(
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
	// 课程批量复制
	function copy(){
		if(isset($_POST['ids'])){
			foreach ($_POST['ids'] as $id){
				//复制课程表中的数据
				$find_course = $this->course_model
								->field('headteacher_id,headteacher_name,parttimeteacher_id,timezones_id,course_type,course_name,course_description,course_content,course_address,course_credit,smeta,course_code,course_academic_hour')
								->where(array('id'=>$id))
								->find();
				if($find_course){
					$find_course['course_modify_time'] = date('Y-m-d H:i:s');
					$find_course['course_status'] = 1;
					$course_id = $this->course_model->add($find_course);
				}
				//复制关联表课程要求中的数据
				$find_course_requirements = $this->requirement_model->field('requirement_key,requirement_description,requirement_grade_percent,requirement_type,requirement_points_system')->where(array('course_id' => $id))->select();
				foreach ($find_course_requirements as $find_course_requirement) {
					$find_course_requirement['course_id'] = $course_id;
					$course_requirement_id = $this->requirement_model->add($find_course_requirement);
				}
			}
			$this->success("复制成功！");
		}
	}
	//课程删除
	function delete() {
	
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['course_status'] = 2;
			if ( $this->course_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['course_status'] = 1;
			if ( $this->course_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->course_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['course_status'] = 2;
			if ( $this->course_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	
	
	}
	//课程批量显示隐藏
	function toggle() {
	
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['course_status'] = 1;
			if ( $this->course_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				
				$this->success(L('DISPLAY_SUCCESS'));
			} else {
				$this->error(L('DISPLAY_FAILED'));
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['course_status'] = 0;
			if ( $this->course_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				
				$this->success(L('HIDE_SUCCESS'));
			} else {
				$this->error(L('HIDE_FAILED'));
			}
		}
	}
	// 课程推荐
	public function recommend(){
		//语言
		$configs = include 'data/conf/config.php';
		$lang = $configs['DEFAULT_LANG'];
	
		if(isset($_POST['ids']) && $_GET["recommend"]){
			$ids = I('post.ids/a');
			if ( $this->course_model->where(array('id'=>array('in',$ids)))->save(array('course_recommended'=>1))!==false) {
				$this->success(L('RECOMMEND_SUCCESS'));
			} else {
				$this->error(L('RECOMMEND_FAILED'));
			}
		}
		if(isset($_POST['ids']) && $_GET["unrecommend"]){
			$ids = I('post.ids/a');
			if ( $this->course_model->where(array('id'=>array('in',$ids)))->save(array('course_recommended'=>0))!==false) {
				$this->success(L('UNRECOMMEND_SUCCESS'));
			} else {
				$this->error(L('UNRECOMMEND_FAILED'));
			}
		}
	}
	//课程排序
	public function listorders() {
	
		$status = parent::_listorders( $this->course_model );
		if ( $status ) {
			$this->success(L('ORDER_UPDATE_SUCCESS'));
		} else {
			$this->error(L('ORDER_UPDATE_FAILED'));
		}
	}
	
	//时间区间
	function time_zones() {
		
		$where = array();
		
		$where['time_status'] = array('neq',2);
		$count = $this->time_zones_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->time_zones_model->where($where)->order('time_zones asc')->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加时间区间
	function add_time() {
		if ( IS_POST ) {
			$time_zones = $this->time_zones_model->select();
			
			if ($_POST['end_time'] < $_POST['start_time']) {
				$this->error(L('结束时间不能小于开始时间'));
			} else {
				$_POST['time_zones'] = $_POST['start_time']."-".$_POST['end_time'];
				foreach ($time_zones as $time_zone) {
					if ($_POST['time_zones'] == $time_zone['time_zones']) $this->error(L('已经存在相同的时间区间，不能重复添加！'));
				}
				unset($_POST['start_time']);
				unset($_POST['end_time']);
			}
				
			$time_id = $this->time_zones_model->add($_POST);
			if ($time_id) {
				//记录日志
				LogController::log_record($time_id,1);
	
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
				
			$this->display();
		}
	}
	//时间区间删除
	function delete_time() {
	
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['time_status'] = 2;
			if ( $this->time_zones_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['time_status'] = 1;
			if ( $this->time_zones_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
	
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->time_zones_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
	
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['time_status'] = 2;
			if ( $this->time_zones_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
	
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	
	
	}
	//时间区间批量显示隐藏
	function toggle_time() {
	
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['time_status'] = 1;
			if ( $this->time_zones_model->where( "id in ($ids)" )->save( $data ) !== false ) {
	
				$this->success(L('DISPLAY_SUCCESS'));
			} else {
				$this->error(L('DISPLAY_FAILED'));
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['time_status'] = 0;
			if ( $this->time_zones_model->where( "id in ($ids)" )->save( $data ) !== false ) {
	
				$this->success(L('HIDE_SUCCESS'));
			} else {
				$this->error(L('HIDE_FAILED'));
			}
		}
	}
	/**
	 * 学季三级联动下拉框
	 * @param $department_id
	 * @param $major_id
	 * @param $category_id
	 */
	private function getSel($department_id,$major_id,$category_id) {
		//学季
		$departments = $this->department_model->where(array('department_status' => 1))->select();
		$department_html = " <option value='0'>请选择学季</option>";
		foreach ($departments as $department) {
			$department_html .= "<option";
			if($department['id'] == $department_id) {
				$department_html .= " selected";
			}
			$department_html .= " value='".$department['id']."'>".$department['department_name']."</option>";
		}
		$this->assign('department_html',$department_html);
		//项目
		$majors = $this->major_model->where(array('did' => $department_id,'major_status' => 1))->select();
		$major_html = " <option value='0'>请选择项目</option>";
		foreach ($majors as $major) {
			$major_html .= "<option";
			if($major['id'] == $major_id) {
				$major_html .= " selected";
			}
			$major_html .= " value='".$major['id']."'>".$major['major_name']."</option>";
		}
		$this->assign('major_html',$major_html);
		//课程
		$categories = $this->category_model->where(array('mid' => $major_id,'category_status' => 1))->select();
		$category_html = " <option value='0'>请选择课程</option>";
		foreach ($categories as $category) {
			$category_html .= "<option";
			if($category['id'] == $category_id) {
				$category_html .= " selected";
			}
			$category_html .= " value='".$category['id']."'>".$category['category_name']."</option>";
		}
		$this->assign('category_html',$category_html);
	}
	//获取某个学季下所有项目
	function getMajors() {
		if (IS_AJAX){
			$department_id = I('did');  
			$majors = $this->major_model->where(array('did' => $department_id,'major_status' => 1))->select();
			$major_html = " <option value ='0'>请选择项目</option>";
			foreach ($majors as $major) {
				$major_html .= "<option value='".$major['id']."'>".$major['major_name']."</option>";
			}
			$this->ajaxReturn($major_html);
		}
	}
	//获取某个项目下所有课程
	function getCategories() {
		if (IS_AJAX){
			$major_id = I('mid');
			$major = $this->major_model->find($major_id);
			$data = array();
			$data['major_start_time'] = date('Y-m-d H:i',strtotime($major['major_start_time']));
			$data['major_end_time'] = date('Y-m-d H:i',strtotime($major['major_end_time']));
			$categories = $this->category_model->where(array('mid' => $major_id,'category_status' => 1))->select();
			$category_html = " <option value ='0'>请选择课程名称</option>";
			foreach ($categories as $category) {
				$category_html .= "<option value='".$category['id']."'>".$category['category_name']."</option>";
			}
			$data['category_html'] = $category_html;
			$this->ajaxReturn($data);
		}
	}
	//获取选中项目基本信息
	function getCourseBaseInfo() {
		if (IS_AJAX){
			$category_id = I('cid');
			$category = $this->category_model->find($category_id);
			$category['smeta'] = json_decode($category['smeta'],true);
			$this->ajaxReturn($category);
		}
	}
	
	//学季（院系）列表
	function department_list() {
		//select d.*,(select count(*) from cmf_course_major where did=d.id) as major_count,(select count(*) from cmf_course_category where did=d.id) as category_count from cmf_course_department where 1=1
		$count = $this->department_model->where(array('department_status'=>array('neq',2)))->count();
		$page = $this->page($count, 20);
		$sql = "select d.*,(select count(*) from ".C('DB_PREFIX')."course_major where did=d.id and major_status!=2) as major_count,(select count(*) from ".C('DB_PREFIX')."course_category where did=d.id and category_status!=2) as category_count from ".C('DB_PREFIX')."course_department d where d.department_status!=2";
		$sql .= " order by d.listorder asc";
		$sql .= " limit ".$page->firstRow.",".$page->listRows;
		$list = $this->department_model->query($sql);
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//学季（院系）添加
	function department_add() {
		if(IS_POST) {
			if (empty($_POST['department_name'])) $this->error("请填写学季名称");
			if ($_POST['department_start_time'] > $_POST['department_end_time']) $this->error("学季开始时间不能大于学季结束时间");
			
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['department_modify_time'] = date("Y-m-d H:i:s",time());
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['department_content'] = htmlspecialchars_decode($_POST['department_content']);
			
			$result=$this->department_model->add($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($result,1);
				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
		} else {
			$this->display();
		}
	}
	//学季（院系）编辑
	function department_edit() {
		if(IS_POST) {
			$id = (int)$_POST['id'];
			if(empty($_POST['department_name'])) $this->error("请填写学季名称");
			if ($_POST['department_start_time'] > $_POST['department_end_time']) $this->error("学季开始时间不能大于学季结束时间");
			
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['department_modify_time'] = date("Y-m-d H:i:s",time());
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['department_content'] = htmlspecialchars_decode($_POST['department_content']);
				
			$result=$this->department_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				$this->success("修改成功！");
			} else {
				$this->error("修改失败！");
			}
		} else {
			$id = intval(I('get.id'));
			$department = $this->department_model->where(array('id' => $id))->find();
			$this->assign($department);
			$this->display();
		}
	}

	//项目（专业）列表
	function major_list() {
		$department_id = intval(I('get.id'));
		$department = $this->department_model->where(array('id' => $department_id))->find();
		$count = $this->major_model->where(array('did' => $department_id,'major_status' => array('neq',2)))->count();
		$page = $this->page($count, 20);
		$sql = "select m.*,(select count(*) from ".C('DB_PREFIX')."course_category where mid=m.id and category_status!=2) as category_count from ".C('DB_PREFIX')."course_major m where m.major_status!=2 and m.did=".$department_id;
		$sql .= " order by m.listorder asc";
		$sql .= " limit ".$page->firstRow.",".$page->listRows;
		$list = $this->major_model->query($sql);
		$this->assign('department',$department);
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//项目（专业）添加
	function major_add() {
		if(IS_POST) {
			$department_id = (int)$_POST['did'];
			$department = $this->department_model->where(array('id' => $department_id))->find();
			if (empty($_POST['major_name'])) $this->error("请填写项目名称");
			if ($_POST['major_start_time'] < $department['department_start_time']) $this->error("项目开始时间不能小于学季开始时间");	
			if ($_POST['major_end_time'] > $department['department_end_time']) $this->error("项目结束时间不能大于学季结束时间");
			if ($_POST['major_start_time'] > $_POST['major_end_time']) $this->error("项目开始时间不能大于项目结束时间");
			
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['major_modify_time'] = date("Y-m-d H:i:s",time());
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['major_content'] = htmlspecialchars_decode($_POST['major_content']);
				
			$result=$this->major_model->add($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($result,1);
				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
		} else {
			$department_id = intval(I('get.id'));
			$this->assign('department_id',$department_id);
			$this->display();
		}
	}
	//项目（专业）编辑
	function major_edit() {
		if(IS_POST) {
			$id = (int)$_POST['id'];
			$department_id = (int)$_POST['did'];
			$department = $this->department_model->where(array('id' => $department_id))->find();
			if (empty($_POST['major_name'])) $this->error("请填写项目名称");
			if ($_POST['major_start_time'] < $department['department_start_time']) $this->error("项目开始时间不能小于学季开始时间");
			if ($_POST['major_end_time'] > $department['department_end_time']) $this->error("项目结束时间不能大于学季结束时间");
			if ($_POST['major_start_time'] > $_POST['major_end_time']) $this->error("项目开始时间不能大于项目结束时间");
				
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['major_modify_time'] = date("Y-m-d H:i:s",time());
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['major_content'] = htmlspecialchars_decode($_POST['major_content']);
	
			$result=$this->major_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				$this->success("修改成功！");
			} else {
				$this->error("修改失败！");
			}
		} else {
			$id = intval(I('get.id'));
			$department_id = intval(I('get.did'));
			$department = $this->department_model->where(array('id' => $department_id))->find();
			$major = $this->major_model->where(array('id' => $id))->find();
			$this->assign('department',$department);
			$this->assign($major);
			$this->display();
		}
	}
	
	//课程分类列表
	function category_list() {
		$department_id = intval(I('get.did'));
		$major_id = intval(I('get.mid'));
		$department = $this->department_model->where(array('id' => $department_id))->find();
		$major = $this->major_model->where(array('id' => $major_id,'category_status' => array('neq',2)))->find();
		$where = array();
		if ($major_id) { //查询所属项目的所有课程名称
			$where['mid'] = $major_id;
		} else { //查询所属学季的所有课程名称
			$where['did'] = $department_id;
		}
		$where['category_status'] = array('neq',2);
		$count = $this->category_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->category_model->where($where)->limit($page->firstRow,$page->listRows)->order("listorder asc")->select();
		$this->assign('department',$department);
		$this->assign('major',$major);
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//课程分类添加
	function category_add() {
		if(IS_POST) {
			if (empty($_POST['category_name'])) $this->error("请填写课程名称");
				
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['category_modify_time'] = date("Y-m-d H:i:s",time());
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['category_content'] = htmlspecialchars_decode($_POST['category_content']);
	
			$result=$this->category_model->add($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($result,1);
				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
		} else {
			$department_id = intval(I('get.did'));
			$major_id = intval(I('get.id'));
			$this->assign('department_id',$department_id);
			$this->assign('major_id',$major_id);
			$this->display();
		}
	}
	//课程分类编辑
	function category_edit() {
		if(IS_POST) {
			$id = (int)$_POST['id'];
			if (empty($_POST['category_name'])) $this->error("请填写课程名称");
	
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['category_modify_time'] = date("Y-m-d H:i:s",time());
			$_POST['smeta'] = json_encode($_POST['smeta']);
			$_POST['category_content'] = htmlspecialchars_decode($_POST['category_content']);
	
			$result=$this->category_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				$this->success("修改成功！");
			} else {
				$this->error("修改失败！");
			}
		} else {
			$id = intval(I('get.id'));
			$department_id = intval(I('get.did'));
			$major_id = intval(I('get.mid'));
			$department = $this->department_model->where(array('id' => $department_id))->find();
			$major = $this->major_model->where(array('id' => $major_id))->find();
			$category = $this->category_model->where(array('id' => $id))->find();
			$this->assign('department',$department);
			$this->assign('major',$major);
			$this->assign($category);
			$this->display();
		}
	}
	
	//课程名称删除
	function category_delete() {
		if ( isset( $_POST['ids'] ) ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['category_status'] = 2;
			if ( $this->category_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['category_status'] = 1;
			if ( $this->category_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( '恢复成功！' );
			} else {
				$this->error( '恢复失败！' );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->category_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( '彻底删除成功！' );
			} else {
				$this->error( '彻底删除失败！' );
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$data['category_status'] = 2;
			if ( $this->category_model->where(array('id' => $id))->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		}
	}
	//课程名称批量显示隐藏
	function category_toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['category_status'] = 1;
			if ( $this->category_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( '显示成功！' );
			} else {
				$this->error( '显示失败！' );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['category_status'] = 0;
			if ( $this->category_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( '隐藏成功！' );
			} else {
				$this->error( '隐藏失败！' );
			}
		}
	}
	//课程名称排序
	public function category_listorders() {
		$status = parent::_listorders( $this->category_model );
		if ( $status ) {
			$this->success( '排序更新成功！' );
		} else {
			$this->error( '排序更新失败！' );
		}
	}
	//项目（专业）删除
	function major_delete() {
		
		if ( isset( $_POST['ids'] ) ) {
			$ids = implode( ',', $_POST['ids'] );
			$major_data['major_status'] = 2;
			$category_data['category_status'] = 2;
			if ( ($this->major_model->where( "id in ($ids)" )->save( $major_data ) !== false) && ($this->category_model->where("mid in ($ids)")->save($category_data) !== false) ) {
				//记录日志
				LogController::log_record($ids,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$major_data['major_status'] = 1;
			$category_data['category_status'] = 1;
			if ( ($this->major_model->where( "id in ($object)" )->save( $major_data ) !== false) && ($this->category_model->where("mid in ($object)")->save($category_data) !== false)) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( '恢复成功！' );
			} else {
				$this->error( '恢复失败！' );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( ($this->major_model->where( "id in ($object)" )->delete() !== false) && ($this->category_model->where("mid in ($object)")->delete() !== false) ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( '彻底删除成功！' );
			} else {
				$this->error( '彻底删除失败！' );
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$major_data['major_status'] = 2;
			$category_data['category_status'] = 2;
			if ( ($this->major_model->where(array('id' => $id))->save( $major_data ) !== false) && ($this->category_model->where(array('mid' => $id))->save($category_data) !== false) ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		}
	}
	//项目（专业）批量显示隐藏
	function major_toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$major_data['major_status'] = 1;
			$category_data['category_status'] = 1;
			if ( ($this->major_model->where( "id in ($ids)" )->save( $major_data ) !== false) && ($this->category_model->where("mid in ($ids)")->save($category_data) !== false) ) {
				$this->success( '显示成功！' );
			} else {
				$this->error( '显示失败！' );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$major_data['major_status'] = 0;
			$category_data['category_status'] = 0;
			if ( ($this->major_model->where( "id in ($ids)" )->save( $major_data ) !== false) && ($this->category_model->where("mid in ($ids)")->save($category_data) !== false) ) {
				$this->success( '隐藏成功！' );
			} else {
				$this->error( '隐藏失败！' );
			}
		}
	}
	
	//项目（专业）排序
	public function major_listorders() {
		$status = parent::_listorders( $this->major_model );
		if ( $status ) {
			$this->success( '排序更新成功！' );
		} else {
			$this->error( '排序更新失败！' );
		}
	}
	//学季（院系）逻辑删除
	function department_delete() {
		
		if ( isset( $_POST['ids'] ) ) {
			$ids = implode( ',', $_POST['ids'] );
			$department_data['department_status'] = 2;
			$major_data['major_status'] = 2;
			$category_data['category_status'] = 2;
			if ( ($this->department_model->where( "id in ($ids)" )->save( $department_data ) !== false) && ($this->major_model->where("did in ($ids)")->save( $major_data ) !== false) && ($this->category_model->where("did in ($ids)")->save($category_data) !== false) ) {
				//记录日志
				LogController::log_record($ids,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$department_data['department_status'] = 1;
			$major_data['major_status'] = 1;
			$category_data['category_status'] = 1;
			if ( ($this->department_model->where( "id in ($object)" )->save( $department_data ) !== false) && ($this->major_model->where("did in ($object)")->save( $major_data ) !== false) && ($this->category_model->where("did in ($object)")->save($category_data) !== false) ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( '恢复成功！' );
			} else {
				$this->error( '恢复失败！' );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( ($this->department_model->where( "id in ($object)" )->delete() !== false) && ($this->major_model->where("did in ($object)")->delete() !== false) && ($this->category_model->where("did in ($object)")->delete() !== false) ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( '彻底删除成功！' );
			} else {
				$this->error( '彻底删除失败！' );
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$department_data['department_status'] = 2;
			$major_data['major_status'] = 2;
			$category_data['category_status'] = 2;
			if ( ($this->department_model->where(array('id' => $id))->save($department_data) !== false) && ($this->major_model->where(array('did' => $id))->save($major_data) !== false) && ($this->category_model->where(array('did' => $id))->save($category_data) !== false) ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		}
	}
	//学季（院系）批量显示隐藏
	function department_toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$department_data['department_status'] = 1;
			$major_data['major_status'] = 1;
			$category_data['category_status'] = 1;
			if ( ($this->department_model->where( "id in ($ids)" )->save( $department_data ) !== false) && ($this->major_model->where( "did in ($ids)" )->save( $major_data ) !== false) && ($this->category_model->where("did in ($ids)")->save($category_data) !== false) ) {
				$this->success( '显示成功！' );
			} else {
				$this->error( '显示失败！' );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$department_data['department_status'] = 0;
			$major_data['major_status'] = 0;
			$category_data['category_status'] = 0;
			if ( ($this->department_model->where( "id in ($ids)" )->save( $department_data ) !== false) && ($this->major_model->where( "did in ($ids)" )->save( $major_data ) !== false) && ($this->category_model->where("did in ($ids)")->save($category_data) !== false) ) {
				$this->success( '隐藏成功！' );
			} else {
				$this->error( '隐藏失败！' );
			}
		}
	}
	
	//学季（院系）排序
	public function department_listorders() {
		$status = parent::_listorders( $this->department_model );
		if ( $status ) {
			$this->success( '排序更新成功！' );
		} else {
			$this->error( '排序更新失败！' );
		}
	}

	
}