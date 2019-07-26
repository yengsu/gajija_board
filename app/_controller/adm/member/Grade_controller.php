<?
use Gajija\service\Member_service;
use Gajija\controller\_traits\AdmController_comm;

/**
 * 회원등급 설정 관리
 * 
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
class Grade_controller extends Member_service
{
	use AdmController_comm ;
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
					//Logged out. Please login again.
					$this->WebAppService->assign( array("error"=>"로그아웃되었습니다. 다시 로그인해 주세요.") );
				}
				
				self::$mbr_conf["grade"] = WebApp::getConf("member.grade");
		}

	}
	
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}

	public function Req_read()
	{
		// DB Table 선언
		$this->setTableName("member_grade");
		$data = $this->dataRead(array(
				"columns"=>"*", 
				"conditions"=>"serial=".$this->routeResult["code"]
		));	
		if( !empty($data) )
		{
			$data = array_pop($data) ;
			$this->WebAppService->assign( $data );
		}
		else{
			$this->WebAppService->assign(array('error'=>'해당 데이타가 없습니다.'));
		    //$this->WebAppService->assign(array('error'=>'No data available.'));
		}
		
	}
	public function Req_configUpdate()
	{
		// DB Table 선언
		$this->setTableName("member_config");
		$res = $this->dataInsertUpdate(
				array(
						"oid" => OID,
						"grade_date" =>(int) $_POST["grade_date"]
				),
				"grade_date=VALUES(grade_date)"
			) ;
		
		$this->WebAppService->assign($res);
	
	}
		
	public function main()
	{
		//등급평가 기간 단위
		$member_grade_dates = array(3,6,12);
		
		// 회원 환경설정 db정보
		$member_config = $this->get_member_config();
		
		// 회원 등급 리스트
		$grads = $this->get_grads_datas();
		
		/* foreach(self::$mbr_conf["grade"] as $k => $grade){
			if( !empty(Func::array_searchKeyValue($grads, 'grade_code', $k)) ) unset(self::$mbr_conf["grade"][$k]);
		} */
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString
				), 
				'MBR_GRADES' => $this->get_grades(),//self::$mbr_conf["grade"], // 회원등급 종류
				'MBR_GRAD_DATAS' => $member_grade_dates,
				'MBR_CONFIG' => $member_config, // 회원 환경설정 db정보
				'LIST' => $grads
		)) ;
		
		$this->WebAppService->Output( "html/adm/member/memberGrade.html", "admin_sub");
		//$this->WebAppService->Display->define('MENU_SUB', Display::getTemplate("_layout/adm/adm.menu.member.html")) ;
		$this->WebAppService->printAll();
	}
	//-----------------------------------------------------
	/**
	 * 회원 환경설정
	 *
	 * @return null|array
	 */
	private function get_member_config()
	{
		$this->setTableName("member_config");
		$data = $this->dataRead(array(
				"columns"=>"*"
		));
		if( !empty($data) )
		{
			$data = array_pop($data) ;
		}
		
		return $data;
		
	}
	/**
	 * 회원등급 리스트
	 * 
	 * @return null|array
	 */
	private function get_grads_datas()
	{
		$queryOption = array(
				"columns" => "G.serial, G.grade_code, G.grade_name, G.benefit_point_rate, count(M.serial) as mbr_cnt",
				"groupBy" => "G.grade_code",
				"order" => "G.grade_code"
		);
		try{
			$datas = $this->GradeMember( $queryOption);
			$cnt = count($datas) ;
			
			if( !empty($datas) ){
				foreach($datas as $k => &$data)
				{
					$data['viewcnt'] = $cnt-$k;
					$data['c_price_more'] = number_format($data['c_price_more']) ;
					$data['c_price_under'] = number_format($data['c_price_under']) ;
					$data['c_qty_more'] = number_format($data['c_qty_more']) ;
				}
			}
			//echo '<pre>';print_r($datas);exit;
			
		}catch(Exception $e){
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
		}

		return $datas ;
		
	}
	
	public function write()
	{
		//echo '<pre>';print_r($_POST) ;exit;
		if(REQUEST_METHOD=="POST")
		{
			if( empty($_POST["grade_code"]) ) $this->WebAppService->assign(array('error'=>'회원등급명을 선택해주세요.'));
		    //if( empty($_POST["grade_code"]) ) $this->WebAppService->assign(array('error'=>'Please select a member class name.'));
			
			/* if( (int) $_POST["c_price_more"] && !(int) $_POST["c_price_under"] ){
				$_POST["c_price_under"] = (int) $_POST["c_price_more"] ;
			} */
			$put_data = array(
					"oid" => OID,
					"grade_code" => (int) $_POST["grade_code"],
					//"grade_name" => (string) trim(self::$mbr_conf["grade"][$_POST["grade_code"]]),
					"grade_name" => (string) trim($_POST["grade_name"])
			) ;
			
			// DB Table 선언
			$this->setTableName("member_grade");
			$insert_id = $this->dataAdd( $put_data	) ;
			if($insert_id)
			{
				header("Location: ".WebAppService::$baseURL."/main".WebAppService::$queryString); // 리스트 페이지 이동
				exit;
			}
			else{
			    /* WebApp::moveBack("저장실패~다시입력해주세요."); */
			    WebApp::moveBack("Failed to save. Please re-enter.");
			}
			
			
		}
	}
	
	public function update()
	{

		if(REQUEST_METHOD=="POST")
		{
			if( ! (int)$this->routeResult["code"] ) $this->WebAppService->assign(array('error'=>'코드를 찾을 수 없습니다.'));
		    //if( ! (int)$this->routeResult["code"] ) $this->WebAppService->assign(array('error'=>'Code not found.'));
			
			/* if( (int) $_POST["c_price_more"] && !(int) $_POST["c_price_under"] ){
				$_POST["c_price_under"] = (int) $_POST["c_price_more"] ;
			} */
			$put_data = array(
					"grade_code" => (int) $_POST["grade_code"],
					//"grade_name" => (string) trim(self::$mbr_conf["grade"][$_POST["grade_code"]]),
					"grade_name" => (string) trim($_POST["grade_name"])
					
			) ;
			// DB Table 선언
			$this->setTableName("member_grade");
			$res = $this->dataUpdate($put_data,	array(
							"serial" => $this->routeResult["code"]
				)) ;
			if(!$res){
				//Exception
				//WebApp::moveBack("업데이트할 자료가 존재하지 않습니다.");
				
			}
			
			header("Location: ".WebAppService::$baseURL."/main".WebAppService::$queryString); // 리스트 페이지 이동
			//header("Location: ".WebAppService::$baseURL."/edit".WebAppService::$queryString); // 리스트 페이지 이동
			exit;
		}
		
	}
	
	/**
	 * 선택삭제
	 */
	public function selDelete()
	{
		echo '<pre>';print_r($_POST);
		if(REQUEST_METHOD=="POST")
		{
			
			if( !empty($_POST["toggle"]) && is_array($_POST["toggle"]) )
			{
				
				foreach($_POST["toggle"]as $serial)
				{
					if( !(int)$serial ) continue ;
					
					// DB Table 선언
					$this->setTableName("member");
					$exist_member = $this->count("serial", array("serial" => (int)$serial) );

					// 해당 등급회원정보가 있는지
					if( (int)$exist_member )
					{
						// DB Table 선언
						$this->setTableName("member_grade");
						
						// Data 제거
						$this->dataDelete(	array(
								"serial" => $serial
						)) ;
					}
					
				}
			}
			
		}
		header("Location: ".WebAppService::$baseURL."/main".WebAppService::$queryString); // 리스트 페이지 이동
		exit;
	}
	
	
	
	
	
	
	
}