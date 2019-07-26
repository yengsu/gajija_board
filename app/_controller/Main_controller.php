<?
use Gajija\controller\_traits\Controller_comm;
use system\traits\DB_NestedSet_Trait;
use Gajija\service\CommNest_service;
use Gajija\controller\_traits\Page_comm;

class Main_controller extends CommNest_service
{
	use Controller_comm, DB_NestedSet_Trait, Page_comm ;
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
	 * 사이트 메뉴정보 데이타
	 *
	 * @var mixed
	 */
	public static $menu_datas ;
	
	/**
	 * image base dir & Width Size
	 *
	 * @var array
	 */
	private static $img_conf = array();
	
	public function __construct($routeResult)
	{
		if($routeResult)
		{
			// 라우팅 결과
			$this->routeResult = $routeResult ;

			// 웹서비스
			if( ! $this->WebAppService instanceof WebAppService )
			{
					// instance 생성
					$this->WebAppService = &WebApp::singleton("WebAppService:system");
					// Query String
					WebAppService::$queryString = Func::QueryString_filter() ;
					// base URL
					WebAppService::$baseURL = $this->routeResult["baseURL"] ;
					
					//self::$img_conf = WebApp::getConf_real("upload_img") ;
			}
		}
	}
	public function __destruct()
	{
		//unset($this);
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	/**
	 * 팝업 (window창)
	 */
	public function popupWin()
	{
		if(REQUEST_METHOD=="GET")
		{
			// P.K 코드 값이 없을경우
			if( ! (int)$_GET["code"] )
			{	// exception
				exit;
			}
			$this->setTableName("popups") ;
			$data = $this->dataRead( array(
					"columns"=> '*',
					"conditions" => array("serial" => (int)$_GET["code"], 'output'=> 'win')
			));
			if( !empty($data) )
			{
				$data = array_pop($data);
				$file = $data['attach_basedir'].$data['attach_file'];
				
				if( is_file($file) )
				{
					$this->WebAppService->Output( $file, "base");
					$this->WebAppService->printAll();
				}
			}
		}
	}
	/**
	 * 팝업 리스트
	 *
	 * @return array
	 */
	private function get_popups()
	{
		//$this->pageScale = 15;
		//$this->pageBlock = 10 ;
		
		$this->setTableName("popups") ;
		
		$curdate = time() ; 
		$queryOption = array(
				"columns" => "serial, width, height, output, attach_basedir, attach_file",
				"conditions" => array(
										'imp' => 1,
										'(edate=0 OR '.$curdate.' BETWEEN sdate AND edate)'
									),
				"order" => "regdate desc"
		);
		try{
			$datas = $this->dataRead( $queryOption);
			if(!empty($datas))
			{
				foreach($datas as &$data)
				{
					if($data['output']=='layer')
					{
						if( !empty($data['attach_file']) && is_file($data['attach_basedir'].$data['attach_file']) ){
							$this->WebAppService->File->file($data['attach_basedir'].$data['attach_file'], 'r');
							$data['attach_file_cont'] = json_encode($this->WebAppService->File->readfile());
						}
					}
					else if($data['output']=='win'){
						$data['url'] = "/Main/popupWin?code=". $data['serial'] ;
					}
					unset($data['attach_basedir'],$data['attach_file']);
				}
				unset($data);
			}
		}catch(Exception $e){
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
		}
		return $datas ;
	}
	
	public function index()
	{
		/**
		 * [applet 사용법1] class method를 쓰는방법
		 * 
		 * --> $this->WebAppService->assign('applet', $this->CommNest_service) ;
		 * --> app/lib/WebApp/namespace/provider/applet.php 파일에 
		 * 				return "{ applet->$attr["method"](".$attr["args"].") }";
		 * 
		 * --> 출력결과 : { applet->test('abc', 32) }
		 * 
		 * html안에 삽입 
		 * 		<provider:applet method="test" bid="abc" serial="32" ignore="0">
		 *     		tpl....
		 * 		</provider:applet> 
		 * *****************************************************
		 * [applet 사용법2] class method를 쓰는방법
		 * .....기본
		 */
		
		// 비동기식(ajax)처리중에 페이지 url 찾을 수없어서 메인페이지로 이동했을 경우 
		if(REQUEST_WITH == 'AJAX') {
			header('HTTP/1.0 404 '.rawurlencode($_SERVER['REQUEST_URI']." 찾을 수 없습니다."));
			exit;
		}
		self::$menu_datas = $this->get_menu('menu');
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString //Func::QueryString_filter()
				)
				,'MNU' => self::$menu_datas
				,'MENU_TOP' => &self::$menu_datas['childs']
				,'IMG_CONF' => &self::$img_conf
		        //,'MOBILE_CHECK' => IS_MOBILE
				,'POPUP' => $this->get_popups()
				//,'applet' => $this // 애플릿 등록후 front페이지에서 provider태그로 이용가능
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("main.html"), "main");
		$this->WebAppService->printAll();
	}
	
	
}