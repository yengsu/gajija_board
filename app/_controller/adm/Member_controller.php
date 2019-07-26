<?
use system\traits\DB_NestedSet_Trait;
use Gajija\controller\_traits\AdmController_comm;
use Gajija\service\CommNest_service;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * 브랜드 관리자
 * 
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
class Member_controller extends CommNest_service
{
	use DB_NestedSet_Trait, AdmController_comm;
	
	/**
	 * 웹서비스용
	 * 
	 * @var object
	 */
	public $WebAppService;

	/**
	 * 라우팅 결과데이타
	 * 
	 * @var array 데이타
	 */
	public $routeResult = array();
	
	/**
	 * 회원 환경정보
	 *
	 * @filesource conf/member.conf.php
	 * @var array
	 */
	public static $mbr_conf = array();
	
	/**
	 * image base dir & Width Size
	 * 
	 * @var array
	 */
	private static $img_conf = array();
	
	/**
	 * Config - upload files
	 *  
	 * @var array
	 */
	private static $upload_options = array();
	
	public function __construct($routeResult)
	{
		
		if($routeResult)
		{
			// 라우팅 결과
			$this->routeResult = $routeResult ;
		}
		// 웹서비스
		if(!$this->WebAppService  || !class_exists('WebAppService'))
		{
				// instance 생성
				$this->WebAppService = &WebApp::singleton("WebAppService:system");
				
				// Query String
				WebAppService::$queryString = Func::QueryString_filter() ;
				// base URL
				WebAppService::$baseURL = $this->routeResult["baseURL"] ;
				
				if(!self::adm_hasLogin(array('flag'=>true, 'queryString'=>REQUEST_URI)) ){
				    //You have been signed out. Please login again.
				    $this->WebAppService->assign( array("error"=>"로그아웃되었습니다. 다시 로그인해주세요.") );
				}
				
				self::$mbr_conf = WebApp::getConf_real("member") ;
		}

	}
	
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k, $obj);
		}
	}

	public function add()
	{
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => "write",
						'queryString' => Func::QueryString_filter(),
						/* 'formType' => "등록" */
				        'formType' => "add"
				),
				'img_conf' => self::$img_conf
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/product/brandReg.html"), "adm");
		$this->WebAppService->Display->define('MENU_SUB', Display::getTemplate("_layout/adm/adm.menu.product.html")) ;
		$this->WebAppService->printAll();
	}
	public function write()
	{
		if(REQUEST_METHOD=="POST")
		{
			if(!$_POST["acronym"]) $this->WebAppService->assign(array('error'=>'영어 약자를 입력해주세요.'));
		    /* if( empty($_POST["name"]) ) $this->WebAppService->assign(array('error'=>'Please enter brand name.'));
		    if(!$_POST["acronym"]) $this->WebAppService->assign(array('error'=>'Please enter the first letter of the brand.')); */
			
			
			$put_data = array(
					"acronym" => (string) $_POST["acronym"],
					"name" => (string) $_POST["name"],
					"description" => (string) $_POST["description"],
					"main_v" => (int) $_POST["main_v"]
			) ;
			
			$upload_options = self::$upload_options ;
			
			$upload_options["width_sizes"]["logo"] = self::$img_conf["img_logo"] ;
			$upload_options["files"]["logo"] = $_FILES["img_logo"] ;
			
			$upload_options["width_sizes"]["base"] = self::$img_conf["img_base"] ;
			$upload_options["files"]["base"] = $_FILES["img_base"] ;
			
			$upload_options["width_sizes"]["banner"] = self::$img_conf["img_banner"] ;
			$upload_options["files"]["banner"] = $_FILES["img_banner"] ;
			
			
			$this->image_upload($upload_options);
			
			if( !empty($upload_options["result"]) ){
				if(!empty($upload_options["result"]["logo"]))
					$put_data = array_merge($put_data, array("img_logo"=> $upload_options["result"]["logo"]) );
					
				if(!empty($upload_options["result"]["banner"]))
					$put_data = array_merge($put_data, array("img_banner"=> $upload_options["result"]["banner"]) );
						
				if(!empty($upload_options["result"]["base"]))
					$put_data = array_merge($put_data, array("img_base"=> $upload_options["result"]["base"]) );
			}
			unset($upload_options);
			
			$put_data = array_merge($put_data ,array("regdate" => time()) ) ;
			
			// DB Table 선언
			$this->setTableName("brand");
			$insert_id = $this->dataAdd( $put_data	) ;
			if($insert_id)
			{
				header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
				exit;
			}
			else{
			    WebApp::moveBack("저장실패~다시입력해주세요.");
			    //WebApp::moveBack("Failed to save. Please re-enter.");
			    
			}
			
			
		}
	}
	
	public function edit()
	{
		if(REQUEST_METHOD=="GET")
		{
			// P.K 코드 값이 없을경우
			if( ! $this->routeResult["code"] )
			{	// exception
				header("Location: /".WebAppService::$baseURL."/add"); // 신규작성 폼으로 이동
				exit;
			}
			// DB Table 선언
			$this->setTableName("brand");
			
			$data = $this->dataRead( array(
					"columns"=> '*',
					"conditions" => array("serial" => $this->routeResult["code"])
			));
			
			if( !empty($data) )
			{
				$data= array_pop($data) ;
				if($data["img_logo"]) $data["img_logo"] = self::$img_conf['basedir']."logo/".$data["img_logo"] ;
				if($data["img_base"]) $data["img_base"] = self::$img_conf['basedir']."base/".$data["img_base"] ;
				if($data["img_banner"]) $data["img_banner"] = self::$img_conf['basedir']."banner/".$data["img_banner"] ;
			}
			else{
				WebApp::moveBack();
				//header("Location: /".WebAppService::$baseURL."/add"); // 신규작성 폼으로 이동
				//exit;
			}
		}
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => "update",
						"CODE" => $this->routeResult["code"],
						'queryString' => Func::QueryString_filter(),
						/* 'formType' => "편집" */
				        'formType' => "edit"
				)
				,'img_conf' => self::$img_conf	// upload image info
				,'DATA' => $data
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/product/brandReg.html"), "adm");
		$this->WebAppService->Display->define('MENU_SUB', Display::getTemplate("_layout/adm/adm.menu.product.html")) ;
		$this->WebAppService->printAll();
	}
	
	public function update()
	{
		
		if( ! (int) $this->routeResult["code"] )
		{	// exception
			WebApp::moveBack();
		}
		
		if( empty($_POST["hp"]) ) $this->WebAppService->assign(array('error'=>'전화번호를 입력해주세요.'));
		//if( empty($_POST["hp"]) ) $this->WebAppService->assign(array('error'=>'Please enter your phone number.'));
		
		$put_data = array(
				"is_admin" => (int) $_POST["is_admin"],
				"hp" => (string) $_POST["hp"],
				"grade" => (int) $_POST["grade"],
				"agree_news" => (int) $_POST["agree_news"],
				"withdrawal" => (int) $_POST["withdrawal"]
		) ;

		$this->setTableName("member");
		//------------------
		// 회원탈퇴가 체크되었고 탈퇴일자가 없으면 탈퇴일자를 넣어줌.
		if( (int) $_POST["withdrawal"] == 1 )
		{
			$data = $this->dataRead(array(
					"columns" => "withdrawal_date",
					"conditions" => "serial=". (int) $this->routeResult["code"]
			));
			if(!empty($data)){
				if( !(int)$data[0]['withdrawal_date'] ) $put_data['withdrawal_date'] = time() ;
			}
		}
		//------------------
		// DB Table 선언
		//$this->setTableName("member");
		$res = $this->dataUpdate($put_data, array(
				"serial" => $this->routeResult["code"]
		)) ;
		//if($res){}
		//header("Location: ".WebAppService::$baseURL."/edit/".$this->routeResult["code"].WebAppService::$queryString); // 리스트 페이지 이동
		header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
		exit;
		
	}
	
	/**
	 * 회원등급 설정 리스트
	 *
	 * @param array $queryOption
	 * @return array
	 */
	private function get_grades( $queryOption=array() )
	{
		$this->setTableName("member_grade");
		
		try
		{
		    $data_grade = $this->dataRead(array_merge(array(
					//"columns"=> 'serial, oid, grade_code, grade_name, c_price_more, c_price_under, c_qty_more, benefit_discount_rate, benefit_point_rate',
					"columns"=> 'serial, grade_code, grade_name',
		    ),$queryOption));
			
			return $data_grade;
		}
		catch (Exception $e) {
			$this->WebAppService->assign( array(
					"error"=>$e->getMessage(),
					"error_code" => $e->getCode()
			) );
		}
		
	}
	public function xx()
	{
		
		//use PhpOffice\PhpSpreadsheet\Spreadsheet;
		//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
		
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$spreadsheet->getProperties()->setCreator("manager")
		->setLastModifiedBy("manager")
		->setTitle("Office 2007 XLSX Test Document")
		->setSubject("Office 2007 XLSX Test Document")
		->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Test result file");
		$spreadsheet->setActiveSheetIndex(0);
		$spreadsheet->getActiveSheet()->getStyle('A1:J1')->applyFromArray(array(
				'font'  => array(
						'bold' => true,
						'color' => array('rgb' => 'FFFFFF'),
						'size'  => 11
						//'name' => 'Verdana'
				)
		))->getFill()->applyFromArray(array(
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'startcolor' => array(
						'rgb' => '696969'
				)
		));
		
		
		
		$sheet->setCellValue('A1', 'Hello World !');
		
		$writer = new Xlsx($spreadsheet);
		$writer->save('hello world.xlsx');
	}
	public function get_Excel()
	{
		$grades = $this->get_grades();
		$datas = $this->get_member_datas($grades) ;
		
		if( empty($datas) ) return ;
		
		
		/** Error reporting */
		//error_reporting(E_ALL);
		error_reporting(0);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
		//date_default_timezone_set('Asia/Seoul');
		if (PHP_SAPI == 'cli')
			die('This example should only be run from a Web Browser');
			
			require_once "PhpOffice/vendor/autoload.php" ;
			$objPHPExcel = new Spreadsheet();
			
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("manager")
			->setLastModifiedBy("manager")
			->setTitle("Office 2007 XLSX Test Document")
			->setSubject("Office 2007 XLSX Test Document")
			->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Test result file");
			
			$objPHPExcel->setActiveSheetIndex(0)
			/* ->setCellValue('A1', 'Seq')
			 ->setCellValue('B1', '회원아이디') // userid
			 ->setCellValue('C1', '회원명') // username
			 ->setCellValue('D1', '등급') // grade
			 ->setCellValue('E1', '전화번호') //hp
			 ->setCellValue('F1', '성별') // sex
			 ->setCellValue('G1', '주문건수') // total_oea
			 ->setCellValue('H1', '주문금액') // total_oprice
			 ->setCellValue('I1', '마일리지') // total_point
			 ->setCellValue('J1', '회원가입일'); // regdate */
			->setCellValue('A1', 'Seq')
			->setCellValue('B1', 'Member ID') // userid
			->setCellValue('C1', 'Name') // username
			->setCellValue('D1', 'Level') // grade
			->setCellValue('E1', 'Phone Number') //hp
			->setCellValue('F1', 'Gender') // sex
			->setCellValue('G1', 'Number of Orders') // total_oea
			->setCellValue('H1', 'Order Amount') // total_oprice
			->setCellValue('I1', 'Point') // total_point
			->setCellValue('J1', 'Registration Date'); // regdate
			
			$objPHPExcel->getActiveSheet()->getStyle('A1:J1')->applyFromArray(array(
					'font'  => array(
							'bold' => true,
							'color' => array('rgb' => '000000'),
							'size'  => 11
							//'name' => 'Verdana'
					)
			))->getFill()->applyFromArray(array(
					'type' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
					'startcolor' => array(
							'rgb' => '696969'
					)
			));
			$objPHPExcel->getActiveSheet()->getStyle('A1:J1')->applyFromArray(array(
					'borders' => array(
							'allborders' => array(
									'style' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
									'color' => array('rgb' => 'FFFFFF')
							)
					)
			));
			// Add some data
			$i = 1 ;
			foreach($datas as $seq => $data)
			{
				$i ++ ;
				/* $objPHPExcel->setActiveSheetIndex(0)
				 ->setCellValue('A'.$i, $i-1)
				 ->setCellValue('B'.$i, $data['userid'])
				 ->setCellValue('C'.$i, $data['username'])
				 ->setCellValue('D'.$i, $data['grade'])
				 ->setCellValue('E'.$i, (string)$data['hp'])
				 ->setCellValue('F'.$i, $data['sex'])
				 ->setCellValue('G'.$i, $data['total_oea'])
				 ->setCellValue('H'.$i, $data['total_oprice'])
				 ->setCellValue('I'.$i, $data['total_point'])
				 ->setCellValue('J'.$i, $data['regdate']); */
				$objPHPExcel->setActiveSheetIndex(0);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.$i, $i-1, PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$i, $data['userid'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('C'.$i, $data['username'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('D'.$i, $data['grade'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('E'.$i, (string)$data['hp'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('F'.$i, $data['sex'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('G'.$i, $data['total_oea'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('H'.$i, $data['total_oprice'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('I'.$i, $data['total_point'], PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$i, $data['regdate']);
				$objPHPExcel->getActiveSheet()->getStyle('J'.$i)->getNumberFormat()->setFormatCode("yyyy-mm-dd");

				$objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
			}
			$objPHPExcel->getActiveSheet()->freezePaneByColumnAndRow(1,2);
			$objPHPExcel->getActiveSheet()->setAutoFilter('B1:J'.$i);//($objPHPExcel->getActiveSheet()->calculateWorksheetDimension());//('B1:J'.$i);
			
			/* foreach(range('A','J') as $columnID) {
			 $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)
			 ->setAutoSize(true);
			 } */
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(18);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(18);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(19);
			
			$page = ($_REQUEST['page']) ? $page = '-'.$_REQUEST['page'] : '';
			
			
			
			// Rename worksheet
			$objPHPExcel->getActiveSheet()->setTitle('Members'.$page);
			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);
			/* 
			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="members'.$page.'_'.date("Y-m-d_His", time()).'.xls"');
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');
			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0
			 */
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="members'.$page.'_'.date("Y-m-d_His", time()).'.xls"');
			header('Cache-Control: max-age=0');
			
			$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
			$objWriter->save('php://output');
	}
	/**
	 * PHPEXcel 라이브러리 이용
	 * 
	 * @access [PHPEXcel] 라이브러리 개발중단 소식으로 사용안하기로 함(사용해도 무방하나 난 사용안할것임 -_-;)
	 * @deprecated
	 */
	public function get_Excel_deprecate()
	{
		$grades = $this->get_grades();
		$datas = $this->get_member_datas($grades) ;
		
		if( empty($datas) ) return ;
		
		
		/** Error reporting */
		//error_reporting(E_ALL);
		error_reporting(0);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
		//date_default_timezone_set('Asia/Seoul');
		if (PHP_SAPI == 'cli')
			die('This example should only be run from a Web Browser');
			
			require_once _APP_LIB."PHPExcel.php" ;
			$objPHPExcel = new PHPExcel();
			
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("manager")
			->setLastModifiedBy("manager")
			->setTitle("Office 2007 XLSX Test Document")
			->setSubject("Office 2007 XLSX Test Document")
			->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Test result file");
			
			$objPHPExcel->setActiveSheetIndex(0)
			/* ->setCellValue('A1', 'Seq')
			->setCellValue('B1', '회원아이디') // userid
			->setCellValue('C1', '회원명') // username
			->setCellValue('D1', '등급') // grade
			->setCellValue('E1', '전화번호') //hp
			->setCellValue('F1', '성별') // sex
			->setCellValue('G1', '주문건수') // total_oea
			->setCellValue('H1', '주문금액') // total_oprice
			->setCellValue('I1', '마일리지') // total_point
			->setCellValue('J1', '회원가입일'); // regdate */
			->setCellValue('A1', 'Seq')
			->setCellValue('B1', 'Member ID') // userid
			->setCellValue('C1', 'Name') // username
			->setCellValue('D1', 'Level') // grade
			->setCellValue('E1', 'Phone Number') //hp
			->setCellValue('F1', 'Gender') // sex
			->setCellValue('G1', 'Number of Orders') // total_oea
			->setCellValue('H1', 'Order Amount') // total_oprice
			->setCellValue('I1', 'Point') // total_point
			->setCellValue('J1', 'Registration Date'); // regdate
			
			$objPHPExcel->getActiveSheet()->getStyle('A1:J1')->applyFromArray(array(
					'font'  => array(
							'bold' => true,
							'color' => array('rgb' => 'FFFFFF'),
							'size'  => 11
							//'name' => 'Verdana'
					)
			))->getFill()->applyFromArray(array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'startcolor' => array(
							'rgb' => '696969'
					)
			));
			$objPHPExcel->getActiveSheet()->getStyle('A1:J1')->applyFromArray(array(
					'borders' => array(
							'allborders' => array(
									'style' => PHPExcel_Style_Border::BORDER_THIN,
									'color' => array('rgb' => 'FFFFFF')
							)
							
					)
			));
			
			// Add some data
			$i = 1 ;
			foreach($datas as $seq => $data)
			{
				$i ++ ;
				/* $objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$i, $i-1)
				->setCellValue('B'.$i, $data['userid'])
				->setCellValue('C'.$i, $data['username'])
				->setCellValue('D'.$i, $data['grade'])
				->setCellValue('E'.$i, (string)$data['hp'])
				->setCellValue('F'.$i, $data['sex'])
				->setCellValue('G'.$i, $data['total_oea'])
				->setCellValue('H'.$i, $data['total_oprice'])
				->setCellValue('I'.$i, $data['total_point'])
				->setCellValue('J'.$i, $data['regdate']); */
				$objPHPExcel->setActiveSheetIndex(0);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.$i, $i-1, PHPExcel_Cell_DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$i, $data['userid'], PHPExcel_Cell_DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('C'.$i, $data['username'], PHPExcel_Cell_DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('D'.$i, $data['grade'], PHPExcel_Cell_DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('E'.$i, (string)$data['hp'], PHPExcel_Cell_DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('F'.$i, $data['sex'], PHPExcel_Cell_DataType::TYPE_STRING2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('G'.$i, $data['total_oea'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('H'.$i, $data['total_oprice'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('I'.$i, $data['total_point'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$i, $data['regdate']);
				$objPHPExcel->getActiveSheet()->getStyle('J'.$i)->getNumberFormat()->setFormatCode("yyyy-mm-dd");
				
				$objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			}
			$objPHPExcel->getActiveSheet()->freezePaneByColumnAndRow(1,2);
			$objPHPExcel->getActiveSheet()->setAutoFilter('B1:J'.$i);//($objPHPExcel->getActiveSheet()->calculateWorksheetDimension());//('B1:J'.$i);
			
			/* foreach(range('A','J') as $columnID) {
				$objPHPExcel->getActiveSheet()->getColumnDimension($columnID)
				->setAutoSize(true);
			} */
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(7);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(12);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(18);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(14);
			
			$page = ($_REQUEST['page']) ? $page = '-'.$_REQUEST['page'] : '';
			
			// Rename worksheet
			$objPHPExcel->getActiveSheet()->setTitle('Members'.$page);
			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);
			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="members'.$page.'_'.date("Y-m-d_His", time()).'.xls"');
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');
			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0
			
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
	}
	
	/**
	 * 
	 */
	private function get_member_datas( &$grades )
	{
		$this->pageScale = 20;
		$this->pageBlock = 5;
		
		//------------------------------------------
		// 조건검색
		//------------------------------------------
		
		
		
		//$queryString = array();
		
		//$search_params["withdrawal"] = 0 ;
		
		// 관리자 인지
		if( isset($_REQUEST['Sis_admin']) && is_numeric($_REQUEST['Sis_admin'])){
			$search_params["is_admin"] = $_REQUEST['Sis_admin'] ;
			$queryString["Sis_admin"] = $_REQUEST['Sis_admin'] ;
		}
		// 회원등급
		if( !empty($_REQUEST['Sgrade']) && is_numeric($_REQUEST['Sgrade'])){
			$search_params["grade"] = $_REQUEST['Sgrade'] ;
			$queryString["Sgrade"] = $_REQUEST['Sgrade'] ;
		}
		//총 주문건수 범위
		/* if( isset($_REQUEST['Stotal_oea_start']) &&
				is_numeric($_REQUEST['Stotal_oea_start']) || is_numeric($_REQUEST['Stotal_oea_end']))
		{
			if( !is_numeric($_REQUEST['Stotal_oea_end']) ){
				$search_params["total_oea"] = (int) $_REQUEST['Stotal_oea_start'] ;
				$queryString["Stotal_oea_start"] = (int) $_REQUEST['Stotal_oea_start'] ;
			}
			else{
				$search_params["total_oea BETWEEN ".(int)$_REQUEST['Stotal_oea_start']." AND ".(int)$_REQUEST['Stotal_oea_end']] = '' ;
				$queryString["Stotal_oea_start"] = (int) $_REQUEST['Stotal_oea_start'] ;
				$queryString["Stotal_oea_end"] =  (int) $_REQUEST['Stotal_oea_end'] ;
			}
		}
		//총 주문금액 범위
		if( isset($_REQUEST['Stotal_oprice_start']) &&
				is_numeric($_REQUEST['Stotal_oprice_start']) || is_numeric($_REQUEST['Stotal_oprice_end']))
		{
			if( !is_numeric($_REQUEST['Stotal_oprice_end']) ){
				$search_params["total_oprice"] = (int) $_REQUEST['Stotal_oprice_start'] ;
				$queryString["Stotal_oprice_start"] = (int) $_REQUEST['Stotal_oprice_start'] ;
			}
			else{
				$search_params["total_oprice BETWEEN ".(int)$_REQUEST['Stotal_oprice_start']." AND ".(int)$_REQUEST['Stotal_oprice_end']] = '' ;
				$queryString["Stotal_oprice_start"] = (int) $_REQUEST['Stotal_oprice_start'] ;
				$queryString["Stotal_oprice_end"] =  (int) $_REQUEST['Stotal_oprice_end'] ;
			}
		} */
		//총 포인트(마일리지) 범위
		if( isset($_REQUEST['Stotal_point_start']) &&
				is_numeric($_REQUEST['Stotal_point_start']) || is_numeric($_REQUEST['Stotal_point_end']))
		{
			if( !is_numeric($_REQUEST['Stotal_point_end']) ){
				$search_params["total_point"] = (int) $_REQUEST['Stotal_point_start'] ;
				$queryString["Stotal_point_start"] = (int) $_REQUEST['Stotal_point_start'] ;
			}
			else{
				$search_params["total_point BETWEEN ".(int)$_REQUEST['Stotal_point_start']." AND ".(int)$_REQUEST['Stotal_point_end']] = '' ;
				$queryString["Stotal_oprice_start"] = (int) $_REQUEST['Stotal_oprice_start'] ;
				$queryString["Stotal_point_end"] =  (int) $_REQUEST['Stotal_point_end'] ;
			}
		}
		//이메일 수신
		/* if( isset($_REQUEST['Sagree_mail']) && !empty($_REQUEST['Sagree_mail']) )
		{
			if( $_REQUEST['Sagree_mail'] == 1 ){ // 동의
				$search_params["agree_mail"] = 1 ;
				$queryString["Sagree_mail"] = $_REQUEST['Sagree_mail'];
			}else if( $_REQUEST['Sagree_mail'] == 2 ){ // 미동의
				$search_params["agree_mail"] = 0 ;
				$queryString["Sagree_mail"] = $_REQUEST['Sagree_mail'];
			}
		}
		//SMS 수신 동의
		if( isset($_REQUEST['Sagree_sms']) && !empty($_REQUEST['Sagree_sms']))
		{
			if( $_REQUEST['Sagree_sms'] == 1 ){ // 동의
				$search_params["agree_sms"] = 1 ;
				$queryString["Sagree_sms"] = $_REQUEST['Sagree_sms'];
			}else if( $_REQUEST['Sagree_sms'] == 2 ){ // 미동의
				$search_params["agree_sms"] = 0 ;
				$queryString["Sagree_sms"] = $_REQUEST['Sagree_sms'];
			}
		} */
		if( isset($_REQUEST['Sagree_news']) && !empty($_REQUEST['Sagree_news']))
		{
			if( $_REQUEST['Sagree_news'] == 1 ){ // 동의
				$search_params["agree_news"] = 1 ;
				$queryString["Sagree_news"] = $_REQUEST['Sagree_news'];
			}else if( $_REQUEST['Sagree_news'] == 2 ){ // 미동의
				$search_params["agree_news"] = 0 ;
				$queryString["Sagree_news"] = $_REQUEST['Sagree_news'];
			}
		}
		//성별 : 1:남성(male) / 2:여성(female)
		if( isset($_REQUEST['Ssex']) && !empty($_REQUEST['Ssex']))
		{
			if( $_REQUEST['Ssex'] == 1 ){ // 남성(male)
				$search_params["sex"] = 1 ;
				$queryString["Ssex"] = $_REQUEST['Ssex'];
			}else if( $_REQUEST['Ssex'] == 2 ){ // 여성(female)
				$search_params["sex"] = 2 ;
				$queryString["Ssex"] = $_REQUEST['Ssex'];
			}
		}
		
		//가입기간
		if( isset($_REQUEST['Sdate_start']) )
		{
			if( $_REQUEST['Sdate_start'] && !(string)$_REQUEST['Sdate_end']){
				$sdate = explode('-', $_REQUEST['Sdate_start']) ;
				$s_date_s = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]) ;
				$s_date_e = mktime(23, 59, 59, $sdate[1], $sdate[2], $sdate[0]) ;
				$search_params["regdate BETWEEN ".(int)$s_date_s." AND ".(int)$s_date_e] = '' ;
				
				$queryString["Sdate_start"] = (string) $_REQUEST['Sdate_start'] ;
			}
			else	if( (string)$_REQUEST['Sdate_start'] && (string)$_REQUEST['Sdate_end'] ){
				$sdate = explode('-', $_REQUEST['Sdate_start']) ;
				$edate = explode('-', $_REQUEST['Sdate_end']) ;
				$s_date = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]) ;
				$e_date = mktime(23, 59, 59, $edate[1], $edate[2], $edate[0]) ;
				$search_params["regdate BETWEEN ".(int)$s_date." AND ".(int)$e_date] = '' ;
				
				$queryString["Sdate_start"] = (string) $_REQUEST['Sdate_start'] ;
				$queryString["Sdate_end"] = (string) $_REQUEST['Sdate_end'] ;
			}
		}
		
		//if( !ctype_space($_REQUEST['search_field']) && !preg_match("/[[:space:]]+/u", $_REQUEST['search_keyword']) ){
		if( isset($_REQUEST['Sfield']) && isset($_REQUEST['Skeyword']))
		{
			if( !empty($_REQUEST['Sfield']) && !empty($_REQUEST['Skeyword']) ){
				//$search_params = array() ;
				//$params[$_POST['search_field']." like CONCAT('%',?,'%')"] = $_POST['keyword'] ;
				if( $_REQUEST['Sfield'] == "username" ||	$_REQUEST['Sfield'] == "userid" || $_REQUEST['Sfield'] == "hp")
					$search_params[$_REQUEST['Sfield']." like ?"] = "%".$_REQUEST['Skeyword']."%" ;
					
					$queryString["Sfield"] = $_REQUEST['Sfield'] ;
					$queryString["Skeyword"] = $_REQUEST['Skeyword'] ;
			}
			else{
				$_REQUEST['Skeyword']='';
			}
		}
		if( isset($_REQUEST['SorderBy']) ) $orderBy = $_REQUEST['SorderBy'] ;
		else $orderBy = "regdate desc, is_admin";
		
		//------------------------------------------
		
		$queryOption = array(
				//"columns" => "serial, is_admin, grade, userid, username, hp, sex, total_oea, total_oprice, total_point, withdrawal, regdate",
				"columns" => "serial, is_admin, grade, userid, username, hp, sex, total_point, withdrawal, regdate",
				"conditions" => $search_params,
				"order" => $orderBy //"serial desc"
		);
		try{
			// DB Table 선언
			$this->setTableName("member");
			
			$datas = $this->dataList($queryOption);
			if( !empty($datas) )
			{
				foreach($datas as &$data)
				{
					$data["total_oea"] = number_format($data["total_oea"]) ;
					$data["total_oprice"] = number_format($data["total_oprice"]) ;
					$data["total_point"] = number_format($data["total_point"]) ;
					//FROM_UNIXTIME(regdate, '%Y-%m-%d') as
					if($data["sex"] == 1) $data["sex"] = 'male' ;   //남성
					else if($data["sex"] == 2) $data["sex"] = 'female' ; //여성
					else $data["sex"] = '-';
					
					$data["regdate"] = ($data["regdate"]) ? date('Y-m-d', $data["regdate"]) : '-' ;
					
					$data['grade_name'] = '';
					foreach($grades as &$grade){
						if( $grade['grade_code'] == $data['grade'] ){
							$data['grade_name'] = $grade['grade_name'] ;
							break ;
						}
					}
					
				}
			}
			
		}catch(Exception $e){
			$this->WebAppService->assign( array(
					"error"=>$e->getMessage(),
					"error_code" => $e->getCode()
			) );
		}
		
		return $datas ;
	}
	
	public function lst()
	{
		$grades = $this->get_grades();
		
		$datas = $this->get_member_datas($grades) ;
		
		$_REQUEST[self::$pageVariable] = $_GET[self::$pageVariable] ;
		$paging = $this->Pagination($_REQUEST[self::$pageVariable], $queryString);
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString
				),
				'LIST' => $datas,
				'TOTAL_CNT' => self::$Total_cnt,
				'VIEW_NUM' => self::$view_num,
				'PAGING' => $paging,
				'MBR_GRADES' => & $grades//self::$mbr_conf["grade"]
		));
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/member/memberList.html"), "admin_sub");
		$this->WebAppService->printAll();
	}
	
	public function view()
	{
		if( !(int)$this->routeResult["code"] ) {
			WebApp::moveBack("코드가 없습니다.");
		    //WebApp::moveBack("No code.");
			exit;
		}
		//회원등급 설정 리스트
		$this->setTableName("member_grade") ;
		$datas_grade = $this->dataRead(array(
				"columns" => "grade_code, grade_name",
				"order" => "grade_code"
		));
		
		// 회원 상세정보
		$this->setTableName("member") ;
		$data = $this->dataRead(array(
			"columns" => "*",
			"conditions" => "serial=".(int)$this->routeResult["code"]
		)) ;
		
		if( !empty($data) )
		{
			$data = array_pop($data) ;
			
			/*생일*/if($data['birthday']) $data['birthday'] = substr($data['birthday'], 0,4).'-'.substr($data['birthday'], 4,2).'-'.substr($data['birthday'], 6,2) ;
			/*회원탈퇴 일자*/if($data['withdrawal_date']) $data['withdrawal_date'] = date('Y-m-d H:i:s', $data['withdrawal_date']) ;
			/*등록일자*/$data['regdate'] = date('Y-m-d H:i:s', $data['regdate']) ;
			/*총 포인트*/$data['total_point'] = number_format($data['total_point']) ;
			
			
			//회원등급 명
			$this->setTableName("member_grade") ;
			$data['grade_name'] = $this->dataRead(array(
					"columns" => "grade_name",
					"conditions" => "grade_code=".$data['grade']
			)) ;
			if(!empty($data['grade_name'])) $data['grade_name'] = array_pop(array_pop($data['grade_name'])) ;
			
		}
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => 'update',
						'CODE' => (int)$this->routeResult["code"],
						'queryString' => WebAppService::$queryString
				),
				'SHIPPING_ADDRESSLIST' => &$data_shippingAddr,
				'MBR_GRADES' => & $datas_grade,//self::$mbr_conf["grade"]
				'DATA' => &$data
		));
		
		$this->WebAppService->Output( Display::getTemplate("adm/member/memberInfo.html"), "admin_sub");
		$this->WebAppService->printAll();
	}
	
	
	
}