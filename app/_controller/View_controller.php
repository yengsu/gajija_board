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
	    //echo '<pre>';print_r($this->routeResult) ;exit;
	    //echo '<pre>';print_r($this) ;exit;
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

	private function a(){

		if($_SERVER['REQUEST_METHOD']=="GET"){
			echo '<html>
			<head>
			<meta charset="utf-8" />
			</head>
			<body>
			
			<form method="post" action="/view/a" enctype="multipart/form-data">
			
			<input type="file" name="attach[]" multiple />
			<input type="submit" value="Submit" />
			
			</form>
			
			</body>
			</html>';
		}
		else{
		    $recvMail = array(
		    		"yslee@smartlab.co.kr", "yengsu@hanmail.net", "yeongsu@naver.com", "yengsu@gmail.com"
		    		,"mylee@smartlab.co.kr", "damon25@hanmail.net"
		    ) ;
			//$recvMail = array("yslee@smartlab.co.kr") ;
			$Attach = array(
					"files" => array("images/common/logo.png", "images/adm/menu07.png"),
					"upload" => array(
							"dir" => "tmp/",
							"files" => $_FILES
							)
			);
			$message = '<div style="font-size:20px;font-weight:bold;color:red;">안녕하세여^^!<br>첨부파일 포함</div>' ;
			//$res = Func::mailSend($recvMail, "manager@smartlab.co.kr", "메일 테스트", $message, $Attach, true) ;
			$res = Func::mailSend($recvMail, "yengsu@gajija.com", "메일 테스트", $message, $Attach, true) ;
			for($i=0; $i < count($recvMail); $i++){
				mail($recvMail[$i], $res["subject"], $res["message"], $res["headers"]);
			}
		}
	}
	/**
	 * 
	 * X-PHP-Originating-Script 제거는 
	 * /etc/php.ini의 
	 * 		mail.add_x_header = Off
	 * 		expose_php = Off
	 */
	private function m1(){
		//$subject = "=?UTF-8?B?".base64_encode("호이짜 호이짜잘갑니까요?")."?=";
		$filename  = "shop_20170619.zip";
		$path      = "app/";
		$file      = $path . $filename;
		$file_size = filesize($file);
		$handle    = fopen($file, "r");
		$content   = fread($handle, $file_size);
		fclose($handle);
		
		$content = chunk_split(base64_encode($content));
		$uid     = md5(uniqid(time()));
		$name    = basename($file);
		
		$eol     = PHP_EOL;
		$subject = "Mail Out Certificate";
		$message = '<h1>Hi i m mashpy</h1>';
		
		$from_name = "yengsu";
		$from_mail = "yengsu@hanmail.net";
		$replyto   = "yengsu@hanmail.net";
		$mailto    = "yslee@smartlab.co.kr";
		$header    = "From: " . $from_name . " <" . $from_mail . ">\n";
		$header .= "Reply-To: " . $replyto . "\n";
		$header .= "MIME-Version: 1.0\n";
		$header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\n\n";
		$emessage = "--" . $uid . "\n";
		$emessage .= "Content-type:text/html; charset=iso-8859-1\n";
		$emessage .= "Content-Transfer-Encoding: 7bit\n\n";
		$emessage .= $message . "\n\n";
		$emessage .= "--" . $uid . "\n";
		$emessage .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"\n"; // use different content types here
		$emessage .= "Content-Transfer-Encoding: base64\n";
		$emessage .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\n\n";
		$emessage .= $content . "\n\n";
		$emessage .= "--" . $uid . "--";
		mail($mailto, $subject, $emessage, $header);
	}
	
}