<?
use Gajija\service\CommNest_service;
use Gajija\controller\_traits\AdmController_comm;
use Gajija\lib\INI_manager;

class Main_controller extends CommNest_service
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
	 * 공용 서비스
	 * 
	 * @var object
	 */
	public $CommNest_service ;
	
	/**
	 * 회원 환경정보
	 *
	 * @filesource conf/member.conf.php
	 * @var array
	 */
	private static $mbr_conf = array();
	
	/**
	 * 쇼핑 환경정보
	 *
	 * @filesource conf/shop.conf.php
	 * @var array
	 */
	private static $shop_conf = array();
	
	public function __construct($routeResult)
	{
		if($routeResult)
		{
			// 라우팅 결과
			$this->routeResult = $routeResult ;

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
			}
		}
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	
	public function index()
	{
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString //Func::QueryString_filter()
				)
		)) ;
		$this->WebAppService->Output( "html/adm/main.html", "admin_sub");
		$this->WebAppService->printAll();
	}
	
}