<?php
/** 
 * 前端评估
 * 11k
 * likun_19911227@163.com
 */
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class AssessmentController extends HomebaseController {

	private $formData = array();
	private $formError = array();
	private $formReturn = array();
	private $assessment_model;
	private $assessment_question_model;
	private $assessment_answer_model;
	private $users_model;
	private $part_time_teacher_model;
	private $course_mentor_model;
	private $course_model;
	private $house_user_model;

	function _initialize() {
		parent::_initialize();

		$this->assessment_model = D( 'Assessment' );
		$this->assessment_question_model = D( 'AssessmentQuestion' );
		$this->assessment_answer_model = D( 'AssessmentAnswer' );
		$this->users_model = D( 'Users' );
		$this->part_time_teacher_model = D( 'PartTimeTeacher' );
		$this->course_mentor_model = D( 'CourseMentorRelationship' );
		$this->course_model = D( 'Course' );
		$this->house_user_model = D( 'HouseUserRelationship' );
	}
	//评估列表
	public function index() {
		$where = array();
		
		$where['assessment_status'] = array('eq',1);
		$count = $this->assessment_model->where($where)->count();
		$page = $this->page($count, 12);
		$list = $this->assessment_model->where($where)->limit( $page->firstRow, $page->listRows )->order("listorder asc,assessment_modify_time desc")->select();
		$this->assign("page", $page->show('Portal'));
		$this->assign( 'list', $list );
		$this->display( '/../assessment' );
	}
	public function detail() {
		$id = (int)$_GET['id'];
		$assessment = $this->assessment_model->find($id);
	
		if ( empty( $assessment ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			header( 'Status:404 Not Found' );
			if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
			return;
		}
		
		$this->assign( $assessment );
		
		$user_id = get_current_userid();
		
		if ( $user_id ) {
			//教师评估
			if ($assessment['assessment_type'] == 2) {
				//所报名课程中的兼职教师id
				$student_parttimeteacher_ids = array();
				$student_parttimeteachers = $this->course_model->alias('c')->field('distinct c.parttimeteacher_id')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.course_id=c.id')->where(array('csr.student_id' => $user_id,'csr.course_student_status' => 1))->select();
				foreach ($student_parttimeteachers as $student_parttimeteacher) {
					$student_parttimeteacher_ids = array_merge($student_parttimeteacher_ids,explode(',', $student_parttimeteacher['parttimeteacher_id']));
				}
				//所报课程的主导教师id
				$student_headteacher_ids = array();
				$student_headteachers = $this->course_model->alias('c')->field('distinct c.headteacher_id')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.course_id=c.id')->where(array('csr.student_id' => $user_id,'csr.course_student_status' => 1))->select();
				foreach ($student_headteachers as $student_headteacher) $student_headteacher_ids[] = $student_headteacher['headteacher_id'];
					
				//所报名课程的所有教师id
				$course_teacher_ids = array_merge($student_parttimeteacher_ids,$student_headteacher_ids);
				//已评估教师id
				$assessment_teachers = $this->assessment_answer_model->field('distinct teacher_id')->where(array('student_id' => $user_id,'assessment_id' => $assessment['id']))->select();
				$assessment_teacher_ids = array();
				foreach ($assessment_teachers as $assessment_teacher) $assessment_teacher_ids[] = $assessment_teacher['teacher_id'];
				//可进行评估的教师id
				$teacher_ids = array_diff($course_teacher_ids, $assessment_teacher_ids);
				$teachers = $this->users_model->field('id,full_name')->where(array('id' => array('in',implode(',', $teacher_ids))))->select();
				$this->assign('teachers',$teachers);
			}
			
			//同屋评估，只对中国同屋和友好家庭进行评估
			if ($assessment['assessment_type'] == 3) {
				$roommate = $this->house_user_model->where(array('user_id' => $user_id))->find();//查出评估对象
				$assessment_answer = $this->assessment_answer_model->where(array('roommate_id' => $roommate['owner_id'],'roommate_type' => $roommate['flg']))->select();
				$this->assign('roommate',$roommate);
				$this->assign('assessment_answer',$assessment_answer);
			}
			$assessment_questions1 = $this->assessment_question_model->where( array( 'assessment_id' => $id, 'question_type' => 1 ) )->select();
			$assessment_questions2 = $this->assessment_question_model->where( array( 'assessment_id' => $id, 'question_type' => 2 ) )->select();
			$assessment_questions3 = $this->assessment_question_model->where( array( 'assessment_id' => $id, 'question_type' => 3 ) )->select();
			$this->assign( 'assessment_questions1', $assessment_questions1 );
			$this->assign( 'assessment_questions2', $assessment_questions2 );
			$this->assign( 'assessment_questions3', $assessment_questions3 );
				
			if ( IS_POST ) {
				$assessment_answer_add = array();
		
				$assessment_type = (int)$_POST['assessment_type'];
				$id = (int)$_POST['id'];
				
				foreach ($assessment_questions1 as $aq1) {
					$danxuan = $_POST['danxuan'.$aq1['id']];
					if ($danxuan) {
						if ($assessment_type == 2) {//教师评估
							if ($_POST['teacher_id']) {
								$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'teacher_id' => $_POST['teacher_id'],'assessment_question_id' => $aq1['id'],'assessment_answer' => $danxuan);
							}else {
								$this->formError[] = '请至少选择一名教师！';
								break;
							}
						} elseif ($assessment_type == 3) {//同屋评估
							$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'roommate_id' => $_POST['roommate_id'],'roommate_type' => $_POST['roommate_type'],'assessment_question_id' => $aq1['id'],'assessment_answer' => $danxuan);
						} else {
							$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'assessment_question_id' => $aq1['id'],'assessment_answer' => $danxuan);
						}
					} else {
						$this->formError[] = '有未作答的单选题！';
						break;
					}
				}
				foreach ($assessment_questions2 as $aq2) {
					$duoxuan = $_POST['duoxuan'.$aq2['id']];
					if ($duoxuan) {
						if ($assessment_type == 2) {//教师评估
							if ($_POST['teacher_id']) {
								foreach ($duoxuan as $dx) {
									$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'teacher_id' => $_POST['teacher_id'],'assessment_question_id' => $aq2['id'],'assessment_answer' => $dx);
								}
							} else {
								$this->formError[] = '请至少选择一名教师！';
								break;
							}
						} elseif ($assessment_type == 3) {//同屋评估
							foreach ($duoxuan as $dx) {
								$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'roommate_id' => $_POST['roommate_id'],'roommate_type' => $_POST['roommate_type'],'assessment_question_id' => $aq2['id'],'assessment_answer' => $dx);
							}
						} else {
							foreach ($duoxuan as $dx) {
								$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'assessment_question_id' => $aq2['id'],'assessment_answer' => $dx);
							}
						}
					} else {
						$this->formError[] = '有未作答的多选题！';
						break;
					}
				}
				foreach ($assessment_questions3 as $aq3) {
					$wenda = $_POST['wenda'.$aq3['id']];
					if ($wenda) {
						if ($assessment_type == 2) {//教师评估
							if ($_POST['teacher_id']) {
								$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'teacher_id' => $_POST['teacher_id'],'assessment_question_id' => $aq3['id'],'assessment_answer' => $wenda);
							}else {
								$this->formError[] = '请至少选择一名教师！';
								break;
							}
						} elseif ($assessment_type == 3) {//同屋评估
							$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'roommate_id' => $_POST['roommate_id'],'roommate_type' => $_POST['roommate_type'],'assessment_question_id' => $aq3['id'],'assessment_answer' => $wenda);
						} else {
							$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'assessment_question_id' => $aq3['id'],'assessment_answer' => $wenda);
						}
					} else {
						$this->formError[] = '有未作答的问答题！';
						break;
					}
				}
		
				if ( !$this->formError ) {
					foreach ($assessment_answer_add as $assessment_answer) {
						$this->assessment_answer_model->add($assessment_answer);
					}
					$this->success('Evaluate success!',U('assessment/detail',array('id' => $id)));
				} else {
					$this->formReturn['success'] = false;
					$this->formReturn['msg'] = 'Evaluate failed!';
				}
			}
				
			$this->assign( 'formData', $this->formData );
			$this->assign( 'formError', $this->formError );
			$this->assign( 'formReturn', $this->formReturn );
				
		}
	
		$this->display( '/../assessment_detail' );
	}
	public function confirm() {
		
		$user_id = get_current_userid();
		
		if ( $user_id ) {
			//所报名课程中的兼职教师id
			$student_mentor_ids = array();
			$student_mentors = $this->course_mentor_model->alias('cm')->field('cm.mentor_id')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.course_id=cm.course_id')->where(array('csr.student_id' => $user_id,'csr.course_student_status' => 1))->select();
			foreach ($student_mentors as $student_mentor) $student_mentor_ids[] = $student_mentor['mentor_id'];
			//所报课程的主导教师id
			$student_headteacher_ids = array();
			$student_headteachers = $this->course_model->alias('c')->field('c.headteacher_id')->join('__COURSE_STUDENT_RELATIONSHIP__ csr ON csr.course_id=c.id')->where(array('csr.student_id' => $user_id,'csr.course_student_status' => 1))->select();
			foreach ($student_headteachers as $student_headteacher) $student_headteacher_ids[] = $student_headteacher['headteacher_id'];
			
			$id = (int)I('request.id');
			
			$assessment = $this->assessment_model->find($id);
			$this->assign( $assessment );
			
			if ($assessment['parttimeteacher_ids']) {
				//参与此评估的兼职教师id
				$parttimeteacher_ids_arr = explode(',', $assessment['parttimeteacher_ids']);
				//此学生可进行评估的兼职教师id
				$parttimeteacher_ids = array_intersect($student_mentor_ids, $parttimeteacher_ids_arr);
				$parttimeteacher_html = " <option value='0'>兼职教师</option>";
				if ($parttimeteacher_ids) {
					$parttimeteacher_ids_str = implode(',', $parttimeteacher_ids);
					$parttimeteachers = $this->part_time_teacher_model->where(array('id' => array('in',$parttimeteacher_ids_str)))->select();
						
					foreach ($parttimeteachers as $parttimeteacher) {
						$parttimeteacher_html .= "<option value='".$parttimeteacher['id']."'>".$parttimeteacher['first_name']." ".$parttimeteacher['last_name']."</option>";
					}
				}
				$this->assign('parttimeteacher_html',$parttimeteacher_html);
			}
			if ($assessment['headteacher_ids']) {
				//参与此评估的主导教师id
				$headteacher_ids_arr = explode(',', $assessment['headteacher_ids']);
				//此学生可进行评估的主导教师id
				$headteacher_ids = array_intersect($student_headteacher_ids, $headteacher_ids_arr);
				$headteacher_html = " <option value='0'>主导教师</option>";
				if ($headteacher_ids) {
					$headteacher_ids_str = implode(',', $headteacher_ids);
					$headteachers = $this->users_model->where(array('id' => array('in',$headteacher_ids_str)))->select();
						
					foreach ($headteachers as $headteacher) {
						$headteacher_html .= "<option value='".$headteacher['id']."'>".$headteacher['full_name']."</option>";
					}
				}
				$this->assign('headteacher_html',$headteacher_html);
			}
			
			if ( empty( $assessment ) ) {
				header( 'HTTP/1.1 404 Not Found' );
				header( 'Status:404 Not Found' );
				if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
				return;
			}
			
			$assessment_questions1 = $this->assessment_question_model->where( array( 'assessment_id' => $id, 'question_type' => 1 ) )->select();
			$assessment_questions2 = $this->assessment_question_model->where( array( 'assessment_id' => $id, 'question_type' => 2 ) )->select();
			$assessment_questions3 = $this->assessment_question_model->where( array( 'assessment_id' => $id, 'question_type' => 3 ) )->select();
			$this->assign( 'assessment_questions1', $assessment_questions1 );
			$this->assign( 'assessment_questions2', $assessment_questions2 );
			$this->assign( 'assessment_questions3', $assessment_questions3 );
			
			if ( IS_POST ) {
				$assessment_answer_add = array();
				
				if ($assessment['headteacher_ids'] || $assessment['parttimeteacher_ids']) {
					$headteacher_id = $_POST['headteacher'];
					$parttimeteacher_id = $_POST['parttimeteacher'];
					
					if ( $headteacher_id && $parttimeteacher_id ) {
						$this->formError[] = '一次只能选择一个教师';
					} elseif ($headteacher_id && empty($parttimeteacher_id)) {
						$student_headteacher_answers = $this->assessment_answer_model->where(array('student_id' => $user_id,'headteacher_id' => $headteacher_id,'assessment_id' => $id))->select();
						if ($student_headteacher_answers) {
							$this->formError[] = '您对此主导教师已进行评估！';
						} else {
							foreach ($assessment_questions1 as $aq1) {
								$danxuan = $_POST['danxuan'.$aq1['id']];
								if ($danxuan) {
									$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'headteacher_id' => $headteacher_id,'assessment_question_id' => $aq1['id'],'assessment_answer' => $danxuan);
								} else {
									$this->formError[] = '有未作答的单选题！';
									break;
								}
							}
							foreach ($assessment_questions2 as $aq2) {
								$duoxuan = $_POST['duoxuan'.$aq2['id']];
								if ($duoxuan) {
									foreach ($duoxuan as $dx) {
										$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'headteacher_id' => $headteacher_id,'assessment_question_id' => $aq2['id'],'assessment_answer' => $dx);
									}
								} else {
									$this->formError[] = '有未作答的多选题！';
									break;
								}
							}
							foreach ($assessment_questions3 as $aq3) {
								$wenda = $_POST['wenda'.$aq3['id']];
								if ($wenda) {
									$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'headteacher_id' => $headteacher_id,'assessment_question_id' => $aq3['id'],'assessment_answer' => $wenda);
								} else {
									$this->formError[] = '有未作答的问答题！';
									break;
								}
							}
						}
					} elseif (empty($headteacher_id) && $parttimeteacher_id) {
						$student_parttimeteacher_answers = $this->assessment_answer_model->where(array('student_id' => $user_id,'parttimeteacher_id' => $parttimeteacher_id,'assessment_id' => $id))->select();
						if ($student_parttimeteacher_answers) {
							$this->formError[] = '您对此兼职教师已进行评估！';
						} else {
							foreach ($assessment_questions1 as $aq1) {
								$danxuan = $_POST['danxuan'.$aq1['id']];
								if ($danxuan) {
									$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'parttimeteacher_id' => $parttimeteacher_id,'assessment_question_id' => $aq1['id'],'assessment_answer' => $danxuan);
								} else {
									$this->formError[] = '有未作答的单选题！';
									break;
								}
							}
							foreach ($assessment_questions2 as $aq2) {
								$duoxuan = $_POST['duoxuan'.$aq2['id']];
								if ($duoxuan) {
									foreach ($duoxuan as $dx) {
										$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'parttimeteacher_id' => $parttimeteacher_id,'assessment_question_id' => $aq2['id'],'assessment_answer' => $dx);
									}
								} else {
									$this->formError[] = '有未作答的多选题！';
									break;
								}
							}
							foreach ($assessment_questions3 as $aq3) {
								$wenda = $_POST['wenda'.$aq3['id']];
								if ($wenda) {
									$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'parttimeteacher_id' => $parttimeteacher_id,'assessment_question_id' => $aq3['id'],'assessment_answer' => $wenda);
								} else {
									$this->formError[] = '有未作答的问答题！';
									break;
								}
							}
						}
					} else {
						$this->formError[] = '请至少选择一个教师！';
					}
				} else {
					$student_answers = $this->assessment_answer_model->where(array('student_id' => $user_id,'assessment_id' => $id))->select();
					if ($student_answers) {
						$this->formError[] = '您已进行过评估！';
					} else {
						foreach ($assessment_questions1 as $aq1) {
							$danxuan = $_POST['danxuan'.$aq1['id']];
							if ($danxuan) {
								$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'assessment_question_id' => $aq1['id'],'assessment_answer' => $danxuan);
							} else {
								$this->formError[] = '有未作答的单选题！';
								break;
							}
						}
						foreach ($assessment_questions2 as $aq2) {
							$duoxuan = $_POST['duoxuan'.$aq2['id']];
							if ($duoxuan) {
								foreach ($duoxuan as $dx) {
									$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'assessment_question_id' => $aq2['id'],'assessment_answer' => $dx);
								}
							} else {
								$this->formError[] = '有未作答的多选题！';
								break;
							}
						}
						foreach ($assessment_questions3 as $aq3) {
							$wenda = $_POST['wenda'.$aq3['id']];
							if ($wenda) {
								$assessment_answer_add[] = array('assessment_id' => $id,'student_id' => $user_id,'assessment_question_id' => $aq3['id'],'assessment_answer' => $wenda);
							} else {
								$this->formError[] = '有未作答的问答题！';
								break;
							}
						}
					}
				}
			
				
				if ( !$this->formError ) {
					foreach ($assessment_answer_add as $assessment_answer) {
						$this->assessment_answer_model->add($assessment_answer);
					}
					$this->success('评估提交成功','assessment/index');
				} else {
					$this->formReturn['success'] = false;
					$this->formReturn['msg'] = '提交评估失败';
				}
			}
			
			$this->assign( 'formData', $this->formData );
			$this->assign( 'formError', $this->formError );
			$this->assign( 'formReturn', $this->formReturn );
			
			
			$this->display( '/../assessment_confirm' );
		} else {
			redirect( U( 'user/login/index' ) );
		}
	
	}

}