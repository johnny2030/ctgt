<?php
/**
 * 后台课程&学季
* 11k
* likun_19911227@163.com
*/
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ScoreController extends AdminbaseController {


	private $score_model;
	private $course_model;
	private $users_model;
	private $course_requirement_model;
	private $term_course_model;
	private $student_course_model;
	private $final_score_model;
	private $role_user_model;

	function _initialize() {
		parent::_initialize();
		$this->score_model = D( 'Score' );
		$this->course_model = D( 'Course' );
		$this->users_model = D( 'Users' );
		$this->course_requirement_model = D('CourseRequirement');
		$this->term_course_model = D( 'TermCourseRelationship' );
		$this->student_course_model = D( 'CourseStudentRelationship' );
		$this->final_score_model = D( 'FinalScore' );
		$this->role_user_model = D( 'RoleUser' );
	}

	function index() {
		//条件查询(学季、课程id)
		$where = array();
		//$keyword=I('request.keyword');
		//$this->assign( 'keyword', $keyword );
		
		$term_year_sess_val = trim($_REQUEST['term_year_sess']);
		$course_id = (int)$_REQUEST['course'];
		
		//当前登录后台用户的id
		$user_id = sp_get_current_admin_id();
		//获取当前用户管理权限id
		$roles = $this->role_user_model->where(array('user_id' => $user_id))->select();
		$role_id = array();
		foreach ($roles as $role) $role_id[] = $role['role_id'];
		//获取当前用户能查看到的课程
		$term_year_sess_html = " <option value=''>".L('SCORE_TERM')."</option>";
		$course_html = " <option value=''>".L('SCORE_COURSE')."</option>";
		//1，超级管理员；10，CIEE初级管理员；11，CIEE中级管理员；12，CIEE高级管理员；这些角色可以查看所有学季和此学季下的所有课程
		if (in_array(1, $role_id) || in_array(12, $role_id)) {
			//已分配课程学季下拉框
			$terms = $this->term_course_model->field('distinct term_year_sess')->order('term_year_sess desc')->select();
			foreach ($terms as $term) {
				$term_year_sess_html .= "<option";
				if($term['term_year_sess'] == $term_year_sess_val) {
					$term_year_sess_html .= " selected";
				}
				$term_year_sess_html .= " value='".$term['term_year_sess']."'>".$term['term_year_sess']."</option>";
			}
			
			//获取此学季下已经分配的所有课程
			$courses = $this->course_model
							->alias('c')
							->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
							->where(array('tc.term_year_sess' => $term_year_sess_val))
							->select();
		} elseif (in_array(3, $role_id) || in_array(13, $role_id)) {//3，CIEE主导教师；13，CIEE兼职教师；这些角色只可以可以查看与自己相关的课程和学季
			if (in_array(3, $role_id) && !in_array(13, $role_id)) {
				//获取此教师教授的所有课程
				$headteacher_courses = $this->course_model->field('id')->where(array('headteacher_id' => $user_id))->select();
				$headteacher_course_ids = array();
				foreach ($headteacher_courses as $headteacher_course) $headteacher_course_ids[] = $headteacher_course['id'];
				//获取这些课程被分配到了哪些学季下面
				$headteacher_terms = $this->term_course_model->field('distinct term_year_sess')->where(array('course_id' => array('in',implode(',', $headteacher_course_ids))))->order('term_year_sess desc')->select();
				foreach ($headteacher_terms as $headteacher_term) {
					$term_year_sess_html .= "<option";
					if($headteacher_term['term_year_sess'] == $term_year_sess_val) {
						$term_year_sess_html .= " selected";
					}
					$term_year_sess_html .= " value='".$headteacher_term['term_year_sess']."'>".$headteacher_term['term_year_sess']."</option>";
				}
				
				//获取此学季下主导教师参与的所有课程
				$courses = $this->course_model
								->alias('c')
								->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
								->where(array('tc.term_year_sess' => $term_year_sess_val,'tc.course_id' => array('in',implode(',', $headteacher_course_ids))))
								->select();
			} elseif (!in_array(3, $role_id) && in_array(13, $role_id)) {
				$all_courses = $this->course_model->field('id,parttimeteacher_id')->select();
				//获取此教师教授的所有课程
				$parttimeteacher_course_ids = array();
				foreach ($all_courses as $all_course) {
					if ($all_course['parttimeteacher_id']) {
						$parttimeteacher_ids = explode(',', $all_course['parttimeteacher_id']);
						if (in_array($user_id, $parttimeteacher_ids)) $parttimeteacher_course_ids[] = $all_course['id'];
					}
				}
				//获取这些课程被分配到了哪些学季下面
				$parttimeteacher_terms = $this->term_course_model->field('distinct term_year_sess')->where(array('course_id' => array('in',implode(',', $parttimeteacher_course_ids))))->order('term_year_sess desc')->select();
				foreach ($parttimeteacher_terms as $parttimeteacher_term) {
					$term_year_sess_html .= "<option";
					if($parttimeteacher_term['term_year_sess'] == $term_year_sess_val) {
						$term_year_sess_html .= " selected";
					}
					$term_year_sess_html .= " value='".$parttimeteacher_term['term_year_sess']."'>".$parttimeteacher_term['term_year_sess']."</option>";
				}
				
				//获取此学季下兼职教师参与的所有课程
				$courses = $this->course_model
								->alias('c')
								->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
								->where(array('tc.term_year_sess' => $term_year_sess_val,'tc.course_id' => array('in',implode(',', $parttimeteacher_course_ids))))
								->select();
			} else {
				//获取此教师教授的所有课程
				$headteacher_courses = $this->course_model->field('id')->where(array('headteacher_id' => $user_id))->select();
				$headteacher_course_ids = array();
				foreach ($headteacher_courses as $headteacher_course) $headteacher_course_ids[] = $headteacher_course['id'];
				$all_courses = $this->course_model->field('id,parttimeteacher_id')->select();
				//获取此教师教授的所有课程
				$parttimeteacher_course_ids = array();
				foreach ($all_courses as $all_course) {
					if ($all_course['parttimeteacher_id']) {
						$parttimeteacher_ids = explode(',', $all_course['parttimeteacher_id']);
						if (in_array($user_id, $parttimeteacher_ids)) $parttimeteacher_course_ids[] = $all_course['id'];
					}
				}
				$course_ids = array_merge($headteacher_course_ids,$parttimeteacher_course_ids);
				
				//获取这些课程被分配到了哪些学季下面
				$teacher_terms = $this->term_course_model->field('distinct term_year_sess')->where(array('course_id' => array('in',implode(',', $course_ids))))->order('term_year_sess desc')->select();
				foreach ($teacher_terms as $teacher_term) {
					$term_year_sess_html .= "<option";
					if($teacher_term['term_year_sess'] == $term_year_sess_val) {
						$term_year_sess_html .= " selected";
					}
					$term_year_sess_html .= " value='".$teacher_term['term_year_sess']."'>".$teacher_term['term_year_sess']."</option>";
				}
				
				//获取此学季下兼职教师参与的所有课程
				$courses = $this->course_model
								->alias('c')
								->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
								->where(array('tc.term_year_sess' => $term_year_sess_val,'tc.course_id' => array('in',implode(',', $course_ids))))
								->select();
			}
		}
		
		if ($term_year_sess_val) $where[] = "concat_ws('-',trim(u.year),trim(u.term),trim(u.session),trim(u.program)) = '".$term_year_sess_val."'";
		
		$search_info = "";
		if ($course_id && $term_year_sess_val) {
			foreach ($courses as $course) {
				$course_html .= "<option";
				if($course['id'] == $course_id) {
					$course_html .= " selected";
				}
				$course_html .= " value='".$course['id']."'>".$course['course_name']."</option>";
			}
			
			$where['cs.course_student_status'] = array('eq',1);
			$where['cs.course_id'] = $course_id;
			$count = $this->users_model
						->alias('u')
						->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.student_id=u.id')
						->where($where)
						->count();
			$page = $this->page($count, 30);
			$list = $this->users_model
						->alias('u')
						->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.student_id=u.id')
						->where($where)
						->order('u.first_name asc,u.last_name asc')
						->limit( $page->firstRow, $page->listRows )
						->select();
			
			if ($count == 0) {
				$search_info = L('SCORE_MSG1');
			} else {
				$search_info = L('SCORE_MSG2')."<span style='color:red;'> ".$count." </span>".L('SCORE_MSG3')." >>";
			}
			$this->assign("page", $page->show('Admin'));
			$this->assign( 'list', $list );
		} 
		if ($term_year_sess_val && empty($course_id)) {
			foreach ($courses as $course) {
				$course_html .= "<option value='".$course['id']."'>".$course['course_name']."</option>";
			}
			$count = $this->users_model
						->alias('u')
						->where($where)
						->count();
			$page = $this->page($count, 30);
			$list = $this->users_model
						->alias('u')
						->where($where)
						->order('u.first_name asc,u.last_name asc')
						->limit( $page->firstRow, $page->listRows )
						->select();
			if ($count == 0) {
				$search_info = L('SCORE_MSG4');
			} else {
				$search_info = L('SCORE_MSG2')."<span style='color:red;'> ".$count." </span>".L('SCORE_MSG5')." >>";
			}
			$this->assign("page", $page->show('Admin'));
			$this->assign( 'list', $list );
		}
		$this->assign('course_html',$course_html);
		$this->assign('term_year_sess_html',$term_year_sess_html);
		$this->assign( 'search_info', $search_info );
		$this->assign( 'course_id', $course_id );
		$this->assign( 'term_year_sess_val', $term_year_sess_val );
		
		$this->display();
	}
	
	//获取某个学季下的课程
	function getCourses() {
		if (IS_AJAX){
			$term_year_sess_val = $_POST['term_year_sess'] ;
			//当前登录后台用户的id
			$user_id = sp_get_current_admin_id();
			//获取当前用户管理权限id
			$roles = $this->role_user_model->where(array('user_id' => $user_id))->select();
			$role_id = array();
			foreach ($roles as $role) $role_id[] = $role['role_id'];
			//获取当前用户能查看到的课程
			//1，超级管理员；10，CIEE初级管理员；11，CIEE中级管理员；12，CIEE高级管理员；这些角色可以查看所有学季和此学季下的所有课程
			if (in_array(1, $role_id) || in_array(12, $role_id)) {
					
				//获取此学季下已经分配的所有课程
				$courses = $this->course_model
								->alias('c')
								->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
								->where(array('tc.term_year_sess' => $term_year_sess_val))
								->select();
			} elseif (in_array(3, $role_id) || in_array(13, $role_id)) {//3，CIEE主导教师；13，CIEE兼职教师；这些角色只可以可以查看与自己相关的课程和学季
				if (in_array(3, $role_id) && !in_array(13, $role_id)) {
					//获取此教师教授的所有课程
					$headteacher_courses = $this->course_model->field('id')->where(array('headteacher_id' => $user_id))->select();
					$headteacher_course_ids = array();
					foreach ($headteacher_courses as $headteacher_course) $headteacher_course_ids[] = $headteacher_course['id'];
			
					//获取此学季下主导教师参与的所有课程
					$courses = $this->course_model
									->alias('c')
									->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
									->where(array('tc.term_year_sess' => $term_year_sess_val,'tc.course_id' => array('in',implode(',', $headteacher_course_ids))))
									->select();
				} elseif (!in_array(3, $role_id) && in_array(13, $role_id)) {
					$all_courses = $this->course_model->field('id,parttimeteacher_id')->select();
					//获取此教师教授的所有课程
					$parttimeteacher_course_ids = array();
					foreach ($all_courses as $all_course) {
						if ($all_course['parttimeteacher_id']) {
							$parttimeteacher_ids = explode(',', $all_course['parttimeteacher_id']);
							if (in_array($user_id, $parttimeteacher_ids)) $parttimeteacher_course_ids[] = $all_course['id'];
						}
					}
					//获取此学季下兼职教师参与的所有课程
					$courses = $this->course_model
									->alias('c')
									->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
									->where(array('tc.term_year_sess' => $term_year_sess_val,'tc.course_id' => array('in',implode(',', $parttimeteacher_course_ids))))
									->select();
				} else {
					//获取此教师教授的所有课程
					$headteacher_courses = $this->course_model->field('id')->where(array('headteacher_id' => $user_id))->select();
					$headteacher_course_ids = array();
					foreach ($headteacher_courses as $headteacher_course) $headteacher_course_ids[] = $headteacher_course['id'];
					$all_courses = $this->course_model->field('id,parttimeteacher_id')->select();
					//获取此教师教授的所有课程
					$parttimeteacher_course_ids = array();
					foreach ($all_courses as $all_course) {
						if ($all_course['parttimeteacher_id']) {
							$parttimeteacher_ids = explode(',', $all_course['parttimeteacher_id']);
							if (in_array($user_id, $parttimeteacher_ids)) $parttimeteacher_course_ids[] = $all_course['id'];
						}
					}
					$course_ids = array_merge($headteacher_course_ids,$parttimeteacher_course_ids);
			
					//获取此学季下兼职教师参与的所有课程
					$courses = $this->course_model
									->alias('c')
									->join('__TERM_COURSE_RELATIONSHIP__ tc ON tc.course_id=c.id')
									->where(array('tc.term_year_sess' => $term_year_sess_val,'tc.course_id' => array('in',implode(',', $course_ids))))
									->select();
				}
			}
			
			$course_html = " <option value =''>".L('SCORE_COURSE')."</option>";
			foreach ($courses as $course) {
				$course_html .= "<option value='".$course['id']."'>".$course['course_name']."</option>";
			}
			$this->ajaxReturn($course_html);
		}
	}
	
	//填写学生成绩
	function enter() {
		if ( IS_POST ) {
			$course_id = I('post.course_id');
			$student_id = I('post.student_id');
			//课程测试类型
			$course_requirements = $this->course_requirement_model->where(array('course_id' => $course_id))->select();
			//查询出数据库中已经存在的这个学生这门课程的成绩
			$scores = $this->score_model->where(array("course_id" => $course_id,'student_id' => $student_id))->select();
			$score_ids = array();
			foreach ($scores as $score) $score_ids[] = $score['id'];
			
			$score_add = array();//新增
			$score_edit = array();//修改
			$score_del = array();//删除
			$score_edit_id = array();//需要修改的所有id
			$score_smeta = array();//
			foreach ($_POST['test_content'] as $k => $test_content) {
				$id = (int)$_POST['id'][$k];
				$test_date = $_POST['test_date'][$k];
				$test_content = $test_content;
				$test_type = $_POST['test_type'][$k];
				$test_late = $_POST['test_late'][$k];
				foreach ($course_requirements as $course_requirement) {
					$requirement_description = $course_requirement['requirement_description'];
					$score_smeta[$requirement_description] = $_POST['smeta'][$requirement_description][$k];
				}
				$smeta = json_encode($score_smeta);
				
				if ($test_type == 3) {
					if (empty($test_late)) $this->error('当类型为Late时，必须填写分钟数');
				}
				if ($test_date) {
					if (in_array($id, $score_ids)) {
						//edit
						$score_edit[] = array('id' => $id,'test_date' => $test_date,'test_content' => $test_content,'smeta' => $smeta,'test_type' => $test_type,'test_late' => $test_late);
						$score_edit_id[] = $id;
					} else {
						//add
						$score_add[] = array('course_id' => $course_id,'student_id' => $student_id,'test_date' => $test_date,'test_content' => $test_content,'test_type' => $test_type,'test_late' => $test_late,'smeta' => $smeta);
					}
				} else {
					$this->error('日期不能为空');
				}
			}
			foreach ($score_ids as $score_id) {
				if (!in_array($score_id, $score_edit_id)) $score_del[] = $score_id;
			}
			
			if ($score_add) {
				foreach ($score_add as $score) $this->score_model->add($score);
			}
			if ($score_edit) {
				foreach ($score_edit as $score) {
					$score_id = $score['id'];
					unset($score['id']);
					$this->score_model->where(array('id' => $score_id))->save($score);
				}
			}
			if ($score_del) {
				$this->score_model->where("id in (".implode(',',$score_del).")")->delete();
			}
			$this->success(L('EDIT_SUCCESS'));
			/* if ($score_id) {
				//记录日志
				LogController::log_record($score_id,1);
					
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			} */
	
		} else {
			$student_id = I('get.student_id');
			$course_id = I('get.course_id');
				
			$student = $this->users_model->find($student_id);
			if ($course_id) {
				$course = $this->course_model->find($course_id);
				$this->assign('course',$course);
			} else {
				$this->error('请先选择一门课程');
			}
			//课程测试类型
			$course_requirements = $this->course_requirement_model->where(array('course_id' => $course_id))->select();
			//某个学生某门课程不同测试的分数
			$scores = $this->score_model->where(array('course_id' => $course_id,'student_id' => $student_id))->select();
				
			$this->assign('student',$student);
			$this->assign('course_id',$course_id);
			$this->assign('course_requirements',$course_requirements);
			$this->assign('scores',$scores);
		}
		$this->display();
	}
	//异步修改数据
	function final_async_edit() {
		if (IS_AJAX){
			$param_key = trim(I('param_key'));
			$param_value = trim(I('param_value'));
			$course_id = (int)I('course_id');
			$student_id = (int)I('student_id');
			$original_score = trim(I('original_value'));
			$score = $param_value;
			$operator = $this->users_model->find(sp_get_current_admin_id());
			$operator = empty($operator['full_name']) ? $operator['user_login'] : $operator['full_name'];
			$time = date('Y-m-d H:i',time());
			$final_score = $this->final_score_model->where(array('course_id' => $course_id,'student_id' => $student_id))->find();
			if ($final_score) {
				$smeta = json_decode($final_score['smeta'],true);
				$smeta[$param_key] = array('score' => $score,'operator' => $operator,'time' => $time,'original_score' => $original_score );
				$smeta = json_encode($smeta);
				$this->final_score_model->where(array('course_id' => $course_id,'student_id' => $student_id))->save(array('smeta' => $smeta));
			} else {
				$smeta[$param_key] = array('score' => $score,'operator' => $operator,'time' => $time,'original_score' => $original_score );
				$smeta = json_encode($smeta);
				$this->final_score_model->add(array('course_id' => $course_id,'student_id' => $student_id,'smeta' => $smeta));
			}
			$data = " ".$param_value;
			$this->ajaxReturn($data);
		}
	}
	function export() {
		$student_id = (int)$_GET['student_id'];
		$course_id = (int)$_GET['course_id'];
		
		$course = $this->course_model->field('course_name,course_code')->find($course_id);
		
		if ($student_id) {
			$student = $this->users_model->field('full_name')->find($student_id);
			
			/* 修改后的最终平均成绩 */
			$final_scores = $this->final_score_model->where(array('course_id' => $course_id,'student_id' => $student_id))->find();
			$final_smeta = array();
			if($final_scores) $final_smeta = json_decode($final_scores['smeta'],true);
			/* 某个学生某门课程的所有有效分数 */
			$course_scores = $this->score_model->where(array('course_id' => $course_id,'student_id' => $student_id,'score_status' => 1))->order('test_date asc')->select();
			/* 某门课程的requirement */
			$course_requirements = $this->course_requirement_model->where(array('course_id' => $course_id))->select();
			/* 某个requirement的总分 */
			$total_scores = array();
			/* 整门课程的总分 */
			$real_score = 0;
			foreach ($course_requirements as $cr) {
				$requirement_description = $cr['requirement_description'];
				/* 初始化某个requirement的所有成绩的总分 */
				$total_scores[$requirement_description]['scores'] = 0;
				/* 初始化某个requirement的计算平均分次数 */
				$total_scores[$requirement_description]['counts'] = 0;
				/* 初始化某个requirement的计算平均分(average_score = scores/counts) */
				$total_scores[$requirement_description]['average_score'] = 0;
				/* 初始化签到FULL次数 */
				$total_scores[$requirement_description]['full_counts'] = 0;
				/* 初始化签到SICK次数 */
				$total_scores[$requirement_description]['sick_counts'] = 0;
				/* 初始化签到LATE小时数 */
				$total_scores[$requirement_description]['late_hours'] = 0;
				/* 初始化签到LATE出去整小时剩余的分钟数 */
				$total_scores[$requirement_description]['late_mins'] = 0;
				/* 初始化签到的总分数(late_scores = 10 - late_hours*0.5) */
				$total_scores[$requirement_description]['late_scores'] = 10;
				foreach ($course_scores as $cs) {
					$smeta = json_decode($cs['smeta'],true);
					/* 正常  */
					if ($cr['requirement_type'] == 1) {
						if ($smeta[$requirement_description] != "") {
							/* 常规（1倍计分）,期中/期末（2倍计分）  */
							$total_scores[$requirement_description]['scores'] += $smeta[$requirement_description]*$cs['test_type'];
							$total_scores[$requirement_description]['counts'] += $cs['test_type'];
						}
					}
					/* 签到  */
					if ($cr['requirement_type'] == 2) {
						/* FULL */
						if ($smeta[$requirement_description] == 1) {
							$total_scores[$requirement_description]['full_counts'] ++ ;
						}
						/* SICK */
						if ($smeta[$requirement_description] == 2) {
							$total_scores[$requirement_description]['sick_counts'] ++ ;
						}
						/* LATE */
						if ($smeta[$requirement_description] == 3) {
							/* 总共迟到的分钟数 */
							$total_scores[$requirement_description]['scores'] += $cs['test_late'];
						}
					}
				}
				if ($cr['requirement_type'] == 1) {
					$total_scores[$requirement_description]['average_score'] = sprintf("%.2f", $total_scores[$requirement_description]['scores']/$total_scores[$requirement_description]['counts']);
						
					if ($final_smeta && $final_smeta[$requirement_description]['score'] != "") {
						$real_score += $final_smeta[$requirement_description]['score']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
					} else {
						$real_score += $total_scores[$requirement_description]['average_score']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
					}
				}
				if ($cr['requirement_type'] == 2) {
					/*总小时数，例如90分钟=1.5小时*/
					$total_scores[$requirement_description]['total_hours'] = sprintf("%.2f",$total_scores[$requirement_description]['scores']/60);
					/*整小时数，例如90分钟=1小时30分钟，取1小时*/
					$total_scores[$requirement_description]['late_hours'] = floor($total_scores[$requirement_description]['scores']/60);
					/*取余分钟数，例如90分钟=1小时30分钟，取30分钟*/
					$total_scores[$requirement_description]['late_mins'] = $total_scores[$requirement_description]['scores']%60;
					$total_scores[$requirement_description]['late_scores'] -= $total_scores[$requirement_description]['late_hours']*0.5;
					if ($final_smeta && $final_smeta[$requirement_description]['score'] != "") {
						$real_score += $final_smeta[$requirement_description]['score']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
					} else {
						$real_score += $total_scores[$requirement_description]['late_scores']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
					}
				}
			}
			$real_score = sprintf("%.2f",$real_score);
			
			$cols = array();
			$cols[] = array(20,'日期','FFFFFF');
			foreach ($course_requirements as $course_requirement) $cols[] = array(20,$course_requirement['requirement_description'],'FFFFFF',$course_requirement['requirement_type']);
			
			//导出
			set_time_limit(0);
			$xls_file_name = $course['course_name']."-".$student['full_name']."-Scores";
			
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
			
			$rowIndex = 3;  //行
			
			if ( count( $course_scores ) > 0 ) {
				foreach ( $course_scores as $course_score ) {
					$colIndex = -1;//列
					$rowIndex++;
			
					$smeta = json_decode($course_score['smeta'],true);
						
					foreach ($cols as $k => $col) {
						$field_name = $col[1];
						$colIndex++;
						if ($k == 0) {
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course_score['test_date'] );
						} else {
							if ($col[3] == 2) {
								if ($smeta[$field_name] == 1) $sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'Full' );
								if ($smeta[$field_name] == 2) $sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'Sick Leave' );
								if ($smeta[$field_name] == 3) $sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'Late' );
							} else {
								$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $smeta[$field_name] );
							}
						}
					}
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial' )
							)
							);
				}
				$colIndex = 0;//列
				$rowIndex = count($course_scores)+4;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '平均成绩' );
				foreach ($course_requirements as $course_requirement) {
					$requirement_description = $course_requirement['requirement_description'];
					$colIndex++;
					if($final_smeta && $final_smeta[$requirement_description]['score'] != "") {
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $final_smeta[$requirement_description]['score'] );
					} else {
						if ($course_requirement['requirement_type'] == 1) {
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $total_scores[$requirement_description]['average_score'] );
						}
						if ($course_requirement['requirement_type'] == 2) {
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $total_scores[$requirement_description]['late_scores'] );
						}
					}
				}
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00FF0000') )
						)
						);
			}
			
			$student_cols = array(array(20,'姓名','FFFFFF'),array(20,'课程','FFFFFF'));
			$rowIndex = 1;//第1行字段名
			$colIndex = 0;//列
			foreach ($student_cols as $col) {
				//第一行
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
				$colIndex = $colIndex+2;
			}
			$colIndex = 1;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $student['full_name'] );
			$colIndex = $colIndex+2;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course['course_name'] );
			$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
					array(
							'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00000000') )
					)
					);
			
			$total_cols = array(array(20,'总成绩','FFFFFF'),array(20,'评级','FFFFFF'));
			$rowIndex = 2;//第2行字段名
			$colIndex = 0;//列
			foreach ($total_cols as $col) {
				//第一行
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
				$colIndex = $colIndex+2;
			}
			$colIndex = 1;
			$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $real_score );
			$colIndex = $colIndex+2;
			if ($real_score >= 92.50 && $real_score <= 100) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'A');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
						)
						);
			}
			if ($real_score >= 89.50 && $real_score <= 92.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'A-');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
						)
						);
			}
			if ($real_score >= 86.50 && $real_score <= 89.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'B+');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
						)
						);
			}
			if ($real_score >= 82.50 && $real_score <= 86.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'B');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
						)
						);
			}
			if ($real_score >= 79.50 && $real_score <= 82.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'B-');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
						)
						);
			}
			if ($real_score >= 76.50 && $real_score <= 79.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'C+');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
						)
						);
			}
			if ($real_score >= 69.50 && $real_score <= 76.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'C');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
						)
						);
			}
			if ($real_score >= 59.50 && $real_score <= 69.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'D');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00FF0000') )
						)
						);
			}
			if ($real_score >= 0 && $real_score <= 59.49) {
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'F');
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00FF0000') )
						)
						);
			}
			
			
			$rowIndex = 3;//第3行字段名
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
		} else {//导出所有报名这门课程的已填写的成绩的学生
			
			//导出
			set_time_limit(0);
				
			$xls_file_name = $course['course_name']."-Scores";
				
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
			
			$student_ids = $this->score_model->alias('s')->field('distinct s.student_id')->join('__USERS__ u ON u.id=s.student_id')->where(array('course_id' => $course_id))->order('full_name asc')->select();
			
			$index = -1;
			$score_rows = 0;
			$score_rows_front = 0;
			foreach ($student_ids as $s) {
				$index++;
				$student_id = $s['student_id'];
				$student = $this->users_model->field('full_name')->find($student_id);
				
				/* 修改后的最终平均成绩 */
				$final_scores = $this->final_score_model->where(array('course_id' => $course_id,'student_id' => $student_id))->find();
				$final_smeta = array();
				if($final_scores) $final_smeta = json_decode($final_scores['smeta'],true);
				/* 某个学生某门课程的所有有效分数 */
				$course_scores = $this->score_model->where(array('course_id' => $course_id,'student_id' => $student_id,'score_status' => 1))->order('test_date asc')->select();
				/* 某门课程的requirement */
				$course_requirements = $this->course_requirement_model->where(array('course_id' => $course_id))->select();
				/* 某个requirement的总分 */
				$total_scores = array();
				/* 整门课程的总分 */
				$real_score = 0;
				foreach ($course_requirements as $cr) {
					$requirement_description = $cr['requirement_description'];
					/* 初始化某个requirement的所有成绩的总分 */
					$total_scores[$requirement_description]['scores'] = 0;
					/* 初始化某个requirement的计算平均分次数 */
					$total_scores[$requirement_description]['counts'] = 0;
					/* 初始化某个requirement的计算平均分(average_score = scores/counts) */
					$total_scores[$requirement_description]['average_score'] = 0;
					/* 初始化签到FULL次数 */
					$total_scores[$requirement_description]['full_counts'] = 0;
					/* 初始化签到SICK次数 */
					$total_scores[$requirement_description]['sick_counts'] = 0;
					/* 初始化签到LATE小时数 */
					$total_scores[$requirement_description]['late_hours'] = 0;
					/* 初始化签到LATE出去整小时剩余的分钟数 */
					$total_scores[$requirement_description]['late_mins'] = 0;
					/* 初始化签到的总分数(late_scores = 10 - late_hours*0.5) */
					$total_scores[$requirement_description]['late_scores'] = 10;
					foreach ($course_scores as $cs) {
						$smeta = json_decode($cs['smeta'],true);
						/* 正常  */
						if ($cr['requirement_type'] == 1) {
							if ($smeta[$requirement_description] != "") {
								/* 常规（1倍计分）,期中/期末（2倍计分）  */
								$total_scores[$requirement_description]['scores'] += $smeta[$requirement_description]*$cs['test_type'];
								$total_scores[$requirement_description]['counts'] += $cs['test_type'];
							}
						}
						/* 签到  */
						if ($cr['requirement_type'] == 2) {
							/* FULL */
							if ($smeta[$requirement_description] == 1) {
								$total_scores[$requirement_description]['full_counts'] ++ ;
							}
							/* SICK */
							if ($smeta[$requirement_description] == 2) {
								$total_scores[$requirement_description]['sick_counts'] ++ ;
							}
							/* LATE */
							if ($smeta[$requirement_description] == 3) {
								/* 总共迟到的分钟数 */
								$total_scores[$requirement_description]['scores'] += $cs['test_late'];
							}
						}
					}
					if ($cr['requirement_type'] == 1) {
						$total_scores[$requirement_description]['average_score'] = sprintf("%.2f", $total_scores[$requirement_description]['scores']/$total_scores[$requirement_description]['counts']);
							
						if ($final_smeta && $final_smeta[$requirement_description]['score'] != "") {
							$real_score += $final_smeta[$requirement_description]['score']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
						} else {
							$real_score += $total_scores[$requirement_description]['average_score']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
						}
					}
					if ($cr['requirement_type'] == 2) {
						/*总小时数，例如90分钟=1.5小时*/
						$total_scores[$requirement_description]['total_hours'] = sprintf("%.2f",$total_scores[$requirement_description]['scores']/60);
						/*整小时数，例如90分钟=1小时30分钟，取1小时*/
						$total_scores[$requirement_description]['late_hours'] = floor($total_scores[$requirement_description]['scores']/60);
						/*取余分钟数，例如90分钟=1小时30分钟，取30分钟*/
						$total_scores[$requirement_description]['late_mins'] = $total_scores[$requirement_description]['scores']%60;
						$total_scores[$requirement_description]['late_scores'] -= $total_scores[$requirement_description]['late_hours']*0.5;
						if ($final_smeta && $final_smeta[$requirement_description]['score'] != "") {
							$real_score += $final_smeta[$requirement_description]['score']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
						} else {
							$real_score += $total_scores[$requirement_description]['late_scores']*$cr['requirement_points_system']*$cr['requirement_grade_percent']/100;
						}
					}
				}
				$real_score = sprintf("%.2f",$real_score);
				
				$cols = array();
				$cols[] = array(20,'日期','FFFFFF');
				foreach ($course_requirements as $course_requirement) $cols[] = array(20,$course_requirement['requirement_description'],'FFFFFF',$course_requirement['requirement_type']);
				
				$score_rows_front = $score_rows_front+count($course_scores);
				$rowIndex = 3*($index+1) + $score_rows + 2*$index;  //行
				
				if ( count( $course_scores ) > 0 ) {
					foreach ( $course_scores as $course_score ) {
						$colIndex = -1;//列
						$rowIndex++;
				
						$smeta = json_decode($course_score['smeta'],true);
							
						foreach ($cols as $k => $col) {
							$field_name = $col[1];
							$colIndex++;
							if ($k == 0) {
								$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course_score['test_date'] );
							} else {
								if ($col[3] == 2) {
									if ($smeta[$field_name] == 1) $sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'Full' );
									if ($smeta[$field_name] == 2) $sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'Sick Leave' );
									if ($smeta[$field_name] == 3) $sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'Late' );
								} else {
									$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $smeta[$field_name] );
								}
							}
						}
						$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
								array(
										'font' => array( 'size' => 10,'name' => 'Arial' )
								)
								);
					}
					$colIndex = 0;//列
					$rowIndex = 4*($index+1) + $score_rows_front + 1*$index;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '平均成绩' );
					foreach ($course_requirements as $course_requirement) {
						$requirement_description = $course_requirement['requirement_description'];
						$colIndex++;
						if($final_smeta && $final_smeta[$requirement_description]['score'] != "") {
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $final_smeta[$requirement_description]['score'] );
						} else {
							if ($course_requirement['requirement_type'] == 1) {
								$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $total_scores[$requirement_description]['average_score'] );
							}
							if ($course_requirement['requirement_type'] == 2) {
								$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $total_scores[$requirement_description]['late_scores'] );
							}
						}
					}
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00FF0000') )
							)
					);
				}
				
				$student_cols = array(array(20,'姓名','FFFFFF'),array(20,'课程','FFFFFF'));
				$rowIndex = 1*($index+1) + $score_rows + 4*$index;//第1行字段名
				$colIndex = 0;//列
				foreach ($student_cols as $col) {
					//第一行
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
					$colIndex = $colIndex+2;
				}
				$colIndex = 1;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $student['full_name'] );
				$colIndex = $colIndex+2;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $course['course_name'] );
				$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
						array(
								'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00000000') )
						)
				);
				
				$total_cols = array(array(20,'总成绩','FFFFFF'),array(20,'评级','FFFFFF'));
				$rowIndex = 2*($index+1) + $score_rows + 3*$index;//第2行字段名
				$colIndex = 0;//列
				foreach ($total_cols as $col) {
					//第一行
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
					$colIndex = $colIndex+2;
				}
				$colIndex = 1;
				$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $real_score );
				$colIndex = $colIndex+2;
				if ($real_score >= 92.50 && $real_score <= 100) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'A');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
							)
					);
				}
				if ($real_score >= 89.50 && $real_score <= 92.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'A-');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
							)
					);
				}
				if ($real_score >= 86.50 && $real_score <= 89.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'B+');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
							)
					);
				}
				if ($real_score >= 82.50 && $real_score <= 86.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'B');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
							)
					);
				}
				if ($real_score >= 79.50 && $real_score <= 82.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'B-');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
							)
					);
				}
				if ($real_score >= 76.50 && $real_score <= 79.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'C+');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
							)
					);
				}
				if ($real_score >= 69.50 && $real_score <= 76.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'C');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00008000') )
							)
					);
				}
				if ($real_score >= 59.50 && $real_score <= 69.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'D');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00FF0000') )
							)
					);
				}
				if ($real_score >= 0 && $real_score <= 59.49) {
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, 'F');
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial','color' => array('argb' => '00FF0000') )
							)
					);
				}
				
				
				$rowIndex = 3*($index+1) + $score_rows + 2*$index;;//第3行字段名
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
				
				$score_rows = $score_rows+count($course_scores);
			}
			
		}
		
		$sheet->getRowDimension(1)->setRowHeight( 18 );
	
		$sheet->setTitle( 'sheet1' );
		header( 'Content-Type: application/vnd.ms-excel' );
		header( "Content-Disposition: attachment;filename=$xls_file_name.xls" );
		header( 'Cache-Control: max-age=0' );
	
		$excelWriter = \PHPExcel_IOFactory::createWriter( $excel, 'Excel5' );
		$excelWriter->save('php://output');
	
		exit;
	}
	function final_delete() {
	
		$course_id = intval( I( 'get.course_id' ) );
		$student_id = intval( I( 'get.student_id' ) );
		$final_scores = $this->final_score_model->where( array('course_id' => $course_id,'student_id' => $student_id) )->find();
		if ($final_scores) {
			if ( $this->final_score_model->where( array('course_id' => $course_id,'student_id' => $student_id) )->delete() !== false ) {
				$this->success(L('修改成功！'));
			} else {
				$this->error(L('修改失败！'));
			}
		} else {
			$this->success(L('当前平均成绩已经是通过成绩算法计算所得'));
		}
	}
	//添加学生成绩
	function add() {
		if ( IS_POST ) {
			$_POST['requirement_id'] = (int)$_POST['course_requirement'];
			$_POST['course_id'] = (int)$_POST['course'];
			if (empty($_POST['course_id'])) $this->error(L('SCORE_MSG6'));
			if (empty($_POST['requirement_id'])) $this->error(L('SCORE_MSG7'));
			
			$score_id = $this->score_model->add($_POST);
			if ($score_id) {
				//记录日志
				LogController::log_record($score_id,1);
			
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
	
		} else {
			$student_id = I('get.student_id');
			$course_id = I('get.course_id');
			$term_year_sess = trim(I('get.term_year_sess'));
			
			$student = $this->users_model->find($student_id);
			
			//课程下拉框
			$course_html = " <option value='0'>".L('SCORE_ENROLLED_COURSES')."</option>";
			$student_courses = $this->course_model
									->alias('c')
									->field('c.id,c.course_name')
									->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.course_id=c.id')
									->where(array('cs.student_id' => $student_id,'cs.course_student_status' => 1))
									->select();
			if ($course_id) {
				
				foreach ($student_courses as $student_course) {
					$course_html .= "<option";
					if($student_course['id'] == $course_id) {
						$course_html .= " selected";
					}
					$course_html .= " value='".$student_course['id']."'>".$student_course['course_name']."</option>";
				}
				
				//requirment
				$course_requirements = $this->course_requirement_model->where(array('course_id' => $course_id))->select();
				$course_requirement_html = "<option value='0'>".L('SCORE_TEST')."</option>";
				foreach ($course_requirements as $course_requirement) {
					$course_requirement_html .= "<option value='".$course_requirement['id']."'>".$course_requirement['requirement_description']."</option>";
				}
			} else {
				foreach ($student_courses as $student_course) {
					$course_html .= "<option value='".$student_course['id']."'>".$student_course['course_name']."</option>";
				}
				$course_requirement_html = "<option value='0'>".L('SCORE_TEST')."</option>";
			}
			$this->assign('course_html',$course_html);
			$this->assign('course_requirement_html',$course_requirement_html);
			$this->assign('student',$student);
			$this->assign('course_id',$course_id);
			$this->assign('term_year_sess',$term_year_sess);
			
		}
		$this->display();
	}
	//获取某个课程下的requirement
	function getRequirements() {
		if (IS_AJAX){
			$course_id = (int)I('course_id');
			$course_requirements = $this->course_requirement_model->where(array('course_id' => $course_id))->select();
			$course_requirement_html = "<option value='0'>".L('SCORE_TEST')."</option>";
			foreach ($course_requirements as $course_requirement) {
				$course_requirement_html .= "<option value='".$course_requirement['id']."'>".$course_requirement['requirement_description']."</option>";
			}
			$this->ajaxReturn($course_requirement_html);
		}
	}
	
	//成绩详情
	function detail() {
		$where = array();
		$entry = trim(I('get.entry'));
		$student_id = (int)I('get.student_id');
		$where['student_id'] = $student_id;
		
		$course_id = (int)I('request.course_id');
		
		$student = $this->users_model->where(array('id' => $student_id))->find();
		
		$term_year_sess_val = trim($student['year'])."-".trim($student['term'])."-".trim($student['session'])."-".trim($student['program']);
		//课程下拉框
		$course_html = " <option value='0'>".L('SCORE_ENROLLED_COURSES')."</option>";
		$courses = $this->course_model
						->alias('c')
						->field('c.id,c.course_name,c.course_code')
						->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.course_id=c.id')
						->where(array('cs.student_id' => $student_id,'cs.course_student_status' => 1))
						->select();
		if ($course_id) {
			$where['course_id'] = $course_id;
			
			foreach ($courses as $course) {
				$course_html .= "<option";
				if($course['id'] == $course_id) {
					$course_html .= " selected";
				}
				$course_html .= " value='".$course['id']."'>".$course['course_name']."(".$course['course_code'].")</option>";
			}
		} else {
			foreach ($courses as $course) {
				$course_html .= "<option value='".$course['id']."'>".$course['course_name']."(".$course['course_code'].")</option>";
			}
		}
		
		$where['course_student_status'] = 1;
		$student_courses = $this->course_model
								->alias('c')
								->join('__COURSE_STUDENT_RELATIONSHIP__ cs ON cs.course_id=c.id')
								->where($where)
								->select();
		$this->assign('course_id',$course_id);
		$this->assign('entry',$entry);
		$this->assign('student',$student);
		$this->assign('student_courses',$student_courses);
		$this->assign('course_html',$course_html);
	
		$this->display();
	}
	//批量设置成绩为有效或无效
	function toggle() {
	
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['score_status'] = 1;
			if ( $this->score_model->where( "id in ($ids)" )->save( $data ) !== false ) {
	
				$this->success(L('SET_SUCCESS'));
			} else {
				$this->error(L('SET_FAILED'));
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['score_status'] = 0;
			if ( $this->score_model->where( "id in ($ids)" )->save( $data ) !== false ) {
	
				$this->success(L('SET_SUCCESS'));
			} else {
				$this->error(L('SET_FAILED'));
			}
		}
	}
	//编辑成绩
	function edit() {
		if ( IS_POST ) {
			$score_id = (int)$_POST['id'];
			unset($_POST['id']);
			$result = $this->score_model->where(array('id' => $score_id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($score_id,2);
	
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$course_id = intval( I( 'get.course_id' ) );
			
			$score = $this->score_model->find($id);
			$this->assign('score',$score);
			$this->assign('course_id',$course_id);
			
	
			$this->display();
		}
	}

	//成绩评级：学生列表
	function grade_first_level() {
		$where = array();
		$where['u.user_type'] = array('eq',2,'and');
		$count = $this->users_model
		->alias('u')
		->field('u.id,u.first_name,u.last_name,c.id as cid,c.class_name')
		->join('__CLASS_USER_RELATIONSHIP__ cu ON u.passport_number=cu.user_id')
		->join('__CLASS__ c ON cu.class_id=c.id')
		->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->users_model
		->alias('u')
		->field('u.id,u.first_name,u.last_name,c.id as cid,c.class_name')
		->join('__CLASS_USER_RELATIONSHIP__ cu ON u.passport_number=cu.user_id')
		->join('__CLASS__ c ON cu.class_id=c.id')
		->where($where)->limit( $page->firstRow, $page->listRows )->order("c.class_name desc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}

	//成绩评级：学生课程列表
	function grade_second_level() {
		$student_id = I('get.student_id');
		$class_id = I('get.class_id');
		$count = $this->course_model->where(array('class_id' => $class_id,'course_status' => 1))->count();
		$page = $this->page($count, 20);
		$list = $this->course_model->where(array('class_id' => $class_id,'course_status' => 1))->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->assign('student_id',$student_id);
		$this->display();
	}
		
		
	//学生成绩评级一览
	function grade_third_level() {
		$course_id = I('get.course_id');
		$student_id = I('get.student_id');
		$list = $this->score_model->where(array('student_id' => $student_id,'course_id' => $course_id))->select();
		$total_pro = 0;//总比例
		foreach ($list as $score) {
			$total_pro = $total_pro + $score['requirement_grade_percent']/10;
		}
		$real_pro = 100/$total_pro;//实际占比
		$total_score = 0;
		foreach ($list as $score) {
			$total_score = $total_score + $score['test_score']*$score['requirement_grade_percent']*$real_pro/1000;
		}
		$this->assign('total_pro',$total_pro);
		$this->assign('total_score',$total_score);
		$this->display();
	}


	

	/**
	 * 学季三级联动下拉框
	 * @param $department_id
	 * @param $major_id
	 * @param $category_id
	 */
	private function getSel($department_id,$major_id,$category_id,$course_id,$requirement_description) {
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
		//course
		$courses = $this->course_model->where(array('cid' => $category_id,'course_status' => 1))->select();
		$course_html = " <option value='0'>请选择course</option>";
		foreach ($courses as $course) {
			$course_html .= "<option";
			if($course['id'] == $course_id) {
				$course_html .= " selected";
			}
			$course_html .= " value='".$course['id']."'>".$course['course_name']."</option>";
		}
		$this->assign('course_html',$course_html);
		//课程考试类型
		$req_descriptions = $this->course_requirement_model->where('course_id = '.$course_id)->select();
		$req_description_html = " <option value='0'>请选择考试类型</option>";
		foreach ($req_descriptions as $req_description) {
			$req_description_html .= "<option";
			if($req_description['id'] == $requirement_description) {
				$req_description_html .= " selected";
			}
			$req_description_html .= " value='".$req_description['id']."'>".$req_description['requirement_description']."</option>";
		}
		$this->assign('req_description_html',$req_description_html);
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


	//获取某个课程下所有考试类型
	function getRequirementDescription() {
		if (IS_AJAX){
			$course_id = I('course_id');
			$req_descriptions = $this->course_requirement_model->where('course_id = '.$course_id)->select();
			$req_description_html = " <option value ='0'>请选择考试类型</option>";
			foreach ($req_descriptions as $req_description) {
				$req_description_html .= "<option value='".$req_description['id']."'>".$req_description['requirement_description']."</option>";
			}
			$this->ajaxReturn($req_description_html);
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
	//删除成绩
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['score_status'] = 2;
			if ( $this->score_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);

				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['score_status'] = 1;
			if ( $this->score_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( '恢复成功！' );
			} else {
				$this->error( '恢复失败！' );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->score_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( '彻底删除成功！' );
			} else {
				$this->error( '彻底删除失败！' );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['score_status'] = 2;
			if ( $this->score_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( '删除成功！' );
			} else {
				$this->error( '删除失败！' );
			}
		}
	}


}