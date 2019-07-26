<?

/**
 */
class Pub_controller {
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
	 * 공용 서비스
	 *
	 * @var object
	 */
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
	    if( ! $this->WebAppService instanceof WebAppService )
	    {
			// instance 생성
			$this->WebAppService = &WebApp::singleton ( "WebAppService:system" );
			
			// Query String
			WebAppService::$queryString = Func::QueryString_filter ();
			// base URL
			WebAppService::$baseURL = $this->routeResult ["baseURL"];
			
			/* if( method_exists(__CLASS__, $this->routeResult["action"]) )
				$this->{$this->routeResult["action"]}(); */
				
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
		if( empty($this->routeResult["layout"]) || empty(WebApp::getConf_real("layout.".$this->routeResult["layout"].".LAYOUT")) ) {
			//header ( "location: /" );
			echo 'not found';
			exit ();
		}
		
		if( !is_dir("html_".$this->routeResult['lang']) ){
			echo 'not language found';
			exit;
		}
		
		
		$file = "html_".$this->routeResult['lang']."/" . $this->routeResult['folder'] . $this->routeResult['action'] . ".html";
		if (! is_file ( $file ))
			$file = "html_".$this->routeResult['lang']."/blank.html";
			
			$this->WebAppService->Output ( Display::getTemplate ( $file ), $this->routeResult["layout"]);
			
			foreach($this->WebAppService->Display->tpl_ as $block => &$tpl){
				$tpl['path'] = str_replace('html/_layout', 'html_'.$this->routeResult['lang'].'/_layout', $tpl['path']);
			}
			//echo '<pre>';print_r($this->WebAppService->Display->tpl_);exit;
			$this->WebAppService->printAll ();
	}
	
	
	
	
	
	
	
	
	
}