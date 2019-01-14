<?php
/**
 * @author Richard_Li
* 住宿管理
* @date 2018年1月08日  上午10:37:28
*/
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AccommodationController extends AdminbaseController {

	private $user_model;
	private $homestay_model;
	private $homestay_family_model;
	private $homestay_term_model;
	private $recruit_member_model;
	private $recruit_member_term_model;
	private $house_address_model;
	private $house_user_relationship;
	function _initialize() {
		parent::_initialize();

		$this->user_model = D( 'Users' );
		$this->homestay_model = D( 'Homestay' );
		$this->homestay_family_model = D( 'HomestayFamily' );
		$this->homestay_term_model = D( 'HomestayTerm' );
		$this->recruit_member_model = D( 'RecruitMember' );
		$this->recruit_member_term_model = D( 'RecruitMemberTerm' );
		$this->house_address_model = D( 'HouseAddress' );
		$this->house_user_relationship = D( 'HouseUserRelationship' );
	}

	//住宿信息
	function housing() {
		//条件搜索
		$name = I('request.name');
		$cell_phone = I('request.cell_phone');
		$check = I('request.check');

		$where = array();
		
		$term_year = $_REQUEST['term_year'];
		
		if ($check) {
			//中国同屋
			if( $check == 1 ){
				
				if ( !empty($name) ) $where['rm.name'] = array('like',"%$name%");
				$where['rm.status'] = 1;
				$where['rm.interview_status'] = 3;
				
				if ($term_year) $where['rmt.term_year'] = $term_year;
				$term_years = $this->recruit_member_term_model->field('distinct term_year')->order('term_year desc')->select();
				$term_year_html = " <option value=''>".L('ACCOMMODATION_TERM')."</option>";
				foreach ($term_years as $ty) {
					$term_year_html .= "<option";
					if ($term_year == $ty['term_year']) {
						$term_year_html .= " selected";
					}
					$term_year_html .= " value='".$ty['term_year']."'>".$ty['term_year']."</option>";
				}
				$this->assign('term_year_html',$term_year_html);
				
				$count = $this->recruit_member_model->alias('rm')->field('distinct rm.*')->join('__RECRUIT_MEMBER_TERM__ rmt ON rmt.recruit_member_id=rm.id')->where($where)->count();
				$page = $this->page($count, 20);
				$list = $this->recruit_member_model->alias('rm')->field('distinct rm.*')->join('__RECRUIT_MEMBER_TERM__ rmt ON rmt.recruit_member_id=rm.id')->where($where)->limit( $page->firstRow, $page->listRows )->order('name asc')->select();
				$this->assign("page", $page->show('Admin'));
				
				if ( $_REQUEST['cmd'] == 'export' ) {
					$chinese_roommates = $this->recruit_member_model->alias('rm')->field('distinct rm.*')->join('__RECRUIT_MEMBER_TERM__ rmt ON rmt.recruit_member_id=rm.id')->where($where)->order('name asc')->select();
					
					$chinese_roommate_cols = array(
							array( 20, '姓名', 'FFFFFF' ),//1
							array( 20, '状态', 'FFFFFF' ),//2
							array( 20, '手机号', 'FFFFFF' ),//3
							array( 20, '邮箱', 'FFFFFF' ),//4
							array( 20, '性别', 'FFFFFF' ),//5
							array( 20, '出生日期', 'FFFFFF' ),//6
							array( 20, '国籍', 'FFFFFF' ),//7
							array( 20, '年级', 'FFFFFF' ),//8
							array( 20, '专业', 'FFFFFF' ),//9
							array( 20, '家庭住址', 'FFFFFF' ),//10
							array( 20, '现居地址', 'FFFFFF' ),//11
							array( 20, '辅导员', 'FFFFFF' ),
							array( 20, '辅导员手机', 'FFFFFF' ),
							array( 20, '辅导员邮箱', 'FFFFFF' ),
							array( 20, '起床时间', 'FFFFFF' ),
							array( 20, '就寝时间', 'FFFFFF' ),
							array( 20, '是否抽烟', 'FFFFFF' ),
							array( 20, '打扫房间频率', 'FFFFFF' ),
							array( 20, '兴趣爱好', 'FFFFFF' )
							
					);
				
					//导出
					set_time_limit(0);
				
					$xls_file_name = 'Chinese Roommate Information '.date('Y-m-d',time());
					require_once 'today/excel/PHPExcel.php';
					$excel = new \PHPExcel();
					$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
					$sheet = $excel->setActiveSheetIndex( 0 );
					$sheet->getDefaultRowDimension()->setRowHeight( 15 );
				
					$rowIndex = 1;  //行
				
					if ( count( $chinese_roommates ) > 0 ) {
						foreach ( $chinese_roommates as $chinese_roommate ) {
							if ($chinese_roommate['gender'] == 1) $gender = '男';
							if ($chinese_roommate['gender'] == 2) $gender = '女';
							if ($chinese_roommate['smoking_status'] == 1) $smoke = '否';
							if ($chinese_roommate['smoking_status'] == 2) $smoke = '是';
							$colIndex = -1;//列
							$rowIndex++;
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['name'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, empty($chinese_roommate['renew_status']) ? '首住' : '续住' );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['phone'].' ' );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['email'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $gender );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['birth_of_date'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['native_place'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['grade'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['speciality'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['home_address'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['present_address'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['counselor'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['counselor_phone'].' ' );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['counselor_email'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, date('H:i',strtotime($chinese_roommate['wakeup_time'])) );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, date('H:i',strtotime($chinese_roommate['bedtime'])) );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $smoke );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['frequency_of_cleaning'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['hobby'] );
							
							$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
									array(
											'font' => array( 'size' => 10,'name' => 'Arial' )
									)
									);
						}
					}
				
					$rowIndex = 1;//第一行字段名
					$colIndex = -1;//列
					foreach ($chinese_roommate_cols as $col) {
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
			}
			if ($check == 2) {
				if ( !empty($name) ) $where['h.name'] = array('like',"%$name%");
				$where['status'] = array('neq',2);
				
				if ($term_year) $where['ht.term_year'] = $term_year;
				$term_years = $this->homestay_term_model->field('distinct term_year')->order('term_year desc')->select();
				$term_year_html = " <option value=''>".L('ACCOMMODATION_TERM')."</option>";
				foreach ($term_years as $ty) {
					$term_year_html .= "<option";
					if ($term_year == $ty['term_year']) {
						$term_year_html .= " selected";
					}
					$term_year_html .= " value='".$ty['term_year']."'>".$ty['term_year']."</option>";
				}
				$this->assign('term_year_html',$term_year_html);
				
				$count = $this->homestay_model->alias('h')->field('distinct h.*')->join('__HOMESTAY_TERM__ ht ON ht.homestay_id=h.id')->where($where)->count();
				$page = $this->page($count, 20);
				$list = $this->homestay_model->alias('h')->field('distinct h.*')->join('__HOMESTAY_TERM__ ht ON ht.homestay_id=h.id')->where($where)->limit( $page->firstRow, $page->listRows )->order('name asc')->select();
				$this->assign("page", $page->show('Admin'));
				
				if ( $_REQUEST['cmd'] == 'export' ) {
					$host_families = $this->homestay_model->alias('h')->field('distinct h.*')->join('__HOMESTAY_TERM__ ht ON ht.homestay_id=h.id')->where($where)->order('name asc')->select();
						
					$host_family_cols = array(
							array( 20, 'Host Family', 'FFFFFF' ),//1
							array( 20, 'Address', 'FFFFFF' ),//2
							array( 20, 'Mobile', 'FFFFFF' ),//3
							array( 20, 'Family Member:1', 'FFFFFF' ),//4
							array( 20, 'Family Member:2', 'FFFFFF' ),//5
							array( 20, 'Zip/Postal Code', 'FFFFFF' ),//6
							array( 20, 'Email', 'FFFFFF' ),//7
							array( 20, 'Phone', 'FFFFFF' ),//8
							array( 20, 'Occupation of Parents', 'FFFFFF' ),//9
							array( 20, 'Ages of parent/s', 'FFFFFF' ),//10
							array( 20, 'Ages of children in the home', 'FFFFFF' ),//11
							array( 20, 'Who else lives in the home', 'FFFFFF' ),
							array( 20, 'Hobbies of host family', 'FFFFFF' ),
							array( 20, 'Pets', 'FFFFFF' ),
							array( 20, 'Any Smokers', 'FFFFFF' ),
							array( 20, 'Usual dinner time', 'FFFFFF' ),
							array( 20, 'Usual Bed time', 'FFFFFF' ),
							array( 20, 'Transportation', 'FFFFFF' ),
							array( 20, 'Number of Students this family can host', 'FFFFFF' ),
							array( 20, 'Gender of students this family can host', 'FFFFFF' ),
							array( 20, 'HF Request', 'FFFFFF' ),
							array( 20, 'Bank Account（ICBC）', 'FFFFFF' ),
							array( 20, 'Account Name', 'FFFFFF' ),
							array( 20, 'ID Number', 'FFFFFF' )
								
					);
				
					//导出
					set_time_limit(0);
				
					$xls_file_name = 'Host Family Information '.date('Y-m-d',time());
					require_once 'today/excel/PHPExcel.php';
					$excel = new \PHPExcel();
					$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
					$sheet = $excel->setActiveSheetIndex( 0 );
					$sheet->getDefaultRowDimension()->setRowHeight( 15 );
				
					$rowIndex = 1;  //行
				
					if ( count( $host_families ) > 0 ) {
						foreach ( $host_families as $host_family ) {
							if ($host_family['in_gender'] == 1) $gender = 'Male';
							if ($host_family['in_gender'] == 2) $gender = 'Female';
							if ($host_family['in_gender'] == 3) $gender = 'Both For Women & Men';
							$colIndex = -1;//列
							$rowIndex++;
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['name'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['address'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['mobile'].' ' );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['family_member_1'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['family_member_2'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['postcode'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['email'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['phone'].' ' );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['profession'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['ages_of_parents'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['ages_of_children'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['other_family'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['family_hobby'].' ' );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['pets'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['any_smokers'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, date('H:i',strtotime($host_family['suppertime'])) );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, date('H:i',strtotime($host_family['bedtime'])) );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['transportation'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['number_of_people'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $gender );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['hf_request'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['bank_account'].' ' );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['account_holder'] );
							$colIndex++;
							$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['id_number'].' ' );
								
							$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
									array(
											'font' => array( 'size' => 10,'name' => 'Arial' )
									)
									);
						}
					}
				
					$rowIndex = 1;//第一行字段名
					$colIndex = -1;//列
					foreach ($host_family_cols as $col) {
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
			}
		}
		$this->assign( 'name', $name );
		$this->assign( 'list', $list );
		$this->assign( 'check', $check );
		$this->display();
	}
	//友好家庭api
	function host_family() {

		$file_contents = file_get_contents('http://localhost:8080/cieeassist/public/api/portal/host_family');
		$file_contents = json_decode($file_contents,true);
		$list = $file_contents['data'];
		$this->assign( 'list', $list );
		$this->display();
	}
	
	//添加友好家庭
	function hs_add() {
		if ( IS_POST ) {
			if (empty($_POST['term_year'])) $this->error("请至少选择一个学季");
			//家庭成员
			/* $homestay_families = array();
			foreach ($_POST['family_name'] as $k => $family_name) {
				$family_name = trim($family_name);
				$family_email = trim($_POST['family_email'][$k]);
				$family_phone = trim($_POST['family_phone'][$k]);
				$family_profession = trim($_POST['family_profession'][$k]);
				$family_age = (int)$_POST['family_age'][$k];
				$family_ties = trim($_POST['family_ties'][$k]);
				if ($family_name) {
					$homestay_families[] = array('family_name' => $family_name,'family_email' => $family_email,'family_phone' => $family_phone,'family_profession' => $family_profession,'family_age' => $family_age,'family_ties' => $family_ties);
				} 
			} */
			//学季
			$homestay_terms = array();
			foreach ($_POST['term_year'] as $term_year) {
				$term_year = trim($term_year);
				if ($term_year) {
					$homestay_terms[] = array('term_year' => $term_year);
				}
			}
			
			//photos
			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['smeta']=json_encode($_POST['smeta']);
			$_POST['create_time'] = date('Y-m-d H:i:s',time());
			$hs_id = $this->homestay_model->add($_POST);
			if ($hs_id) {
				/* foreach ($homestay_families as $homestay_family) {
					$homestay_family['homestay_id'] = $hs_id;
					$this->homestay_family_model->add($homestay_family);
				} */
				foreach ($homestay_terms as $homestay_term) {
					$homestay_term['homestay_id'] = $hs_id;
					$this->homestay_term_model->add($homestay_term);
				}
				//记录日志
				LogController::log_record($hs_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$term_year = $this->user_model->field('distinct term,year,term_status')->where($map)->order('year desc')->select();
			$term_year_html = " ";
			foreach ($term_year as $ty) {
				$term_year_str = trim($ty['year'])."-".trim($ty['term']);
				$term_year_html .= "<option value='".$term_year_str."'>".$term_year_str."</option>";
			}
			$this->assign('term_year_html',$term_year_html);
			$this->display();
		}
	}
	//编辑友好家庭
	function hs_edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			if (empty($_POST['term_year'])) $this->error("请至少选择一个学季");
			//家庭成员
			/* $homestay_families = $this->homestay_family_model->where(array("homestay_id" => $id))->select();
			$homestay_family_ids = array();
			foreach ($homestay_families as $homestay_family) $homestay_family_ids[] = $homestay_family['id'];
				
			$homestay_family_add = array();
			$homestay_family_edit = array();
			$homestay_family_del = array();
			$homestay_family_edit_id = array();
			if (implode('', $_POST['family_name'])) {
				foreach ($_POST['family_name'] as $k => $family_name) {
					$family_id = (int)$_POST['family_id'][$k];
					$family_name = trim($family_name);
					$family_email = trim($_POST['family_email'][$k]);
					$family_phone = trim($_POST['family_phone'][$k]);
					$family_profession = trim($_POST['family_profession'][$k]);
					$family_age = (int)$_POST['family_age'][$k];
					$family_ties = trim($_POST['family_ties'][$k]);
					if ($family_name) {
						if (in_array($family_id, $homestay_family_ids)) {
							//edit
							$homestay_family_edit[] = array('id' => $family_id,'homestay_id' => $id,'family_name' => $family_name,'family_email' => $family_email,'family_phone' => $family_phone,'family_profession' => $family_profession,'family_age' => $family_age,'family_ties' => $family_ties);
							$homestay_family_edit_id[] = $family_id;
						} else {
							//add
							$homestay_family_add[] = array('homestay_id' =>$id,'family_name' => $family_name,'family_email' => $family_email,'family_phone' => $family_phone,'family_profession' => $family_profession,'family_age' => $family_age,'family_ties' => $family_ties);
						}
					} 
				}
				foreach ($homestay_family_ids as $homestay_family_id) {
					if (!in_array($homestay_family_id, $homestay_family_edit_id)) $homestay_family_del[] = $homestay_family_id;
				}
			} else {
				$this->homestay_family_model->where(array('homestay_id' => $id))->delete();
			} */
			//学季
			$homestay_terms = $this->homestay_term_model->where(array('homestay_id' => $id))->select();
			$homestay_term_ids = array();
			foreach ($homestay_terms as $homestay_term) $homestay_term_ids[] = $homestay_term['id'];
			
			$homestay_term_add = array();
			$homestay_term_del_ids = array();
			$homestay_term_edit_ids = array();
			$index = 0;
			foreach ($_POST['term_year'] as $term_year){
				$term_year = $term_year;
				$homestay_term_search = $this->homestay_term_model->where(array('term_year' => $term_year,'homestay_id' => $id))->find();
				if ($homestay_term_search){
					//修改
					$homestay_term_edit_ids[] = $homestay_term_search['id'];
				} else {
					//新增
					$homestay_term_add[] = array('homestay_id' => $id,'term_year' => $term_year);
				}
			}
			foreach ($homestay_term_ids as $homestay_term_id) {
				if (!in_array($homestay_term_id, $homestay_term_edit_ids)) $homestay_term_del_ids[] = $homestay_term_id;
			}
			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['term_year'] = json_encode($_POST['term_year']);
			$_POST['smeta']=json_encode($_POST['smeta']);
			$_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->homestay_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				/* if ($homestay_family_add) {
					foreach ($homestay_family_add as $homestay_family) $this->homestay_family_model->add($homestay_family);
				}
				if ($homestay_family_edit) {
					foreach ($homestay_family_edit as $homestay_family) {
						$homestay_family_id = $homestay_family['id'];
						unset($homestay_family['id']);
						$this->homestay_family_model->where(array('id' => $homestay_family_id))->save($homestay_family);
					}
				}
				if ($homestay_family_del) {
					$this->homestay_family_model->where("id in (".implode(',',$homestay_family_del).")")->delete();
				} */
				if ($homestay_term_add) {
					foreach ($homestay_term_add as $homestay_term) $this->homestay_term_model->add($homestay_term);
				}
				if ($homestay_term_del_ids) {
					$this->homestay_term_model->where("id in (".implode(',',$homestay_term_del_ids).")")->delete();
				}
				//记录日志
				LogController::log_record($id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$homestay = $this->homestay_model->find($id);
			//$homestay_families = $this->homestay_family_model->where(array('homestay_id' => $id))->select();
			
			//学季
			$where = array();
			$map = array();
			$where[] = "term != '' AND term is not null";
			$where[] = "year != '' AND year is not null";
			$where['_logic'] = "OR";
			$map['_complex'] = $where;
			$map['user_type'] = 2;
			$map['term_status'] = 1;
			$term_year = $this->user_model->field('distinct term,year,term_status')->where($map)->order('year desc')->select();
			$term_year_html = " ";
			$homestay_terms = $this->homestay_term_model->where(array('homestay_id' => $id))->select();
			foreach ($term_year as $ty) {
				$term_year_str = trim($ty['year'])."-".trim($ty['term']);
				$term_year_html .= "<option";
				foreach ($homestay_terms as $homestay_term) {
					if ($homestay_term['term_year'] == $term_year_str) {
						$term_year_html .= " selected";
					}
				}
				$term_year_html .= " value='".$term_year_str."'>".$term_year_str."</option>";
			}
			$this->assign('term_year_html',$term_year_html);
			$this->assign($homestay);
			//$this->assign('homestay_families',$homestay_families);
			$this->display();
		}
	}
	//查看家庭成员
	function view_family() {
		$id = (int)$_GET['id'];
		$homestay = $this->homestay_model->find($id);
		$homestay_families = $this->homestay_family_model->where(array('homestay_id' => $id))->select();
		$this->assign( $homestay );
		$this->assign( 'homestay_families', $homestay_families );
		$this->display();
	}
	//删除友好家庭
	function hs_delete() {
		if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
			$ids = implode( ',', $_POST['ids'] );
			$data['status'] = 2;
			if ( $this->homestay_model->where( "id in ($ids)" )->save( $data ) !== false ) {
				//记录日志
				LogController::log_record($ids,3);
	
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		} else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			$data['status'] = 1;
			if ( $this->homestay_model->where( "id in ($object)" )->save( $data ) !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 4);
				$this->success(L('RESTORE_SUCCESS'));
			} else {
				$this->error(L('RESTORE_FAILED'));
			}
		} else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
			$object = $_GET['object'];
			$log_id = $_GET['id'];
			if ( $this->homestay_model->where( "id in ($object)" )->delete() !== false ) {
				//更改日志状态
				LogController::modify_log_type($log_id, 5);
				$this->success(L('COMPLETE_DELETE_SUCCESS'));
			} else {
				$this->error(L('COMPLETE_DELETE_FAILED'));
			}
		} else {//单个逻辑删除
			$id = intval( I( 'get.id' ) );
			$data['status'] = 2;
			if ( $this->homestay_model->where(array('id' => $id))->save($data) !== false ) {
				//记录日志
				LogController::log_record($id,3);
				$this->success(L('DELETE_SUCCESS'));
			} else {
				$this->error(L('DELETE_FAILED'));
			}
		}
	}
	//编辑中国同屋
	function cr_edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			$_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->recruit_member_model->where(array('id' => $id))->save($_POST);
			if ($result) {
				//记录日志
				LogController::log_record($id,2);
				$this->success(L('EDIT_SUCCESS'));
			} else {
				$this->error(L('EDIT_FAILED'));
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$chinese_roommate = $this->recruit_member_model->find($id);
			$this->assign($chinese_roommate);
			$this->display();
		}
	}
	//住宿匹配列表
	function house_matching() {
		//条件搜索
		$name = I('request.name');
		$check = I('request.check');
		$match_status = $_REQUEST['match_status'];
		$term_year = $_REQUEST['term_year'];
		//学季
		$where = array();
		$map = array();
		$where[] = "term != '' AND term is not null";
		$where[] = "year != '' AND year is not null";
		$where['_logic'] = "OR";
		$map['_complex'] = $where;
		$map['user_type'] = 2;
		$map['term_status'] = 1;
		$term_years = $this->user_model->field('distinct term,year,term_status')->where($map)->order('year desc')->select();
		$term_year_html = " <option value=''>".L('ACCOMMODATION_TERM')."</option>";
		//$homestay_terms = $this->homestay_term_model->where(array('homestay_id' => $id))->select();
		foreach ($term_years as $ty) {
			$term_year_str = trim($ty['year'])."-".trim($ty['term']);
			$term_year_html .= "<option";
			if ($term_year == $term_year_str) {
				$term_year_html .= " selected";
			}
			$term_year_html .= " value='".$term_year_str."'>".$term_year_str."</option>";
		}
		$this->assign('term_year_html',$term_year_html);
		$term_year_arr = explode('-', $term_year);
		//住宿信息
		$where = array();
		if ($name) $where['u.full_name'] = array('like',"%$name%");
		
		$where['u.term_status'] = 1;//学季下显示的学生
		$where['u.user_status'] = 1;//正常学生，即未拉黑
		$where['u.user_type'] = 2;//学生
		if ($term_year) {
			$where['u.term'] = $term_year_arr[1];
			$where['u.year'] = $term_year_arr[0];
		}
		if ($match_status) {
			if ($match_status == 1) {//已匹配
				$matched_ids = array();
				$house_user_where = array();
				if ($check) {
					$house_user_where['flg'] = array('in',implode(',', $check));
				}
				$house_users = $this->house_user_relationship->where($house_user_where)->select();
				foreach ($house_users as $house_user) $matched_ids[] = $house_user['user_id'];
				$where['u.id'] = array('in',implode(',', $matched_ids));
				$count = $this->user_model->alias('u')->where($where)->count();
				$page = $this->page($count, 20);
				if ($_REQUEST['cmd'] == 'export') {
					$list = $this->user_model
								->alias('u')
								->field('u.*')
								->join('__HOUSE_USER_RELATIONSHIP__ hur ON hur.user_id=u.id')
								->where($where)
								->order('hur.address asc,u.full_name asc')
								->select();
				} else {
					$list = $this->user_model
								->alias('u')
								->field("u.*")
								->join('__HOUSE_USER_RELATIONSHIP__ hur ON hur.user_id=u.id')
								->where($where)
								->order('hur.address asc,u.full_name asc')
								->limit( $page->firstRow, $page->listRows )
								->select();
				}
				$this->assign("page", $page->show('Admin'));
			}
			if ($match_status == 2) {//未匹配
				$matched_ids = array();//已匹配
				$house_users = $this->house_user_relationship->select();
				foreach ($house_users as $house_user) $matched_ids[] = $house_user['user_id'];
				$student_ids = array();//所有学生
				$students = $this->user_model->alias('u')->where($where)->select();
				foreach ($students as $student) $student_ids[] = $student['id'];
				$unmatch_ids = array_diff($student_ids,$matched_ids);//差集即为未匹配学生
				$where['u.id'] = array('in',implode(',', $unmatch_ids));
				$count = $this->user_model->alias('u')->where($where)->count();
				$page = $this->page($count, 20);
				if ($_REQUEST['cmd'] == 'export') {
					$list = $this->user_model->alias('u')->where($where)->order('u.full_name asc')->select();
				} else {
					$list = $this->user_model->alias('u')->where($where)->order('u.full_name asc')->limit( $page->firstRow, $page->listRows )->select();
				}
				$this->assign("page", $page->show('Admin'));
			}
		} else {
			$count = $this->user_model->alias('u')->where($where)->count();
			$page = $this->page($count, 20);
			if ($_REQUEST['cmd'] == 'export') {
				$list = $this->user_model->alias('u')->where($where)->order('u.full_name asc')->select();
			} else {
				$list = $this->user_model->alias('u')->where($where)->order('u.full_name asc')->limit( $page->firstRow, $page->listRows )->select();
			}
			$this->assign("page", $page->show('Admin'));
		}
		$this->assign( 'check', $check );
		$this->assign( 'name', $name );
		$this->assign( 'match_status', $match_status );
		$this->assign('list',$list);
		
		if ( $_REQUEST['cmd'] == 'export' ) {
				$cols = array(
						array( 20, 'Student Name', 'FFFFFF' ),//1
						array( 20, 'Gender', 'FFFFFF' ),//2
						array( 20, 'Room', 'FFFFFF' ),//3
						array( 20, 'Roommate', 'FFFFFF' ),//4
						array( 20, 'Homestay', 'FFFFFF' ),//5
						array( 20, 'Student', 'FFFFFF' ),//6
						array( 20, 'Language level', 'FFFFFF' ),//7
						array( 20, 'University', 'FFFFFF' ),//8
						array( 20, 'Email', 'FFFFFF' ),//9
						array( 20, 'Term', 'FFFFFF' )//10
						
				);
				
			//导出
			set_time_limit(0);
		
			$xls_file_name = 'Roommate '.date('Y-m-d',time());
			require_once 'today/excel/PHPExcel.php';
			$excel = new \PHPExcel();
			$excel->getProperties()->setCreator( 'CIEE' )->setLastModifiedBy( 'CIEE' )->setTitle( $xls_file_name );
			$sheet = $excel->setActiveSheetIndex( 0 );
			$sheet->getDefaultRowDimension()->setRowHeight( 15 );
		
			$rowIndex = 1;  //行
		
			if ( count( $list ) > 0 ) {
				foreach ( $list as $user ) {
					$colIndex = -1;//列
					$rowIndex++;
					$term_year = trim($user['year'])."-".trim($user['term']);
					$house_user = $this->house_user_relationship->where(array('user_id' => $user['id']))->find();
					if ($house_user) {
						if ($house_user['flg'] == 1) {
							$chinese_roommate = D('RecruitMember')->find($house_user['owner_id']);
							$recruit_member_term = D('RecruitMemberTerm')->where(array('recruit_member_id' => $house_user['owner_id'],'term_year' => $term_year))->find();
						}
						if ($house_user['flg'] == 2) $host_family = D('Homestay')->find($house_user['owner_id']);
						if ($house_user['flg'] == 3) $foreign_roommate = D('Users')->find($house_user['owner_id']);
					}
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user['full_name'] );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user['gender'] );
					if ($house_user['flg'] == 1) {
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $recruit_member_term['address'] );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $chinese_roommate['name'] );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '' );
					}
					if ($house_user['flg'] == 2) {
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['address'] );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $host_family['name'] );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '' );
					}
					if ($house_user['flg'] == 3) {
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $house_user['address'] );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, '' );
						$colIndex++;
						$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $foreign_roommate['full_name'] );
					}
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user['chinese_level'] );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user['institution'] );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $user['student_email'] );
					$colIndex++;
					$sheet->setCellValue( \PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex, $term_year );
					$sheet->getStyle( \PHPExcel_Cell::stringFromColumnIndex(0).$rowIndex.':'.\PHPExcel_Cell::stringFromColumnIndex( $colIndex ).$rowIndex )->applyFromArray(
							array(
									'font' => array( 'size' => 10,'name' => 'Arial' )
							)
					);
				}
			}
		
			$rowIndex = 1;//第一行字段名
			$colIndex = -1;//列
			foreach ($cols as $col) {
				$colIndex++;
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
	//住宿匹配
	function hm_add() {
		if (IS_POST) {
			$user = $this->user_model->find($_POST['user_id']);
			$term_year = trim($user['year'])."-".trim($user['term']);
			$house_user = $this->house_user_relationship->where(array('user_id' => $_POST['user_id']))->find();
			//如果这次匹配的是中国同屋，则把此中国同屋的匹配状态改为1
			if ($_POST['flg'] == 1) {
				//$recruit_member_term = $this->recruit_member_term_model->where(array('recruit_member_id' => $_POST['owner_id'],'term_year' => $term_year))->find();
				$this->recruit_member_term_model->where(array('recruit_member_id' => $_POST['owner_id'],'term_year' => $term_year))->save(array('housing_status' => 1));
				//$_POST['recruit_member_term_id'] = $recruit_member_term['id'];
			}
			//如果这次匹配的是友好家庭，则把此友好家庭的匹配人数加1
			if ($_POST['flg'] == 2) {
				$homestay_term = $this->homestay_term_model->where(array('homestay_id' => $_POST['owner_id'],'term_year' => $term_year))->find();
				$people_number = $homestay_term['people_number']+1;
				$this->homestay_term_model->where(array('homestay_id' => $_POST['owner_id'],'term_year' => $term_year))->save(array('people_number' => $people_number));
				//$_POST['recruit_member_term_id'] = 0;
			}
			//如果这次匹配的是留学生，则添加此留学生的匹配记录
			if ($_POST['flg'] == 3) {
				$this->house_user_relationship->add(array('user_id' => $_POST['owner_id'],'owner_id' => $_POST['user_id'],'flg' => 3));
				//$_POST['recruit_member_term_id'] = 0;
			}
			if ($house_user) {//此学生已经匹配过
				//如果上次匹配的是中国同屋，则把上一个中国同屋的匹配状态改为0
				if ($house_user['flg'] == 1) {
					$this->recruit_member_term_model->where(array('recruit_member_id' => $house_user['owner_id'],'term_year' => $term_year))->save(array('housing_status' => 0));
				}
				//如果上次匹配的是友好家庭，则把上一个友好家庭的匹配人数减1
				if ($house_user['flg'] == 2) {
					$homestay_term = $this->homestay_term_model->where(array('homestay_id' => $house_user['owner_id'],'term_year' => $term_year))->find();
					$people_number = $homestay_term['people_number']-1;
					$this->homestay_term_model->where(array('homestay_id' => $house_user['owner_id'],'term_year' => $term_year))->save(array('people_number' => $people_number));
				}
				//如果上次匹配的是留学生，则把此留学生的匹配记录删除
				if ($house_user['flg'] == 3) {
					$this->house_user_relationship->where(array('user_id' => $house_user['owner_id']))->delete();
				}
				//添加这个学生的匹配记录
				$this->house_user_relationship->where(array('user_id' => $_POST['user_id']))->save($_POST);
			} else {
				//添加这个学生的匹配记录
				$this->house_user_relationship->add($_POST);
			}
			
			$this->success(L('MATCH_SUCCESS'));
		} else {
			$student_id = (int)$_GET['student_id'];
			$student = $this->user_model->find($student_id);
			
			$host_family_where = array();
			$chinese_roommate_where = array();
			$foreign_roommate_where = array();
			if ($student['gender'] == 'Male') {
				$host_family_where['h.in_gender'] = array('neq',2);//如果学生是男生，选择可带性别为男和男女皆可的友好家庭
				$chinese_roommate_where['rm.gender'] = 1;//中国同屋为男生
				$foreign_roommate_where['gender'] = 'Male';
			}
			if ($student['gender'] == 'Female') {
				$host_family_where['h.in_gender'] = array('neq',1);//如果学生是女生，选择可带性别为女和男女皆可的友好家庭
				$chinese_roommate_where['rm.gender'] = 2;//中国同屋为女生
				$foreign_roommate_where['gender'] = 'Female';
			}
			//中国同屋
			$chinese_roommate_ids = array();//可选中国同屋id
			$chinese_roommate_where['rm.interview_status'] = 3;//面试通过
			$chinese_roommate_where['rm.status'] = 1;//学生
			$chinese_roommate_where['rmt.term_year'] = trim($student['year'])."-".trim($student['term']);
			$chinese_roommates = $this->recruit_member_model->alias('rm')->field('rm.*,rmt.housing_status')->join('__RECRUIT_MEMBER_TERM__ rmt ON rmt.recruit_member_id=rm.id')->where($chinese_roommate_where)->select();//筛选出来中国同屋
			foreach ($chinese_roommates as $chinese_roommate) {
				//$house_user = $this->house_user_relationship->where(array('flg' => 1,'owner_id' => $chinese_roommate['id']))->find();//查询中国同屋是否已经匹配过
				if (empty($chinese_roommate['housing_status'])) $chinese_roommate_ids[] = $chinese_roommate['id'];//如果这个中国同屋的学生没匹配过，则添加到可选中国同屋id数组中
			}
			$usable_chinese_roommates = $this->recruit_member_model->where(array('id' => array('in',implode(',', $chinese_roommate_ids))))->order('name asc')->select();
			
			//友好家庭
			$usable_host_family_ids = array();//可选友好家庭id
			$host_family_where['h.status'] = 1;
			$host_family_where['ht.term_year'] = trim($student['year'])."-".trim($student['term']);
			$host_families = $this->homestay_model->alias('h')->field('h.*,ht.people_number')->join('__HOMESTAY_TERM__ ht ON ht.homestay_id=h.id')->where($host_family_where)->select();//筛选出来友好家庭
			foreach ($host_families as $host_family) {
				//$house_user_count = $this->house_user_relationship->where(array('flg' => 2,'owner_id' => $host_family['id']))->count();//计算某一个友好家庭的入住人数
				if ($host_family['people_number'] < $host_family['number_of_people']) $usable_host_family_ids[] = $host_family['id'];//如果这个家庭现在入住人数小于可带人数，则添加到可选友好家庭id数组
			}
			$usable_host_families = $this->homestay_model->where(array('id' => array('in',implode(',', $usable_host_family_ids))))->order('name asc')->select();
			
			//外国同屋
			$foreign_roommate_ids = array();//可选外国同屋id
			$foreign_roommate_where['term_status'] = 1;
			$foreign_roommate_where['user_status'] = 1;
			$foreign_roommate_where['user_type'] = 2;
			$foreign_roommate_where['id'] = array('neq',$student_id);
			$foreign_roommate_where['term'] = $student['term'];
			$foreign_roommate_where['year'] = $student['year'];
			$foreign_roommates = $this->user_model->where($foreign_roommate_where)->select();//筛选出除自己之外的外国同屋
			foreach ($foreign_roommates as $foreign_roommate) {
				$house_user = $this->house_user_relationship->where(array('user_id' => $foreign_roommate['id']))->find();//查询外国同屋是否已经匹配过
				if (empty($house_user)) $foreign_roommate_ids[] = $foreign_roommate['id'];
			}
			$usable_foreign_roommates = $this->user_model->where(array('id' => array('in',implode(',', $foreign_roommate_ids))))->order('full_name asc')->select();
			
			$this->assign('student',$student);
			$this->assign('usable_chinese_roommates',$usable_chinese_roommates);
			$this->assign('usable_host_families',$usable_host_families);
			$this->assign('usable_foreign_roommates',$usable_foreign_roommates);
			$this->display();
		}
	}
	//填写地址
	function enter_address() {
		if ( IS_POST ) {
	
			$student_id = (int)$_POST['student_id'];
			$student = $this->user_model->find($student_id);
			$house_user = $this->house_user_relationship->where(array('user_id' => $student_id))->find();
	
			$term_year = trim($student['year'])."-".trim($student['term']);
			//中国同屋
			if ($house_user['flg'] == 1) {
				$house_user_save = $this->recruit_member_term_model->where(array('recruit_member_id' => $house_user['owner_id'],'term_year' => $term_year))->save(array('address' => $_POST['address']));
			} else {
				$house_user_save = $this->house_user_relationship->where(array('user_id' => $student_id))->save(array('address' => $_POST['address']));
				if ($house_user['flg'] == 3) $house_owner_save = $this->house_user_relationship->where(array('user_id' => $house_user['owner_id']))->save(array('address' => $_POST['address']));
			}
			
			if ($house_user_save) {
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->assign('student_id',$_GET['student_id']);
			$this->display();
		}
	}
	//添加同屋房源
	function cr_add() {
		if ( IS_POST ) {
			$_POST['create_time'] = date('Y-m-d H:i:s',time());
			$cr_id = $this->house_address_model->add($_POST);
			if ($cr_id) {
				//记录日志
				LogController::log_record($cr_id,1);
				$this->success(L('ADD_SUCCESS'));
			} else {
				$this->error(L('ADD_FAILED'));
			}
		} else {
			$this->display();
		}
	}
    
    //删除同屋房源
    function cr_delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            if ( $this->house_address_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,3);

                $this->success(L('DELETE_SUCCESS'));
            } else {
                $this->error(L('DELETE_FAILED'));
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            if ( $this->house_address_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //更改日志状态
                LogController::modify_log_type($log_id, 4);
                $this->success(L('RESTORE_SUCCESS'));
            } else {
                $this->error(L('RESTORE_FAILED'));
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->house_address_model->where( "id in ($object)" )->delete() !== false ) {
                //更改日志状态
                LogController::modify_log_type($log_id, 5);
                $this->success(L('COMPLETE_DELETE_SUCCESS'));
            } else {
                $this->error(L('COMPLETE_DELETE_FAILED'));
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            if ( $this->house_address_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success(L('DELETE_SUCCESS'));
            } else {
                $this->error(L('DELETE_FAILED'));
            }
        }
    }

    //同屋房源排序
    public function cr_orders() {
        $status = parent::_listorders( $this->house_address_model );
        if ( $status ) {
            $this->success(L('ORDER_UPDATE_SUCCESS'));
        } else {
            $this->error(L('ORDER_UPDATE_FAILED'));
        }
    }
    //友好家庭排序
    public function hs_orders() {
        $status = parent::_listorders( $this->homestay_model );
        if ( $status ) {
            $this->success(L('ORDER_UPDATE_SUCCESS'));
        } else {
            $this->error(L('ORDER_UPDATE_FAILED'));
        }
    }
    
    //住宿排序
    public function hm_orders() {
        $status = parent::_listorders( $this->house_user_relationship );
        if ( $status ) {
            $this->success(L('ORDER_UPDATE_SUCCESS'));
        } else {
            $this->error(L('ORDER_UPDATE_FAILED'));
        }
    }
    //删除住宿信息
    function hm_delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            if ( $this->house_user_relationship->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,3);

                $this->success(L('DELETE_SUCCESS'));
            } else {
                $this->error(L('DELETE_FAILED'));
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            if ( $this->house_user_relationship->where( "id in ($object)" )->save( $data ) !== false ) {
                //更改日志状态
                LogController::modify_log_type($log_id, 4);
                $this->success(L('RESTORE_SUCCESS'));
            } else {
                $this->error(L('RESTORE_FAILED'));
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->house_user_relationship->where( "id in ($object)" )->delete() !== false ) {
                //更改日志状态
                LogController::modify_log_type($log_id, 5);
                $this->success(L('COMPLETE_DELETE_SUCCESS'));
            } else {
                $this->error(L('COMPLETE_DELETE_FAILED'));
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            if ( $this->house_user_relationship->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success(L('DELETE_SUCCESS'));
            } else {
                $this->error(L('DELETE_FAILED'));
            }
        }
    }
   /*  //添加住宿匹配
    function hm_add() {
        if ( IS_POST ) {
            $hr_id = $this->house_user_relationship->add($_POST);
            if ($hr_id) {
                //记录日志
                LogController::log_record($hr_id,1);
                $this->success(L('ADD_SUCCESS'));
            } else {
                $this->error(L('ADD_FAILED'));
            }
        } else {
            $check=I('check');
            $check_html = null;
            $where = array();
            $where['status'] = array('eq',0);
            $where['del_flg'] = array('eq',0);
            //取出已匹配信息
            $list = $this->house_user_relationship->field('user_id,owner_id,flg')->where($where)->select();
            foreach ($list as $hm){
                $user[] = $hm['user_id'];
                if ( $hm['flg'] == 3 ){
                    $user[] = $hm['owner_id'];
                }else{
                    $owner[] = $hm['owner_id'];
                }
            }
            $user_str = implode(',',$user);
            $owner_str = implode(',',$owner);

            //取出可分配学生
            $u_where = array();
            $u_where['user_type'] = array('eq',2);
            $u_where['user_status'] = array('neq',0);
            if (!empty($user_str)){
                $u_where['id'] = array('not in',$user_str);
            }
            $user_list = $this->user_model
                ->field('id,full_name as name,phone,student_email as content')
                ->where($u_where)
                ->select();

            $o_where = array();
            if ( $check == 1 ){//取出可分配中国同屋
                $o_where['status'] = array('eq',1);
                $o_where['interview_status'] = array('eq',3);
                if (!empty($owner_str)){
                    $o_where['id'] = array('not in',$owner_str);
                }
                $owner_list = $this->recruit_member_model
                    ->field('id,name,phone,email as content')
                    ->where($o_where)
                    ->select();

                $check_html .= "<option value='1' selected>中国同屋</option>";
                $check_html .= "<option value='2'>友好家庭</option>";
                $check_html .= "<option value='3'>学生</option>";
            }elseif ( $check == 2 ){//取出可分配友好家庭
                $o_where['status'] = array('eq',0);
                $o_where['del_flg'] = array('eq',0);
                if (!empty($owner_str)){
                    $o_where['id'] = array('not in',$owner_str);
                }
                $owner_list = $this->homestay_model
                    ->field('id,name,cell_phone as phone,address as content')
                    ->where($o_where)
                    ->select();

                $check_html .= "<option value='1'>中国同屋</option>";
                $check_html .= "<option value='2' selected>友好家庭</option>";
                $check_html .= "<option value='3'>学生</option>";
            }elseif ( $check == 3 ){//取出可分配学生
                $owner_list = $user_list;
                $check_html .= "<option value='1'>中国同屋</option>";
                $check_html .= "<option value='2'>友好家庭</option>";
                $check_html .= "<option value='3' selected>学生</option>";
            }
            $this->assign( 'check', $check );
            $this->assign( 'user_list', $user_list );
            $this->assign( 'owner_list', $owner_list );
            $this->assign( 'check_html', $check_html );
            $this->display();
        }
    } */
    //编辑住宿匹配
    function hm_edit() {
        if ( IS_POST ) {
            $hr_id = (int)$_POST['id'];
            $result = $this->house_user_relationship->where(array('id' => $hr_id))->save($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($hr_id,2);
                $this->success(L('EDIT_SUCCESS'));
            } else {
                $this->error(L('EDIT_FAILED'));
            }
        } else {
            $check=I('check');
            $id = intval( I( 'get.id' ) );

            $check_html = null;
            $where = array();
            $where['status'] = array('eq',0);
            $where['del_flg'] = array('eq',0);

            $hrInfo = $this->house_user_relationship->find($id);
            $this->assign( 'hrInfo', $hrInfo );

            $user_id = (int)$hrInfo['user_id'];
            $userInfo = $this->user_model->find($user_id);
            $this->assign( 'userInfo', $userInfo );

            $owner_id = (int)$hrInfo['owner_id'];
            //取出匹配信息（中国同屋）
            if ( $hrInfo['flg'] == 1 ) {
                $ownerInfo = $this->recruit_member->field('id as id,name as owner_name,phone as owner_phone,email as owner_content')->find($owner_id);
            }
            //取出匹配信息（友好家庭）
            if ( $hrInfo['flg'] == 2 ) {
                $ownerInfo = $this->homestay_model->field('id as id,name as owner_name,cell_phone as owner_phone,address as owner_content')->find($owner_id);
            }
            //取出匹配信息（学生）
            if ( $hrInfo['flg'] == 3 ) {
                $ownerInfo = $this->user_model->field('id as id,full_name as owner_name,phone as owner_phone,student_email as owner_content')->find($owner_id);
            }
            if (empty($check)){
                //取出匹配信息（中国同屋）
                if ( $hrInfo['flg'] == 1 ) {
                    $where['id'] = array('neq',$owner_id);
                    $owner_list = $this->recruit_member->field('id as id,name as owner_name,phone as owner_phone,email as owner_content')->where($where)->select();
                    $check_html .= "<option value='1' selected>中国同屋</option>";
                    $check_html .= "<option value='2'>友好家庭</option>";
                    $check_html .= "<option value='3'>学生</option>";
                }
                //取出匹配信息（友好家庭）
                if ( $hrInfo['flg'] == 2 ) {
                    $where['id'] = array('neq',$owner_id);
                    $owner_list = $this->homestay_model->field('id as id,name as owner_name,cell_phone as owner_phone,address as owner_content')->where($where)->select();
                    $check_html .= "<option value='1'>中国同屋</option>";
                    $check_html .= "<option value='2' selected>友好家庭</option>";
                    $check_html .= "<option value='3'>学生</option>";
                }
                //取出匹配信息（学生）
                if ( $hrInfo['flg'] == 3 ) {
                    $where['id'] = array('neq',$owner_id);
                    $owner_list = $this->homestay_model->field('id as id,full_name as owner_name,phone as owner_phone,student_email as owner_content')->where($where)->select();
                    $check_html .= "<option value='1'>中国同屋</option>";
                    $check_html .= "<option value='2'>友好家庭</option>";
                    $check_html .= "<option value='3' selected>学生</option>";
                }
            }else{
                //取出匹配信息（中国同屋）
                if ( $check == 1 ) {
                    $where['id'] = array('neq',$owner_id);
                    $owner_list = $this->recruit_member->field('id as id,name as owner_name,phone as owner_phone,email as owner_content')->where($where)->select();
                    $check_html .= "<option value='1' selected>中国同屋</option>";
                    $check_html .= "<option value='2'>友好家庭</option>";
                    $check_html .= "<option value='3'>学生</option>";
                }
                //取出匹配信息（友好家庭）
                if ( $check == 2 ) {
                    $where['id'] = array('neq',$owner_id);
                    $owner_list = $this->homestay_model->field('id as id,name as owner_name,cell_phone as owner_phone,address as owner_content')->where($where)->select();
                    $check_html .= "<option value='1'>中国同屋</option>";
                    $check_html .= "<option value='2' selected>友好家庭</option>";
                    $check_html .= "<option value='3'>学生</option>";
                }
                //取出匹配信息（学生）
                if ( $check == 3 ) {
                    $where['id'] = array('neq',$owner_id);
                    $owner_list = $this->homestay_model->field('id as id,full_name as owner_name,phone as owner_phone,student_email as owner_content')->where($where)->select();
                    $check_html .= "<option value='1'>中国同屋</option>";
                    $check_html .= "<option value='2'>友好家庭</option>";
                    $check_html .= "<option value='3' selected>学生</option>";
                }
            }
            $this->assign( 'check', $check );
            $this->assign( 'ownerInfo', $ownerInfo );
            $this->assign( 'owner_list', $owner_list );
            $this->assign( 'check_html', $check_html );
            $this->display();
        }
    }
    //住宿匹配
    /*function house_template() {
        if ( IS_POST ) {
            $uploadConfig = array(
                    'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                    'rootPath' => './'.C( 'UPLOADPATH' ),
                    'savePath' => './excel/house/',
                    'saveName' => array( 'uniqid', '' ),
                    'exts' => array( 'xls', 'xlsx' ),
                    'autoSub' => false
            );
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file_house = './'.C( 'UPLOADPATH' ).$info['file']['savepath']['0'].$info['file']['savename']['0'];
            $file_user = './'.C( 'UPLOADPATH' ).$info['file']['savepath']['1'].$info['file']['savename']['1'];

            require_once 'today/excel/PHPExcel.php';
            require_once 'today/excel/PHPExcel/IOFactory.php';
            require_once 'today/excel/PHPExcel/Reader/Excel5.php';
            require_once 'today/excel/PHPExcel/Reader/Excel2007.php';

            //学生信息读取
            $reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file_user ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
            $obj = $reader->load( $file_user );
            $sheet = $obj->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $colCount = $sheet->getHighestColumn();
            $realRowCount = 0;
            $importCount = 0;
            $student_info_add = array();
            $table_first_name = $sheet->getCell( 'A1' )->getValue();
            $table_middle_name = $sheet->getCell( 'B1' )->getValue();
            $table_last_name = $sheet->getCell( 'C1' )->getValue();
            $table_year = $sheet->getCell( 'D1' )->getValue();
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $first_name = $sheet->getCell( 'A'.$i )->getValue();
                $middle_name = $sheet->getCell( 'B'.$i )->getValue();
                $last_name = $sheet->getCell( 'C'.$i )->getValue();
                $year = $sheet->getCell( 'D'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $student_info_add[] = array(
                        $table_first_name => $first_name, $table_middle_name => $middle_name, $table_last_name => $last_name, $table_year => $year
                );
            }

            //房屋信息读取
            $reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file_house ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
            $obj = $reader->load( $file_house );
            $sheet = $obj->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $colCount = $sheet->getHighestColumn();
            $realRowCount = 0;
            $importCount = 0;
            $student_info_add = array();
            $table_first_name = $sheet->getCell( 'A1' )->getValue();
            $table_last_name = $sheet->getCell( 'C1' )->getValue();
            $table_middle_name = $sheet->getCell( 'B1' )->getValue();
            $table_year = $sheet->getCell( 'D1' )->getValue();
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $first_name = $sheet->getCell( 'A'.$i )->getValue();
                $middle_name = $sheet->getCell( 'B'.$i )->getValue();
                $last_name = $sheet->getCell( 'C'.$i )->getValue();
                $year = $sheet->getCell( 'D'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $student_info_add[] = array(
                        $table_first_name => $first_name, $table_middle_name => $middle_name, $table_last_name => $last_name, $table_year => $year
                );
            }

            $this->success( '成功导入'.$importCount.'条学生记录', U( 'student/index' ) );
        } else {
            $this->display();
        }
    }*/
}/*$obj = $reader->load( $file );*/