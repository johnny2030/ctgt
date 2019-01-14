<?php

/*
 * 后台课件管理
 * 1xd
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class TextBookController extends AdminbaseController {
	private $textbook_model;
	private $users_model;
	function _initialize() {
		parent::_initialize ();
		$this->textbook_model = D ( 'Textbook' );
		$this->users_model = D( 'Users' );
	}
	// 课件列表
	function index() {
		
		$where = array();
		//课件搜索
		$textbook_name=I('request.textbook_name');
		$this->assign( 'textbook_name', $textbook_name );
		if ( !empty($textbook_name) ) {
			$where['textbook_name'] = array('like',"%$textbook_name%");
		}
		
		$count = $this->textbook_model->where ( $where )->count ();
		$page = $this->page ( $count, 20 );
		$list = $this->textbook_model
		->alias('s')
		->field('s.*,cc.category_name,us.user_login')
		->join('__USERS__ us ON s.admin_id=us.id')
		->join('__COURSE_CATEGORY__ cc ON s.course_id=cc.id')
		->where($where)->limit( $page->firstRow, $page->listRows )->order("s.textbook_modify_time desc")->select();
		$this->assign ( "page", $page->show ( 'Admin' ) );
		$this->assign ( 'list', $list );
		$this->display ();
			
	}
	// 添加课件
	function add() {
		if (IS_POST) {
				if (empty ( $_POST ['category'] ))$this->error ( '请选择课程' );
				$_POST['course_id'] = (int)$_POST['category'];
				$_POST['course_name'] = $_POST['category'];
				$_POST['admin_id'] = sp_get_current_admin_id ();
				$_POST['textbook_modify_time'] = date( 'Y-m-d H:i:s', time () );
				unset ( $_POST['category'] );
				$result = $this->textbook_model->add ( $_POST );
				if ($result) {
					//记录日志
					LogController::log_record($result,1);
					$this->success ( '添加成功！' );
				} else {
					$this->error ( '添加失败！' );
				}
		} else {
			$categorys = $this->category_model->where(array('category_status' => 1))->select();
			$category_html = "<option value='0'>请选择课程</option>";
			foreach ($categorys as $category) {
				$category_html .= "<option value='".$category['id']."'>".$category['category_name']."</option>";
			}
			$this->assign('category_html',$category_html);
			$this->display ();
		}
	}
	
	// 编辑课件
	function edit() {
		if (IS_POST) {
			$id = ( int ) $_POST ['id'];
					$_POST ['textbook_modify_time'] = date ( "Y-m-d H:i:s", time () );
					$_POST['course_id'] = $_POST['course_name'];
					$_POST ['textbook_content'] = htmlspecialchars_decode ( $_POST ['textbook_content'] );
					$result = $this->textbook_model->where ( array (
							'id' => $id
					) )->save ( $_POST );
					if ($result) {
						//记录日志
						LogController::log_record($result,2);
						$this->success ( "修改成功！" );
					} else {
						$this->error ( "修改失败！" );
					}
		} else {
			$id = intval ( I ( 'get.id' ) );
			
			$textbook = $this->textbook_model->find($id);
			
			$this->assign ( $textbook );
			
			$this->getSel($textbook['course_id']);
			
			$this->display ();
		}
	}
	
	// 课件删除
	public function delete(){
		if(isset($_POST['ids'])){
				
		}else{
			$id = I("get.id",0,'intval');
			if ($this->textbook_model->delete($id)!==false) {
				//记录日志
				LogController::log_record($id,3);
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	
	}
	
	/**
	 * 选中下拉框
	 * @param 课程id
	 */
	private function getSel($course_id) {
		//课程下拉框
		$categoryes = $this->category_model->where(array('category_status' => 1))->select();
		$category_html = "<option value='0'>请选择课程</option>";
		foreach ($categoryes as $category) {
			$category_html .= "<option";
			if ($category['id'] == $course_id) {
				$category_html .= " selected";
			}
		$category_html .= " value='".$category['id']."'>".$category['category_name']."</option>";
		}
		$this->assign('category_html',$category_html);
		
	
	}
	
}