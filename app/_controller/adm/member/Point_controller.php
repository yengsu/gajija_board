<?
use system\traits\DB_NestedSet_Trait;
//use Gajija\service\_traits\Service_Mail_Trait;
use Gajija\service\Member_service;
use Gajija\controller\_traits\AdmController_comm;

/**
 * 포인트(마일리지) 설정
 * 
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
class Point_controller extends Member_service
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
				self::$mbr_conf = WebApp::getConf_real("member") ;
				//self::$mbr_conf["grade"] = WebApp::getConf("member.grade");
		}

	}
	
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}

	/**
	 * 포인트(마일리지) - 기본설정 페이지
	 */
	public function setMain()
	{
		// DB Table 선언
		$this->setTableName("shop_point");
		$data = $this->dataRead(array("columns"=> '*'));
		if( !empty($data) ){
			$data = array_pop($data) ;
		}
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => 'setPost',
						'queryString' => WebAppService::$queryString
						//'formType' => "등록"
						//'CODE' => $CODE
				),
				'DATA' => $data

		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/member/mileageSet.html"), "adm");
		$this->WebAppService->Display->define('MENU_SUB', Display::getTemplate("_layout/adm/adm.menu.member.html")) ;
		$this->WebAppService->printAll();
	}
	
	/**
	 * Ajax - 비동기 처리
	 * 
	 * 포인트(마일리지) - 기본설정 Insert/Update
	 */
	public function setPost()
	{
		if(REQUEST_METHOD=="POST")
		{
			// DB Table 선언
			$this->setTableName("shop_point");
			$res = $this->dataInsertUpdate(
					array(
							"oid" => (int) OID,
							"add_rate" => (int) $_POST["add_rate"],
							"usable_point" => (int) $_POST["usable_point"]
					),
					"add_rate=VALUES(add_rate),".
					"usable_point=VALUES(usable_point)"
				) ;
			
			$this->WebAppService->assign( $res );
			//header("Location: ".WebAppService::$baseURL."/setMain"); // 메인 페이지 이동
			
		}
		
		
	}
	//-----------------------------------------------------
	
	private function get_pointHistory()
	{
		$this->pageScale = 20;
		$this->pageBlock = 5;

		// 조건검색
		if( isset($_REQUEST['Sfield']) && isset($_REQUEST['Skeyword']))
		{
			if( !empty($_REQUEST['Sfield']) && !empty($_REQUEST['Skeyword']) ){
				//$params[$_POST['search_field']." like CONCAT('%',?,'%')"] = $_POST['keyword'] ;
				if( $_REQUEST['Sfield'] == "userid" ||
						$_REQUEST['Sfield'] == "username" ||
						$_REQUEST['Sfield'] == "hp")
				{
					$search_params['M.'.$_REQUEST['Sfield']." like ?"] = "%".$_REQUEST['Skeyword']."%" ;
					
					$queryString["Sfield"] = $_REQUEST['Sfield'] ;
					$queryString["Skeyword"] = $_REQUEST['Skeyword'] ;
				}
			}
			else{
				$_REQUEST['Skeyword']='';
			}
		}
		
		//기간
		if( isset($_REQUEST['Sdate_start']) )
		{
			if( $_REQUEST['Sdate_start'] && !(string)$_REQUEST['Sdate_end']){
				$sdate = explode('-', $_REQUEST['Sdate_start']) ;
				$s_date_s = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]) ;
				$s_date_e = mktime(23, 59, 59, $sdate[1], $sdate[2], $sdate[0]) ;
				$search_params["H.regdate BETWEEN ".(int)$s_date_s." AND ".(int)$s_date_e] = '' ;
				
				$queryString["Sdate_start"] = (string) $_REQUEST['Sdate_start'] ;
			}
			else	if( (string)$_REQUEST['Sdate_start'] && (string)$_REQUEST['Sdate_end'] ){
				$sdate = explode('-', $_REQUEST['Sdate_start']) ;
				$edate = explode('-', $_REQUEST['Sdate_end']) ;
				$s_date = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]) ;
				$e_date = mktime(23, 59, 59, $edate[1], $edate[2], $edate[0]) ;
				$search_params["H.regdate BETWEEN ".(int)$s_date." AND ".(int)$e_date] = '' ;
				
				$queryString["Sdate_start"] = (string) $_REQUEST['Sdate_start'] ;
				$queryString["Sdate_end"] = (string) $_REQUEST['Sdate_end'] ;
			}
		}

		$_REQUEST[self::$pageVariable] = $_GET[self::$pageVariable] ;
		
		/* $columns = "
				CASE
					WHEN @cvalue is NULL THEN
						@sum:= H.point
					WHEN @cvalue != M.userid THEN
						@sum:= H.point
					ELSE
						@sum:= @sum + H.point
				END as cur_point,
				CASE
					WHEN @cvalue is NULL THEN
						@cvalue:= M.userid
					WHEN @cvalue != M.userid THEN
						@cvalue:= M.userid
				END as rep,
				H.serial, M.userid, M.username, G.grade_name, H.point, H.description, H.regdate"; */
		try{
			$datas = $this->PointHistoryMember(array(
					"columns"=> 'H.serial, M.userid, M.username, M.hp, G.grade_name, H.point, H.cur_point, H.description, H.regdate',
					"conditions" => $search_params,
					"order" => "H.regdate desc"
			));
			//echo'<pre>';print_r($datas);exit;
			if( !empty($datas) ){
				foreach($datas as &$data)
				{
					$data['num_point'] = number_format($data['point']) ;
					$data['cur_point'] = number_format($data['cur_point']) ;
					$data['regdate'] = date('Y-m-d H:i', $data['regdate']) ;
				}
			}
		}catch(Exception $e){
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
		}

		$paging = $this->Pagination($_REQUEST[self::$pageVariable], $queryString);

		WebAppService::$queryString = Func::QueryString_filter( $queryString );

		return array(
				'LIST' => $datas,
				'TOTAL_CNT' => self::$Total_cnt,
				'VIEW_NUM' => self::$view_num,
				'PAGING' => $paging
		);

	}

	/**
	 * 포인트(마일리지) - 적립/사용 내역 페이지
	 */
	public function pointHistory()
	{
		$this->WebAppService->assign(
				array_merge(array(
						'Doc' => array(
								'baseURL' => WebAppService::$baseURL,
								//'Action' => $Action,
								'queryString' => WebAppService::$queryString,
								//'formType' => "등록"
								//'CODE' => $CODE
						)
				), $this->get_pointHistory()
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/member/mileagepay.html"), "adm");
		$this->WebAppService->Display->define('MENU_SUB', Display::getTemplate("_layout/adm/adm.menu.member.html")) ;
		$this->WebAppService->Display->define('ORDER_MODAL', Display::getTemplate("_layout/adm/adm.modal.member.html")) ;
		$this->WebAppService->printAll();
	}
	
	
}