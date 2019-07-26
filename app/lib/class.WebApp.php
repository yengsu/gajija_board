<?php
require_once _APP_LIB.'system/traits/DB_Trait.php';
/*
 작성자 : 이영수
 용도 : 다목적 공용 클래스
 */
class WebApp {

	public static $provider_dir = array(
			"lib" => _APP_PATH."lib/",
			"system"=> _APP_PATH."lib/system/",
			"controller" => _APP_PATH."_controller/",
			"model" => _APP_PATH."_model/",
			"service" => _APP_PATH."_service/",
			"widget" => _APP_PATH."_widget/",
			"plugin" => ""
	) ;
	
	public function __construct($module='')
	{
	}

	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}

	public function __call($method, $arg_array)
	{
		if( is_object( self::$instance ) )
			return call_user_func_array(array(self::$instance, $method), self::refValues($arg_array));
	}

	public function __get($property) {
            if (property_exists($this, $property)) {
                return $this->$property;
            }
    }

	public function __set($property, $value) {
        if (property_exists($this, $property)) {
            return $this->$property = $value;
        }
    }
    
    /**
     * 싱글톤
     * 
     * @param <mixed> $name
     * 
     * @example singleton( 'Router:system', "", "default", true ) ;
     * 
     * @example singleton( '모듈명:provider명', 폴더명, class의 파라미터,파라미터... ) ; # app/_provider명/폴더명/모듈명_provider 파일
     * @example singleton( '모듈명:provider명', 폴더명, class의 파라미터,파라미터... ) ;  # app/_provider명/폴더명/모듈명_provider 파일
     * @example singleton( '모듈명', 폴더명, class의 파라미터,파라미터... ) ;   # app/lib/폴더명/class.모듈명파일 
     * 
     * provider 경로 참고 => self::$provider_dir
     */
    public static function &singleton( $name )
    {
    	static $instance;
    	
    	$provider = null;
    	if(preg_match("/([:])/", $name))
    	{
    		$tmp = explode(':', $name);
    		if( array_key_exists( strtolower($tmp[1]), self::$provider_dir) )
    		{
    			$name = ucfirst($tmp[0]);
    			$provider = $tmp[1]; // system, model, controller....
    		}
    	}
    	 
    	$args = func_get_args();
    	unset($args[0]) ;
    
    	
    	if( !isset( $instance ) ) $instance = array();
    	//if (!class_exists($name))//{
    	if( !isset( $instance[$name] ) || !is_object($instance[$name]) )
    	{
    		if( !empty($provider) ){ // 제공자 로드 방식
    			
    			$name = self::import( $name, $provider, $args[1] );
    			unset($args[1]) ;
    		}
    		else{
    			$name = self::import( $name, NULL, $args[1] );
    			unset($args[1]) ;
    		}
    		if(class_exists ($name)) {
    			$instance[$name] = self::create_instance($name, self::refValues($args)) ;
    		}
    	}
    	
    	return $instance[$name] ;
    }
    /**
     * 새로운 인스턴스 생성
     * 
     * @param string $class
     * @param <mixed> $params
     * @return object
     */
    public static function create_instance($class, $params) {
    	$reflection_class = new ReflectionClass($class);
    	return $reflection_class->newInstanceArgs($params);
    }
	/**
	 * 
	 * @param string $module_name
	 * @param string $provider
	 * @param string $add_folder
	 * @throws Exception
	 * @return string $module_name (모듈명)
	 */
	public static function import($module_name, $provider=NULL, $add_folder='')
	{
		$args = func_get_args();

		$module_name = ucfirst($module_name);
		
		if( !empty($provider) )
		{
			if($add_folder) $add_folder =  ( mb_substr($add_folder, -1) != "/" ) ? $add_folder.'/' : $add_folder ;
			
			if( $provider != 'system' && $provider != 'lib')
			{
				 if( !preg_match("/(".$provider.")/", $module_name) ) $module_name .= '_'.$provider ;
			}
			
			if( !empty($add_folder) )
			{
				$file = self::$provider_dir[$provider].
										$add_folder .
											$module_name.".php";
			}
			else{
				$file = self::$provider_dir[$provider].
									$module_name.".php";
			}
		}
		else{
			if($add_folder) 
				$add_folder =  ( mb_substr($add_folder, -1) != "/" ) ? _APP_PATH."lib/".$add_folder.'/' : _APP_PATH."lib/".$add_folder ;
			else 
				$add_folder = self::$provider_dir["lib"];
			
			$file = $add_folder."class.".$module_name.".php";
		}
			//if( preg_match("/^class\./i", $file) || is_file($file) ){
		//echo $file."___".$module_name."<br>" ;
		if( is_file($file) ){
			//echo $file."<br>" ;
			require_once($file);
		}
		try {
			
			if(class_exists ($module_name)) {
				return $module_name ;
			}else{
				throw new Exception($file." not found");
			}
		} catch (Exception $e) {
			if (!headers_sent()) {
				//header("location: /");exit;
			}
			//header("HTTP/1.1 404 Not Found");
			//$_SERVER["REDIRECT_STATUS"] = 404 ;
			$error_page = "module/error.php" ;
			if( is_file($error_page) ) include_once($error_page) ;
			exit;
			
		}
	}
	
	/**
	* WebApp::call()
	* 특정 모듈을 호출한다
	*
	* @param string $provide  모듈별명(도트구분)
	* @param array|mixed $param     파라미터(key값을가진 array)
	*/
	public static function call($provide,$param) {

		/* if($provide == 'applet') // 자체 내부 제공 프로그램
		else if($provide == 'plugin') // 자체 내부 플러그인
		else if($provide == 'vendor') // 외부 프로그램
		return ; */

		if(!$provide) return ;

		$module = $param['module'] ;

		if($module)
		{
			$default_folder = "module" ;
			$module = str_replace('..','', $module);
			//$module = preg_replace(array('..', '../', '//'), array('','',''), $module);

			$sep = substr($module, 0,1); // --> 모듈명이 '/'로 시작하는지
			if($sep == '/') 
				$path = $default_folder."/".$provide.$module ;
			else 
				$path = $default_folder."/".$provide.'/'.$module ;

			if (is_file($path)) {
				include_once $path;
			} else {
				echo "<div style='display:block;color:red;'>".$provide." is not exist ".$attr['widget']."</div>" ; 
				/* $parts = explode('.',$module);
				$__METHOD = array_pop($parts);
				$path = '_view/'.implode('/',$parts).'/__call.php';
				include_once $path; */
			}
		}
	}
	public static function call_class($attr)
	{
		$instance = &WebApp::singleton($attr['name'].":widget", $attr['folder'], $attr['param']);
		try
		{
			// instance 있으면
			if( $instance )
			{
				if( method_exists( $instance, $attr["action"]) ) {
					
					$reflection = new ReflectionMethod($instance,  $attr["action"]);
					if($reflection->isPublic()){ // public 메서드만
						return $reflection->invokeArgs($instance, array($attr)); // 해당메서드에 arguments까지 전달시
					}else{
						throw new Exception( get_class($instance)." is not exist ".$attr["action"]." method");
					}
					
				}else{
					throw new Exception( get_class($instance)." is not exist ".$attr["action"]." method");
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
			if( method_exists( $instance, 'index') ){
				return $instance->index($attr);
			}else{
				$ret = "<div style='display:block;color:red;'>".$provide." is not exist ".$attr['name']."</div>" ;
				return $ret ;
				//header("HTTP/1.1 404 Not Found");
				//header('Location: '.$file);
				//debug_print_backtrace();
				//header('Location: /');
				//echo '<pre>';print_r($e);
				//exit;
			}
		}
	}
	/**
	* WebApp::getConf()
	* 웹 어플리케이션 설정을 얻어옵니다.
	* 다중array의 값일경우 dot 연산자로 구분하여 가져올 수 있습니다 ex) WebApp::getConf('board.rownum');
	*
	* @param string $key
	* @return mixed
	*/
	public static function getConf($key="",$scope='merged') {

		global $_CONF;
		$_CONF['global'] = @parse_ini_file(_APP_PATH."conf/global.conf.php",true);
		$_CONF['local'] = @parse_ini_file("conf/global.conf.php",true);
		$_CONF['merged'] = array_merge($_CONF['global'],$_CONF['local']);	// local 설정이 global 설정을 덮어씀!
		//$_CONF['local'] = @parse_ini_file(_APP_LIB."_theme/".$_CONF['global']['design']['theme']."/conf/global.conf.php",true);

		if($_CONF['global']['design']['theme']=='') $_CONF['global']['design']['theme'] = 'WEB';

		if (!$key) return $_CONF[$scope];

		if(strpos($key, ".") > -1) {

			$t = explode(".", $key);

			$v = $_CONF[$scope];
			
			for($z=0,$c=count($t); $z<$c; $z++) {

				$v = $v[$t[$z]];

				if (!$v) {

					if( is_file(_APP_LIB.'conf/'.$t[$z].'.conf.php') )
						$_CONF['global'][$t[$z]] = parse_ini_file(_APP_LIB.'conf/'.$t[$z].'.conf.php',true);
					else
						unset($_CONF['global'][$t[$z]]);

					if( is_file("conf/".$t[$z].'.conf.php') )
						$_CONF['local'][$t[$z]] = @parse_ini_file("conf/".$t[$z].'.conf.php',true);
					else
						unset($_CONF['local'][$t[$z]]);

					if (!$_CONF['local'][$t[$z]]) unset($_CONF['local'][$t[$z]]);	// 비어있는 로칼설정이 글로벌 설정을 지워버리는 오류 보완

					$_CONF['merged'] = array_merge($_CONF['global'],$_CONF['local']);

					try {
						$v = $_CONF[$scope][$t[$z]];
					}catch(Exception $e){}

				}
				if (!$v) return;


			}

			return $v;
		} else {
			return $_CONF[$scope][$key];

		}
	}
	
	/**
	* getConf() 와의 차이점
	* -- 기본적으로 global.conf.php를 포함하지만
	* -- 아래함수는 찾고자 하는 정보를 추출함
	*/
	public static function getConf_real($key="",$scope="") {
		global $_CONF;

		if( !isset($_CONF) ) $_CONF = array();
		if(strpos($key, ".") > -1) 
			$t = explode(".", $key);
		else	
			$t[0]=$key;
		
		for($z=0,$c=count($t); $z < $c; $z++) {
			
				$v = $v[$t[$z]];
				
				if (!$v) {
					
					if( is_file(_APP_LIB.'conf/'.$t[$z].'.conf.php') )
						$_CONF['global'][$t[$z]] = parse_ini_file(_APP_LIB.'conf/'.$t[$z].'.conf.php',true);
					else
						unset($_CONF['global'][$t[$z]]);

					if( is_file("conf/".$t[$z].'.conf.php') )
						$_CONF['local'][$t[$z]] = @parse_ini_file("conf/".$t[$z].'.conf.php',true);
					else
						unset($_CONF['local'][$t[$z]]);

					if (!$_CONF['local'][$t[$z]]) unset($_CONF['local'][$t[$z]]);	// 비어있는 로칼설정이 글로벌 설정을 지워버리는 오류 보완

					if( !isset($_CONF['global']) ) $_CONF['global'] = array();
					if(!empty($_CONF['local'])) $_CONF['merged'] = array_merge($_CONF['global'],$_CONF['local']);
					else $_CONF['merged'] = $_CONF['global'] ;

					try {
						if($scope) $v = $_CONF[$scope][$t[$z]];
						else $v = $_CONF['local'][$t[$z]];
						
						
					}catch(Exception $e){}

				}
				if (!$v) return;


		}

		return $v;
	}

	public function layout_list(){
		$this->INI_manager = &WebApp::singleton('INI_manager');
		return @array_keys($this->INI_manager->get_ini_array('./conf/layout.conf.php'));
	}
	
	public static function showError($errno, $errstr, $errfile, $errline, $errcontext) {
		global $tpl;

		switch ($errno) {
			case E_USER_WARNING: case E_USER_NOTICE:
				$tpl->setLayout('blank');
				$tpl->define('CONTENT', Display::getTemplate('error.html'));
				$tpl->assign('TITLE',_('ERROR'));
				$tpl->assign('message', $errstr);
				$tpl->printAll();
				exit;
				//echo "<b>에라</b> $errstr $errfile 파일 $errline 번째 라인에서<br>";
				break;

				// skip other errors
		}
	}
	/**
	 * WebApp::alert()
	 * 자바스크립트 경고창을 출력한다.
	 *
	 * @param string $msg  경고창으로 출력할 메시지
	 */
	public static function alert($msg) {
		$msg = str_replace(array("\n","'"),array("\\n","\'"),$msg);
		echo '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>';
		echo "<script>alert('$msg');</script>";
		echo "</body></html>";
	}
	/**
	 * WebApp::win_alert()
	 * 자바스크립트 경고창을 출력하고 window 창(자기자신)을 닫음
	 *
	 * @param string $msg  경고창으로 출력할 메시지
	 */
	public static function win_alert($msg) {
		if( !empty($msg) )
		{
			$msg = str_replace(array("\n","'"),array("\\n","\'"),$msg);
			$alert = "alert('$msg');" ;
		}
		echo '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>';
		echo "<script>". $alert ."window.close();</script>";
		echo "</body></html>";
		exit;
	}
	/**
	 * WebApp::moveBack()
	 * 히스토리 바로 이전으로 이동한다
	 *
	 * @param string $msg  경고창으로 출력할 메시지
	 */
	public static function moveBack($msg="") {
		if ($msg) WebApp::alert($msg);
		echo "<script>history.back();</script>";
		exit;
	}
	/**
	 * WebApp::redirect()
	 * 해당 페이지로 이동한다
	 *
	 * @param string $url  이동할 페이지
	 * @param string $msg  경고창으로 출력할 메시지
	 */
	public static function redirect($url,$msg="") {
		//if ($msg) WebApp::alert($msg);
		$output = '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>';
		$output .= "<script>";
		if($msg) $output .= "alert('$msg');";
		$output .= "document.location.replace('$url');</script>";
		$output .= "</body></html>";
		echo $output;
		exit;
		/* if (headers_sent()) {
			$output = '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>';
			$output .= "<script>";
			if($msg) $output .= "alert('$msg');";
			$output .= "document.location.replace('$url');</script>";
			$output .= "</body></html>";
			echo $output;
			exit;
		} else {
			$redirect_to = "Location:" . $url;
			exit(header($redirect_to));
		} */
	}
	/**
	 * 모바일이면 true 리턴
	 * @return number|boolean
	 */
	public static function mobileCheck()
	{
	    //Check Mobile
	    $mAgent = array("Mobile", "iPhone","iPad","Android","Blackberry",
	    		"Opera Mini", "Windows ce", "Nokia", "sony" );//"Chrome", 

	    $chkMobile = false;
	    for($i=0, $l=sizeof($mAgent); $i<$l; $i++){
	        if(strpos( $_SERVER['HTTP_USER_AGENT'], $mAgent[$i] )){
	            return true;
	            break;
	        }
	    }
	    return false ;
	    
	}
	public static function refValues($arr)
	{
        if (strnatcmp(phpversion(),'5.3') >= 0) // PHP 5.3+이상에서 호환
        {
            $refs = array();
            foreach( $arr as $key => $value)
                $refs[] = &$arr[$key];
                //$refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }
}