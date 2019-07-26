<?
use system\traits\DB_NestedSet_Trait;
use Gajija\controller\_traits\AdmController_comm;
use Gajija\service\CommNest_service;

/**
 * 브랜드 관리자
 * 
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
class Leave_controller extends CommNest_service
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
				    $this->WebAppService->assign( array("error"=>"로그아웃되었습니다. 다시 로그인해주세요.") );
				}
				
				self::$mbr_conf = WebApp::getConf_real("member") ;
				
		}

	}
	
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	
	/**
	 * 회원등급 설정 리스트
	 *
	 * @param array $queryOption
	 * @return array
	 */
	private function get_grades( $queryOption=null )
	{
		$this->setTableName("member_grade");
		
		try
		{
			$data_grade = $this->dataRead(array(
					//"columns"=> 'serial, oid, grade_code, grade_name, c_price_more, c_price_under, c_qty_more, benefit_discount_rate, benefit_point_rate',
					"columns"=> 'serial, grade_code, grade_name',
			));
			return $data_grade;
		}
		catch (Exception $e) {
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
		}
		
	}
	
	/**
	 * 
	 */
	private function get_leaveMember_datas( &$grades )
	{
		$this->pageScale = 20;
		$this->pageBlock = 5;
		
		//------------------------------------------
		// 조건검색
		//------------------------------------------
		//$queryString = array();
		$search_params["withdrawal"] = 1 ;
		
		// 회원등급
		if( !empty($_REQUEST['Sgrade']) && is_numeric($_REQUEST['Sgrade'])){
			$search_params["grade"] = $_REQUEST['Sgrade'] ;
			$queryString["Sgrade"] = $_REQUEST['Sgrade'] ;
		}
		//탈퇴기간
		if( isset($_REQUEST['Sdate_start']) )
		{
			if( $_REQUEST['Sdate_start'] && !(string)$_REQUEST['Sdate_end']){
				$sdate = explode('-', $_REQUEST['Sdate_start']) ;
				$s_date_s = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]) ;
				$s_date_e = mktime(23, 59, 59, $sdate[1], $sdate[2], $sdate[0]) ;
				$search_params["leave_date BETWEEN ".(int)$s_date_s." AND ".(int)$s_date_e] = '' ;
				
				$queryString["Sdate_start"] = (string) $_REQUEST['Sdate_start'] ;
			}
			else	if( (string)$_REQUEST['Sdate_start'] && (string)$_REQUEST['Sdate_end'] ){
				$sdate = explode('-', $_REQUEST['Sdate_start']) ;
				$edate = explode('-', $_REQUEST['Sdate_end']) ;
				$s_date = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]) ;
				$e_date = mktime(23, 59, 59, $edate[1], $edate[2], $edate[0]) ;
				$search_params["leave_date BETWEEN ".(int)$s_date." AND ".(int)$e_date] = '' ;
				
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
		else $orderBy = "regdate desc";
		
		//------------------------------------------
		
		$queryOption = array(
				"columns" => "serial, is_admin, grade, userid, username, hp, sex, total_oea, total_oprice, total_point, regdate, withdrawal_date",
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
					if($data["sex"] == 1) $data["sex"] = '남성' ;
					else if($data["sex"] == 2) $data["sex"] = '여성' ;
					else $data["sex"] = '-';
					
					$data["regdate"] = ($data["regdate"]) ? date('Y-m-d', $data["regdate"]) : '-' ;
					$data["withdrawal_date"] = ($data["withdrawal_date"]) ? date('Y-m-d', $data["withdrawal_date"]) : '-' ;
					
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
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
		}
		
		return $datas ;
	}
	
	public function lst()
	{
		$grades = $this->get_grades();
		
		$datas = $this->get_leaveMember_datas($grades) ;
		
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
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/member/memberSecession.html"), "adm");
		$this->WebAppService->Display->define('MENU_SUB', Display::getTemplate("_layout/adm/adm.menu.member.html")) ;
		//회원아이디 모달
		$this->WebAppService->Display->define('ORDER_MODAL', Display::getTemplate("_layout/adm/adm.modal.member.html")) ;
		
		$this->WebAppService->printAll();
	}
	/**
	 * 선택삭제
	 */
	public function selDelete()
	{
		if(REQUEST_METHOD=="POST")
		{
			
			if( !empty($_POST["toggle"]) && is_array($_POST["toggle"]) )
			{
				foreach($_POST["toggle"]as $serial)
				{
					if( !(int)$serial ) continue ;
					
					// Data 제거
					$this->dataDelete(	array(
							"serial" => (int)$serial,
							"withdrawal" => 1
					)) ;
				}
			}
			
		}
		header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
		exit;
	}
	
}