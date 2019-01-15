<?php
/**
 * 客户管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class CustomerController extends AdminbaseController {

    private $common_user_model;
	
	function _initialize() {
		parent::_initialize();

		$this->common_user_model = D( 'Common_user' );
	}
	//客户信息列表
	function index() {
		$where = array();
		//公司名/姓名
		$name=I('name');
        if ( $name ){
            $where['company_name|name'] = array('like',"%$name%");
            $this->assign( 'name', $name );
        }
        $where['del_flg'] = array('eq',0);
		$count = $this->common_user_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_user_model->where($where)->limit( $page->firstRow, $page->listRows )->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}

	//添加客户信息
	function add() {
		if ( IS_POST ) {
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_user_model->add($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($result,1);
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！');
			}
		} else {
			$this->display();
		}
	}
	//编辑客户信息
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_user_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$customer = $this->common_user_model->find($id);
			$this->assign($customer);
			$this->display();
		}
	}
    //删除客户信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_user_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
    //导入客户信息
    function upload() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/doctor/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['doctor']['savepath'].$info['doctor']['savename'];

            require_once 'today/excel/PHPExcel.php';
            require_once 'today/excel/PHPExcel/IOFactory.php';
            require_once 'today/excel/PHPExcel/Reader/Excel5.php';
            require_once 'today/excel/PHPExcel/Reader/Excel2007.php';

            //医生信息读取
            $reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
            $obj = $reader->load( $file );
            $sheet = $obj->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $doctor_info_add = array();
            $time = date('Y-m-d H:i:s',time());
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $practice_number = $sheet->getCell( 'A'.$i )->getValue();
                $name = $sheet->getCell( 'B'.$i )->getValue();
                $sex = $sheet->getCell( 'C'.$i )->getValue();
                $age = $sheet->getCell( 'D'.$i )->getValue();
                $money = $sheet->getCell( 'E'.$i )->getValue();
                $hospital = $sheet->getCell( 'F'.$i )->getValue();
                $office = $sheet->getCell( 'G'.$i )->getValue();
                $tag = $sheet->getCell( 'H'.$i )->getValue();
                $area = $sheet->getCell( 'I'.$i )->getValue();
                $speciality = $sheet->getCell( 'J'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $doctor_info_add[] = array(
                    "practice_number" => $practice_number, "name" => $name, "sex" => $sex, "age" => $age, "money" => $money, "hospital" => $hospital, "office" => $office,
                    "tag" => $tag, "area" => $area, "speciality" => $speciality, "create_time" => $time
                );
            }
            foreach ($doctor_info_add as $table_doctor) {
                $this->doctor_user_model->add($table_doctor);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条记录', U( 'doctor/index' ) );
        } else {
            $this->display();
        }
    }
    //图片上传
/*$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
$_POST['smeta'] = json_encode($_POST['smeta']);

$_POST['expert_details'] = htmlspecialchars_decode($_POST['expert_details']);
$_POST['pubdate'] = date('Y-m-d H:i:s',time());*/
}