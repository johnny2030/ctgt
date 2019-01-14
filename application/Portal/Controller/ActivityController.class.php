<?php
/**
 * Activity
 * @author 11k
 * 510912430@qq.com
 */
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class ActivityController extends HomebaseController {

	private $formData = array();
	private $formError = array();
	private $formReturn = array();
	private $activity_model;
	private $activity_student_model;
	private $activity_classify_model;
	private $activity_time_model;
	private $activity_address_model;
	
	public function _initialize() {
		parent::_initialize();
		
		$this->activity_model = D( 'Activity' );
		$this->activity_student_model = D( 'ActivityStudentRelationship' );
		$this->activity_classify_model = D( 'ActivityClassify' );
		$this->activity_time_model = D( 'ActivityTime' );
		$this->activity_address_model = D( 'ActivityAddress' );
	}

	public function index() {
		$classifies = $this->activity_classify_model->where(array('classify_status' => 0))->select();
		$this->assign('classifies',$classifies);
		
		$this->display( '/../activity' );
	}

	public function detail() {
		if (IS_POST) {
			$classify_id = (int)$_POST['classify_id'];
			if ($classify_id == 2) {
				$activity_id = (int)$_POST['activity_id'];
				/* $activity_tendency_arr = array();
				$activity_addresses = $this->activity_address_model->where(array('activity_id' => $activity_id))->select();
				foreach ($activity_addresses as $k=>$activity_address) {
					$activity_tendency_arr[] = array('no' => $_POST['activity_tendency'][$k],'tendency' => $activity_address['activity_address']); 
				} */
				$activity_tendency = json_encode($_POST['activity_tendency']);
				$result = $this->activity_student_model->add(array('activity_id' => $activity_id,'student_id' => get_current_userid(),'activity_tendency' => $activity_tendency));
			} else {
				$activity_id = (int)$_POST['id'];
				$result = $this->activity_student_model->add(array('activity_id' => $_POST['id'],'student_id' => get_current_userid()));
			}
			if ($result) {
				$this->success('Registration Success!',U('portal/activity/detail',array('id' => $activity_id)));
			}
		} else {
			$id = (int)$_GET['id'];
			$activity = $this->activity_model
						->alias('a')
						->field('a.*,u.full_name')
						->join('__USERS__ u ON u.id=a.admin_id')
						->where(array('a.id' => $id))
						->find();
			$activity_times = $this->activity_time_model->where(array('activity_id' => $id))->order('activity_start_time asc')->select();
			$activity_addresses = $this->activity_address_model->where(array('activity_id' => $id))->select();
	
			if ($activity['classify_id'] == 2) {
				$address_html = " ";
				foreach ($activity_addresses as $activity_address) {
					$address_html .= "<option value='".$activity_address['activity_address']."'>".$activity_address['activity_address']."</option>";
				}
				$this->assign('address_html',$address_html);
			}
			if ( empty( $activity ) ) {
				header( 'HTTP/1.1 404 Not Found' );
				header( 'Status:404 Not Found' );
				if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
				return;
			}
	
			$this->assign( $activity );
			$this->assign( 'activity_times', $activity_times );
			$this->assign( 'activity_addresses', $activity_addresses );
	
			$this->display( '/../activity_detail' );
		}
	}

	public function signup() {
		$user_id = sp_get_current_userid();
		$id = (int)$_GET['activity_id'];
		$activity = $this->activity_model->find($id);
		if ( empty( $activity ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			header( 'Status:404 Not Found' );
			if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
			return;
		}
		$this->assign( $activity );
		
		$result = $this->activity_student_model->add(array('activity_id' => $id,'student_id' => $user_id));
		
		if ($result) {
			$this->success('Registration Success!');
		} else {
			$this->error('Registration Failed!');
		}

	}

}