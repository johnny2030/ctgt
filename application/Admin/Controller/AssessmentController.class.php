<?php
/** 
 * 后台管理系统之评估管理
 * @author 11k
 * likun_19911227@163.com
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AssessmentController extends AdminbaseController {

	private $assessment_model;
	private $assessment_question_model;
	private $assessment_answer_model;
	private $users_model;
	private $part_time_teacher_model;
	private $recruit_member_model;
	private $homestay_model;

	function _initialize() {
		parent::_initialize();

		$this->assessment_model = D( 'Assessment' );
		$this->assessment_question_model = D( 'AssessmentQuestion' );
		$this->assessment_answer_model = D( 'AssessmentAnswer' );
		$this->users_model = D( 'Users' );
		$this->part_time_teacher_model = D( 'PartTimeTeacher' );
		$this->recruit_member_model = D( 'RecruitMember' );
		$this->homestay_model = D( 'Homestay' );
	}
	//评估列表
	function index() {
		
		$where = array();
		//评估名称搜索
		$keyword=I('request.keyword');
		$this->assign( 'keyword', $keyword );
		$assessment_type = (int)$_REQUEST['assessment_type'];
		$this->assign( 'assessment_type', $assessment_type );
		
		if ( !empty($keyword) ) {
			$where['assessment_name'] = array('like',"%$keyword%");
		}
		if ($assessment_type) $where['assessment_type'] = $assessment_type;
		$where['assessment_status'] = array('neq',2);
		$count = $this->assessment_model->where($where)->count();
		$page = $this->page($count, 10);
		$list = $this->assessment_model->where($where)->limit( $page->firstRow, $page->listRows )->order("listorder asc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//添加评估
	function add() {
		if ( IS_POST ) {
			if (empty($_POST['assessment_name'])) $this->error(L('ASSESSMENT_MSG1'));
			if (empty($_POST['term_year_sess'])) $this->error(L('ASSESSMENT_MSG0'));
			
			//单选题
			$assessment_questions1 = array();
			$index1 = 0;
			foreach ($_POST['assessment_question1'] as $k => $assessment_question1){
				$assessment_question1 = trim($assessment_question1);
				$option_counts = (int)$_POST['option_counts1'][$k];
				$assessment_option1_arr = array();
				for ($i=$index1; $i<$index1+$option_counts; $i++) $assessment_option1_arr[] = trim($_POST['assessment_option1'][$i]);
				$index1 += $option_counts;
				
				$assessment_option1 = json_encode($assessment_option1_arr);
				
				if ($assessment_question1) $assessment_questions1[] = array('assessment_question' => $assessment_question1,'assessment_option' => $assessment_option1,'question_type' => 1);
			}
			//多选题
			$assessment_questions2 = array();
			$index2 = 0;
			foreach ($_POST['assessment_question2'] as $k => $assessment_question2){
				$assessment_question2 = trim($assessment_question2);
				$option_counts = (int)$_POST['option_counts2'][$k];
				$assessment_option2_arr = array();
				for ($i=$index2; $i<$index2+$option_counts; $i++) $assessment_option2_arr[] = trim($_POST['assessment_option2'][$i]);
				$index2 += $option_counts;
				
				$assessment_option2 = json_encode($assessment_option2_arr);
			
				if ($assessment_question2) $assessment_questions2[] = array('assessment_question' => $assessment_question2,'assessment_option' => $assessment_option2,'question_type' => 2);
			}
			//问答题
			$assessment_questions3 = array();
			foreach ($_POST['assessment_question3'] as $k => $assessment_question3){
				$assessment_question3 = trim($assessment_question3);
			
				if ($assessment_question3) $assessment_questions3[] = array('assessment_question' => $assessment_question3,'question_type' => 3);
			}
			
			$_POST['term_year_sess'] = json_encode($_POST['term_year_sess']);
			$_POST['assessment_content'] = htmlspecialchars_decode($_POST['assessment_content']);
			$_POST['assessment_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			
			if ($_POST['headteachers']) {
				$_POST['headteacher_ids'] = implode(',', $_POST['headteachers']);
			} else {
				unset($_POST['headteachers']);
			}
			if ($_POST['parttimeteachers']) {
				$_POST['parttimeteacher_ids'] = implode(',', $_POST['parttimeteachers']);
			} else {
				unset($_POST['parttimeteachers']);
			}
			
			unset($_POST['assessment_question1']);
			unset($_POST['assessment_option1']);
			unset($_POST['assessment_question2']);
			unset($_POST['assessment_option2']);
			unset($_POST['assessment_question3']);
			
			$assessment_id = $this->assessment_model->add($_POST);
			if ($assessment_id) {
				foreach ($assessment_questions1 as $assessment_question1) {
					$assessment_question1['assessment_id'] = $assessment_id;
					$this->assessment_question_model->add($assessment_question1);
				}
				foreach ($assessment_questions2 as $assessment_question2) {
					$assessment_question2['assessment_id'] = $assessment_id;
					$this->assessment_question_model->add($assessment_question2);
				}
				foreach ($assessment_questions3 as $assessment_question3) {
					$assessment_question3['assessment_id'] = $assessment_id;
					$this->assessment_question_model->add($assessment_question3);
				}
				
				//记录日志
				LogController::log_record($assessment_id,1);
				
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			//选择学季
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where[] = "session != '' AND session is not null";
			$where[] = "program != '' AND program is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$terms = $this->users_model->field('distinct term,year,session,program')->where($map)->order('year desc')->select();
			$term_html = " ";
			foreach ($terms as $term) {
				$term_year_sess_str = trim($term['year'])."-".trim($term['term'])."-".trim($term['session'])."-".trim($term['program']);
				$term_html .= "<option value='".$term_year_sess_str."'>".$term_year_sess_str."</option>";
			}
			$this->assign('term_html',$term_html);
			$this->display();
		}
	}
	//编辑课程
	function edit() {
		if ( IS_POST ) {
			$assessment_id = (int)$_POST['id'];
			
			if (empty($_POST['assessment_name'])) $this->error(L('ASSESSMENT_MSG1'));
			if (empty($_POST['term_year_sess'])) $this->error(L('ASSESSMENT_MSG0'));
			
			//单选题
			$assessment_questions1 = $this->assessment_question_model->where(array('assessment_id' => $assessment_id,'question_type' => 1))->select();
			$assessment_question_ids1 = array();
			foreach ($assessment_questions1 as $assessment_question1) $assessment_question_ids1[] = $assessment_question1['id']; 
				
			$assessment_question_add1 = array();
			$assessment_question_edit1 = array();
			$assessment_question_del_ids1 = array();
			$assessment_question_edit_ids1 = array();
			$index1 = 0;
			foreach ($_POST['assessment_question1'] as $k => $assessment_question1){
				$assessment_question_id1 = (int)$_POST['assessment_question_id1'][$k];
				$assessment_question1 = trim($assessment_question1);
				$option_counts = (int)$_POST['option_counts1'][$k];
				$assessment_option1_arr = array();
				for ($i=$index1; $i<$index1+$option_counts; $i++) $assessment_option1_arr[] = trim($_POST['assessment_option1'][$i]);
				$index1 += $option_counts;
				
				$assessment_option1 = json_encode($assessment_option1_arr);
				
				if ($assessment_question1) {
					if (in_array($assessment_question_id1, $assessment_question_ids1)){
						//修改
						$assessment_question_edit1[] = array('id' => $assessment_question_id1,'assessment_id' => $assessment_id,'assessment_question' => $assessment_question1,'assessment_option' => $assessment_option1);		
						$assessment_question_edit_ids1[] = $assessment_question_id1;
					} else {
						//新增
						$assessment_question_add1[] = array('assessment_id' => $assessment_id,'assessment_question' => $assessment_question1,'assessment_option' => $assessment_option1,'question_type' => 1);
					}
				}
			}
			foreach ($assessment_question_ids1 as $assessment_question_id1) {
				if (!in_array($assessment_question_id1, $assessment_question_edit_ids1)) $assessment_question_del_ids1[] = $assessment_question_id1;
			}
			//多选题
			$assessment_questions2 = $this->assessment_question_model->where(array('assessment_id' => $assessment_id,'question_type' => 2))->select();
			$assessment_question_ids2 = array();
			foreach ($assessment_questions2 as $assessment_question2) $assessment_question_ids2[] = $assessment_question2['id'];
			
			$assessment_question_add2 = array();
			$assessment_question_edit2 = array();
			$assessment_question_del_ids2 = array();
			$assessment_question_edit_ids2 = array();
			$index2 = 0;
			foreach ($_POST['assessment_question2'] as $k => $assessment_question2){
				$assessment_question_id2 = (int)$_POST['assessment_question_id2'][$k];
				$assessment_question2 = trim($assessment_question2);
				$option_counts = (int)$_POST['option_counts2'][$k];
				$assessment_option2_arr = array();
				for ($i=$index2; $i<$index2+$option_counts; $i++) $assessment_option2_arr[] = trim($_POST['assessment_option2'][$i]);
				$index2 += $option_counts;
				
				$assessment_option2 = json_encode($assessment_option2_arr);
			
				if ($assessment_question2) {
					if (in_array($assessment_question_id2, $assessment_question_ids2)){
						//修改
						$assessment_question_edit2[] = array('id' => $assessment_question_id2,'assessment_id' => $assessment_id,'assessment_question' => $assessment_question2,'assessment_option' => $assessment_option2);
						$assessment_question_edit_ids2[] = $assessment_question_id2;
					} else {
						//新增
						$assessment_question_add2[] = array('assessment_id' => $assessment_id,'assessment_question' => $assessment_question2,'assessment_option' => $assessment_option2,'question_type' => 2);
					}
				}
			}
			foreach ($assessment_question_ids2 as $assessment_question_id2) {
				if (!in_array($assessment_question_id2, $assessment_question_edit_ids2)) $assessment_question_del_ids2[] = $assessment_question_id2;
			}
			//问答
			$assessment_questions3 = $this->assessment_question_model->where(array('assessment_id' => $assessment_id,'question_type' => 3))->select();
			$assessment_question_ids3 = array();
			foreach ($assessment_questions3 as $assessment_question3) $assessment_question_ids3[] = $assessment_question3['id'];
			
			$assessment_question_add3 = array();
			$assessment_question_edit3 = array();
			$assessment_question_del_ids3 = array();
			$assessment_question_edit_ids3 = array();
			foreach ($_POST['assessment_question3'] as $k => $assessment_question3){
				$assessment_question_id3 = (int)$_POST['assessment_question_id3'][$k];
				$assessment_question3 = trim($assessment_question3);
			
				if ($assessment_question3) {
					if (in_array($assessment_question_id3, $assessment_question_ids3)){
						//修改
						$assessment_question_edit3[] = array('id' => $assessment_question_id3,'assessment_id' => $assessment_id,'assessment_question' => $assessment_question3);
						$assessment_question_edit_ids3[] = $assessment_question_id3;
					} else {
						//新增
						$assessment_question_add3[] = array('assessment_id' => $assessment_id,'assessment_question' => $assessment_question3,'question_type' => 3);
					}
				}
			}
			foreach ($assessment_question_ids3 as $assessment_question_id3) {
				if (!in_array($assessment_question_id3, $assessment_question_edit_ids3)) $assessment_question_del_ids3[] = $assessment_question_id3;
			}
			
			$_POST['term_year_sess'] = json_encode($_POST['term_year_sess']);
			$_POST['assessment_content'] = htmlspecialchars_decode($_POST['assessment_content']);
			$_POST['assessment_modify_time'] = date('Y-m-d H:i:s',time());
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta'] = json_encode($_POST['smeta']);
			
			$_POST['headteacher_ids'] = implode(',', $_POST['headteachers']);
			$_POST['parttimeteacher_ids'] = implode(',', $_POST['parttimeteachers']);
			
			unset($_POST['assessment_question_id1']);
			unset($_POST['assessment_question1']);
			unset($_POST['assessment_option1']);
			unset($_POST['assessment_question_id2']);
			unset($_POST['assessment_question2']);
			unset($_POST['assessment_option2']);
			unset($_POST['assessment_question_id3']);
			unset($_POST['assessment_question3']);
				
			$result = $this->assessment_model->where(array('id' => $assessment_id))->save($_POST);
			if ($result) {
				//单选
				foreach ($assessment_question_add1 as $assessment_question) $this->assessment_question_model->add($assessment_question);//新增
				foreach ($assessment_question_edit1 as $assessment_question) {//在原有上修改
					$assessment_question_id = $assessment_question['id'];
					unset($assessment_question['id']);
					$this->assessment_question_model->where(array('id' => $assessment_question_id))->save($assessment_question);
				}
				if ($assessment_question_del_ids1) {//本来有的，在编辑过程中删除
					$this->assessment_question_model->where("id in (".implode(',',$assessment_question_del_ids1).")")->delete();
				}
				//多选
				foreach ($assessment_question_add2 as $assessment_question) $this->assessment_question_model->add($assessment_question);//新增
				foreach ($assessment_question_edit2 as $assessment_question) {//在原有上修改
					$assessment_question_id = $assessment_question['id'];
					unset($assessment_question['id']);
					$this->assessment_question_model->where(array('id' => $assessment_question_id))->save($assessment_question);
				}
				if ($assessment_question_del_ids2) {//本来有的，在编辑过程中删除
					$this->assessment_question_model->where("id in (".implode(',',$assessment_question_del_ids2).")")->delete();
				}
				//问答
				foreach ($assessment_question_add3 as $assessment_question) $this->assessment_question_model->add($assessment_question);//新增
				foreach ($assessment_question_edit3 as $assessment_question) {//在原有上修改
					$assessment_question_id = $assessment_question['id'];
					unset($assessment_question['id']);
					$this->assessment_question_model->where(array('id' => $assessment_question_id))->save($assessment_question);
				}
				if ($assessment_question_del_ids3) {//本来有的，在编辑过程中删除
					$this->assessment_question_model->where("id in (".implode(',',$assessment_question_del_ids3).")")->delete();
				}
				
				//记录日志
				LogController::log_record($assessment_id,2);
				
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$assessment = $this->assessment_model->find($id);
			$this->assign($assessment);
			
			//选择学季
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where[] = "session != '' AND session is not null";
			$where[] = "program != '' AND program is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$terms = $this->users_model->field('distinct term,year,session,program')->where($map)->order('year desc')->select();
			$term_year_sess_arr = json_decode($assessment['term_year_sess'],true);
			$term_html = " ";
			foreach ($terms as $term) {
				$term_year_sess_str = trim($term['year'])."-".trim($term['term'])."-".trim($term['session'])."-".trim($term['program']);
				$term_html .= "<option";
				foreach ($term_year_sess_arr as $term_year_sess) {
					if($term_year_sess_str == $term_year_sess) {
						$term_html .= " selected";
					}
				}
				$term_html .= " value='".$term_year_sess_str."'>".$term_year_sess_str."</option>";
			}
			$this->assign('term_html',$term_html);
			
			//单选题
			$assessment_questions1 = $this->assessment_question_model->where(array('assessment_id' => $id,'question_type' => 1))->select();
			$this->assign('assessment_questions1',$assessment_questions1);
			//多选题
			$assessment_questions2 = $this->assessment_question_model->where(array('assessment_id' => $id,'question_type' => 2))->select();
			$this->assign('assessment_questions2',$assessment_questions2);
			//问答题
			$assessment_questions3 = $this->assessment_question_model->where(array('assessment_id' => $id,'question_type' => 3))->select();
			$this->assign('assessment_questions3',$assessment_questions3);
			
			$this->display();
		}
	}
	
	//评估结果
	function result() {
		
		$id = (int)I('get.id');
		$assessment = $this->assessment_model->find($id);
		$this->assign($assessment);
	
		$where = array();
		$where['assessment_id'] = $id;
		if ($assessment['assessment_type'] == 2) {
			$teacher_id = (int)$_POST['teacher_id'];
			$teacher_html = " <option value=''>".L('ASSESSMENT_TYPE_TEACHER')."</option>";
			
			$assessment_teacher_ids = $this->assessment_answer_model->field('distinct teacher_id')->where(array('assessment_id' => $assessment['id']))->select();
			
			if ($teacher_id) {
				$where['teacher_id'] = $teacher_id;
				
				foreach ($assessment_teacher_ids as $assessment_teacher_id) {
					$assessment_teacher = $this->users_model->field('id,full_name')->find($assessment_teacher_id['teacher_id']);
					$teacher_html .= "<option";
					if($assessment_teacher_id['teacher_id'] == $teacher_id) {
						$teacher_html .= " selected";
					}
					$teacher_html .= " value='".$assessment_teacher['id']."'>".$assessment_teacher['full_name']."</option>";
				}
			} else {
				foreach ($assessment_teacher_ids as $assessment_teacher_id) {
					$assessment_teacher = $this->users_model->field('id,full_name')->find($assessment_teacher_id['teacher_id']);
					$teacher_html .= "<option value='".$assessment_teacher['id']."'>".$assessment_teacher['full_name']."</option>";
				}
			}
			$this->assign('teacher_id',$teacher_id);
			$this->assign('teacher_html',$teacher_html);
		} elseif ($assessment['assessment_type'] == 3) {
			$cr_id = (int)$_POST['cr_id'];
			$hf_id = (int)$_POST['hf_id'];
			//中国同屋
			$chinese_roommate_html =  " <option value=''>".L('ASSESSMENT_CHINESE_ROOMMATE')."</option>";
			
			$chinese_roommate_ids = $this->assessment_answer_model->field('distinct roommate_id')->where(array('assessment_id' => $assessment['id'],'roommate_type' => 1))->select();
			if ($cr_id) {
				$where['roommate_id'] = $cr_id;
				$where['roommate_type'] = 1;
			
				foreach ($chinese_roommate_ids as $chinese_roommate_id) {
					$chinese_roommate = $this->recruit_member_model->field('id,name')->find($chinese_roommate_id['roommate_id']);
					$chinese_roommate_html .= "<option";
					if($chinese_roommate['id'] == $cr_id) {
						$chinese_roommate_html .= " selected";
					}
					$chinese_roommate_html .= " value='".$chinese_roommate['id']."'>".$chinese_roommate['name']."</option>";
				}
			} else {
				foreach ($chinese_roommate_ids as $chinese_roommate_id) {
					$chinese_roommate = $this->recruit_member_model->field('id,name')->find($chinese_roommate_id['roommate_id']);
					$chinese_roommate_html .= "<option value='".$chinese_roommate['id']."'>".$chinese_roommate['name']."</option>";
				}
			}
			//中国同屋
			$host_family_html =  " <option value=''>".L('ASSESSMENT_HOST_FAMILY')."</option>";
				
			$host_family_ids = $this->assessment_answer_model->field('distinct roommate_id')->where(array('assessment_id' => $assessment['id'],'roommate_type' => 2))->select();
			if ($hf_id) {
				$where['roommate_id'] = $hf_id;
				$where['roommate_type'] = 2;
					
				foreach ($host_family_ids as $host_family_id) {
					$host_family = $this->homestay_model->field('id,name')->find($host_family_id['roommate_id']);
					$host_family_html .= "<option";
					if($host_family['id'] == $hf_id) {
						$host_family_html .= " selected";
					}
					$host_family_html .= " value='".$host_family['id']."'>".$host_family['name']."</option>";
				}
			} else {
				foreach ($host_family_ids as $host_family_id) {
					$host_family = $this->homestay_model->field('id,name')->find($host_family_id['roommate_id']);
					$host_family_html .= "<option value='".$host_family['id']."'>".$host_family['name']."</option>";
				}
			}
			$this->assign('chinese_roommate_id',$cr_id);
			$this->assign('host_family_id',$hf_id);
			$this->assign('chinese_roommate_html',$chinese_roommate_html);
			$this->assign('host_family_html',$host_family_html);
			
		} else {
			
		}
		$assessment_questions1 = $this->assessment_question_model->where(array('assessment_id' => $id,'question_type' => 1))->select();
		$assessment_questions2 = $this->assessment_question_model->where(array('assessment_id' => $id,'question_type' => 2))->select();
		$assessment_questions3 = $this->assessment_question_model->where(array('assessment_id' => $id,'question_type' => 3))->select();
		
		$students = $this->assessment_answer_model->field('distinct student_id')->where($where)->select();
		$student_count = count($students);
		$this->assign('student_count',$student_count);
		$cr_student_ids = array();
		$aa_chinese_roommates = $this->assessment_answer_model->field('distinct student_id')->where(array('assessment_id' => $id,'roommate_type' => 1))->select();
		foreach ($aa_chinese_roommates as $aa_chinese_roommate) $cr_student_ids[] = $aa_chinese_roommate['student_id'];
		$cr_students = $this->recruit_member_model->alias('rm')->field('u.full_name,rm.name,rm.id')->join('__HOUSE_USER_RELATIONSHIP__ hur ON hur.owner_id=rm.id')->join('__USERS__ u ON u.id=hur.user_id')->where(array('u.id' => array('in',implode(',', $cr_student_ids))))->select();
		
		$hf_student_ids = array();
		$aa_host_families = $this->assessment_answer_model->field('distinct student_id')->where(array('assessment_id' => $id,'roommate_type' => 2))->select();
		foreach ($aa_host_families as $aa_host_family) $hf_student_ids[] = $aa_host_family['student_id'];
		$hf_students = $this->homestay_model->alias('h')->field('u.full_name,h.name,h.id')->join('__HOUSE_USER_RELATIONSHIP__ hur ON hur.owner_id=h.id')->join('__USERS__ u ON u.id=hur.user_id')->where(array('u.id' => array('in',implode(',', $hf_student_ids))))->select();
		
		$this->assign('cr_students',$cr_students);
		$this->assign('hf_students',$hf_students);
		
		$this->assign('assessment_questions1',$assessment_questions1);
		$this->assign('assessment_questions2',$assessment_questions2);
		$this->assign('assessment_questions3',$assessment_questions3);
		
		$this->display();
	}
	
	//评估删除
	function delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['assessment_status'] = 2;
			if ( $this->assessment_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
				
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['assessment_status'] = 1;
			if ( $this->assessment_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success( L('RESTORE_SUCCESS') );
			} else {
				$this->error( L('RESTORE_FAILED') );
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->assessment_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success( L('COMPLETE_DELETE_SUCCESS') );
			} else {
				$this->error( L('COMPLETE_DELETE_FAILED') );
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['assessment_status'] = 2;
			if ( $this->assessment_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success( L('DELETE_SUCCESS') );
			} else {
				$this->error( L('DELETE_FAILED') );
			}
		}
		
		
	}
	//评估批量显示隐藏
	function toggle() {
		if ( isset( $_POST['ids'] ) && $_GET['display'] ) {
			$ids = implode( ',',  $_POST['ids'] );
			$data['assessment_status'] = 1;
			if ( $this->assessment_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('DISPLAY_SUCCESS') );
			} else {
				$this->error( L('DISPLAY_FAILED') );
			}
		}
		if ( isset( $_POST['ids'] ) && $_GET['hide'] ) {
			$ids = implode( ',', $_POST['ids'] );
			$data['assessment_status'] = 0;
			if ( $this->assessment_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				$this->success( L('HIDE_SUCCESS') );
			} else {
				$this->error( L('HIDE_FAILED') );
			}
		}
	}
	
	//评估排序
	public function listorders() {
		$status = parent::_listorders( $this->assessment_model );
		if ( $status ) {
			$this->success( L('ORDER_UPDATE_SUCCESS') );
		} else {
			$this->error( L('ORDER_UPDATE_FAILED') );
		}
	}
	

}