<?
use Gajija\service\CommNest_service;

use Gajija\controller\_traits\Controller_comm;
use Gajija\controller\_traits\Page_comm;
use Gajija\service\Api\GoogleApi_service;
use Gajija\service\Api\FacebookApi_service;
use Gajija\service\Api\KakaoApi_service;
use Gajija\service\Api\NaverApi_service;
use Gajija\service\Api\InstagramApi_service;
use Gajija\service\Mail_service;
use Gajija\service\Api\Sms_service;
use Gajija\service\Api\Captcha_service;
use Gajija\service\Member_service;


class Member_controller extends CommNest_service
{
	use Controller_comm, Page_comm;
	/**
	 * @desc 웹서비스용
	 * 
	 * @var object
	 */
	public $WebAppService;

	/**
	 * @desc 라우팅 결과데이타
	 * 
	 * @var array 데이타
	 */
	public $routeResult = array();
	
	/**
	 * @desc 회원 서비스
	 * 
	 * @var object
	 */
	protected $Member_service ;
	
	/**
	 * @desc 회원 환경정보
	 * 
	 * @filesource conf/member.conf.php
	 * @var array
	 */
	public static $mbr_conf = array();
	
	/**
	 * @desc 사이트 메뉴정보 데이타
	 *
	 * @var mixed
	 */
	public static $menu_datas ;
	
	/**
	 * @desc SMS 또는 Email 인증대기 제한 시간(분단위)
	 * 
	 * @var integer
	 */
	protected static $authen_expire_minute = 5 ;
	
	/**
	 * @desc 인증정보 저장 변수명
	 * @var string
	 */
	protected static $authVar = "authData" ;
	
	/**
	 * @desc 비밀번호 변경 메일발송후 비밀번호 재설정 만료시간
	 * 
	 * @var integer
	 */
	private static $expire_time_pwd = (60*10*1) ; // 10 분까지
	
	/**
	 * @desc 메일 서비스
	 *
	 * @var object
	 */
	public $MailService ;
	
	/**
	 * 페이지 레이아웃명
	 * @var string
	 * @desc conf/layout.conf.php 참조
	 */
	public $page_layout = "";
	
	public function __construct($routeResult)
	{
		$this->__constructor($routeResult);

		// DB Table 선언
		$this->setTableName("member");

		$this->Member_service = new Member_service() ;

		self::$mbr_conf = WebApp::getConf_real("member") ;

		$this->menu_display_apply($routeResult["mcode"]) ;

		if(isset(self::$menu_datas['self']['layout']) && self::$menu_datas['self']['layout']){
			$this->page_layout = self::$menu_datas['self']['layout'] ;
		}
	}

	/**
	 * @desc window opener 제어
	 * 
	 * @param string $url
	 * @param string $msg
	 */
	private static function moveParent_win($url='', $msg='') {
		if( !empty($url) ){
			$url = "opener.parent.location.replace('".$url."');" ;
		}
		if( !empty($msg) ){
			$msg = str_replace(array("\n","'"),array("\\n","\'"),$msg);
			$alert = "alert('$msg');" ;
		}
		echo '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>';
		echo "<script>". $alert . $url. "window.close();</script>";
		echo "</body></html>";
		exit;
	}
	
	/**
	 * @desc 유효성 검사 - 회원정보 저장 및 로그인 처리
	 * @param array $profile 유저 프로파일(profile)
	 * @return void
	 */
	private function loginApi_validate( $profile )
	{
		$msg = '';
		if( empty((string)$profile["provider"]) ){
			$msg = "로그인 Api 공급자가 아닙니다." ; //It's Not Login  Access provider
		}
		else if( empty((string)$profile["id"]) ){
			$msg = "Login Not Access[id]" ;
		}
		/* else if( !filter_var( (string) $profile["email"], FILTER_VALIDATE_EMAIL) ){
			$msg = "Email 주소를 입력해주세요." ;
		} */
		if($msg) $this->moveParent_win('', $msg) ;
	}
	/**
	 * @desc [Google, Facebook..] 회원정보 저장 및 로그인 처리
	 * 
	 * @param array $profile
	 * 
	 * @example $profile Array
		(
			[provider] => facebook
		    [email] => gildong@gmail.com
		    [id] => 10670412340421001067404
		    [gender] => male
		    [name] => gildong hong
		    
			[familyName] => hong (Google용)
			[givenName] => gildong (Google용)
			
            [first_name] => 길동 (Facebook용)
            [last_name] => 홍 (Facebook용)
            
		    [locale] => ko
		    [picture] => https://lh3.googleusercontent.com/-XdSEMkCX/AAAFFAAI/FSDAAAAAA/4252rsFFG43M/photo.jpg
		    [link] => https://plus.google.com/10670412340421001067404
		)
	 */
	private function loginApi_process( $profile )
	{ 
		$this->loginApi_validate($profile) ;
		
		$this->setTableName("member");
		$exist = $this->count('serial', array(
				'userid' => (string)$profile["email"]
				//"oauth_provider" => $profile["provider"],
				//"oauth_uid" => $profile["id"]
		)) ;
		if( (int)$exist )
		{
		    // 저장된 회원정보 가져오기
			$mbr_data = $this->dataRead(array(
					"columns" => 'serial, userid, username, grade, withdrawal',
					"conditions" => array(
							'userid' => (string)$profile["email"]
							//'oauth_provider' => $profile["provider"], // provider unique id
							//'oauth_uid' => $profile["id"] // provider unique id
					)
			));
		}
		//--------------------
		// Insert - 회원저장
		//--------------------
		if( empty($mbr_data) )
		{
    			//성별
    			if($profile['gender'] == 1 || $profile['gender'] == 2)
    			{
    				$gender = $profile['gender'] ;
    			}
    			else{
    				if( $profile['gender'] ) $profile['gender'] = strtolower($profile['gender']) ;
    				if( $profile['gender']=="male" || $profile['gender']=="m" ) $gender = 1;
    				else if( $profile['gender']=="female" || $profile['gender']=="f" ) $gender = 2;
    			}
    			
    			//회원, sns 공통정보
    			$put_data = array(
    					"oid" => OID,
    					"userid" => (string) $profile["email"],
    					"username" => (string) $profile["name"],
    					"locale" => (string) $profile["locale"], // 국적(ko, eng.....)
    					"sex" => (int) $gender, // 성별
    					//"oauth_provider" => (string) $profile["provider"], // facebook, google
    					//"oauth_uid" => $profile["id"], // provider id
    					"hp" => (string)$profile["phone"],
    					"authen" => ($profile["authen_phone"] || $profile["authen_email"]) ? 1:0,
    					"authen_sms" => (string)$profile["authen_phone"],
    					"authen_email" => (string)$profile["authen_email"],
    			        "profile_photo" => (string)$profile["picture"],
    					"ip" => $_SERVER['REMOTE_ADDR'],
    					"recent_login" => time()
    			);
    			$a = array_merge(
    					$put_data, array("oauth_login" => 1, "agree_news" => 1, "regdate" => time())
    					);

    			$this->DB->startTransaction();
    			
    			// 회원 저장
    			$insert_id = $this->dataAdd( array_merge(
    			                         $put_data, array("oauth_login" => 1, "agree_news" => 1, "regdate" => time())
    								) ) ;
    			if($insert_id)
    			{
    				// sns 저장
    				$this->setTableName("member_sns");
    				$put_data = array_merge($put_data, array(
    						"oauth_provider" => (string) $profile["provider"], // facebook, google
    						"oauth_uid" => $profile["id"] // provider id
    				));
    				$insert_id = $this->dataAdd( $put_data ) ;
    				if($insert_id)
    				{
    					$this->DB->commit();
    					
    					// 가입축하 mail 발송
    					$this->join_MailSend(array(
    					    "mail" => $profile["email"],
    					    "username" => $profile["name"]
    					    //"phone" => $profile["phone"],
    					));
    					/* try{
    					    // 가입축하 mail 발송
    					    $this->join_MailSend(array(
    					        "mail" => $profile["email"],
    					        "username" => $profile["name"]
    					        //"phone" => $profile["phone"],
    					    ));
    					}
    					catch ( \Exception $e ){
    					    $this->WebAppService->assign( array(
    					        "error"=>$e->getMessage(),
    					        "error_code" => $e->getCode()
    					    ) );
    					} */
    					//-----------------------------------------
    					// 회원등급 명
    					//-----------------------------------------
    					/* $this->setTableName("member_grade");
    					$grade_name = $this->dataRead(array(
    					    "columns" => 'grade_name',
    					    "conditions" => 'grade_code='.(int) $mbr_data[0]['grade']
    					)); */
    					//-----------------------------------------
    					// 회원 세션 생성
    					//-----------------------------------------
    					$this->add_session( array(
    					    'mbrSerial' => (int) $insert_id,
    					    'mbrId' => (string) $profile["email"],
    					    'mbrName' => (string) $profile["name"]
    					    //'mbrGrade' => (int) $mbr_data[0]['grade'],
    					    //'mbrGradeName' => (string) $mbr_data[0]['grade_name']
    					)) ;
    					//-----------------------------------------
    				}
    				else{
    					$this->DB->rollback();
    				}
    				$this->moveParent_win('/') ;
    			}
    			else{
    				$this->moveParent_win('', '로그인 실패했습니다. 다시 시도해주세요.') ;
    			}
		}
		//--------------------
		// Update - 회원정보 
		//--------------------
		else{
			
		      if($mbr_data[0]['withdrawal']==1) {
    				$this->moveParent_win('', '탈퇴한 회원입니다.') ;
    				exit;
    			}
    			$put_data = array(
    			    //"username" => $profile["name"],
    			    "oauth_login" => 1,
    			    "recent_login" => time(),
    			    "ip" => $_SERVER['REMOTE_ADDR']
    			);
    			// 폰인증 저장
    			if( !$this->authen_successValidate($profile) )
    			{
    			    if($profile['authen_email']){
    			        //$put_data['userid'] = (string)$profile["email"] ;
    			        $put_data['authen_email'] = (string)$profile["authen_email"] ;
    			    }
    			    else if($profile['authen_phone']){
    			        $put_data['hp'] = (string)$profile["phone"] ;
    			        $put_data['authen_sms'] = (string)$profile["authen_phone"] ;
    			    }
    			    $put_data['authen'] = ($profile["authen_phone"] || $profile["authen_email"]) ? 1:0 ;
    			}
    			//echo '<pre>';print_r($put_data);exit;
    			// 회원 업데이트
    			$this->setTableName("member");
    			$res = $this->dataUpdate($put_data, array(
    					"userid" => (string)$profile["email"]
    			)) ;
    			if($res)
    			{
        			// sns 업데이트
        			$this->setTableName("member_sns");
        			$this->dataInsertUpdate(
        					array(
        							"oid" => OID,
        							"oauth_provider" => (string) $profile["provider"], // facebook, google
        							"oauth_uid" => $profile["id"], // provider id
        							"userid" => (string) $profile["email"],
        							"username" => (string) $profile["name"],
        							"locale" => (string) $profile["locale"], // 국적(ko, eng.....)
        							"sex" => (int) $gender, // 성별
        							"hp" => (string)$profile["phone"],
        							"authen" => ($profile["authen_phone"] || $profile["authen_email"]) ? 1:0,
        							"authen_sms" => (string)$profile["authen_phone"],
        							"authen_email" => (string)$profile["authen_email"],
        					        "profile_photo" => (string)$profile["picture"],
        							"ip" => $_SERVER['REMOTE_ADDR'],
        							"recent_login" => time(),
        					),
        					"username=VALUES(username),".
        					"locale=VALUES(locale),".
        					"sex=VALUES(sex),".
        					"hp=VALUES(hp),".
        			        "profile_photo=VALUES(profile_photo),".
        					"recent_login=VALUES(recent_login),".
        					"ip=VALUES(ip)"
        					) ;
        			
        			/* $res = $this->dataUpdate($put_data, array(
        					//"userid" => (string)$profile["email"]
        					'oauth_provider' => $profile["provider"], // provider unique id
        					'oauth_uid' => $profile["id"] // provider unique id
        			)) ; */
        			//-----------------------------------------
        			// 회원등급 명
        			//-----------------------------------------
        			/* $this->setTableName("member_grade");
        			$grade_name = $this->dataRead(array(
        			    "columns" => 'grade_name',
        			    "conditions" => 'grade_code='.(int) $mbr_data[0]['grade']
        			)); */
        			//-----------------------------------------
        			// 회원 세션 생성
        			//-----------------------------------------
        			$this->add_session( array(
        			    'mbrSerial' => (int) $mbr_data[0]['serial'],
        			    'mbrId' => (string) $mbr_data[0]['userid'],
        			    'mbrName' => (string) $mbr_data[0]['username']
        			    //'mbrGrade' => (int) $mbr_data[0]['grade'],
        			    //'mbrGradeName' => (string) $mbr_data[0]['grade_name']
        			)) ;
        			//-----------------------------------------
        			
        			$this->moveParent_win('/') ;
    			}
    			else{
    			 $this->moveParent_win('', '회원 정보를 업데이트 실패했습니다..') ;
    			}
			
		}
		
	}
	
	/**
	 * @desc [Google-Api] Login Callback
	 * 
	 * @param string $_GET["code"]
	 * 
	 * @return Array
		(
		    [access_token] => ya29.GlExsBLPygBhvRAnK1bL9F....
		    [token_type] => Bearer
		    [expires_in] => 3600
		    [id_token] => exJhcGciOiJSUeI1NiIsImtpZCI6IjZkMjJk....
		    [created] => 1508660348
		)
	 */
	public function oauthCallback_google(){
		
		$GoogleApi_service = new GoogleApi_service() ;
		if (isset($_GET['code'])) 
		//if(!$_SESSION['id_token_token'])
		{
			$access_token = $GoogleApi_service->oauthCallback_google();
			// default방법
			//$profile= $GoogleApi_service->GetUserProfileInfo();
			//echo '<pre>';print_r($access_token);exit;
			//if(empty($access_token)) $this->moveParent_win('') ;
		}
		$profile = $GoogleApi_service->getUserProfile();
		if( $this->authen_successValidate($profile) )
		{
			//$profile = $GoogleApi_service->getUserProfile();
			/*저장*/$this->loginApi_process($profile) ;
		}
		else{
			//$this->phone_auth_page("google");
			$this->authen_page("google", $profile) ;
		}
	}
	/**
	 * @desc [Facebook-Api] Login Callback
	 * 
	 * @param string $_GET["code"]
	 */
	public function oauthCallback_facebook(){
		$FacebookApi_service = new FacebookApi_service() ;
		$access_token = $FacebookApi_service->oauthCallback_facebook() ;
		
		if(empty($access_token)) $this->moveParent_win('') ;
		
		$profile = $FacebookApi_service->getUserProfile();
		if( $this->authen_successValidate($profile) )
		{
			//$profile = $FacebookApi_service->getUserProfile();
			/*저장*/$this->loginApi_process($profile) ;
		}
		else{
			$this->authen_page("facebook", $profile);
		}
	}
	/**
	 * @desc [Instrgram-Api] Login Callback
	 *
	 * @param string $_GET["code"]
	 */
	public function oauthCallback_instagram()
	{
		/* echo '<pre>';print_r($_SERVER["REQUEST_METHOD"]);
		 echo '<pre>';print_r($_REQUEST); */
		//exit;
		if( !empty($_GET["code"]) )
		{
			$InstagramApi_service= new InstagramApi_service();
			//if ( !isset($InstagramApi_service->Token) || empty($InstagramApi_service->Token) )
			//{
				$access_token = $InstagramApi_service->oauthCallback_instagram();
			//}
			$profile = $InstagramApi_service->getUserProfile();
			if( $this->authen_successValidate($profile) )
			{
				//$profile = $GoogleApi_service->getUserProfile();
				/*저장*/$this->loginApi_process($profile) ;
			}
			else{
				$this->authen_page("instagram", $profile);
			}
		}
		else{
			$this->moveParent_win('') ;
		}
	}
	/**
	 * @desc [Kakao-Api] Login Callback
	 * 
	 * @param string $_GET["code"]
	 */
	public function oauthCallback_kakao()
	{
		/* echo '<pre>';print_r($_SERVER["REQUEST_METHOD"]);
		echo '<pre>';print_r($_REQUEST);
		exit; */
		$KakaoApi_service = new KakaoApi_service() ;
		if ( !isset($KakaoApi_service::$Token) || empty($KakaoApi_service::$Token) )
		{
			$access_token = $KakaoApi_service->oauthCallback_kakao();
			// default방법
			//$profile= $GoogleApi_service->GetUserProfileInfo();
			//if(empty($access_token)) $this->moveParent_win('') ;
		}
		$profile = $KakaoApi_service->getUserProfile();
		//echo '<pre>';print_r($profile);exit;
		if( $this->authen_successValidate($profile) )
		{
			//$profile = $GoogleApi_service->getUserProfile();
			/*저장*/$this->loginApi_process($profile) ;
		}
		else{
			$this->authen_page("kakao", $profile);
		}
	}
	/**
	 * @desc [Naver-Api] Login Callback
	 * 
	 * @param string $_GET["code"]
	 * @param string $_GET["state"]
	 */
	public function oauthCallback_naver()
	{
		/* echo '<pre>';print_r($_SERVER["REQUEST_METHOD"]);
		echo '<pre>';print_r($_REQUEST); */
		//exit;
		if( !empty($_GET["code"]) && !empty($_GET["state"]) )
		{
			$NaverApi_service = new NaverApi_service() ;
			if ( !isset($NaverApi_service->Token) || empty($NaverApi_service->Token) )
			{
				$access_token = $NaverApi_service->oauthCallback_naver();
				// default방법
				//$profile= $GoogleApi_service->GetUserProfileInfo();
				//if(empty($access_token)) $this->moveParent_win('') ;
			}
			$profile = $NaverApi_service->getUserProfile();
			if( $this->authen_successValidate($profile) )
			{
				//$profile = $GoogleApi_service->getUserProfile();
				/*저장*/$this->loginApi_process($profile) ;
			}
			else{
				$this->authen_page("naver", $profile);
			}
		}
		else{
			$this->moveParent_win('') ;
		}
	}
	
	/**
	 * @desc 인증코드 Encode
	 *
	 * @param array|mixed $data
	 * @return string|mixed
	 */
	private function authen_Encrypt( $data )
	{
		$Pcrypt = &WebApp::singleton('Pcrypt');
		return SMpcrypt_encode( $data );
	}
	/**
	 * @desc 인증코드 Decode
	 *
	 * @param string $data
	 * @return array
	 */
	private function authen_Decrypt( $data )
	{
		$Pcrypt = &WebApp::singleton('Pcrypt');
		return SMpcrypt_decode( $data );
	}
	/**
	 * @desc [DB-회원sns] Sms 또는 Email 인증유무 검사
	 * 
	 * @param string $mbrSerial ( member TB의 P.K )
	 * 
	 * @return boolean (인증: true, 미인증: false)
	 */
	private function authen_successValidate( $profile )
	{
	    $exist = 0;
		if( !empty($profile) )
		{
			$this->setTableName("member_sns");
			$exist = $this->count('oauth_uid', array(
					"oauth_provider" => $profile['provider'],
					"oauth_uid" => $profile['id'],
					"authen" => 1
			         //"auth_".$kind." !=''"
			)) ;
		}
		if( (int)$exist ) return true ;
		else return false ;
	}
	/**
	 * @desc [ 인증 ] 인증번호 전송
	 * 
	 * @param string $kind ( sms인증: 'sms', 이메일인증: 'email' )
	 * @param array $params 파라미터
	 * @return mixed
	 */
	private function authen_send( $kind, $params )
	{
	    $res = NULL ;
	    $method_name = "authen_send_" .(string)$kind ;
	    
	    if( method_exists($this, $method_name) &&
	        is_callable(array($this, $method_name)) )
	    {
	        $res = $this->{$method_name}( $params );
	    }
	    return $res ;
	}
	/**
	 * @desc [ 인증코드 전송 ] Email 전송
	 *
	 * @param string $To_email (수신 Email)
	 * @return bool (성공: true , 실패: false)
	 */
	private function authen_send_email( $To_email )
	{
		if( !filter_var( (string) $To_email, FILTER_VALIDATE_EMAIL) ){
			throw new \Exception("Email 주소를 정확히 입력해주세요.", 501) ;
		}
		
	    $AuthCode= mt_rand();
	    $AuthCode = substr($AuthCode, 0, 7);

	    $data = array(
	           'kind'=>'email', 
	           'email'=>$To_email, // 수신 메일주소
	           'code'=> $AuthCode // 인증코드
	    ) ;
	    $data = $this->authen_Encrypt($data) ;

	    if( $this->authen_save($data) )
	    {
    	    //---------------------------
    	    $email_auth_file = "datas/templates/email/member/join_auth_mail.html" ;

    	    if(is_file($email_auth_file))
    	    {
    	        $content = file_get_contents($email_auth_file);
    	        /*호스트 url*/$content = str_replace("{HOST}", "http://".HOST, $content) ;
    	        /*인증번호*/$content = str_replace("{AUTHEN_CODE}", $AuthCode, $content) ;

    	        $post_data = array(
    	            //'from'=> $this->get_mail_From(),
    	            'to'=> array("email"=>$To_email, "name"=>""),
    	            'subject' => "[".CNAME."] Email 인증",
    	            'html'=>$content
    	        );
    	        $res = $this->mail_send($post_data);
    	        //Success
    	        if($res)
    	        {
    	            return true ;
    	        }
    	        // Faild
    	        else{
    	            return false ;
    	        }
    	    }
	    }
	    //---------------------------
	    return false ;
	}
	/**
	 * @desc [ 인증코드 전송 ] SMS 전송
	 * 
	 * @param string $phone_number (폰번호)
	 * @return bool (성공: true , 실패: false)
	 */
	private function authen_send_sms($phone_number)
	{
	    // 인증번호 생성
	    $AuthCode= mt_rand();
	    $AuthCode = substr($AuthCode, 0, 5); //인증번호는 02818입니다.
	    //$msg = "The certification number is [ ".$AuthCode." ]." ;
	    $msg = "인증번호는 [ ".$AuthCode." ] 입니다." ;
	    
	    $data = array(
	           'kind'=>'sms', 
	           'phone'=>$phone_number, // 수신 폰번호
	           'code'=> $AuthCode // 인증번호
	    ) ;

	    $data = $this->authen_Encrypt($data) ;
	    
	    if( $this->authen_save($data) )
	    {
    	    // 인증번호 전송
    	    $SMS = new Sms_service();
    	    
    	    $res = $SMS->promptText($msg, $phone_number);
	    
    	    //Success
    	    if($res)
    	    {
    	        return true ;
    	    }
    	    // Faild
    	    else{
    	        return false ;
    	    }
	    }

	    return false ;
	}
	/**
	 * @desc [ 인증 ] 인증정보 저장
	 * 
	 * @param array|mixed $data
	 * @return bool (성공: true , 실패: false)
	 */
	private function authen_save( $data )
	{
	    if(!empty($data)) {
	    	setcookie(self::$authVar, $data, time() + (self::$authen_expire_minute * 60), "/", HOST);
	        return true ;
	    }
	    else{
	        return false ;
	    }
	}
	/**
	 * @desc 인증정보 삭제
	 * 
	 * @return void
	 */
	private function authen_delete()
	{
	    if( !empty($_COOKIE[self::$authVar]) ) {
    	    setcookie(self::$authVar, '', 0, "/", HOST);
    	    unset($_COOKIE[self::$authVar]);
	    }
	}
	/**
	 * @desc 인증코드 검증
	 * 
	 * @throws \Exception
	 * 
	 * @param string $kind 인증방식( "email" or "sms" )
	 * @param array $authenData 인증받을 정보 array("code"=>??, .....)
	 * @param string $input_code
	 * 
	 * @return bool (성공: true , 실패: false)
	 */
	private function authen_code_compare($kind, $authenData, $input_code)
	{
	    if($authenData['kind'] != $kind)
	    {
	        $this->authen_delete() ; // 인증정보 초기화 ( 기존 인증방식과 다른경우 )
	        throw new \Exception("인증방식이 변경되어\n\n인증코드를 다시 받으세여.", 400) ;
	    }
	    // 인증 성공시
	    else if( $authenData['kind'] == $kind && $authenData['code'] == $input_code)
	    {
	        $authenData['success'] = 1; // 인증성공 코드
	        $authenData = $this->authen_Encrypt($authenData) ;
	        
	        if( !empty($authenData) ) {
	            setcookie(self::$authVar, $authenData, 0, "/", HOST);
	        	return true ;
	        }
	    }
	    
	    return false ;
	}
	
	/**
	 * @desc Email 인증, 핸드폰(sms) 인증 페이지
	 * 
	 * @param string $sdk_kind (google, facebook, kakao....)
	 * @param array|null $user_profile 유저 프로파일(profile) 정보
	 */
	private function authen_page($sdk_kind, $user_profile=NULL)
	{
	    $this->authen_delete() ;
	    
	    //echo '<pre>';print_r($inputVar);
    	/**
    	 * 필수 입력정보 없으면 입력받음
    	 */
	    $inputVar = array() ;
	    
	    // userid (email)
	    if( !isset($user_profile['email']) || empty($user_profile['email']) ){
	        $inputVar["email"]=1 ;
	    }
	    // 이름(name) - 한번더 정확히 입력받기 위해
	    $inputVar["name"]=1 ;
    	/* if( !isset($user_profile['name']) || empty($user_profile['name']) ){
    		$inputVar["name"]=1 ;
    	} */
    	// 성별(gender)
    	if( !isset($user_profile['gender']) || empty($user_profile['gender']) ){
    		$inputVar["gender"]=1 ;
    	}
    	//-----------------
	    if( !empty($inputVar) ){
    		$sdk = array("sdk" => $sdk_kind, "necessary_input" => $inputVar) ;
	    }else{
	    	$sdk = array("sdk" => $sdk_kind) ;
	    }
	    $sdk = $this->authen_Encrypt($sdk) ;
	    
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString, //Func::QueryString_filter(),
						'Action' => "loginSdk",
						//'CODE' => ''
						'redirectURL' => Func::QueryString_filter(WebAppService::$queryString, TRUE),//$_GET['redir'], //Func::QueryString_filter(),
				),
				'SDK' => $sdk,
		        'USER_PROFILE' => $user_profile,
				"INPUT_VAR" => $inputVar,
				"INPUT_VAR_JS" => "'".implode("','", array_keys($inputVar))."'" // 필수항목( javascript 체크 )
				
				//'LOGIN_GOOGLE_API' => 'https://accounts.google.com/o/oauth2/v2/auth?scope=' . urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/plus.me') . '&redirect_uri=' . urlencode(CLIENT_REDIRECT_URL) . '&response_type=code&client_id=' . CLIENT_ID . '&access_type=online'
		)) ;
		//$this->WebAppService->Output( Display::getTemplate('html/yeppu/members/phone_auth.html'), "generic");
		$this->WebAppService->Output( Display::getTemplate('html/yeppu/members/join_auth.html'), "generic");
		$this->WebAppService->printAll();
	}
	/**
	 * @desc Api 로그인 처리
	 */
	public function loginSdk()
	{
		//------------------------------------------
		// 인증체크
		//------------------------------------------
		//$smsAuth = $this->check_api_phoneAuth();
		try {
			// 임시 저장정보와 입력정보가 일치하는지 검증
			if($_REQUEST['auth_kind'] == "email") $var = $_POST['memail'] ;
			else if($_REQUEST['auth_kind'] == "sms") $var = $_POST['mphone'] ;
			$auth_data = $this->authen_validate($_COOKIE[self::$authVar], $var);
		}
		catch ( \Exception $e ){
			//WebApp::moveBack('휴대전화 인증 해주세요.');
			$this->WebAppService->assign( array(
					"error"=>$e->getMessage(),
					"error_code" => $e->getCode()
			) );
		}
		//------------------------------------------
		// Api 회원 프로파일(Profile) 정보 가져옴
		//------------------------------------------
		$Api = $this->authen_Decrypt( (string)$_POST['sdk'] ) ;
		if(empty($Api)) $this->moveParent_win('', "Api를 알수 없습니다.") ;
		
		if($Api['sdk'] == "google")
		{
		    $GoogleApi_service = new GoogleApi_service() ;
			//if(!empty($_SESSION['id_token_token']) && is_array($_SESSION['id_token_token']))
			if( !empty($_SESSION['api_token'][$GoogleApi_service::$Provider]) )
			{
				$profile = $GoogleApi_service->getUserProfile();
			}
		}
		else if($Api['sdk']== "facebook"){
			
		    $FacebookApi_service = new FacebookApi_service() ;
			//if ( !empty($_SESSION['fb_access_token']) )
			if( !empty($_SESSION['api_token'][$FacebookApi_service::$Provider]) )
			{
				$profile = $FacebookApi_service->getUserProfile();
			}
		}
		else if($Api['sdk'] == "instagram"){
			
			$InstagramApi_service= new InstagramApi_service() ;
			if( !empty($_SESSION['api_token'][$InstagramApi_service::$Provider]) )
			{
				$profile = $InstagramApi_service->getUserProfile();
			}
		}
		else if($Api['sdk'] == "kakao"){
			
			$KakaoApi_service = new KakaoApi_service() ;
			if( !empty($_SESSION['api_token'][$KakaoApi_service::$Provider]) )
			{
				$profile = $KakaoApi_service->getUserProfile();
			}
		}
		else if($Api['sdk'] == "naver"){
			
			$NaverApi_service = new NaverApi_service() ;
			if( !empty($_SESSION['api_token'][$NaverApi_service::$Provider]) )
			{
				$profile = $NaverApi_service->getUserProfile();
			}
		}
		else{
			$this->moveParent_win('', "Api를 알수 없습니다.") ;
		}
		
		//-------------------------------------
		if($auth_data["kind"] == "email")
		{
			/*email주소*/$profile['email'] = $auth_data['email'] ;
		    /*email인증 코드*/$profile['authen_email'] = $auth_data['email'] ;
		}
		else if($auth_data["kind"] == "sms"){
			/*폰번호*/$profile['phone'] = $auth_data['phone'] ;
		    /*폰인증 코드*/$profile['authen_phone'] = $auth_data['phone'] ;
		}
		
		/**
		 * 필수입력 항목이 있는경우
		 */
		if( !empty($Api['necessary_input']) )
		{
			try 
			{
				foreach($Api['necessary_input'] as $item => $data)
				{
					if($item == "name"){
						
						$error = $this->getValidate( array(
								"musername",
						)) ;
						if( !empty($error) ){
							throw new \Exception("이름 정보가 없습니다.", 401) ;
						}else{
							$profile['name'] = (string)$_POST['musername'] ;
						}
						/* if( !empty((string)$_POST['musername']) ){
							$profile['name'] = (string)$_POST['musername'] ;
						}else{
							throw new \Exception("이름 정보가 없습니다.", 401) ;
						} */
					}
					else if($item == "email"){
							$profile['email'] = (string)$_POST['memail'] ;
					}
					else if($item == "gender"){
						if( (int)$_POST['msex'] ){ // Male: 1 / Female: 2
							$profile['gender'] = (int)$_POST['msex'] ;
						}else{
							throw new \Exception("성별(gender) 정보가 없습니다.", 401) ;
						}
					}
				}
				
			} 
			catch (Exception $e) {
				//$e->getCode()
				$this->moveParent_win('', $e->getMessage()) ;
			}
			
		}
		/* echo '<Pre>';print_r($Api);
		echo '<Pre>';print_r($profile);exit; */
		/*저장*/$this->loginApi_process($profile) ;
		
	}
	/**
	 * @desc Google Login 페이지이동
	 */
	public function loginGoogle()
	{
		$GoogleApi_service = new GoogleApi_service() ;
		$GoogleApi_service->SignOut();
		if($authUrl = $GoogleApi_service->SignIn()){
			header('Location:'.$authUrl) ;	exit;
		}
	}
	/**
	 * @desc Facebook Login 페이지이동
	 */
	public function loginFacebook()
	{
		$FacebookApi_service = new FacebookApi_service() ;
		//$FacebookApi_service->SignOut();
		if($authUrl = $FacebookApi_service->Signin()){
			header('Location:'.$authUrl) ;	exit;
		}
	}
	/**
	 * @desc Instagram Login 페이지이동
	 */
	public function loginInstagram()
	{
		$InstagramApi_service= new InstagramApi_service();
		if($authUrl = $InstagramApi_service->Signin()){
			header('Location:'.$authUrl) ;	exit;
		}
	}
	/**
	 * @desc Kakao Login 페이지이동
	 */
	public function loginKakao()
	{
		//echo '<pre>';print_r($_SESSION);exit;
		$KakaoApi_service = new KakaoApi_service() ;
		//$KakaoApi_service->SignOut();exit;
		if($authUrl = $KakaoApi_service->Signin()){
			header('Location:'.$authUrl) ;	exit;
		}
		//------------------------
		$profile = $KakaoApi_service->getUserProfile();
		//echo '<pre>';print_r($profile);
		if( is_array($profile) && !empty($profile) )
		{
		    
			if( $this->authen_successValidate($profile) )
			{
				/*저장*/$this->loginApi_process($profile) ;
			}
			else{
				$this->authen_page("kakao", $profile);
			}
		}
		else{
			$this->moveParent_win('') ;
		}
		
	}
	/**
	 * @desc Kakao Login 페이지이동
	 */
	public function loginNaver()
	{
	    
	    $NaverApi_service = new NaverApi_service() ;
	    /* $NaverApi_service->Disconnect();
	    echo '1<pre>';print_r($NaverApi_service->Token);
	    echo '2<pre>';print_r($_SESSION);exit; */
	    
	    //$NaverApi_service->SignOut();exit;
	    if($authUrl = $NaverApi_service->Signin()){
	    	header('Location:'.$authUrl) ;	exit;
	    }
	    //------------------------
	    $profile = $NaverApi_service->getUserProfile();
	    if( is_array($profile) && !empty($profile) )
	    {
	    	if( $this->authen_successValidate($profile) )
	    	{
	    		/*저장*/$this->loginApi_process($profile) ;
	    	}
	    	else{
	    		$this->authen_page("naver", $profile);
	    	}
	    }
	    else{
	    	$this->moveParent_win('') ;
	    }
	}
	public function test()
	{
	    $a = new Gajija\service\Api\GoogleUrlshortenerApi_service() ;
	    echo $a->shortURL();
	}
	public function login()
	{
		if( REQUEST_METHOD == 'GET')
		{
				/* $GoogleApi_service = new GoogleApi_service() ;
				$GoogleApi_service->SignOut();
				if($google_authUrl = $GoogleApi_service->SignIn()){
					$this->WebAppService->assign(array("LOGIN_GOOGLE_API" => $google_authUrl)) ;
				}
				//#################################
				
				$FacebookApi_service = new FacebookApi_service() ;
				$FacebookApi_service->SignOut();
				if($facebook_authUrl = $FacebookApi_service->Signin()){
					$this->WebAppService->assign(array("LOGIN_FACEBOOK_API" => $facebook_authUrl)) ;
				} */
			
				/* require_once _APP_LIB."Api/facebook/vendor/autoload.php" ;
				$fb = new Facebook([
				//$fb = new Facebook\Facebook([
						'app_id' => '1553794354701126', // Replace {app-id} with your app id
						'app_secret' => '185be3358f411170bf2e28ed5bd3ed03',
						'default_graph_version' => 'v2.10',
				]);
				
				$helper = $fb->getRedirectLoginHelper();
				
				$permissions = ['email']; // Optional permissions
				//echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';exit;
				$this->WebAppService->assign(array("LOGIN_FACEBOOK_API" => $loginUrl)) ; */
		        //echo WebAppService::$queryString;
				$this->WebAppService->assign(array(
						'Doc' => array(
								'baseURL' => WebAppService::$baseURL,
								'queryString' => WebAppService::$queryString, //Func::QueryString_filter(),
								//'CODE' => ''
								'redirectURL' => Func::QueryString_filter(WebAppService::$queryString, TRUE),//$_GET['redir'], //Func::QueryString_filter(),
						),
						'MENU_TOP' => &self::$menu_datas['childs'],
						'LOGIN_GOOGLE_API' => '/Member/loginGoogle',
						'LOGIN_FACEBOOK_API' => '/Member/loginFacebook',
						'LOGIN_KAKAO_API' => '/Member/loginKakao',
                        'LOGIN_NAVER_API' => '/Member/loginNaver',
						'LOGIN_INSTAGRAM_API' => '/Member/loginInstagram'
						
						
				)) ;
				$this->WebAppService->Output( Display::getTemplate('member/login.html'), "one");
				//echo '<pre>';print_r($this->WebAppService->Display);exit;
				$this->WebAppService->printAll();
		}
		
		else if( REQUEST_METHOD == 'POST')
		{
	   	       if(REQUEST_WITH != 'AJAX') {
					header('Location:/') ;	exit;
				}
				
				$error = $this->getValidate( array(
						"muserid",
						"muserpw"
				)) ;
				if( !empty($error) ){
					$this->WebAppService->assign( array("error"=>$error) );
				}
				
				$this->setTableName("member");
				$data = $this->dataRead(array(
								"columns" => 'serial, userid, username, usernick, grade, is_admin',
								"conditions" => array(
											'userid' => (string)$_POST['muserid'],
								            'userpw' => $this->WebAppService->Strings::encrypt_sha256( trim((string)$_POST['muserpw']) ),
											'withdrawal' => 0 // 탈퇴회원이 아니면
									)
							));
				if( !empty($data) )
				{
				// login 성공
				
						$put_data = array(
								"recent_login" => time(),
								"ip" => $_SERVER['REMOTE_ADDR']
						);
						$this->setTableName("member");
						$res = $this->dataUpdate($put_data, array("serial" => (int)$data[0]['serial'])) ;
						//----------------------------------
						/* $this->setTableName("member_grade");
						$grade_name = $this->dataRead(array(
									"columns" => 'grade_name',
									"conditions" => 'grade_code='.(int) $data[0]['grade']
								)); */
						//----------------------------------
						$this->add_session( array(
								'mbrSerial' => (int) $data[0]['serial'],
								'mbrId' => (string) $data[0]['userid'],
								'mbrName' => (string) $data[0]['username'],
								'mbrNick' => (string) $data[0]['usernick'],
								'adm' => (int) $data[0]['is_admin'],
								'mbrGrade' => (int) $data[0]['grade'],
								'adm' => (int) $data[0]['is_admin']
								//'mbrGradeName' => (string) $grade_name[0]['grade_name']
						)) ;
						
						/* if($_POST['redir']) header('Location:'. urldecode($_POST['redir']) ) ;
						else header('Location:/') ; */
						if(!empty($_POST['redir'])) $url = urldecode(filter_var($_POST['redir'], FILTER_SANITIZE_URL));
						else $url = '/';
						
						$this->WebAppService->assign( $url ) ;
						exit;

				}else{
					// login 실패 
					//Id or Password do not match.
					$this->WebAppService->assign(array('error'=>'아이디 또는 비밀번호가 일치하지 않습니다.'));
				}

		}
	}
	/**
	 * @desc 회원 로그아웃
	 *
	 * @return void
	 */
	public function logout()
	{
	    /* unset(
	        $_SESSION["MBRSERIAL"],
	        $_SESSION["MBRID"],
	        $_SESSION["MBRNAME"],
	        $_SESSION["MBRGRADE"],
	        $_SESSION["MBRGRADENAME"],
	        $_SESSION["api_token"],
	        $_SESSION["REMOTE_ADDR"],
	        $_SESSION["FBRLH_state"]
	        );
	    echo '<pre>';print_r($_SESSION);exit; */
		
		try {
			$GoogleApi_service = new GoogleApi_service() ;
			$GoogleApi_service->SignOut();
		} catch (Exception $e) {
		}

		try {
			$FacebookApi_service = new FacebookApi_service() ;
			$FacebookApi_service->SignOut();
		} catch (Exception $e) {
		}
		
		try {
			$KakaoApi_service = new KakaoApi_service() ;
			$KakaoApi_service->SignOut();
		} catch (Exception $e) {
		}


		//$NaverApi_service = new NaverApi_service() ;
		//$NaverApi_service->SignOut();
		
		/* if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
					);
		} */

		//$_SESSION = array();
		//unset( $_SESSION );
		//session_destroy();

		unset( 
		    $_SESSION["MBRSERIAL"], 
		    $_SESSION["MBRID"], 
		    $_SESSION["MBRNAME"],
		    $_SESSION["MBRGRADE"],
		    $_SESSION["MBRGRADENAME"],
			$_SESSION["ADM"],
		    $_SESSION["api_token"],
		    $_SESSION["REMOTE_ADDR"]
		    );
		session_destroy();
		header('Location:/') ;
		exit;
	}
	
	/**
	 * @desc 비밀번호 찾기
	 */
	public function idpw()
	{
		if( REQUEST_METHOD == 'GET')
		{
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'queryString' => WebAppService::$queryString, //Func::QueryString_filter(),
							//'Action' => "write"//$this->routeResult['action']
					),
					'MENU_TOP' => &self::$menu_datas['childs']
			)) ;
			$this->WebAppService->Output( Display::getTemplate('member/password.html'), "one");
			$this->WebAppService->printAll();
		}
	}
	
	/**
	 * @desc 메일전송 처리
	 * 
	 * @param array $data ('from'=>??, 'to'=>??, 'subject'=>??, 'html'=>??.....)
	 * 
	 * @example $data = array(
					'from'=> array('email'=>이메일주소, 'name'=>발신자명), // 발신자
					'to'=> array('email'=>이메일주소, 'name'=>수신자명), // 수신자
					'subject'=>$subject, // 제목
					'html'=>$html, // 내용(html)
					'text'=>$text, // 내용(텍스트)
					'attachment' => 'image/2.png' // 첨부파일
				) ;
	 * 
	 * @return bool
	 */
	private function mail_send( $post_data )
	{
		//$res = $this->sendMailByMailgun( $post_data) ;
		if( ! $this->MailService instanceof Mail_service) $this->MailService = new Mail_service();
		
		if( !empty($post_data["from"]) ) $post_data["from"] = $this->MailService->get_from_mail();
		
		try
		{
			$res = $this->MailService->sendMailByPHPMailer( $post_data ) ;
			return $res ;
		} 
		catch ( \Exception $e ){
			$this->WebAppService->assign( array(
					"error"=>$e->getMessage(),
					"error_code" => $e->getCode()
			) );
		}
		
		
	}
	/**
	 * @desc [비밀번호 찾기] 인증코드 검증
	 *
	 * @tutorial 검증위치 : 메일수신 후 링크타고 접근한 경우, 비밀번호 업데이트
	 *
	 * @param string $Authen (encoding data)
	 *
	 * @return array $dec_data (decoding data)
	 */
	private function pwdValidation( $Authen )
	{
	    if( empty($Authen) ) {
	    	WebApp::redirect('/', '잘못된 접근입니다.') ; //The wrong approach.
	        exit;
	    }
	    
	    $dec_data = $this->decrypt_Pcrypt( (string)$Authen) ;
	    if( !is_array($dec_data) || empty($dec_data) ){
	    	WebApp::redirect('/', '잘못된 접근입니다.') ; //The wrong approach.
	    }
	    
	    //$this->Req_pwdCall()의 $auth_data 변수 참조
	    if( time() > $dec_data['expire'] ){
	    	WebApp::redirect('/Member/idpw', '시간이 초과되었습니다..') ;     //Timed out
	    }
	    
	    return (array) $dec_data ;
	}
	/**
	 * @desc [비밀번호 찾기 #1] 요청
	 * 
	 * @tutorial 비밀번호 변경을 위해 메일발송
	 * 
	 * @param string $_POST['mail']
	 */
	public function Req_pwdCall()
	{
		if(REQUEST_WITH != 'AJAX') {
			header('Location:/') ;	exit;
		}
		
		if(empty( (string) $_POST['mail']) || !filter_var( (string) $_POST['mail'], FILTER_VALIDATE_EMAIL)){
			$this->WebAppService->assign( array("error"=>"받을 email을 정확히 입력해주세여.") );    //Please enter a valid email address.
		}
		// 회원정보
		$this->setTableName("member");
		$data= $this->dataRead(array(
				"columns"=> "serial, userid, username",
				"conditions" => array("userid" => (string) $_POST['mail'])
		));
		if( !empty($data) ){
			$data = array_pop($data) ;
			/* if( !empty($data["oauth_provider"]) ){
				$this->WebAppService->assign( array("error"=> "SNS 통해 가입한 회원입니다.\n\n 문의해주세요.") );
			} */
			
		}else{
			$this->WebAppService->assign( array("error"=>"가입한 email이 아닙니다.") );     //This is not an email I joined.
		}
		
		//템플릿 파일(template file)
		$email_pw_file = "datas/templates/email/member/password_mail.html" ;
		if(is_file($email_pw_file))
		{
				$Redirect_url = "http://".HOST."/Member/pwdChange" ;
				$auth_data = array(
						'serial' => $data['serial'], // member TB의 P.K
						'userid' => $data['userid'],
						'username' => $data['username'],
						'expire' => time() + self::$expire_time_pwd
						//'ip' => $_SERVER['REMOTE_ADDR']
				) ;
				$authen = $this->encrypt_Pcrypt( $auth_data ) ;
				$Redirect_url .= "?authen=". $authen ;
				
				$content = file_get_contents($email_pw_file);
				/*호스트 url*/$content = str_replace("{HOST}", "http://".HOST, $content) ;
				/*회원명*/$content = str_replace("{USERNAME}", $data['username'], $content) ;
				/*url*/$content = str_replace("{PWD_SET_URL}", $Redirect_url, $content) ;
				
				/* $post_data = array(
						'from'=> $this->get_from_email(),
						'to'=> $_POST['mail'],
				    'subject' => "[NOTICE ".HOST."] Password change guide.",     //비밀번호 변경 안내입니다.
						'html'=> $content
				); */
				$post_data = array(
						//'from'=> $this->MailService->get_from_mail(),
						'to'=> array("email"=>$_POST['mail'], "name"=>$data['username']),
				        'subject' => "[".CNAME."] 비밀번호 변경 안내입니다.", //Password change guide.",
						'html'=>$content
				);
				//
				$res = $this->mail_send($post_data);
				
				//Success
				if($res)
				{
					$this->WebAppService->assign( array("message" => "메일이 발송되었습니다. 메일을 확인해 주세요.") );     //We sent you an email. Please check your mail.
				}
				// Faild
				else{
					//$this->WebAppService->assign( array("error"=>"[".$res['http_code']."] 메일 전송 실패") );
					$this->WebAppService->assign( array("error" => "메일 전송 실패") );    //Failed to send mail
				}
		}
		else{
			$this->WebAppService->assign( array("error"=>"파일을 찾을 수 없습니다.") );     //File not found.
		}
	}
	/**
	 * @desc [비밀번호 찾기 #2] 비밀번호 변경 페이지
	 * 
	 * @tutorial 메일수신 후 링크타고 접근한 경우
	 * 
	 * @param string $_GET['authen']
	 */
	public function pwdChange()
	{
		$dec_data = $this->pwdValidation( (string)$_GET['authen'] ) ;
		
		$this->setTableName("member");
		$data = $this->dataRead( array(
				"columns"=> 'username',
				"conditions" => array(
						"serial" => (int) $dec_data['serial'],
						"userid" => (string) $dec_data['userid']
				) ));
		if( empty($data) ) WebApp::redirect('/Member/login', '회원님의 정보를 찾을 수 없습니다.') ;   //We can not find your information.
		$data = array_pop($data);
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString,
						'Action' => "pwdUpdate"
				),
				'MENU_TOP' => &self::$menu_datas['childs'],
				'SHOP_COMMON' => $this->global_shopAction(), // ◆공용 데이타◆ (쇼핑카트 갯수, 위시리스트 갯수.....)
				'AUTHEN_PWD' => (string) $_GET['authen'], // 메일수신해서 넘어온 값
				'DATA_MBR' => & $data // 회원정보
		)) ;
		$this->WebAppService->Output( 'html/yeppu/members/password_set.html', "main2");
		$this->WebAppService->printAll();
	}
	/**
	 * @desc [비밀번호 찾기 #3] 비밀번호 갱신(update)
	 * 
	 * @tutorial 입력한 비밀번호로 변경처리
	 * 
	 * @param string $_GET['authen']
	 */
	public function pwdUpdate()
	{
		$dec_data = $this->pwdValidation( (string)$_GET['authen'] ) ;
		
		$error = $this->getValidate( array(
				"muserpw",
				"muserpw_confirm"
		)) ;
		if( !empty($error) ){
			WebApp::moveBack($error);
		}
		if($_POST['muserpw'] != $_POST['muserpw_confirm']){
			WebApp::moveBack('비밀번호 확인은 비밀번호와 정확히 일치해야합니다.'); //Confirm password must be exactly equivalent to Password.
		}
		
		$this->setTableName("member");
		$data_exist = $this->count( 'serial',	 array(
						"serial" => (int) $dec_data['serial'],
						"userid" => (string) $dec_data['userid']
				));
		if( (int) $data ) WebApp::redirect('/Member/login', '회원님의 정보를 찾을 수 없습니다.') ;    //We can not find your information.
		
		$res = $this->dataUpdate( 
		                        array( "userpw" => $this->WebAppService->Strings::encrypt_sha256(trim((string)$_POST['muserpw'])) ),
								array( 
										"serial" => (int) $dec_data['serial'],
										"userid" => (string) $dec_data['userid']
								)) ;
		//if($res){
		WebApp::redirect('/Member/login', '비밀번호가 변경되었습니다.') ;    //Your password has been changed.
		//}
		
	}
	
	/**
	 * @desc [회원가입 인증 1] SMS 발송 
	 * @deprecated
	 */
	public function Req_smsAuthSend(){
		if(REQUEST_WITH != 'AJAX') {
			header('Location:/') ;	exit;
		}
		
		if (!(int) $_POST['phone']) {
		    $this->WebAppService->assign( array("error"=>"휴대 전화 번호를 올바르게 입력하십시오.") ); // Please enter your cell phone number correctly.
		}
		$AuthCode= mt_rand();
		$AuthCode = substr($AuthCode, 0, 5);
		//인증번호는 02818입니다.
		$msg = "The certification number is [ ".$AuthCode." ]." ;
		
		$Code = array('phone'=>$_POST['phone'], 'code'=> $AuthCode) ;
		$Pcrypt = &WebApp::singleton('Pcrypt');
		$Code = SMpcrypt_encode($Code);

		setcookie(self::$authVar, $Code, time() + (self::$authen_expire_minute * 60), "/", HOST);

		$SMS = new Sms_service();
		$res = $SMS->promptText($msg, $_POST['phone']);
		echo $res;
	}
	/**
	 * @desc [회원가입 인증 1] e메일 또는 sms 발송
	 */
	public function Req_AuthenSend(){
	    if(REQUEST_WITH != 'AJAX') {
	        header('Location:/') ;	exit;
	    }
	   /*  if($_POST['kind'] == "sms"){
	        $this->WebAppService->assign( array("error"=>"폰인증은 서비스 준비중입니다.") ) ;
	    } */
	    //echo '<pre>';print_r($_POST) ;exit;
	    if($_POST['kind'] == "email" || $_POST['kind'] == "sms")
	    {
	        try
	        {
	        	$this->authen_delete() ;
	            $res = $this->authen_send($_POST['kind'], $_POST['params']) ;
	            
	            if($res) $this->WebAppService->assign($res) ;
	            else $this->WebAppService->assign( array("error"=>"인증코드 전송 실패하였습니다..") ) ;
	        }
	        catch ( \Exception $e ){
	            $this->WebAppService->assign( array(
	                "error"=>$e->getMessage(),
	                "error_code" => $e->getCode()
	            ) );
	        }
	    }
	    echo 0;
	}
	/**
	 * @desc [회원가입 인증 2] Email, SMS 수신코드 검증
	 * 
	 * @throws \Exception
	 * 
	 * @param string $_POST['kind'] "email" or "sms"
	 * @param int|string $_POST['code'] 인증코드
	 */
	public function Req_AuthenConfirm(){
		if(REQUEST_WITH != 'AJAX') {
			header('Location:/') ;	exit;
		}
		//-----------------------
		if( !isset($_COOKIE[self::$authVar]) || empty($_COOKIE[self::$authVar]) ){
		    $this->WebAppService->assign( array("error"=>"인증코드를 받으세요.") ); // Please enter your verification number.
		}
		if ( empty($_POST['code']) ) {
		    $this->WebAppService->assign( array("error"=>"인증번호를 입력해주세요.") ); // Please enter your verification number.
		}
		//-----------------------
		
		$AuthData = $this->authen_Decrypt($_COOKIE[self::$authVar]) ;
		
		// 인증 성공시
		try 
		{
		    if( $this->authen_code_compare($_POST['kind'], $AuthData, $_POST['code']) )
		    {
		        $this->WebAppService->assign('000');
		    }
		    //인증 실패시
		    else{
		        //The authentication number is different. Please re-enter.
		        throw new \Exception("인증 번호가 다릅니다. 다시 입력하십시오.", 401) ;
		    }
		}
		catch ( \Exception $e ){
		    $this->WebAppService->assign( array(
		        "error"=>$e->getMessage(),
		        "error_code" => $e->getCode()
		    ) );
		}
		
		//setcookie("smsAuth", "", time()+61, "/Member/", HOST, 1);
	}
	/* private function get_mcode()
	{
		$this->setTableName("menu");
		$data = $this->dataRead( array(
				"columns"=> '*',
				"conditions" => array("serial" => $_SESSION['MBRSERIAL'])
		));
		//if( empty($data) ) WebApp::moveBack();
	} */
	/**
	 * @desc [userid] 회원 아이디(email) 사용중인지 체크
	 */
	public function Req_useridExist(){
		if(REQUEST_WITH != 'AJAX') {
			header('Location:/') ;	exit;
		}
		$error = $this->getValidate( array(
				"muserid"
		)) ;
		if( !empty($error) ){
			$this->WebAppService->assign( array("error"=>$error) );
		}
		
		$this->setTableName("member");
		$exist = $this->count( 'serial', array(
				"userid"=>$_POST['muserid']
		) ) ;
		if( (int)$exist ) $this->WebAppService->assign(2);//$this->WebAppService->assign(array("error" => "The email is already in use. Please check again.") );
		else $this->WebAppService->assign(1);
	}
	/**
	 * @deprecated
	 */
	public function Req_CaptchaConfirm()
	{
	    exit;
		//echo '<pre>';print_r($_POST) ;
		//$_POST['g-recaptcha-response']
		if( ! $this->captcha_validate($_POST['captcha']) ){
			//WebApp::moveBack('로봇이 아니면 체크해주세요.');
			$this->WebAppService->assign( array("error"=>"로봇이 아니면 체크해주세요.") ); // Please enter your verification number.
		}
		$this->WebAppService->assign(true);
	}
	/**
	 * @desc 회원가입
	 */
	public function join()
	{   
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => Func::QueryString_filter(),
						'Action' => "write"//$this->routeResult['action']
				),
				'MENU_TOP' => &self::$menu_datas['childs'],
				'CAPTCHA_KEY' => Captcha_service::$apiKey[HOST]['site_key']
		)) ;
		$this->WebAppService->Output( Display::getTemplate('member/join.html'), "one");
		$this->WebAppService->printAll();

	}
	public function joinAdd()
	{
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => Func::QueryString_filter(),
						'Action' => "writeAdd"//$this->routeResult['action']
				),
				'MENU_TOP' => &self::$menu_datas['childs']
		)) ;
		$this->WebAppService->Output( 'html/yeppu/members/join_add.html', "sub");
		
		$this->WebAppService->printAll();
		
	}
	/**
	 * @desc CAPTCHA 인증
	 * 
	 * @param string $recaptcha_response
	 * @return bool
	 */
	private function captcha_validate($captcha_response)
	{
		if(!empty($captcha_response))
		{
			$Captcha_service = new Captcha_service() ;
			$res = $Captcha_service->send( $captcha_response) ;
			return $res ;
		}
		return false ;
	}
	/**
	 * @desc 유효성 검사
	 *
	 * @param array $vars (REQUEST 변수)
	 * @example $vars = array('frm_mbr_id', 'frm_title'...)
	 *
	 * @return string|array
	 *
	 *      return 받는 2가지 방식
	 *      ========================
	 *      1. 문자열형 인 경우 =>
	 *                "회원아이디를 정확히 입력해주세요"
	 *      ========================
	 *      2. 배열형 인 경우 =>
	 *              array(
	 *                 "frm_mbr_id" => array( "회원아이디를 정확히 입력해주세요" ),
	 *                 "frm_title" => array( "타이틀명 을 정확히 입력해주세요" )
	 *                 );
	 */
	private function getValidate($vars)
	{
		if( is_array($vars) )
		{
			$rule = array(
					'muserid' => array(
							'label' => '아이디(Email)',
							//'rules' => 'required|alpha_numeric|min_char[5]|max_char[20]'
							//'rules' => 'required|email|min_char[5]|max_char[20]'
							'rules' => 'required|email'
							//'rules' => 'required|whitespace'
					),
					'muserpw' => array(
							'label' => '비밀번호 ',
							'rules' => 'required|min_char[6]|max_char[15]'
							//'rules' => 'required|alpha_numeric|min_char[5]|max_char[20]'
					),
					/* 'Muserpw_confirm' => array(
							'label' => 'Confirm password',
							'rules' => 'required|equal[Muserpw]'
					), */
					'musername' => array(
							'label' => '이름 ',
							'rules' => 'required|whitespace'
							//'rules' => 'required|whitespace'
					),
					'musernick' => array(
							'label' => '닉네임(별명) ',
							'rules' => 'required|whitespace'
							//'rules' => 'required|whitespace'
					),
					'mhp' => array(
							'label' => '휴대폰번호 ',
							'rules' => 'required|natural'
							//'rules' => 'required|whitespace'
					)
			) ;

			$rules = array_intersect_key($rule, array_flip($vars));
			$error = $this->WebAppService->Validate($rules, true) ;

			if( is_array($error) ) $error = array_pop($error);
			if( is_array($error) ) $error = array_pop($error);
			
			return $error ;
		}
	}
	/**
	 * @desc 세션 변수 저장
	 * @param array<key,value> $vars
	 * @return void
	 */
	private function add_session($vars)
	{
		if( is_array($vars) )
		{
			foreach( $vars as $k => $v){
				//if( !empty($v) ) 
					$_SESSION[strtoupper($k)] = $v;
			}
			if( !$_SESSION['REMOTE_ADDR'] ) $_SESSION['REMOTE_ADDR'] = getenv('REMOTE_ADDR');
		}
	}
	/**
	 * @desc SMS API 인증 체크 (인증코드 인증 성공했는지 체크)
	 * 
	 * @example array $smsAuth(
	 * 			"phone"=>0102223456(폰번호), 
	 * 			"code"=>20906(인증코드), 
	 * 			"success"=>1(성공)
	 * 
	 * @return array $smsAuth | null
	 */
	private function check_api_phoneAuth()
	{
		//------------------------------------------
		// SMS 인증체크
		//------------------------------------------
		$Pcrypt = &WebApp::singleton('Pcrypt');
		$smsAuth = SMpcrypt_decode($_COOKIE[self::$authVar]);
		
		setcookie(self::$authVar, '', 0, "/", HOST);
		unset($_COOKIE[self::$authVar]);

		if(empty($smsAuth) || $smsAuth['phone'] != $_POST['mhp'] || $smsAuth['success']!=1){			
			WebApp::moveBack('폰 인증 해주세요.');
			exit;
		}
		return $smsAuth ;
	}
	/**
	 * @desc [ 인증 ] 인증되었는지 검증
	 * 
	 * @throws \Exception
	 * 
	 * @param string $enData
	 * @param string $var [email인증] Email주소 / [sms인증] : 01012340123
	 * @return array 인증 성공한 데이타
	 * @example return array(
	 * 			"kind" => "email" or "sms"
	 * 			"email" => "abcde@abc.com"
	 * 			"phone" => "0107773333"
	 * 			"success" => 0 or 1
	 */
	private function authen_validate($enData, $var=NULL)
	{
		$data = $this->authen_Decrypt( $enData ) ;
		$this->authen_delete() ;
		
		if($data["kind"] == "email")
		{
			if( ! filter_var( (string) $var, FILTER_VALIDATE_EMAIL) ){
				throw new \Exception("Email 주소를 정확히 입력해주세요.", 401) ;
			}
			else if(empty($data['email']) || $data['email'] != $var || $data['success']!=1){
				//WebApp::moveBack('휴대전화 인증 해주세요.');
				throw new \Exception("Email 인증 해주세요.", 401) ;
			}
		}
		else if($data["kind"] == "sms")
		{
			if(empty($data['phone']) || $data['phone'] != $var || $data['success']!=1){
				//WebApp::moveBack('휴대전화 인증 해주세요.');
				throw new \Exception("폰 인증 해주세요.", 401) ;
			}
		}
		else{
			throw new \Exception("인증 되지않았습니다.", 401) ;
		}
		
		return $data ;
	}
	/**
	 * 닉네임 사용가능 유무 체크
	 * 
	 * @param string $nickname
	 * @return boolean
	 */
	private function nickname_validate( string $nickname )
	{
		if( preg_match('/(관리|운영|admin|manage|master)/i', (string)$nickname) ) return false ;
		else return true ;
	}
	/**
	 * @desc DB 저장
	 */
	public function write()
	{
		if(REQUEST_METHOD == "POST")
		{
		    
			if( ! $this->captcha_validate($_POST['g-recaptcha-response']) ){
				WebApp::moveBack('로봇이 아니면 체크해주세요.');
			}
			//echo '222<pre>';print_r($_POST);exit;
			$_POST["muserid"] = trim($_POST["muserid"]);
			$_POST["muserpw"] = trim($_POST["muserpw"]);
			$_POST["msex"] = trim($_POST["msex"]);
			$_POST["musername"] = trim(Strings::tag_remove($_POST["musername"]));
			$_POST["mhp"] = trim(Strings::tag_remove($_POST["mhp"]));
			
			$error = $this->getValidate( array(
				"muserid",
				"muserpw",
				"muserpw_confirm",
				"musername",
				"musernick",
				"mhp"
			)) ;
			if( !empty($error) ){
				WebApp::moveBack($error);
			}
			
			if($_POST['muserpw'] != $_POST['muserpw_confirm']){
				WebApp::moveBack('비밀번호 확인은 비밀번호와 정확히 일치해야합니다.'); //Confirm password must be exactly equivalent to Password.
			}
			$this->setTableName("member");
			$exist = $this->count("serial", array(
					"userid" => $_POST["muserid"]//,
					//"oauth_provider=''"
			) );
			if( (int)$exist ) WebApp::moveBack('회원아이디(Email)가 이미 존재합니다.'); // The ID(email) already exists.
			
			if( ! $this->nickname_validate($_POST["musernick"])) $this->WebAppService->assign(array('error'=>'사용할 수 없는 닉네임입니다.'));
			
			if(!empty($_POST["musernick"]))
			{
				$_POST["musernick"] = trim(Strings::tag_remove($_POST["musernick"]));
				$exist = $this->count("serial", array(
						"usernick" => $_POST["musernick"]
						//"oauth_provider=''"
				) );
				if( (int)$exist ) WebApp::moveBack('회원닉네임(별명)이 이미 존재합니다.'); // The Nickname already exists.
			}
			//------------------------------------------
			// 인증체크(email or sms)
			//------------------------------------------
			//$this->check_api_phoneAuth();
			try {
			    $auth_data = $this->authen_validate($_COOKIE[self::$authVar], (string)$_POST['muserid']);
			}
			catch ( \Exception $e ){
			    //WebApp::moveBack('휴대전화 인증 해주세요.');
			    $this->WebAppService->assign( array(
			        "error"=>$e->getMessage(),
			        "error_code" => $e->getCode()
			    ) );
			}
			//------------------------------------------
			###################################################
			$upload_options = array(
					"basedir" => self::$mbr_conf["profile"]["basedir"],
					"width_maxSize" => self::$mbr_conf["profile"]["width_Size"],
					"newFileName" => time()
			);
			$profile_photo_file = $this->profile_upload($_FILES['profile_photo'], $upload_options) ;
			###################################################
			
			$put_data = array(
					"oid" => OID,
			        "userid" => trim($auth_data["email"]), //trim($_POST["muserid"]),
			        "userpw" => $this->WebAppService->Strings::encrypt_sha256(trim((string)$_POST['muserpw'])), //$this->passwd_encrypt( trim((string)$_POST['muserpw'])),
					"sex" => $_POST["msex"],
					"username" => $_POST["musername"],
					"musernick" => $_POST["musernick"],
					"hp" => trim($_POST["mhp"]),
					"profile_photo" => $profile_photo_file,
			        "authen" => ($auth_data["email"] || $auth_data["phone"]) ? 1:0,
			        "authen_email" => trim($auth_data["email"]),
			        "authen_sms" => trim($auth_data["phone"]),
					"agree_news" => (int)$_POST["magree_news"],
					"ip" => $_SERVER['REMOTE_ADDR'],
					"regdate" => time()
			);
			
			$this->setTableName("member");
			$insert_id = $this->dataAdd( $put_data ) ;
			if($insert_id)
			{
			    // 가입축하 mail 발송
			    $this->join_MailSend(array(
			        "mail" => $_POST["muserid"],
			        "username" => $_POST["musername"]
			        //"phone" => $_POST["mhp"],
			    ));
				/* try{
					// 가입축하 mail 발송
					$this->join_MailSend(array(
							"mail" => $_POST["muserid"],
							"username" => $_POST["musername"]
							//"phone" => $_POST["mhp"],
					));
				}
				catch ( \Exception $e ){
					$this->WebAppService->assign( array(
							"error"=>$e->getMessage(),
							"error_code" => $e->getCode()
					) );
				} */
				
				$this->add_session( array(
						'mbrSerial' => $insert_id,
						'mbrId' => $_POST['muserid'],
						'mbrName' => $_POST['musername']
				)) ;
				
				//header("Location: ".WebAppService::$baseURL."/joinAdd".WebAppService::$queryString); // 리스트 페이지 이동
				header('Location:/') ;
				exit;
			}
			else{
				//Exception
			    WebApp::moveBack("저장실패~다시입력해주세요.");
			    //WebApp::moveBack("Failed to save. Please re-enter.");
			    
			}

		}
	
	}
	
	/**
	 * @desc 회원가입 메일발송
	 * 
	 * @param array $userinfo("mail" => mail, "username" => user name, "phone" => telephone)
	 */
	private function join_MailSend($userinfo)
	{
		
		$joined_mail = "datas/templates/email/member/join_mail.html" ;
		
		$content = file_get_contents($joined_mail);
		/*호스트 url*/$content = str_replace("{HOST}", "http://".HOST, $content) ;
		/*회원명*/$content = str_replace("{USERNAME}", $userinfo['username'], $content) ;
		/*메일*/$content = str_replace("{USERID}", $userinfo['mail'], $content) ;
		/*연락처*///$content = str_replace("{PHONE}", $userinfo['phone'], $content) ;
		
		$post_data = array(
				//'from'=> $this->get_mail_From(),
				'to'=> array("email"=>$userinfo['mail'], "name"=>$userinfo['username']),
		        'subject' => "[".CNAME."] 회원가입 축하드립니다.",
				'html'=>$content
		);
		$res = $this->mail_send($post_data);
		//Success
		if($res)
		{
			return true ;
		}
		// Faild
		else{
			return false ;
		}
		
	}
	/**
	 * @deprecated
	 */
	public function writeAdd()
	{
		if(REQUEST_METHOD == "POST")
		{
			if( !isset($_SESSION['MBRSERIAL']) || empty($_SESSION['MBRSERIAL']) ){
				header('Location:/') ;	exit;
			}
			$birthday = (int)$_POST["birth_year"].$_POST["birth_month"].$_POST["birth_day"] ;
			if( strlen($birthday) != 8) $birthday = 0 ;
			
			$put_data = array(
					"birthday" => (int)$birthday,
					"add_skin_type" => (int)$_POST["add_skin_type"],
					"add_skin_anxiety" => (int)$_POST["add_skin_anxiety"],
					"add_pref_brand1" => (int)$_POST["add_pref_brand1"],
					"add_pref_brand2" => (int)$_POST["add_pref_brand2"],
					"add_pref_brand3" => (int)$_POST["add_pref_brand3"]
			);
			$this->setTableName("member");
			$res = $this->dataUpdate($put_data, array('serial'=>$_SESSION['MBRSERIAL'])) ;
			
			if($res)
			{
				//WebApp::redirect(WebAppService::$baseURL."/join".WebAppService::$queryString, "저장되었습니다.") ;
				header('Location:/') ;
				exit;
			}
			else{
				//Exception
			    WebApp::moveBack("저장실패~다시입력해주세요.");
			    //WebApp::moveBack("Failed to save. Please re-enter.");			    
			}
			
		}
		
	}
	/**
	 * @desc 편집페이지
	 */
	public function edit()
	{
		if(REQUEST_METHOD=="GET")
		{
			// P.K 코드 값이 없을경우
			if( ! $_SESSION['MBRSERIAL'] )
			{	// exception
				if( ! $this->Member_service->hasLogin(array('flag'=>1, 'queryString'=>REQUEST_URI)) ) // 비동기식일 경우에 작동
					$this->WebAppService->assign(array('error'=>'로그인 후 이용해주세요.'));
				    //$this->WebAppService->assign(array('error'=>'Please try again after logging in.'));
				//exit;
			}
			
			$this->setTableName("member");
			$data = $this->dataRead( array(
					"columns"=> '*',
					"conditions" => array("serial" => $_SESSION['MBRSERIAL'])
			));
			if( empty($data) ) WebApp::moveBack();
			
			if( !empty($data[0]["profile_photo"]) ) {
				if( is_file(self::$mbr_conf["profile"]["basedir"].$data[0]["profile_photo"]) )
					$data[0]["profile_photo"] = "/".self::$mbr_conf["profile"]["basedir"].$data[0]["profile_photo"] ;
					else
						$data[0]["profile_photo"] = "" ;
			}
			
			//self::$menu_datas = $this->get_menu_top('shop_cate');

			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'queryString' => WebAppService::$queryString,
							'Action' => "update",
							"CODE" => $this->routeResult["code"]
					),
					'MENU_TOP' => &self::$menu_datas['childs'],
					'DATA' => &$data[0]
			)) ;
			//$this->page_layout
			$this->WebAppService->Output( Display::getTemplate('mypage/member_info.html'), "sub");
			//echo '<pre>';print_r($this->WebAppService->Display);
			//$this->WebAppService->Display->define('MYPAGE_TOP', Display::getTemplate("yeppu/mypage/include.top.html")) ;
			$this->WebAppService->printAll();
		}
		else{
			// exception
		}
	}

	/**
	 * @desc DB 업데이트
	 */
	public function update()
	{
		if(REQUEST_METHOD=="POST")
		{
		    $put_data = array();
		    
			$this->setTableName("member");
			$getData = $this->dataRead(array(
					"columns" => "serial, userpw, birthday, oauth_login",
					"conditions" => array(
							"serial" => (int) $_SESSION['MBRSERIAL']
							//"userpw" => $this->passwd_encrypt( (string)$_POST['muserpw_cur'] )
					)
			));
			
			if(empty($getData) || !(int)$getData[0]['serial'])
			{
				$this->WebAppService->assign(array(
						"error" => "정보를 찾을 수 없습니다."
						//"error" => "Password is wrong or information can not be found."
				));
				exit;
			}
			//$getData[0]['serial'] != $this->passwd_encrypt( (string)$_POST['muserpw_cur'] )
			
			$getData = array_pop($getData) ;

			// google, facebook login api 아니면(x)
			if($getData['userpw'] != '')
			{
			    $_POST['muserpw_cur'] = trim($_POST['muserpw_cur']) ;
			    
			    if( $getData['userpw'] != $this->WebAppService->Strings::encrypt_sha256( (string)$_POST['muserpw_cur']) ){ //$this->passwd_encrypt( (string)$_POST['muserpw_cur'] ) ){
			        $this->WebAppService->assign(array(
			            "error" => "비밀번호가 다릅니다. 다시 입력해주세요."
			            //"error" => "Password is wrong or information can not be found."
			        ));
			        exit;
			    }
			    
				//-----------------------
				// 비밀번호 변경이 있을 경우
				if( $_POST["muserpw"] )
				{
					$error = $this->getValidate( array(
							"muserpw",
							"muserpw_confirm"
					)) ;
					if( !empty($error) )
						WebApp::moveBack($error);
						
						if($_POST['muserpw'] != $_POST['muserpw_confirm']){
							WebApp::moveBack('비밀번호 확인은 비밀번호와 정확히 일치해야합니다.'); //Confirm password must be exactly equivalent to Password.
						}
						$put_data = array_merge($put_data, array("userpw"=>$this->WebAppService->Strings::encrypt_sha256( trim((string)$_POST['muserpw'])) ));//$this->passwd_encrypt( (string)$_POST['muserpw'] ))) ;
				}
				//-----------------------
			}
			// 닉네임 정보 변경시
			if( ! $this->nickname_validate($_POST["musernick"])) $this->WebAppService->assign(array('error'=>'사용할 수 없는 닉네임입니다.'));
			
			if(!empty($_POST["musernick"])){
				$_POST["musernick"] = trim(Strings::tag_remove($_POST["musernick"]));
				$exist = $this->count("serial", array(
						"serial != ".(int)$_SESSION['MBRSERIAL'],
						"usernick" => (string) $_POST["musernick"]
				) );
				
				if( (int)$exist ) WebApp::moveBack('회원닉네임(별명)이 이미 존재합니다.'); // The Nickname already exists.
				$put_data['usernick'] = $_POST["musernick"] ;
			}
			// 휴대폰번호
			if( is_numeric($_POST["mhp"]) ) $put_data['hp'] = $_POST["mhp"] ;
			
			//생일(년월일)
			$birthday = (int)$_POST["birth_year"].$_POST["birth_month"].$_POST["birth_day"] ;
			if( strlen($birthday) != 8) $birthday = 0 ;
			$put_data['birthday'] = ( !(int)$getData['birthday'] ) ? (int)$birthday : (int)$getData['birthday'];
			
			
			###################################################
			$upload_options = array(
					"basedir" => self::$mbr_conf["profile"]["basedir"],
					"width_maxSize" => self::$mbr_conf["profile"]["width_Size"],
					"newFileName" => time(),
					"USERID" => $_SESSION['MBRID']
			);
			//$profile_photo_file = $this->profile_upload($_FILES['profile_photo'], $upload_options) ;
			$profile_photo_file = $this->profile_upload($_FILES['profile_photo'], $upload_options) ;
			###################################################
			# 프로파일 이미지 변경시
			if( !empty($profile_photo_file) )
				$put_data = array_merge($put_data, array("profile_photo"=>$profile_photo_file)) ;
			
			$put_data = array_merge($put_data, array(
					"agree_news" => (int)$_POST["magree_news"]
					//"birthday" => (int)$birthday
					/* "add_skin_type" => (int)$_POST["add_skin_type"],
					"add_skin_anxiety" => (int)$_POST["add_skin_anxiety"],
					"add_pref_brand1" => (int)$_POST["add_pref_brand1"],
					"add_pref_brand2" => (int)$_POST["add_pref_brand2"],
					"add_pref_brand3" => (int)$_POST["add_pref_brand3"] */
			));
			
			//--------------
			try
			{
				$res = $this->dataUpdate($put_data,
						array(
								"serial" => $_SESSION['MBRSERIAL']
						)
				) ;
			}
			catch (BaseException $e) {
				//$e->printException('controller');
				$this->WebAppService->assign( array(
				 "error" => $e->getMessage(),
				 "error_code" => $e->getCode()
				 ));
			}
			catch (Exception $e) {
				$this->WebAppService->assign( array(
						"error" => $e->getMessage(),
						"error_code" => $e->getCode()
				));
				exit;
			}
			
			if(!$res){
				//Exception
				//WebApp::moveBack("업데이트할 자료가 존재하지 않습니다.");
			}

			header("Location: /Member/edit".WebAppService::$queryString); // 리스트 페이지 이동
			//header("Location: ".WebAppService::$baseURL."/edit".WebAppService::$queryString); // 리스트 페이지 이동
			exit;
		}
	
	}
	/**
	 * 이미지 업로드
	 * @param array $UploadFile ( $_FILES )
	 * @param array $options (업로드 옵션)
	 *
	 * @example
	 * 		image_upload( $_FILES['profile_photo'], array(
	 "basedir" => "images/member/profile/", (기본 저장경로)
	 "width_maxSize" => 180, (가로 Max 사이즈)
	 "newFileName" => "apple", (파일명 명명)
	 "USERID" => "user-id" (이미 가입한경우 : 회원 아이디)
	 )) ;
	 
	 * @return String ( uploadfile )
	 */
	private function profile_upload($UploadFile, $Options)
	{
		if( isset($UploadFile) && $UploadFile['error']==0)
		{
			$image_info = @getimagesize($UploadFile['tmp_name']);
			$image_width = $image_info[0] ;
			$image_height = $image_info[1] ;
			
			// 이미지인지 체크
			if(!$this->WebAppService->Func->imageType_Check($image_info['mime']) )
				WebApp::moveBack("이미지파일(gif,jpg,png)만 가능합니다.") ;
				
				# 이미 회원가입된 정보가 있을경우 (저장된 프로파일 이미지 제거)
				if( isset($Options['USERID']) && !empty($Options['USERID']) )
				{
					$image_data = $this->dataRead( array(
							"columns" => "profile_photo",
							"conditions" => array(
									"userid"=>$_SESSION['MBRID']
							)
					));
					
					# Profile-image Delete
					if(!empty($image_data)){
						$image_data_file = $Options["basedir"].$image_data[0]["profile_photo"] ;
						if( is_file($image_data_file) ) unlink($image_data_file);
					}
				}
				
				# Profile-image Resizing after Upload
				$GDimage = &WebApp::singleton('GDimage','graphic', $UploadFile);
				if ($GDimage->uploaded)
				{
					//$GDimage->file_new_name_body = $_SESSION['MBRSERIAL'] ."_". $_SESSION['MBRID']; //file-name
					$GDimage->file_new_name_body = $Options['newFileName'] ."_". $this->WebAppService->Strings->shuffle_alphaNum(5);
					
					if( $Options["width_maxSize"] ){
						$GDimage->image_resize = true;
						$GDimage->image_x = $Options["width_maxSize"];
						$GDimage->image_ratio_y = true;
					}
					$GDimage->jpeg_quality = 100;
					//$GDimage->image_pixelate = 3;
					$GDimage->image_unsharp = true;
					
					/* $rsSize = $this->WebAppService->Func->imageResizing($image_width, $image_height, $profile_conf["width_maxSize"]);
					 $GDimage->image_resize = true;
					 $GDimage->image_x = $rsSize["width"];
					 $GDimage->image_y = $rsSize["height"];
					 $GDimage->jpeg_quality = 100; */
					
					$GDimage->Process($Options["basedir"]);
					
					# Success
					if ($GDimage->processed) {
						$GDimage->Clean();
						/*
						 * file_dst_path         		: images/member/profile/\
						 file_dst_name_body	: 6_Xg6RA8Gk9a
						 file_dst_name_ext     	: jpg
						 file_dst_name         	: 6_Xg6RA8Gk9a.jpg
						 file_dst_pathname    	: images/member/profile/\6_Xg6RA8Gk9a.jpg
						 */
						return $GDimage->file_dst_name ;
						/* $this->dataUpdate(
						 array("profile_photo"=>$GDimage->file_dst_name),
						 array("userid"=>$_SESSION['MBRID'])
						 );
						 exit; */
					}else{
						//$errMSG = "이미지 파일에 문제가 있습니다.".PHP_EOL."파일을 확인해 주세요." ;
						WebApp::moveBack($GDimage->error) ;
						exit;
					}
				}
		}
	}
	public function pointHistory()
	{
		try{
			$datas = $this->PointHistoryMember(array(
					"columns"=> '
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
								H.serial, M.userid, M.username, G.grade_name, H.point, H.description, H.regdate',
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
	}

	
	
	
}