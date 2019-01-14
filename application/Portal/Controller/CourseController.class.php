<?php
/**
 * 前端课程
 * 11K
 */
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class CourseController extends HomebaseController {

	private $formData = array();
	private $formError = array();
	private $formReturn = array();
	private $course_model;
	private $time_model;
	private $course_mentor_model;
	private $term_course_model;
	private $user_model;
	
	public function _initialize() {
		parent::_initialize();
		
		$this->course_model = D( 'Course' );
		$this->time_model = D('CourseTime');
		$this->course_mentor_model = D( 'CourseMentorRelationship' );
		$this->term_course_model = D( 'TermCourseRelationship' );
		$this->user_model = D( 'Users' );
	}

	public function index() {
		$term_years = $this->user_model
							->field('distinct term,year,session,program,term_status')
							->where(array('user_type' => 2,'term_status' => 1))
							->limit(0,1)
							->select();
		if($term_years) {
			foreach ($term_years as $term_year) {
				$term_year_sess = trim($term_year['year'])."-".trim($term_year['term'])."-".trim($term_year['session'])."-".trim($term_year['program']);
			}
		}
		$count = $this->course_model
					->alias('c')
					->field('c.*,u.full_name')
					->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
					->join('__USERS__ u ON u.id=c.headteacher_id')
					->where(array('tc.term_year_sess' => $term_year_sess,'c.course_status' => 1))
					->order('c.course_modify_time desc')
					->count();
		$page = $this->page($count, 9);
		$list = $this->course_model
					->alias('c')
					->field('c.*,u.full_name')
					->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
					->join('__USERS__ u ON u.id=c.headteacher_id')
					->where(array('tc.term_year_sess' => $term_year_sess,'c.course_status' => 1))
					->order('c.course_modify_time desc')
					->limit( $page->firstRow, $page->listRows )
					->select();
		$this->assign("page", $page->show('Portal'));
		$this->assign('list',$list);
		
		$this->display( '/../course' );
	}

	public function detail() {
		$id = (int)$_GET['id'];
		$course = $this->course_model->where(array('id' => $id,'course_status' => 1))->find();

		if ( empty( $course ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			header( 'Status:404 Not Found' );
			if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
			return;
		}
		//授课时间
		//$course_times = $this->time_model->where(array('course_id' => $id))->select();
		//搭班教师
		/* $course_mentors = $this->course_mentor_model->alias('cm')->join('__PART_TIME_TEACHER__ ptt ON ptt.id=cm.mentor_id')->where(array('cm.course_id' => $id))->select();
		$mentor_names_arr = array();
		foreach ($course_mentors as $course_mentor) $mentor_names_arr[] = $course_mentor['first_name']." ".$course_mentor['last_name'];
		$mentor_names = implode(' 、', $mentor_names_arr); */
		
		$this->assign( $course );
		/* $this->assign('course_times',$course_times);
		$this->assign('mentor_names',$mentor_names);
 */
		$this->display( '/../course_detail' );
	}

	//某学季下课程已报名学生列表
	function roster() {
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
		$course_students = $this->user_model->alias('u')->field('u.*')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.student_id=u.id')->where($where)->order('u.first_name asc,u.last_name asc')->select();
		$course_times = $this->time_model->where(array('course_id' => $id))->select();
		
		$this->assign( 'term', $term );
		$this->assign( 'year', $year );
		$this->assign( 'sess', $sess );
		$this->assign( 'program', $program );
		$this->assign( 'term_year_sess', $term_year_sess );
		$this->assign( 'course_students', $course_students );
		$this->assign( 'course_times', $course_times );
		$this->assign( $course );
		$this->display('/../roster');
	}
	//精确到session课程已报名学生列表
	function roster_session() {
		$where = array();
		$id = (int)I('get.id');
	
		$term = str_replace('*', '&', trim(I( 'get.term' ))) ;
		$year = str_replace('*', '&', trim(I( 'get.year' ))) ;
		$sess = str_replace('*', '&', trim(I( 'get.session' ))) ;
	
		$where['csr.course_id'] = $id;
		$where['csr.course_student_status'] = 1;
		if ($term) $where['u.term'] = $term;
		if ($year) $where['u.year'] = $year;
		if ($sess) {
			$where['u.session'] = $sess;
		} else {
			$where['u.session'] = '';
		}
	
		$course = $this->course_model->find($id);
		$course_students = $this->user_model->alias('u')->field('u.*')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.student_id=u.id')->where($where)->order('u.program asc,u.first_name asc,u.last_name asc')->select();
		$course_times = $this->time_model->where(array('course_id' => $id))->select();
	
		$this->assign( 'term', $term );
		$this->assign( 'year', $year );
		$this->assign( 'sess', $sess );
		$this->assign( 'course_students', $course_students );
		$this->assign( 'course_times', $course_times );
		$this->assign( $course );
		$this->display('/../roster_session');
	}
	
	function program() {
		$where = array();
		
		$term = str_replace('*', '&', trim(I( 'get.term' ))) ;
		$year = str_replace('*', '&', trim(I( 'get.year' ))) ;
		$sess = str_replace('*', '&', trim(I( 'get.session' ))) ;
		$program = str_replace('*', '&', trim(I( 'get.program' ))) ;
		
		if ($term) {
			$where['u.term'] = $term;
		} else {
			$where[] = "u.term='' OR u.term is null" ;
		}
		if ($year) {
			$where['u.year'] = $year;
		} else {
			$where[] = "u.year='' OR u.year is null" ;
		}
		if ($session) {
			$where['u.session'] = $session;
		} else {
			$where[] = "u.session='' OR u.session is null" ;
		}
		if ($program) {
			$where['u.program'] = $program;
		} else {
			$where[] = "u.program='' OR u.program is null" ;
		}
		
		$where['u.user_status'] = 1;
		$where['u.user_type'] = 2;
		$program_students = $this->user_model->alias('u')->field('u.*')->where($where)->order('u.first_name asc,u.last_name asc')->select();
		
		$this->assign( 'term', $term );
		$this->assign( 'year', $year );
		$this->assign( 'sess', $sess );
		$this->assign( 'program', $program );
		$this->assign( 'program_students', $program_students );
		$this->display('/../program');
	}
}