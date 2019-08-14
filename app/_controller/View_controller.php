<?

/**
 */
use Gajija\controller\_traits\Page_comm;
use system\traits\DB_NestedSet_Trait;

class View_controller {
    
    use Page_comm, DB_NestedSet_Trait ;
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
	public $routeResult = array ();
	/**
	 * 사이트 메뉴정보 데이타
	 * @var mixed
	 */
	public static $menu_datas ;
	/**
	 * 공용 서비스
	 *
	 * @var object
	 */
	public $CommNest_service;
	public function __construct($routeResult) {
		if ($routeResult) {
			// 라우팅 결과
			$this->routeResult = $routeResult;
		}
		if (empty ( $this->routeResult ['action'] )) {
			header ( "location: /" );
			exit ();
		}
		
		// 웹서비스
		if (! $this->WebAppService || ! class_exists ( 'WebAppService' )) {
			// instance 생성
			$this->WebAppService = &WebApp::singleton ( "WebAppService:system" );
			
			// Query String
			WebAppService::$queryString = Func::QueryString_filter ();
			// base URL
			WebAppService::$baseURL = $this->routeResult ["baseURL"];
			
			/* if( method_exists(__CLASS__, $this->routeResult["action"]) )
				$this->{$this->routeResult["action"]}(); */
		}
		
		try
		{
		    $this->menu_display_apply($routeResult["mcode"]) ;
		}
		catch (\Exception $e) {
		    $this->WebAppService->assign( array(
		        "error" => $e->getMessage(),
		        "error_code" => $e->getCode()
		    ));
		}
		
	}
	public function __destruct() {
	}
	
	public function index()
	{
		/**
		 * [applet 사용법1] class method를 쓰는방법
		 *
		 * --> $this->WebAppService->assign('applet', $this->CommNest_service) ;
		 * --> app/lib/WebApp/namespace/provider/applet.php 파일에
		 * return "{ applet->$attr["method"](".$attr["args"].") }";
		 *
		 * --> 출력결과 : { applet->test('abc', 32) }
		 *
		 * html안에 삽입
		 * <provider:applet method="test" bid="abc" serial="32" ignore="0">
		 * tpl....
		 * </provider:applet>
		 * *****************************************************
		 * [applet 사용법2] class method를 쓰는방법
		 * .....기본
		 */
		$this->WebAppService->assign ( array (
				'Doc' => array (
						'baseURL' => WebAppService::$baseURL,
						'queryString' => Func::QueryString_filter ()
				),
				'applet' => $this  // 애플릿 등록후 front페이지에서 provider태그로 이용가능
		) );
		
		if( empty($this->routeResult["layout"]) || ctype_space($this->routeResult["layout"]) || 
				empty($this->routeResult["action"]) || ctype_space($this->routeResult["action"]) )
		{
			header ( "location: /" );
			exit;
		}
		
		if( ! preg_match('/^[a-zA-Z0-9_]+$/', $this->routeResult["layout"]) ){
			header ( "location: /" );
			exit;
		}
		if( ! preg_match('/^[a-zA-Z0-9_]+$/', $this->routeResult["action"]) ){
			header ( "location: /" );
			exit;
		}
		if( !empty($this->routeResult["folder"]) && !ctype_space($this->routeResult["folder"]) ){
			if( ! preg_match('/^[a-zA-Z0-9_]+$/', substr($this->routeResult["folder"], 0, -1)) ){
				header ( "location: /" );
				exit;
			}
		}
		
		if( empty($this->routeResult["layout"]) || empty(WebApp::getConf_real("layout.".$this->routeResult["layout"].".LAYOUT")) ) {
			header ( "location: /" );
			exit;
		}
		
		$file = $this->routeResult['folder'] . $this->routeResult['action'] . ".html";
			
		$this->WebAppService->Output ( Display::getTemplate( $file ), $this->routeResult["layout"]);
		$this->WebAppService->printAll();
	}
	
}
