<?
//require 'Curl/vendor/autoload.php';
use Gajija\service\CommNest_service;
use Gajija\controller\_traits\AdmController_comm;
//use Curl\Curl;
class XMLHttpRequest{
	/**
	 *	String version of data returned from server process.
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-responsetext
	 *	@access public
	 *	@var string
	 *	@name $responseText
	 */
	var $responseText;
	/**
	 *	DOM-compatible document object of data returned from server process.
	 *	which can be examined and parsed using W3C DOM node tree methods and properties
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-responsexml
	 *	@access public
	 *	@var object
	 *	@name $responseXML
	 */
	var $responseXML;
	/**
	 *	The http status code returned by server as a number (e.g. 404 for "Not Found" or 200 for "OK").
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-status
	 *	@access public
	 *	@var number
	 *	@name $status
	 */
	var $status;
	/**
	 *	The http status code returned by server as a string (e.g. "Not Found" or "OK")
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-statustext
	 *	@access public
	 *	@var string
	 *	@name $statusText
	 */
	var $statusText;
	/**
	 *	The state of the object
	 *	0 = uninitialized
	 *	1 = loading
	 *	2 = loaded
	 *	3 = interactive
	 *	4 = complete
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-readystate
	 *	@access public
	 *	@var number
	 *	@name $readyState
	 */
	var $readyState;
	/**
	 *	The error string
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#notcovered
	 *	@access public
	 *	@var string
	 *	@name $error
	 */
	var $error;
	/**
	 *	An event handler for an event that fires at every state change
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-onreadystatechange
	 *	@access public
	 *	@name $onreadystatechange
	 */
	var $onreadystatechange;
	/**
	 *	An event handler for an event that fires at finished requisition
	 *	@link  http://www.w3.org/TR/XMLHttpRequest/#notcovered
	 *	@access public
	 *	@name $onload
	 */
	var $onload;
	/**
	 *	An event handler for an event that fires at errors
	 *	@link  http://www.w3.org/TR/XMLHttpRequest/#notcovered
	 *	@access public
	 *	@name $onerror
	 */
	var $onerror; // http://www.w3.org/TR/XMLHttpRequest/#notcovered
	/**
	 *	cURL handle
	 *	@access private
	 *	@name $curl
	 */
	var $curl;
	/**
	 *	responseHeaders process
	 *	@access private
	 *	@name $responseHeaders
	 */
	var $responseHeaders;
	/**
	 *	cURL headers
	 *	@access private
	 *	@name $headers
	 */
	var $headers=array("Connection: Keep-Alive","Keep-Alive: 300");
	/**
	 *	Curl info
	 *	@access public
	 *	@name $curl_version
	 *	@var Array
	 */
	var $curl_version;
	/**
	 *	TRUE to follow any "Location: " header that the server sends as part of the HTTP header.
	 *	@access public
	 *	@name $followLocation
	 *	@var Bolean
	 */
	var $followLocation;
	
	/**
	 *	Class constructor (compatibility with PHP 4).
	 */
	//function XMLHttpRequest(){
	public function __construct()
	{
		$this->open="function open() { [native code] }";
		$this->setRequestHeader="function setRequestHeader() { [native code] }";
		$this->getAllResponseHeaders="function getAllResponseHeaders() { [native code] }";
		$this->getResponseHeader="function getResponseHeader() { [native code] }";
		$this->send="function send() { [native code] }";
		$this->readyState = 0;
		$this->curl = curl_init();
		$this->curl_version = curl_version();
		$this->followLocation=false;
		curl_setopt($this->curl, CURLOPT_HEADER, true);
		if(isset($_SERVER['HTTP_USER_AGENT'])){
			curl_setopt($this->curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		}else{
			curl_setopt($this->curl, CURLOPT_USERAGENT, "XMLHttpRequest/0.2");
		}
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 1000);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 300);
	}
	/**
	 *	@access private
	 */
	function __toString(){
		return "[object XMLHttpRequest]";
	}
	/**
	 *	Specifies the method, URL, and other optional attributes of a request.
	 *	@access public
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-open
	 *	@param String $method HTTP Methods defined in section 5.1.1 of RFC 2616 http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
	 *	@param String $url Specifies either the absolute or a relative URL of the data on the Web service.
	 *	@param Bolean $async FakeSauro Erectus.
	 *	@param String $user specifies the name of the user for HTTP authentication.
	 *	@param String $password specifies the password of the user for HTTP authentication.
	 *	@return void
	 */
	function open($method, $url, $async=true, $user="", $password=""){
		$this->readyState = 1;
		if(!empty($method) && !empty($url)){
			$method=strtoupper(trim($method));
			/*
			 if(!ereg("^(GET|POST|HEAD|PUT|DELETE|OPTIONS)$",$method)){
			 throw new Exception("Unknown HTTP request method [$method]");
			 }
			 */
			if(isset($_SERVER['HTTP_REFERER']) && empty($this->url) ){
				curl_setopt($this->curl,  CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
			}elseif(isset($this->url)){
				curl_setopt($this->curl,  CURLOPT_REFERER, $this->url);
			}
			$this->url = $url;
			curl_setopt($this->curl, CURLOPT_URL, $this->url);
			if($method=="POST"){
				curl_setopt($this->curl, CURLOPT_POST, 1);
			}elseif($method=="GET"){
				curl_setopt($this->curl, CURLOPT_POST, 0);
			}else{
				curl_setopt($this->curl, CURLOPT_POST, 0);
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
			}
		}
		if(preg_match("/^(https)/",$url)){
			curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER,false);
		}
		if(!empty($user) && !empty($password)){
			curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($this->curl,CURLOPT_USERPWD,$user.":". $password);
		}
	}
	/**
	 *	Assigns a label/value pair to the header to be sent with a request.
	 *	@access public
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-setrequestheader
	 *	@param String $label Specifies the header label.
	 *	@param String $value Specifies the header value.
	 *	@return void
	 */
	function setRequestHeader($label, $value){
		$this->headers[] = "$label: $value";
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
	}
	/**
	 *	Returns complete set of headers (labels and values) as a string.
	 *	@access public
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-getallresponseheaders
	 *	@return string Complete set of headers (labels and values) as a string
	 */
	function getAllResponseHeaders(){
		return $this->responseHeaders;
	}
	/**
	 *	Returns the value of the specified http header.
	 *	@access public
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-getresponseheader.
	 *	@param String $label
	 *	@return String|null The string value of a single header label.
	 */
	function getResponseHeader($label){
		$value=array();
		preg_match_all('/(?s)'.$label.': (.*?)\s\n/i', $this->responseHeaders , $value);
		if(count($value ) > 0){
			return implode(', ' , $value[1]);
		}
		return null;
	}
	function getResponseHeader2($label){
		$value=array();
		preg_match('/(?s)'.$label.': (.*?)\s\n/i', $this->responseHeaders , $value);
		if(count($value ) > 0){
			return $value[1];
		}
		return null;
	}
	/**
	 *	Transmits the request, optionally with postable string or DOM object data.
	 *	@access public
	 *	@link http://www.w3.org/TR/XMLHttpRequest/#dfn-getresponseheader
	 *	@param String $data
	 *	@return void
	 */
	function send($data=null){
		$sT=array();
		if(isset($this->onreadystatechange))eval($this->onreadystatechange);
		if($data){
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		}
		$this->response= curl_exec($this->curl);
		$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		$this->responseHeaders  = substr($this->response, 0, $header_size - 4);
		if($this->followLocation){
			$location=array();
			while(preg_match('/Location:(.*?)\n/', $this->responseHeaders, $location)){
				curl_setopt($this->curl,  CURLOPT_REFERER, $this->url);
				$url = @parse_url(trim(array_pop($location)));
				if (!$url){
					break;
				}
				$last_url = parse_url(curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL));
				if (!isset($url['scheme']))$url['scheme'] = $last_url['scheme'];
				if (!isset($url['host']))$url['host'] = $last_url['host'];
				if (!isset($url['path']))$url['path'] = $last_url['path'];
				$this->url = $url['scheme'] . '://' . $url['host'] . $url['path'] . (isset($url['query'])?'?'.$url['query']:'');
				curl_setopt($this->curl, CURLOPT_POST, 0);
				//curl_setopt($this->curl, CURLOPT_POSTFIELDS,0);
				curl_setopt($this->curl, CURLOPT_URL, $this->url);
				$this->response= curl_exec($this->curl);
				$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
				$this->responseHeaders  = substr($this->response, 0, $header_size - 4);
			}
		}
		$this->error = curl_error($this->curl);
		if ($this->error) {
			if(isset($this->onerror))eval($this->onerror);
		}
		$this->readyState = 2;
		if(isset($this->onreadystatechange))eval($this->onreadystatechange);
		$this->responseText = substr($this->response, $header_size);
		preg_match_all('/^HTTP\/\d\.\d\s+(\d{3}) (.*)\s\n/i', $this->responseHeaders , $sT);

		if(count($sT ) > 2){
			$this->responseHeaders = preg_replace ($sT[0], "", $this->responseHeaders);
			$this->status = $sT[1];
			$this->statusText = $sT[2];
		}
		if(version_compare(PHP_VERSION , "5", ">=")){
			if (preg_match('/(application|text)\/[\w+\+]?xml/i', $this->getResponseHeader("Content-Type"))){
				libxml_use_internal_errors(true);
				$this->responseXML = new DOMDocument();
				$this->responseXML->loadXML($this->responseText);
				$errors = libxml_get_errors();
				if (!empty($errors)){
					$this->responseXML=null;
					$error=$errors[0];
					$this->error= trim($error->message) ." in $this->url on line $error->line column: $error->column ";
					if(isset($this->onerror))eval($this->onerror);
				}
				libxml_clear_errors();
			}
		}
		$this->readyState = 3;
		if(isset($this->onreadystatechange))eval($this->onreadystatechange);
		$this->headers=Array();
		$this->readyState = 4;
		if(isset($this->onreadystatechange))eval($this->onreadystatechange);
		if(isset($this->onload))eval($this->onload);
	}
	/**
	 *	Closes a cURL session and frees all resources.
	 *	@name close
	 *	@access public
	 *	@return void
	 */
	function close(){
		curl_close($this->curl);
	}
}
class Shopinside_controller extends CommNest_service
{
	use AdmController_comm ;
	
	/**
	 * Curl object
	 * @var object
	 */
	public $Curl ;
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
	/**
	 * @desc Google-CAPTCHA API 요청 주소
	 * @var string
	 */
	protected static $API_HOST_CATEGORYS= "https://openapi.naver.com/v1/datalab/shopping/categories";
	protected static $API_HOST_gender = "https://openapi.naver.com/v1/datalab/shopping/category/gender";
	protected static $API_HOST_keywords= "https://openapi.naver.com/v1/datalab/shopping/category/keywords";
	
	public static $apiKey = array(
			"g.com" => array(
					'site_key' => "DAiZtflMANcu8vyXs0cN",
					'secret_key' => "8y4EjSadGt"
			),
			"www.gajija.kr" => array(
					'site_key' => "DAiZtflMANcu8vyXs0cN",
					'secret_key' => "8y4EjSadGt"
			)
	);
	private function Curl_start()
	{
	    //curl 시작
	    if( ! is_object($this->Curl) ) $this->Curl = curl_init();
	}
	private function Curl_stop()
	{
	    if(is_object($this->Curl)) curl_close( $this->Curl );
	}
	/**
	 * @desc KAKAO 서버에 요청
	 *
	 * @param string $Api_path 요청 api 주소
	 * @param array $params ["post"=>array | queryString, "header"=>array(??,??)]
	 * @param string $http_method ( GET, POST )
	 * @return array ["info"=>??, "response" => ??]
	 */
	protected function _Request($Api_path, $params = null, $http_method = "GET")
	{
		//###############################
		$Opts = array(
				CURLOPT_URL => (string)$Api_path,
				//CURLOPT_ENCODING => "UTF-8",
		        CURLOPT_ENCODING => '',//gzip, deflate',
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false
		      ,CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
				//CURLOPT_POST=> true
				//CURLOPT_TIMEOUT => 30
		);
		// header 선언
		if( !empty($params['header']))
		{
			$Opts[CURLOPT_HTTPHEADER] = (array)$params['header'] ;
		}
		
		if($http_method == "POST") $Opts[CURLOPT_POST] = true ;
		
		if( !empty($params['post'])) {
			if( ! isset($Opts[CURLOPT_POST]) ) $Opts[CURLOPT_POST] = true ;
			$Opts[CURLOPT_POSTFIELDS] = (string)$params['post'] ;
		}
		
		if( !empty($params['cookie'])) {
		  $Opts[CURLOPT_COOKIEJAR] = (string)$params['cookie'] ;
		  $Opts[CURLOPT_COOKIEFILE] = (string)$params['cookie'] ;
		}
		
		/* echo "-----------------------------------------------<br>";
		 echo $Api_path ."<br>";
		 echo "-----------------------------------------------<br>";
		 echo '■파라미터■ : <pre>';print_r($params);
		 echo '■Session■ : <pre>';print_r($_SESSION);
		 //---------------------------- */
		//echo '<pre>';print_r($this->Curl);
		
		//$ch = curl_init();
		curl_setopt_array( $this->Curl, $Opts ) ;
		
		$response = curl_exec( $this->Curl );
		$results = json_decode($response, true);
		$info = curl_getinfo($this->Curl);//curl_getinfo($ch, CURLINFO_HTTP_CODE );
		
		curl_reset($this->Curl);
		//echo '■결과■ : <pre>';print_r($response);

		//curl_close( $this->Curl );
		//----------------------------
		return array(
				"info" => $info, // 최종 반환된 모든 전송정보 (배열)
				"response" => $results // 응답정보
		) ;
	}
	
	/**
	 * 쇼핑인사이트 분야별 트렌드 조회
	 * 
	 * @uses https://openapi.naver.com/v1/datalab/shopping/categories
	 */
	public function categories()
	{
		$HEADER = array(
				"X-Naver-Client-Id: ".self::$apiKey[HOST]['site_key'],
				"X-Naver-Client-Secret: ". self::$apiKey[HOST]['secret_key'],
				"Content-Type: application/json"
		);
		
		$post = array(
			'startDate' => "2018-12-21",
				'endDate' => "2019-01-21",
				'timeUnit' => "date",
				'category' => array(
						/* array(
							'name' => "패션의류",
							'param' => array("50000000")
						),
						array(
							'name' => "여성의류",
							'param' => array("50000167")
						), */
						array(
								'name' => "니트/스웨터",
								'param' => array("50000805")
						)
				)
				//,'device' => "pc",
				//'ages' => array("20", "30"),
				//'gender' => "f"
		);
		$post = json_encode($post) ;
		
		$result = $this->_Request(
				static::$API_HOST_CATEGORYS,
				array(
						"header" => $HEADER,
						"post" => (string)$post//"{\"startDate\":\"2017-08-01\",\"endDate\":\"2017-09-30\",\"timeUnit\":\"month\",\"category\":[{\"name\":\"패션의류\",\"param\":[\"50000000\"]},{\"name\":\"화장품/미용\",\"param\":[\"50000002\"]}],\"device\":\"pc\",\"ages\":[\"20\",\"30\"],\"gender\":\"f\"}"
				),
				"POST"
				) ;
		echo '<pre>';print_r($result);exit;
	}
	/**
	 * 쇼핑인사이트 분야별 트렌드 조회
	 *
	 * @uses https://openapi.naver.com/v1/datalab/shopping/category/gender
	 */
	public function gender()
	{
		$HEADER = array(
				"X-Naver-Client-Id: ".self::$apiKey[HOST]['site_key'],
				"X-Naver-Client-Secret: ". self::$apiKey[HOST]['secret_key'],
				"Content-Type: application/json"
		);
		
		$post = array(
				'startDate' => "2018-12-21",
				'endDate' => "2019-01-21",
				'timeUnit' => "month",
				'category' => "50000805"
				//,'device' => "pc",
				//'ages' => array("20", "30"),
				//,'gender' => "m"
		);
		$post = json_encode($post) ;
		
		$result = $this->_Request(
				static::$API_HOST_gender,
				array(
						"header" => $HEADER,
						"post" => (string)$post//"{\"startDate\":\"2017-08-01\",\"endDate\":\"2017-09-30\",\"timeUnit\":\"month\",\"category\":[{\"name\":\"패션의류\",\"param\":[\"50000000\"]},{\"name\":\"화장품/미용\",\"param\":[\"50000002\"]}],\"device\":\"pc\",\"ages\":[\"20\",\"30\"],\"gender\":\"f\"}"
				),
				"POST"
				) ;
		echo '<pre>';print_r($result);exit;
	}
	/**
	 * 쇼핑인사이트 분야별 트렌드 조회
	 *
	 * @uses https://openapi.naver.com/v1/datalab/shopping/category/keywords
	 */
	public function keywords()
	{
		$HEADER = array(
				"X-Naver-Client-Id: ".self::$apiKey[HOST]['site_key'],
				"X-Naver-Client-Secret: ". self::$apiKey[HOST]['secret_key'],
				"Content-Type: application/json"
		);
		
		$post = array(
				'startDate' => "2018-12-21",
				'endDate' => "2019-01-21",
				'timeUnit' => "month",
				'category' => "50000805",
				"keyword" => array(array("name"=>"니트/스웨터", "param" => array("여성니트티")))
				//,'device' => "pc",
				//'ages' => array("20", "30"),
				//,'gender' => "m"
		);
		$post = json_encode($post) ;
		echo $post;
		$result = $this->_Request(
				static::$API_HOST_keywords,
				array(
						"header" => $HEADER,
						"post" => (string)$post//"{\"startDate\":\"2017-08-01\",\"endDate\":\"2017-09-30\",\"timeUnit\":\"month\",\"category\":[{\"name\":\"패션의류\",\"param\":[\"50000000\"]},{\"name\":\"화장품/미용\",\"param\":[\"50000002\"]}],\"device\":\"pc\",\"ages\":[\"20\",\"30\"],\"gender\":\"f\"}"
				),
				"POST"
				) ;
		echo '<pre>';print_r($result);exit;
	}
	
	/**
	 * 쇼핑 검색정보
	 * 
	 * @tutorial https://developers.naver.com/docs/search/shopping/
	 * @return array
	 * @example 
	 * [response] => Array
		(
				[lastBuildDate] => Tue, 22 Jan 2019 18:45:30 +0900
				[total] => 2974237
				[start] => 1
				[display] => 10
				[items] => Array
				(
						[0] => Array
						(
								[title] => 스카시 보들보들 라운드니트
								[link] => http://search.shopping.naver.com/gate.nhn?id=16634532014
								[image] => https://shopping-phinf.pstatic.net/main_1663453/16634532014.20181216154854.jpg
								[lprice] => 15940
								[hprice] => 17600
								[mallName] => 네이버
								[productId] => 16634532014
								[productType] => 1
								)
						
						[1] => Array
						(
								[title] => 보들보들 루즈핏 라운드넥 니트
								[link] => http://search.shopping.naver.com/gate.nhn?id=16745580288
								[image] => https://shopping-phinf.pstatic.net/main_1674558/16745580288.20181224164602.jpg
								[lprice] => 13000
								[hprice] => 19800
								[mallName] => 네이버
								[productId] => 16745580288
								[productType] => 1
								)
						........
	 */
	public function getShopKeyword()
	{
	    $this->Curl_start();
	    $result = $this->shop_keyword( array(
	    		'query'=>'니트/스웨터',	
	    		'display' => 1
	    ));
		$this->Curl_stop();
		echo 'curl 출력 : <pre>';print_r($result);
		if(empty($result['total'])){
			$result = $this->shop_keyword_getUrl( '니트/스웨터' );
		}
		echo 'url Parsing : <pre>';print_r($result);exit;
	}
	/**
	 * 카테고리
	 */
	public function Req_getCate()
	{
		/* if(REQUEST_WITH != 'AJAX') {
			header('Location:/') ;	exit;
		} */
		
		$this->Curl_start();
		$result = $this->cate( (int)$_POST['cid'] );
		$this->Curl_stop();
		
		$this->WebAppService->assign($result) ;
		//echo '<pre>';print_r($result);exit;
	}
	/**
	 * 쇼핑분야 카테고리 가져오기
	 *  
	 * @param integer $cid 쇼핑카테고리 아이디
	 * @return array
	 * @example return Array
								(
								    [0] => Array
								        (
								            [cid] => 50000000
								            [pid] => 0
								            [name] => 패션의류
								            [parentPath] => 
								            [level] => 1
								            [expsOrder] => 1
								            [parents] => Array
								                (
								                    [0] => Array
								                        (
								                            [cid] => 0
								                            [name] => 전체
								                        )
								
								                )
								
								            [childList] => Array
								                (
								                )
								
								            [leaf] => 
								            [fullPath] => 패션의류
								            [deleted] => 
								            [svcUse] => 1
								            [sblogUse] => 1
								        )
								        ......
	 */
	private function cate( int $cid = 0 )
	{
		$HEADER = array(
				//"content-type: application/x-www-form-urlencoded; charset=UTF-8",
				//"origin: https://datalab.naver.com",
				"referer: https://datalab.naver.com/shoppingInsight/sCategory.naver",
				"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
				"x-requested-with: XMLHttpRequest"
		);
		
		$result = $this->_Request(
				'https://datalab.naver.com/shoppingInsight/getCategory.naver?cid='.(int)$cid,
				array(
						"header" => $HEADER
						//,"post" => $post
				)
				//,"POST"
				) ;
		
		return $result['response']['childList'] ;
	}
	/**
	 * 인기검색어 순위 (1~100) 
	 * 
	 * @example
	 * 		[response] => Array
		        (
		            [message] => 
		            [statusCode] => 200
		            [returnCode] => 0
		            [date] => 
		            [datetime] => 
		            [range] => 2018.12.22. ~ 2019.01.22.
		            [ranks] => Array
		                (
		                    [0] => Array
		                        (
		                            [rank] => 1
		                            [keyword] => 데일리니트팬츠투피스
		                            [linkId] => 데일리니트팬츠투피스
		                        )
		
		                    [1] => Array
		                        (
		                            [rank] => 2
		                            [keyword] => 니트
		                            [linkId] => 니트
		                        )
		                        ........

	 */
	private function keyword_rank($post_data)
	{
		/* $post[] = "cid=50000805";
		$post[] = "timeUnit=date";
		$post[] = "startDate=".urlencode("2018-12-22");
		$post[] = "endDate=".urlencode("2019-01-22");
		$post[] = "page=1";
		$post[] = "count=100";
		$post = implode('&', $post); */
		foreach($post_data as $k => $v){
			$post[] = $k.'='.urlencode($v) ;
		}
		$post = implode('&', $post);
		
		$HEADER = array(
				//"content-type: application/x-www-form-urlencoded; charset=UTF-8",
				"origin: https://datalab.naver.com",
				"referer: https://datalab.naver.com/shoppingInsight/getCategoryKeywordRank.naver",
				"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
				"x-requested-with: XMLHttpRequest"
		);
		
		$result = $this->_Request(
				'https://datalab.naver.com/shoppingInsight/getCategoryKeywordRank.naver',
				array(
						"header" => $HEADER,
						"post" => $post
				)
				//,"POST"
				) ;
		//echo '<pre>';print_r($result);exit;
		return $result['response']['ranks'] ;
	}
	public function tt()
	{
	    $ch_1 = curl_init('http://jtbc.joins.com/');
	    $ch_2 = curl_init('https://www.phpschool.com');
	    curl_setopt($ch_1, CURLOPT_HEADER, false);
	    curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch_1, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch_1, CURLOPT_ENCODING, 'UTF-8');
	    //curl_setopt($ch_1, CURLOPT_SSLVERSION, 1);
	    
	    curl_setopt($ch_2, CURLOPT_HEADER, false);
	    curl_setopt($ch_2, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch_2, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch_2, CURLOPT_ENCODING, 'UTF-8');
	    
	    // build the multi-curl handle, adding both $ch
	    $mh = curl_multi_init();
	    curl_multi_add_handle($mh, $ch_1);
	    curl_multi_add_handle($mh, $ch_2);
	    
	    // execute all queries simultaneously, and continue when all are complete
	    $running = null;
	    do {
	        curl_multi_exec($mh, $running);
	        $info = curl_multi_info_read($mh);
	        if (false !== $info) {
	            echo 'info : <pre>';print_r($info);
	        }
	    } while ($running);
	    
	    //close the handles
	    curl_multi_remove_handle($mh, $ch_1);
	    curl_multi_remove_handle($mh, $ch_2);
	    curl_multi_close($mh);
	    
	    // all of our requests are done, we can now access the results
	    $response_1 = curl_multi_getcontent($ch_1);
	    $response_2 = curl_multi_getcontent($ch_2);
	    echo '<pre>';print_r($response_1);
	    echo '<pre>';print_r($response_2);
	    //echo "$response_1 $response_2"; // output results
	}
	/**
	 * @deprecated
	 */
	public function multi()
	{
	        $Request_url = array(
	            /*디바이스(pc,mobile)*/'device' => 'https://datalab.naver.com/shoppingInsight/getKeywordDeviceRate.naver',
	            /*성별*/'gender' => 'https://datalab.naver.com/shoppingInsight/getKeywordGenderRate.naver',
	            /*나이별*/'age' => 'https://datalab.naver.com/shoppingInsight/getKeywordAgeRate.naver'
	        );
	        
	        
	        //curl_setopt ($ch, CURLOPT_SSLVERSION,1);
	        //curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	        $CHS = array();
	        
	        foreach($Request_url as $kind => $URL)
	        {
	             $post = array();
	             $post[] = "cid=50000805";
	             $post[] = "timeUnit=date";
	             $post[] = "startDate=".urlencode("2018-12-22");
	             $post[] = "endDate=".urlencode("2019-01-22");
	             $post[] = "keyword=".urlencode('데일리니트팬츠투피스');
	             $post = implode('&', $post);
	             
	            
	            $HEADER = array(
	                //"content-type: application/x-www-form-urlencoded; charset=UTF-8",
	                "origin: https://datalab.naver.com",
	                "referer: https://datalab.naver.com/shoppingInsight/sKeyword.naver",
	                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
	                "x-requested-with: XMLHttpRequest"
	            );
	            
	            $Opts = array(
	                CURLOPT_URL => $URL,
	                //CURLOPT_ENCODING => "UTF-8",
	                CURLOPT_ENCODING => '',//gzip, deflate',
	                CURLOPT_HEADER => false,
	                CURLOPT_RETURNTRANSFER => true,
	                CURLOPT_SSL_VERIFYPEER => false,
	                CURLOPT_POST => 1,
	                CURLOPT_POSTFIELDS => $post,
	                CURLOPT_TIMEOUT => 5,
	                CURLOPT_HTTPHEADER => (array)$HEADER
	                //,CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
	                //CURLOPT_POST=> true
	                //CURLOPT_TIMEOUT => 30
	            );
	            $CHS[$kind] = curl_init();
	            
	            curl_setopt($CHS[$kind], CURLOPT_URL, $URL);
	            curl_setopt($CHS[$kind], CURLOPT_ENCODING, '');
	            curl_setopt($CHS[$kind], CURLOPT_HEADER, false);
	            curl_setopt($CHS[$kind], CURLOPT_RETURNTRANSFER, true);
	            curl_setopt($CHS[$kind], CURLOPT_SSL_VERIFYPEER, false);
	            curl_setopt($CHS[$kind], CURLOPT_POST, 1);
	            curl_setopt($CHS[$kind], CURLOPT_POSTFIELDS, $post);
	            curl_setopt($CHS[$kind], CURLOPT_TIMEOUT, 30);
	            curl_setopt($CHS[$kind], CURLOPT_HTTPHEADER, $HEADER);
	            //foreach($Opts as $k => $v) curl_setopt($CHS[$kind], $k, $v);
	            //curl_setopt_array($CHS[$kind], $Opts);
	        }
	        
	        $mh = curl_multi_init();
	        
	        foreach($CHS as $k => $ch) {
	            curl_multi_add_handle($mh, $CHS[$k]);
	        }
	        
	        $active = null;
	        do {
	            $mrc = curl_multi_exec($mh, $active);
	            /* $info = curl_multi_info_read($mh);
	            if (false !== $info) {
	                echo 'info : <pre>';print_r($info);
	            } */
	        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
	        
	        while ($active && $mrc == CURLM_OK) {
	            if (curl_multi_select($mh) != -1) {
	                do {
	                    $mrc = curl_multi_exec($mh, $active);
	                    $info = curl_multi_info_read($mh);
	                    if (false !== $info) {
	                        echo 'info : <pre>';print_r($info);
	                    }
	                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
	            }
	        }
	        
	        $result = array();
	        foreach($CHS as $k => $ch) {
	            $result[$k] = curl_multi_getcontent($CHS[$k]);
	            curl_close($ch);
	            
	            curl_multi_remove_handle($mh, $CHS[$k]);
	        }
	        curl_multi_close($mh);
	        
	        //echo '<pre>';print_r($CHS);
	        echo '<pre>';print_r($result);
	        
	}
	private function keyword_datas($post_data)
	{
	    //$this->Curl_start();
	    
	    //로그인후 토큰정보 반환
	    $manageLogin_result = $this->manageSearchAdLogin();
	    //echo '<pre>';print_r($manageLogin_result);exit;
		$datas_keyword = $this->keyword_rank($post_data);
		
		if(empty($datas_keyword)) return false ;
		//echo '<pre>';print_r($datas_keyword);exit;
		//for($i=0,$len=count($keywords); $i<$len; $i++)
		$datas = $data = array();
		foreach($datas_keyword as &$data_keyword)
		{
			$Request_url = array(
					/*디바이스(pc,mobile)*/'device' => 'https://datalab.naver.com/shoppingInsight/getKeywordDeviceRate.naver',
					/*성별*/'gender' => 'https://datalab.naver.com/shoppingInsight/getKeywordGenderRate.naver',
					/*나이별*/'age' => 'https://datalab.naver.com/shoppingInsight/getKeywordAgeRate.naver'
			);
			
			foreach($Request_url as $kind => $URL)
			{
				$post = "cid=". urlencode($post_data['cid']) ;
				$post .= "&timeUnit=". urlencode($post_data['timeUnit']) ;
				$post .= "&startDate=". urlencode($post_data['startDate']) ;
				$post .= "&endDate=". urlencode($post_data['endDate']) ;
				$post .= "&age=". urlencode($post_data['age']) ;
				$post .= "&gender=". urlencode($post_data['gender']) ;
				$post .= "&device=". urlencode($post_data['device']) ;
				$post .= "&keyword=". urlencode($data_keyword['keyword']) ;
				//echo $post."<br>" ;
				/* $post[] = "cid=50000805";
				$post[] = "timeUnit=date";
				$post[] = "startDate=".urlencode("2018-12-22");
				$post[] = "endDate=".urlencode("2019-01-22"); */
				//$post[] = "age=";
				//$post[] = "gender=";
				//$post[] = "device=";
				/* $post[] = "keyword=".urlencode($data_keyword['keyword']);
				$post = implode('&', $post); */

				$HEADER = array(
						//"content-type: application/x-www-form-urlencoded; charset=UTF-8",
						"origin: https://datalab.naver.com",
						"referer: https://datalab.naver.com/shoppingInsight/sKeyword.naver",
						"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
						"x-requested-with: XMLHttpRequest"
				);
				
				$response = $this->_Request(
						$URL,
						array(
								"header" => $HEADER,
								"post" => $post
						)
						//,"POST"
						) ;
				
				// 소숫점 2자리까지 표시(반올림 처리)
				if(!empty($response))
				{
					foreach($response['response']['result'][0]['data'] as &$res)
					{
						if( is_float($res['ratio']) ) $res['ratio'] = round($res['ratio'], 2) ;
						$data[$kind][$res['code']] = $res ;
					}
				}
				
				//$data[$kind] = $response['response']['result'][0]['data'];
				//$data[$kind] = Func::array_group_by($data[$kind], 'code');
				unset($post, $response, $res);
				//sleep(0.2);
				//echo $kind.'<pre>';print_r($response['response']['result'][0]['data']);
			}
			//echo '<pre>';print_r($data);exit;
			//$datas[] = $data;
			//-------------------------------
			$manageRes = $this->manageSearchAd($data_keyword['keyword'], $manageLogin_result);
			// 못가져오면 재시도
			if(empty($manageRes)) {
				//echo $data_keyword['keyword']."<br>" ;
				$f = 0.1 ;
				for($i=0;$i<30;$i++){
					$manageRes = $this->manageSearchAd($data_keyword['keyword'], $manageLogin_result);
					if(!empty($manageRes)) break ;
					sleep( $f + floatval("0.".$i) ) ;
					//echo ($f + floatval("0.".$i))."-";
				}
				//echo '<br>' ;
			}
			
			/* [relKeyword] => 니트
			[monthlyPcQcCnt] => 15700            // 월간 검색수(PC)
			[monthlyMobileQcCnt] => 95800       // 월간 검색수(모바일)
			[monthlyAvePcClkCnt] => 49            // 월평균 클릭수(PC)
			[monthlyAveMobileClkCnt] => 632     // 월평균 클릭수(모바일)
			[monthlyAvePcCtr] => 0.33              // 월평균 클릭율(PC)
			[monthlyAveMobileCtr] => 0.7          // 월평균 클릭율(모바일)
			[plAvgDepth] => 15                        // 월평균 노출광고수
			[compIdx] => 높음 */                     // 월간검색수(PC)
			//-------------------------------
			//echo '<pre>';print_r($manageRes);
			
			
			$result = $this->shop_keyword( array(
					'query'=>$data_keyword['keyword'],
					'display' => 1
			));
			/*
			 * api 인증 실패한 경우
			 * Array
					(
					    [errorMessage] => Not Exist Client ID : Authentication failed. (인증에 실패했습니다.)
					    [errorCode] => 024
					)
			 */
			if(empty($result) || empty($result['total'])){
				$result = $this->shop_keyword_getUrl($data_keyword['keyword']) ;
			}
			//echo '<pre>';print_r($result);
			$monthlyPcQcCnt = str_replace('< ', '', $manageRes['monthlyPcQcCnt']);
			$monthlyMobileQcCnt = str_replace('< ', '', $manageRes['monthlyMobileQcCnt']);
			//비율(쇼핑수 / 월간검색수)
			$manageRes['searchRate'] = round( (int)$result['total'] / ( (int)$monthlyPcQcCnt + (int)$monthlyMobileQcCnt), 2 ) ;
			
			$data['manageAd'] = $manageRes ;
			array_push($datas, array(
					'rank' => $data_keyword['rank'],
					'keyword'=> $data_keyword['keyword'],
					'keyword_count' => $result['total'],
					'datas' => $data
			) );
			unset($data);
		}
		
		// curl 종료
		//$this->Curl_stop();
		
		//echo '<pre>';print_r($datas);exit;
		return $datas;
		
	}
	/**
	 * 로그인
	 * 
	 * @return Array
                    (
                        [token] => eyJ0eXAiOiJKV1QiLCJhbGci..........
                        [refreshToken] => vt-Gci5pgqzAd9M8_...........
                    )
	 */
	private function manageSearchAdLogin()
	{
	    $post = '{"loginId":"yengsuad","loginPwd":"yengsu1458"}';
	    
	    $HEADER = array(
	        "content-type: application/json",
	        //"accept: application/json, text/plain, */*",
	        "origin: https://searchad.naver.com",
	        "referer: https://searchad.naver.com",
	        "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
	    );
	    // 로그인
	    $result = $this->_Request(
	        'https://searchad.naver.com/auth/login',
	        array(
	            "header" => $HEADER,
	            "post" => $post,
	            "cookie" => "./cookie.txt"
	        )
	        ) ;
	    return $result['response'] ;
	}
	/**
	 * [로그인 후] 데이터 가져오기
	 * 
	 *  @param string $keyword 검색어
	 *  @param array $result 로그인후 받은 데이타( 토큰[token], 리프래쉬[refreshToken] )
	 *  @return 'keywordList' => Array
                        (
                            [0] => Array
                                (
                                    [relKeyword] => 니트
                                    [monthlyPcQcCnt] => 15700
                                    [monthlyMobileQcCnt] => 95800
                                    [monthlyAvePcClkCnt] => 49
                                    [monthlyAveMobileClkCnt] => 632
                                    [monthlyAvePcCtr] => 0.33
                                    [monthlyAveMobileCtr] => 0.7
                                    [plAvgDepth] => 15
                                    [compIdx] => 높음
                                )
        
                            [1] => Array
                                (
                                    [relKeyword] => 뜨개실
                                    [monthlyPcQcCnt] => 3970
                                    [monthlyMobileQcCnt] => 23600
                                    [monthlyAvePcClkCnt] => 268.5
                                    [monthlyAveMobileClkCnt] => 2538.7
                                    [monthlyAvePcCtr] => 7.14
                                    [monthlyAveMobileCtr] => 11.04
                                    [plAvgDepth] => 15
                                    [compIdx] => 높음
                                )
                                ..........
	 */
	private function manageSearchAd($keyword, $result)
	{
	    //$this->Curl_start();
	    //echo '<pre>';print_r($result);
		//sleep(0.7);
	    $HEADER = array(
	        //"content-type: application/json",
	        "accept: application/json, text/plain, */*",
	        "origin: https://manage.searchad.naver.com",
	        "referer: https://manage.searchad.naver.com/customers/1560030/tool/keyword-planner",
	        "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
	        "authorization: Bearer ".$result['token'],
	        "x-accept-language: ko-KR"
	    );
	    // 데이타 가져오기
	    $queryString = "format=json";
	    $queryString .= "&siteId=";
        $queryString .= "&mobileSiteId=";
        $queryString .= "&hintKeywords=".urlencode($keyword);//%EB%8B%88%ED%8A%B8";
        $queryString .= "&includeHintKeywords=0";
        $queryString .= "&showDetail=1";
        $queryString .= "&biztpId=";
        $queryString .= "&mobileBiztpId=";
        $queryString .= "&month=";
        $queryString .= "&event=";
        $queryString .= "&keyword=";
        
	    $result = $this->_Request(
	        'https://manage.searchad.naver.com/keywordstool?'. $queryString,
	        array(
	            "header" => $HEADER
	            //,"cookie" => "./cookie.txt"
	        )
	        ) ;
	    //echo '---------111<pre>';print_r($result['response']['keywordList'][0]);
	    //echo '22<pre>';print_r($result['response']['keywordList'][1]);
	    //echo $keyword.'<pre>';print_r($result);
	    // curl 종료
	    //$this->Curl_stop();
	    return $result['response']['keywordList'][0];
	}
	
	/**
	 * 쇼핑수 가져오기 방법1 [ 네이버 Api 이용 ]
	 * 
	 * @param array $queryString array('query'=> '니트/스웨터', 'display'=> 10, 'start'=> 1, 'sort'=> 'sim') 
	 * 
	 * @uses $queryString['query'] 쇼핑 검색어
	 * @uses $queryString['display'] 검색 결과 출력 건수 지정 :: 10(기본값), 100(최대)
	 * @uses $queryString['start'] 검색 시작 위치로 최대 1000까지 가능 :: 1(기본값), 1000(최대)
	 * @uses $queryString['sort'] 정렬 옵션:: sim[기본값](유사도순), date (날짜순), asc(가격오름차순) ,dsc(가격내림차순)
	    
	 * @example $queryString = array(
	    		"display" => 10,	
	    		"start" => 1,
	    		"sort" => 'sim'
	    ) ;
	    
	 * @return array|mixed[]
	 * @example return [response] => Array
                        (
                            [lastBuildDate] => Thu, 24 Jan 2019 05:22:54 +0900
                            [total] => 3338547
                            [start] => 1
                            [display] => 10
                            [items] => Array
                                (
                                    [0] => Array
                                        (
                                            [title] => 스카시 보들보들 라운드니트
                                            [link] => http://search.shopping.naver.com/gate.nhn?id=16634532014
                                            [image] => https://shopping-phinf.pstatic.net/main_1663453/16634532014.20181216154854.jpg
                                            [lprice] => 15770
                                            [hprice] => 17600
                                            [mallName] => 네이버
                                            [productId] => 16634532014
                                            [productType] => 1
                                        )
                
                                    [1] => Array
                                        (
                                            [title] => 보들보들 루즈핏 라운드넥 니트
                                            [link] => http://search.shopping.naver.com/gate.nhn?id=16745580288
                                            [image] => https://shopping-phinf.pstatic.net/main_1674558/16745580288.20181224164602.jpg
                                            [lprice] => 13860
                                            [hprice] => 19800
                                            [mallName] => 네이버
                                            [productId] => 16745580288
                                            [productType] => 1
                                        )
                                        ......
	 */
	private function shop_keyword($queryString = null)
	{
	    //$this->Curl_start();
	    $HEADER = array(
	        "X-Naver-Client-Id: ".self::$apiKey[HOST]['site_key'],
	        "X-Naver-Client-Secret: ". self::$apiKey[HOST]['secret_key']
	        //,"Content-Type: application/json"
	    );
	    //$queryString['query'] = urlencode('니트/스웨터') ;
	    
	    foreach($queryString as $k => $v){
	    	$qrys[] = $k.'='.urlencode($v) ;
	    }
	    if(!empty($qrys)) $qrys = implode('&', $qrys);
	    
	    $result = $this->_Request(
	    		'https://openapi.naver.com/v1/search/shop.json?'.$qrys,
	        array(
	            "header" => $HEADER
	            //,"post" => (string)$post//"{\"startDate\":\"2017-08-01\",\"endDate\":\"2017-09-30\",\"timeUnit\":\"month\",\"category\":[{\"name\":\"패션의류\",\"param\":[\"50000000\"]},{\"name\":\"화장품/미용\",\"param\":[\"50000002\"]}],\"device\":\"pc\",\"ages\":[\"20\",\"30\"],\"gender\":\"f\"}"
	        )
	        //,"POST"
	        ) ;
	   // $this->Curl_stop();
	    
	    //echo '<pre>';print_r($result);exit;
	    return $result['response'] ;
	}
	/**
	 * 쇼핑수 가져오기 방법2 [ Url 페이지에서 파싱 ]
	 *
	 * @param string $keyword 쇼핑 검색어
	 * @return array('total' => int)
	 */
	private function shop_keyword_getUrl(string $keyword)
	{
		$queryString = "query=".urlencode($keyword) ;
		$queryString .= "&cat_id=&frm=NVSHATC" ;
		$data = file_get_contents('https://search.shopping.naver.com/search/all.nhn?'. $queryString );
		
		//preg_match_all("/<li class=\"snb_all on\">(.*?)<\/li>/si", $data, $match);
		preg_match_all("/<a href=\"#\" class=\"_productSet_total\" data-filter-name=\"productSet\" data-filter-value=\"total\" title=\"전체\">(.*?)<\/a>/si", $data, $match);
		$match[1] = str_replace(array('<em>전체</em>', ','), '', $match[1]) ;
		
		//echo '<pre>';print_r($match[1]);exit;
		if( !empty($match[1]) ) $match[1] = trim(array_pop($match[1])) ;
		return array(
				'total' => $match[1]
		) ;
	}
	public function getKeywordRank()
	{
		$post['cid'] = "50000805";
		$post['timeUnit'] = "date";
		$post['startDate'] = urlencode("2018-12-22");
		$post['endDate'] = urlencode("2019-01-22");
		$post['page'] = 1;
		$post['count'] = 100;
		
		$this->Curl_start();
		$res = $this->keyword_rank($post);
		$this->Curl_stop();
		echo '<pre>';print_r($res);exit;
	}
	public function getKeywordDatas()
	{
		$post['cid'] = "50000805";
		$post['timeUnit'] = "date";
		$post['startDate'] = urlencode("2018-12-22");
		$post['endDate'] = urlencode("2019-01-22");
		$post['page'] = 1;
		$post['count'] = 3;
		
		$res = $this->keyword_datas($post);
		echo '<pre>';print_r($res);exit;
	}
	/**
	 * 
	 * @example 
	 * Array
		(
		    [0] => Array
		        (
		            [rank] => 1
		            [keyword] => 데일리니트팬츠투피스
		            [datas] => Array
		                (
		                    [device] => Array
		                        (
		                            [0] => Array
		                                (
		                                    [code] => mo
		                                    [label] => 모바일
		                                    [ratio] => 0.02
		                                )
		
		                            [1] => Array
		                                (
		                                    [code] => pc
		                                    [label] => PC
		                                    [ratio] => 100
		                                )
		
		                        )
		
		                    [gender] => Array
		                        (
		                            [0] => Array
		                                (
		                                    [code] => f
		                                    [label] => 여성
		                                    [ratio] => 100
		                                )
		
		                            [1] => Array
		                                (
		                                    [code] => m
		                                    [label] => 남성
		                                    [ratio] => 20
		                                )
		
		                        )
		
		                    [age] => Array
		                        (
		                            [0] => Array
		                                (
		                                    [code] => 10
		                                    [label] => 10대
		                                    [ratio] => 11.76
		                                )
		
		                            [1] => Array
		                                (
		                                    [code] => 20
		                                    [label] => 20대
		                                    [ratio] => 82.35
		                                )
		
		                            [2] => Array
		                                (
		                                    [code] => 30
		                                    [label] => 30대
		                                    [ratio] => 100
		                                )
		
		                            [3] => Array
		                                (
		                                    [code] => 40
		                                    [label] => 40대
		                                    [ratio] => 35.29
		                                )
		
		                            [4] => Array
		                                (
		                                    [code] => 50
		                                    [label] => 50대
		                                    [ratio] => 11.76
		                                )
		
		                            [5] => Array
		                                (
		                                    [code] => 60
		                                    [label] => 60대
		                                    [ratio] => 5.88
		                                )
		
		                        )
		
		                )
		
		        )
		        .....
	 */
	public function lst()
	{
		//echo '<pre>';print_r($_REQUEST);exit;
		
		//-------------------------------
		$device = $gender = $age = array();
		
		// 디바이스별
		if( !empty($_REQUEST['device']) && is_array($_REQUEST['device']) )
		{
			foreach($_REQUEST['device'] as $v){
				if($v == 'all') break;
				else if($v == 'mo' || $v ==  'pc') $device[] = $v ;
			}
			if(!empty($device)) $device = implode(',', $device) ;
		}
		// 성별
		if( !empty($_REQUEST['gender']) && is_array($_REQUEST['gender']) )
		{
			foreach($_REQUEST['gender'] as $v){
				if($v == 'all') break ;
				else if($v == 'f' || $v ==  'm') $gender[] = $v ;
			}
			if(!empty($gender)) $gender= implode(',', $gender) ;
		}
		// 나이별
		if( !empty($_REQUEST['age']) && is_array($_REQUEST['age']) )
		{
			foreach($_REQUEST['age'] as $v){
				if($v == 'all') break;
				else if( (int)$v >=10 && (int)$v <=60) $age[] = $v ;
			}
			if(!empty($age)) $age= implode(',', $age) ;
		}
		// 일자
		if(preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_REQUEST['Sdate_start'], $sdate)){
			$Sdate_start = $_REQUEST['Sdate_start'] ;
		}
		if(preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_REQUEST['Sdate_end'], $sdate)){
			$Sdate_end= $_REQUEST['Sdate_end'] ;
		}
		if(!$Sdate_start && !$Sdate_start){
			$curtime = time();
			$Sdate_start = $_REQUEST['Sdate_start'] = date("Y-m-d", strtotime ( "-1 Year " )) ;
			$Sdate_end = $_REQUEST['Sdate_end'] = date("Y-m-d", $curtime) ;
		}

		// 쇼핑 분류 카테고리
		//-------------------
		$cid = 0 ;
		if( isset($_GET['scate']) && is_array($_GET['scate']) )
		{
			$_GET['scate'] = array_reverse($_GET['scate']);
			foreach($_GET['scate'] as &$scate)
			{
				if(!empty($scate) && (int)$scate){
					$cid = $scate ;
					break ;
				}
				else unset($scate) ;
			}
			$_GET['scate'] = array_reverse($_GET['scate']);
		}
		
		
		//-------------------
		$this->Curl_start();
		$root_categorys = array();
		$root_categorys[] = $this->cate();
		if( isset($_GET['scate']) && is_array($_GET['scate']) )
		{
			foreach($_GET['scate'] as $v){
				
				if((int)$v) $root_categorys[] = $this->cate($v);
			}
		}
		
		$this->pageScale = 20;
		$this->pageBlock = 10;
		$_REQUEST['page'] = (!isset($_GET['page']) || !(int)$_GET['page']) ? 1 : $_GET['page'] ;
		
		if( (int)$cid )
		{
			$post['cid'] = $cid ; //"50000805";
			$post['timeUnit'] = "date";
			if(!empty($Sdate_start)) $post['startDate'] = urlencode($Sdate_start);
			if(!empty($Sdate_end)) $post['endDate'] = urlencode($Sdate_end);
			$post['page'] = $_REQUEST['page'] ;
			$post['count'] = $this->pageScale;
			if(!empty($age)) $post['age'] = $age;
			if(!empty($gender)) $post['gender'] = $gender;
			if(!empty($device)) $post['device'] = $device;
			//echo '<pre>';print_r($post);exit;
			$datas = $this->keyword_datas($post); 
		}
		
		$this->Curl_stop();
		//-------------------------------
		self::$Total_cnt = 500 ;
		$paging = $this->Pagination($_GET['page'], (string)$queryString);
		WebAppService::$queryString = Func::QueryString_filter( (string)$queryString );
		
		//echo '<pre>';print_r($root_categorys);exit;
		//echo '<pre>';print_r($datas);exit;
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString
				),
				'SHOP_CATES' => $root_categorys,
				'LIST' => $datas,
				'TOTAL_CNT' => self::$Total_cnt,
				'VIEW_NUM' => self::$view_num,
				'PAGING' => $paging
		));
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/naver_shopinside.html"),"admin_sub");
		
		/* // Turn off output buffering
		ini_set('output_buffering', 'off');
		// Turn off PHP output compression
		ini_set('zlib.output_compression', false);
		
		//Flush (send) the output buffer and turn off output buffering
		while (@ob_end_flush());
		
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
		ob_start();
		 */
		$this->WebAppService->printAll();
		
		//ob_end_flush();
	}
	/**
	 * 네이버 광고 관리자페이지
	 * 
	 * @link https://manage.searchad.naver.com
	 * 
	 * @return array
	 * @example 
	 *         [response] => Array
                    (
                        [keywordList] => Array
                            (
                                [0] => Array
                                    (
                                        [relKeyword] => 니트
                                        [monthlyPcQcCnt] => 15700
                                        [monthlyMobileQcCnt] => 95800
                                        [monthlyAvePcClkCnt] => 49
                                        [monthlyAveMobileClkCnt] => 632
                                        [monthlyAvePcCtr] => 0.33
                                        [monthlyAveMobileCtr] => 0.7
                                        [plAvgDepth] => 15
                                        [compIdx] => 높음
                                    )
            
                                [1] => Array
                                    (
                                        [relKeyword] => 뜨개실
                                        [monthlyPcQcCnt] => 3970
                                        [monthlyMobileQcCnt] => 23600
                                        [monthlyAvePcClkCnt] => 268.5
                                        [monthlyAveMobileClkCnt] => 2538.7
                                        [monthlyAvePcCtr] => 7.14
                                        [monthlyAveMobileCtr] => 11.04
                                        [plAvgDepth] => 15
                                        [compIdx] => 높음
                                    )
	*/
	public function searchad()
	{
	    $this->Curl_start();
	    
	    $post = '{"loginId":"yengsuad","loginPwd":"yengsu1458"}';
	    
	    $HEADER = array(
	        "content-type: application/json",
	        //"accept: application/json, text/plain, */*",
	        "origin: https://searchad.naver.com",
	        "referer: https://searchad.naver.com",
	        "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
	    );
	    
	    /* $cookie_nm = "./cookie.txt";
	    curl_setopt ($ch, CURLOPT_SSLVERSION,3);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_nm);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_nm);
	    
	    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_nm);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_nm); */
	    
	    $result = $this->_Request(
	        'https://searchad.naver.com/auth/login',
	        array(
	            "header" => $HEADER,
	            "post" => $post,
	            "cookie" => "./cookie.txt"
	        )
	        //,"POST"
	        ) ;
	    echo '<pre>';print_r($result);
	    
	    $HEADER = array(
	        //"content-type: application/json",
	        "accept: application/json, text/plain, */*",
	        "origin: https://manage.searchad.naver.com",
	        "referer: https://manage.searchad.naver.com/customers/1560030/tool/keyword-planner",
	        "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
	        "authorization: Bearer ".$result['response']['token'],
	        "x-accept-language: ko-KR"
	    );
	    
	    
	    $result = $this->_Request(
	        'https://manage.searchad.naver.com/keywordstool?format=json&siteId=&mobileSiteId=&hintKeywords=%EB%8B%88%ED%8A%B8&includeHintKeywords=0&showDetail=1&biztpId=&mobileBiztpId=&month=&event=&keyword=',
	        array(
	            "header" => $HEADER,
	            //"post" => $post,
	            "cookie" => "./cookie.txt"
	        )
	        //,"POST"
	        ) ;
	    echo '<pre>';print_r($result);
	    // curl 종료
	    $this->Curl_stop();
	}
	public function keyword_go()
	{
		$this->keyword_rank();
	}
	public function tot_keyword()
	{
		$HEADER = array(
				/* "X-Naver-Client-Id: ".self::$apiKey[HOST]['site_key'],
				"X-Naver-Client-Secret: ". self::$apiKey[HOST]['secret_key']
				,"Content-Type: application/json" */
				
				
				"content-type: application/x-www-form-urlencoded; charset=UTF-8",
				"cookie: NNB=HS2UMQ4IVKNFQ; npic=CNrD1PLTVyr8fCmupctk6K7+GZCC3WZw16+30hUX7MKuAt+Z8e1/lHNGRpc+mASaCA==; ASID=742d94690000015aff81b73b00000049; _ga=GA1.2.348935831.1489642964; nx_ssl=2; _datalab_cid=50000000; 066c80626d06ffa5b32035f35cabe88d=%04%1A%A9%DE%AF%D0%07%D78V%B9%95%15%1E%D7%7F%E93%E6%D8%B5y.%9B%FCJ%24%E6%D2%E1U%02W%09%7C%B3%08%F6%89%3F%E1%DEHh%E7H%A6%A7r%F4%7F%92AAh%F4%F8%D8%DF%E3%C1-%E7J%FD%93%16%7CB%0A%E6m%87%CBY%E1%BC%F6uJ%046%9A%7C%810%BC%87%8C8h%0E+%E7%F9%5D%BEEL%AA%7E%40%0B%09%D3%C5%B4BJ%96%E6%D3%21%7B%3B%9F%A1%29%CE%F5%DB%0D%5EK%7E%EE%24%E6%E0%92A%02%90%7E%BE%3F%BCI%8A%9C%DE%0B2%C2; 1a5b69166387515780349607c54875af=1%F4%28Z8pK%9A; nid_inf=1931715665; NID_AUT=EP7ngSnaxBld16TWzyzV1uyDHW2hDlK+UISQ1F8uTcPZ/c4bedxNXk4hzHBzaIAC; NID_JKL=6Sx8WPibPOzvEthwxI7mOt8nal/MkMjWIy1BM3M039M=; NID_SES=AAABdoQGx9KgpAssqtiqgwTuW7OEgvoPCli/VplXa7QrtMAQLoHv89q5k0FNC8z/ZqHE/Y/14Tbm5yOYHZh7CNq3jfQ15ttpz4eWh3B4apuv3rN46y8rWn+nW6jI9TBPd+daTHq9yYwqeHG7bJY0S2vSP/Pl7ahbbmkvhAlrBeiEqixeayWbA2eJcHjKJu4+7wa7vvS1Xd9bUFA/GYaWgyLZngXGtMbZeiXf2y3uEzHHhPKIRsXsYrtPJ15f5BF5UjsITQ1ZOQ3CTVPpSH04guUsfjiJwG21NLfLTSbvDBovZFVb1E1LQOJEOfMz18AuDAEv0iPWcGHnD+bpg3DCCHCenk9ZE/jPZ0lT0jSdMPGTWQPBfC4olBy2ODRdP61jxx9Fm9RYNnNnIElKgxPkVFb6+nb0Svd/z7IL79c0BQalR/OhUltcDHyCDrM8tP3PpLJPFn0UK9lTnhXbp1i879GNpTuuAlr+nsHJiG5Rj05AJV+h4XrQXOa4VcSaZw715M+pBA==; page_uid=UZkE6spl6GGssnyvsHNssssssvZ-398923",
				"origin: https://datalab.naver.com",
				"referer: https://datalab.naver.com/shoppingInsight/sCategory.naver",
				"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
				"x-requested-with: XMLHttpRequest"
		);
		
		$post = array(
				'startDate' => "2018-12-21",
				'endDate' => "2019-01-21",
				'timeUnit' => "date",
				'cid' => "50000805",
				"page" => 1,
				"page" => 20
				//,'device' => "pc",
				//'ages' => array("20", "30"),
				//,'gender' => "m"
		);
		//$post = json_encode($post) ;
		echo $post;
		$result = $this->_Request(
				'https://datalab.naver.com/shoppingInsight/getCategoryKeywordRank.naver',
				array(
						"header" => $HEADER,
						"post" => $post//"{\"startDate\":\"2017-08-01\",\"endDate\":\"2017-09-30\",\"timeUnit\":\"month\",\"category\":[{\"name\":\"패션의류\",\"param\":[\"50000000\"]},{\"name\":\"화장품/미용\",\"param\":[\"50000002\"]}],\"device\":\"pc\",\"ages\":[\"20\",\"30\"],\"gender\":\"f\"}"
				),
				"POST"
				) ;
		echo '<pre>';print_r($result);exit;
	}
	//###################################################################################
	//###################################################################################
	 /* public function lst()
	 {
	 
	 $homepage = file_get_contents('https://datalab.naver.com/shoppingInsight/sCategory.naver');
	 echo $homepage;
	 } */
	/* public function aa(){
		
		$client_id = "DAiZtflMANcu8vyXs0cN";
		$client_secret = "8y4EjSadGt";
		
		$url = "https://openapi.naver.com/v1/datalab/shopping/categories";
		$body = "{\"startDate\":\"2017-08-01\",\"endDate\":\"2017-09-30\",\"timeUnit\":\"month\",\"category\":[{\"name\":\"패션의류\",\"param\":[\"50000000\"]},{\"name\":\"화장품/미용\",\"param\":[\"50000002\"]}],\"device\":\"pc\",\"ages\":[\"20\",\"30\"],\"gender\":\"f\"}";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$headers = array();
		$headers[] = "X-Naver-Client-Id: ".$client_id;
		$headers[] = "X-Naver-Client-Secret: ".$client_secret;
		$headers[] = "Content-Type: application/json";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		
		$response = curl_exec ($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		echo "status_code:".$status_code."
";
		curl_close ($ch);
		if($status_code == 200) {
			echo $response;
		} else {
			echo "Error 내용:".$response;
		}
	} */
	
	public function xx()
	{
		
		$curl= new Curl();
		//$curl->setBasicAuthentication('yeongsu', 'dnflskfk');
		//$curl->setUserAgent('MyUserAgent/0.0.1 (+https://www.example.com/bot.html)');
		$curl->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36');
		$curl->setReferrer('https://datalab.naver.com/shoppingInsight/sCategory.naver');
		$curl->setHeader('X-Requested-With', 'XMLHttpRequest');
		$curl->setHeader('origin', 'https://datalab.naver.com');
		$curl->setHeader('Referer', 'https://datalab.naver.com/shoppingInsight/getCategoryKeywordRank.naver');
		//$curl->setCookie('key', 'value');
		/* $curl->setCookie('NNB','HS2UMQ4IVKNFQ');
		$curl->setCookie('npic','CNrD1PLTVyr8fCmupctk6K7+GZCC3WZw16+30hUX7MKuAt+Z8e1/lHNGRpc+mASaCA==');
		$curl->setCookie('ASID','742d94690000015aff81b73b00000049');
		$curl->setCookie('_ga','GA1.2.348935831.1489642964');
		$curl->setCookie('nx_ssl','2');
		$curl->setCookie('_datalab_cid','50000000'); */
		//$curl->setCookie('066c80626d06ffa5b32035f35cabe88d','%04%1A%A9%DE%AF%D0%07%D78V%B9%95%15%1E%D7%7F%E93%E6%D8%B5y.%9B%FCJ%24%E6%D2%E1U%02W%09%7C%B3%08%F6%89%3F%E1%DEHh%E7H%A6%A7r%F4%7F%92AAh%F4%F8%D8%DF%E3%C1-%E7J%FD%93%16%7CB%0A%E6m%87%CBY%E1%BC%F6uJ%046%9A%7C%810%BC%87%8C8h%0E+%E7%F9%5D%BEEL%AA%7E%40%0B%09%D3%C5%B4BJ%96%E6%D3%21%7B%3B%9F%A1%29%CE%F5%DB%0D%5EK%7E%EE%24%E6%E0%92A%02%90%7E%BE%3F%BCI%8A%9C%DE%0B2%C2');
		//$curl->setCookie('1a5b69166387515780349607c54875af','1%F4%28Z8pK%9A');
		//$curl->setCookie('BMR','s=1548213427461&r=https%3A%2F%2Fm.blog.naver.com%2FPostView.nhn%3FblogId%3Dhttp-log%26logNo%3D221220107734%26proxyReferer%3Dhttps%253A%252F%252Fwww.google.com%252F&r2=https%3A%2F%2Fwww.google.com%2F');

		$curl->post('https://datalab.naver.com/shoppingInsight/getCategoryKeywordRank.naver', array(
				'cid' => '50000000',
				'timeUnit' => 'date',
				'startDate' => '2018-12-22',
				'endDate' => '2019-01-22',
				'page' => 1,
				'count' => 20
		));
		echo '<pre>';print_r($curl->rawResponse);
		if ($curl->error) {
			//echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
			echo '<pre>';print_r($curl->errorMessage);
		} else {
			echo 'Response:' . "\n";
			//var_dump($curl->response);
			echo '<pre>';print_r($curl->response);
		}
		
		var_dump($curl->requestHeaders);
		var_dump($curl->responseHeaders);
		echo '<pre>';print_r($curl->requestHeaders);
		echo '<pre>';print_r($curl->responseHeaders);
	}
	public function ad()
	{
		$url = "https://datalab.naver.com/shoppingInsight/getCategoryKeywordRank.naver" ;
		
		$header = array();
		//$header[] = 'Accept: */*';
		//$headers[] = "Pragma: no-cache";
		//$header[] = 'Accept-Encoding: gzip,deflate';
		//$header[] = 'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7';
		//$header[] = 'Connection: keep-alive';
		//$header[] = 'Content-Length: 46';
		//$header[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
		$header[] = 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36';
		$header[] = 'X-Requested-With: XMLHttpRequest';
		//$header[] = 'cookie: NNB=HS2UMQ4IVKNFQ; npic=CNrD1PLTVyr8fCmupctk6K7+GZCC3WZw16+30hUX7MKuAt+Z8e1/lHNGRpc+mASaCA==; ASID=742d94690000015aff81b73b00000049; _ga=GA1.2.348935831.1489642964; nx_ssl=2; _datalab_cid=50000000; 066c80626d06ffa5b32035f35cabe88d=%04%1A%A9%DE%AF%D0%07%D78V%B9%95%15%1E%D7%7F%E93%E6%D8%B5y.%9B%FCJ%24%E6%D2%E1U%02W%09%7C%B3%08%F6%89%3F%E1%DEHh%E7H%A6%A7r%F4%7F%92AAh%F4%F8%D8%DF%E3%C1-%E7J%FD%93%16%7CB%0A%E6m%87%CBY%E1%BC%F6uJ%046%9A%7C%810%BC%87%8C8h%0E+%E7%F9%5D%BEEL%AA%7E%40%0B%09%D3%C5%B4BJ%96%E6%D3%21%7B%3B%9F%A1%29%CE%F5%DB%0D%5EK%7E%EE%24%E6%E0%92A%02%90%7E%BE%3F%BCI%8A%9C%DE%0B2%C2; 1a5b69166387515780349607c54875af=1%F4%28Z8pK%9A; BMR=s=1548213427461&r=https%3A%2F%2Fm.blog.naver.com%2FPostView.nhn%3FblogId%3Dhttp-log%26logNo%3D221220107734%26proxyReferer%3Dhttps%253A%252F%252Fwww.google.com%252F&r2=https%3A%2F%2Fwww.google.com%2F';
		$header[] = "origin: https://datalab.naver.com";
		$header[] = "Referer: https://datalab.naver.com/shoppingInsight/getCategoryKeywordRank.naver";
		//origin: https://datalab.naver.com
		$post = "cid=50000000&timeUnit=date&startDate=".urlencode("2018-12-22")."&endDate=".urlencode("2019-01-22");
		$post .= "&page=1&count=20";
		
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL,$url); //접속할 URL 주소
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSLVERSION,1);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_USERAGENT, AGENT);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36');
		$result = curl_exec ($ch);
		
		$info = curl_getinfo($ch);//curl_getinfo($ch, CURLINFO_HTTP_CODE );
		//echo '■결과■ : <pre>';print_r($response);
		echo '<pre>';print_r($result);
		echo '<pre>';print_r($info);
		
		curl_close ($ch);
		
	}
	/* public function ad()
	{
		//https://nid.naver.com/nidlogin.login
		//https://nid.naver.com/nidlogin.login?url=https%3A%2F%2Fsell.smartstore.naver.com%2F%23%2FnaverLoginCallback%3Furl%3Dhttps%253A%252F%252Fsell.smartstore.naver.com%252F%2523
		//<input type="hidden" name="url" id="url" value="https://sell.smartstore.naver.com/#/naverLoginCallback?url=https%3A%2F%2Fsell.smartstore.naver.com%2F%23">
		//$loginUrl = 'https://logins.daum.net/Mail-bin/login.cgi?dummy=1238466344458';
		//$login_data = 'enpw=비밀번호&id=아이디&pw=비밀번호&url=http://www.daum.net&webmsg=-1';
		//$login_data = 'enpw=비밀번호&id=아이디&pw=비밀번호&url=http://www.daum.net&webmsg=-1';
		$loginUrl = 'https://nid.naver.com/nidlogin.login?mode=form&url=https%3A%2F%2Fwww.naver.com';
		$login_data = 'enctp=1&locale=ko_KR&smart_LEVEL=-1&id=vega99c&pw=kjs@46894689&url=https%3A%2F%2Fwww.naver.com';//https://manage.searchad.naver.com/customers/1548412/tool/keyword-planner';
		$cookie_nm = "./cookie.txt";
		
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL,$loginUrl); //접속할 URL 주소
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSLVERSION,3);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_nm);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_nm);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, AGENT);
		$result = curl_exec ($ch);
		
		 curl_setopt ($ch, CURLOPT_URL,"https://naver.com");//https://manage.searchad.naver.com/customers/1548412/tool/keyword-planner"); //접속할 URL 주소
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSLVERSION,1);
		curl_setopt ($ch, CURLOPT_HEADER, 1);
		//curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_nm);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_nm);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, AGENT);
		$result = curl_exec ($ch); 
		
		$info = curl_getinfo($ch);//curl_getinfo($ch, CURLINFO_HTTP_CODE );
		//echo '■결과■ : <pre>';print_r($response);
		echo '<pre>';print_r($result);
		echo '<pre>';print_r($info);
		
		curl_close ($ch);
		//echo $result;
		
	} */
	
	
}