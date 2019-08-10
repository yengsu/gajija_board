<?php
use Gajija\lib\BikeRouter;

class Router extends BikeRouter{

	private $routeRes = array(); // Router Parsing Result

	/**
	 * 
	 * @param string $routes (라우터 패턴그룹 선택)
	 * @param boolean $preLoad ( 바로 parsing 할건지)
	 */
	public function __construct($routes = 'default', $preLoad)
	{
		if( ! $this->routes ) $this->routes = self::dsn($routes) ;
		
		if($preLoad)
			$this->routeUrlParse();
	}
	
	
	/**
	 * 
	 * @param string $routes(라우팅 패턴:: router.conf.php파일안의 블럭이름 정의)
	 * @param string $preLoad(최초실행시)
	 * @return multitype:
	 */
	public function index($routes = 'default', $preLoad=NULL)
	{
		$this->routes = self::dsn($routes) ;

     	if($preLoad)     		
     		$this->routeUrlParse();
	}
	
	/**
	 * URL parsing(파싱)
	 * 
	 * @return array 
	 */
	public function routeUrlParse($URI = REQUEST_URI)
	{
		$this->routeRes = $this->match( 'GET, POST', $URI);
		$this->routeParseUrl();
	}
	/**
	 * 라우팅 파싱후 Controller, View 호출
	 * 
	 * 
	 * mcode는 라우팅을 통해 받은값 또는 $_REQUEST로 받을 수 있다.
	 * 마지막 최종값은 라우팅보다 $_REQUEST로 받은값
	 * 
	 * $this->routeRes ===>
	 * 
	 * 		[data] => Array
        	(
            	[bid] => aa
            	[page] => 1
            	[folder] => 폴더명
            	[controller] => BoardComm 또는 [view] => BoardComm 
            	[mcode] => 메뉴코드
            	[action] => lst
        	)
	 * 
	 */
	public function routeParseUrl()
	{
		if( empty($this->routeRes) || !is_array($this->routeRes) || empty($this->routeRes['url']) || substr($this->routeRes['url']["action"],0,2) == "__" ){
			$this->routeRes = array(
						"data" => array(
									"controller" => "main",
									"action" => "index"
								)
					) ;
			//header("location: /");
			//exit;
		}
		
		/**
		 * 메뉴코드(mcode)에 따른 baseURL 처리-1
		 * 
		 * ==> /board/BoardComm/lst?bid=aa&mcode=4
		 */
		if( isset($_REQUEST['mcode']) )
		{
			// 폴더검사 (뒤에 '/' 붙임)
			if( isset($this->routeRes["data"]['folder']) )
				$this->routeRes["data"]['folder'] =  ( mb_substr($this->routeRes["data"]['folder'], -1) != "/" ) ? $this->routeRes["data"]['folder'].'/' : $this->routeRes["data"]['folder'] ;
			else
				$this->routeRes["data"]['folder'] = '';
				
			// 기본 URL (링크 URL 설정)
			$baseURL = '/'.$this->routeRes["data"]["folder"].$this->routeRes["data"]["controller"] ;
			$this->routeRes["data"]["baseURL"] = $baseURL ;
		}
		/**
		 * 메뉴코드(mcode)에 따른 baseURL 처리-2
		 *
		 * ==> /4/board/BoardComm/lst?bid=aa
		 */
		else{
			// mcode (앞에 '/' 붙임)
			if( isset($this->routeRes["data"]['mcode']) )
				$mcode_str =  ( mb_substr($this->routeRes["data"]['mcode'], 0) != "/" ) ? '/'.$this->routeRes["data"]['mcode'] : $this->routeRes["data"]['mcode'] ;
			else
				$mcode_str = '';
			
			// 폴더검사 (뒤에 '/' 붙임)
			if( isset($this->routeRes["data"]['folder']) )
				$this->routeRes["data"]['folder'] =  ( mb_substr($this->routeRes["data"]['folder'], -1) != "/" ) ? $this->routeRes["data"]['folder'].'/' : $this->routeRes["data"]['folder'] ;
			else
				$this->routeRes["data"]['folder'] = '';
			
			
			// 기본 URL (링크 URL 설정)
			$baseURL = $mcode_str.'/'.$this->routeRes["data"]["folder"].$this->routeRes["data"]["controller"] ;
			$this->routeRes["data"]["baseURL"] = $baseURL ;
		}
		
		// Controller
		if($this->routeRes["data"]["controller"])
		{
			$this->routeAction($this->routeRes["data"], "controller");
		}
		else if($this->routeRes["data"]["plugin"])
		{
			$this->routeAction($this->routeRes["data"], "plugin");
		}
		// View
		else if($this->routeRes["data"]["view"]){
			$this->routeView($this->routeRes["data"]);
		}
	}
	/**
	 * 라우팅 파싱후 호출된 Controller 처리
	 *
	 * @param array $data( routing파싱 결과값)
	 * @return ( method 값이 있으면 메서드 호출, 없으면 void)
	 */
	public function routeAction($data, $prefix)
	{
		
		if( !preg_match( '([0-9A-Za-z.]+)', $prefix) ) return ;
		
		$data[$prefix] = ucfirst( $data[$prefix] );

		$className = $data[$prefix].'_'.$prefix ;
		
		if( $className == "View_controller") 
			$instance = &WebApp::singleton($className.':'.$prefix, '', $data);
		else if( $className == "Pub_controller")
			$instance = &WebApp::singleton($className.':'.$prefix, '', $data);
		else 
			$instance = &WebApp::singleton($className.':'.$prefix, $data['folder'], $data);
		
		try
		{
			// instance 있으면
			if( $instance )
			{
				$class_methods = array_flip(get_class_methods($instance));
				
				if( empty($class_methods[$data["action"]])) throw new Exception( get_class($instance)." is not exist ".$data["action"]." method");
				
				if( method_exists( $instance, $data["action"]) ) {
				
					$reflection = new ReflectionMethod($instance,  $data["action"]);
					if($reflection->isPublic()){ // public 메서드만 
						$reflection->invokeArgs($instance, array($data)); // 해당메서드에 arguments까지 전달시
					}else{
						throw new Exception( get_class($instance)." is not exist ".$data["action"]." method");
					}
					
				}else{ 
					throw new Exception( get_class($instance)." is not exist ".$data["action"]." method");
				}
			}else{
				//header("location: /main");
				//header("location: http://".$_SERVER['HTTP_HOST']."/main");
				//exit;
				throw new Exception( get_class($instance)." is not exist");
			}
			
		}
		catch (Exception $e) {
			//echo $e->getMessage();exit;
			//header("location: /main");
			if( method_exists( $instance, 'index') ) 
				$instance->index($data);
			else
				header("location: /");
			//header("HTTP/1.1 404 Not Found");
			//header('Location: '.$file);
			//debug_print_backtrace();
			//header('Location: /');
			//echo '<pre>';print_r($e);
			exit;
		}
	}
	/**
	 * 라우팅 파싱후 호출된 View 처리
	 * 
	 * @param array $data( routing파싱 결과값)
	 * @return void
	 */
	public function routeView($data)
	{
		$data["view"] = ucfirst( $data["view"] );
		$file = self::findFile($data['folder'].DIRECTORY_SEPARATOR.$data['view'], "controller") ;
		$this->routeRequireFile($file);
	}
	/**
	 * Controller, View 파일 로드
	 * 
	 * @param string $file
	 * @throws Exception
	 */
	private function routeRequireFile($file)
	{
		try
		{
			if( is_file($file) ) require_once $file ;
			else throw new Exception(404);
		}
		catch (Exception $e) {
			//header("HTTP/1.1 404 Not Found");
			//header('Location: '.$file);
			//debug_print_backtrace();
			echo '<pre>';print_r($e);
			exit;
		}
	}
	/**
	 * 컨트롤러(controller) or 모델(model) 파일 검색
	 *
	 * @param : $c_file
	 *                 - 컨트롤러명
	 *                 - 폴더명/컨트롤러명 or 폴더명/모델명
	 * @param : $type (값 : controller, view .. )
	 * @param : $sign ( default값 : _ )
	 * @return : (string) 파일
	 *                 _controller/Admin_controller.php
	 *                 _controller/admin/Admin_controller.php
	 *
	 *                 _model/Admin_model.php
	 *                 _model/admin/Admin_model.php
	 * ---------------------------------------------
	 * 컨트롤러 호출시 서비스 인크루드함
	 *   _service/컨트롤러명_service.php
	 *
	 */
	private static function findFile($c_file, $type='', $sign='_')
	{
		if($type) $c_file .= "_".$type.".php" ;
		else $c_file .= ".php" ;
	
		$find_order = array(
				$sign. $type .'/'. $c_file,
				_APP_PATH. $sign . $type ."/". $c_file,
				_APP_PATH. $sign . $type ."/admin/". $c_file
		);
	
		foreach($find_order as $file){
			if (!is_file($file)) continue;
			return $file;
			break;
		}
	}

}