<?php
/** 
 * 前端住宿
 * @author 11k
 * likun_19911227@163.com
 */
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class AccommodationController extends HomebaseController {

	private $formData = array();
	private $formError = array();
	private $formReturn = array();
	private $house_model;

	function _initialize() {
		parent::_initialize();

		$this->house_model = D( 'House' );
	}
	//房源列表
	public function index() {
		$where = array();
		
		$where['del_flg'] = array('eq',0);
		$count = $this->house_model->where($where)->count();
		$page = $this->page($count, 9);
		$list = $this->house_model->where($where)->limit( $page->firstRow, $page->listRows )->order("listorder asc,update_time desc")->select();
		$this->assign("page", $page->show('Portal'));
		$this->assign( 'list', $list );
		$this->display( '/../accommodation' );
	}
	public function detail() {
		$id = (int)$_GET['id'];
		$house = $this->house_model->find($id);
	
		if ( empty( $house ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			header( 'Status:404 Not Found' );
			if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
			return;
		}
	
		$this->assign( $house );
	
		$this->display( '/../accommodation_detail' );
	}

}