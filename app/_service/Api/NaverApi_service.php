<?php
namespace Gajija\service\Api;

/**
 * :: Naver Api 서비스....
 *
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class NaverApi_service
{
	/**
	 * Api 제공업체 이름
	 * @var string
	 */
	public static $Provider = "naver" ; 
	
	/**
	 * 토큰정보
	 * @var array
	 */
	public $Token = array() ;
	
	/**
	 * 네이버 인증 API 주소
	 * @var string
	 */
	protected static $OAUTH_HOST = "https://nid.naver.com/oauth2.0" ;
	
	/**
	 * 네이버 API 요청 주소
	 * @var string
	 */
	protected static $API_HOST = "https://openapi.naver.com";
	
	//==========================================
	/**
	 * [네이버 요청] 토큰발급을 위한 인증코드 받기 ( /authorize )
	 * @var string
	 */
	protected static $API_REQ_AUTHORIZE = "/authorize";
	/**
	 * [네이버 요청] 토큰 요청&갱신 ( /token )
	 * @var string
	 */
	protected static $Api_REQ_TOKEN = "/token";
	/**
	 * [네이버 요청] 로그아웃 ( /v1/user/logout )
	 * @var string
	 */
	//protected static $API_REQ_LOGOUT = "/v1/user/logout";
	/**
	 * [네이버 요청] 앱 연결 ( /v1/user/signup )
	 * @var string
	 */
	//protected static $API_REQ_SIGNUP= "/v1/user/signup";
	/**
	 * [네이버 요청] 연결해제[회원탈퇴] ( /v1/user/unlink )
	 * @var string
	 */
	//protected static $API_REQ_UNLINK= "/v1/user/unlink";
	/**
	 * [네이버 요청] 회원 정보[profile] 얻어오기 ( /v1/nid/me )
	 * @var string
	 */
	protected static $API_REQ_ME = "/v1/nid/me";
	/**
	 * [네이버 요청] 사용자 토큰 유효성 검사 ( /v1/user/access_token_info )
	 * @var string
	 */
	//protected static $API_REQ_ACCESS_TOKEN_INFO= "/v1/user/access_token_info";
	//==========================================
	
	/**
	 * 네이버 콜백 주소
	 * @var string
	 */
	protected static $REDIRECT_URI = "http://".HOST."/Member/oauthCallback_naver" ;
	
	protected static $apiKey = array(
			
			"www.test.kr" => array(
					'clientId' => "",
					'client_secret' => ""
			),
			"test.test.com" => array(
					'clientId' => "12fRfsdsssdaNP314G",
					'client_secret' => "0fddddd1ahjvD6f"
			)
	);
	
	public function __construct()
	{
		$this->init() ;
	}
	public function __destruct()
	{
		foreach($this as $k => $obj){
			unset($this->$k);
		}
	}
	public function init()
	{
		if( isset($_SESSION['api_token'][static::$Provider]) && !empty($_SESSION['api_token'][static::$Provider]) )
		{
			$this->Token = & $_SESSION['api_token'][self::$Provider] ;
		}
	}
	/**
	 * oauth 토큰 세션 생성
	 *
	 * @param array $token
	 * @return array $_SESSION['api_token'][Api이름]
	 */
	protected function session_save( array $token )
	{
		if( !isset($_SESSION['api_token']) ) $_SESSION['api_token'] = array();
		$_SESSION['api_token'][static::$Provider] = $token ;
		$this->Token = & $_SESSION['api_token'][static::$Provider] ;
	}
	/**
	 * Naver 서버에 요청
	 * 
	 * @param string $Api_path
	 * @param array $params ["post"=>array | queryString, "header"=>??]
	 * @param string $http_method ( GET, POST )
	 * @return array ["info"=>??, "response" => ??]
	 */
	protected function _Request($Api_path, $params = null, $http_method = "GET")
	{
	    //###############################
		$Opts = array(
				CURLOPT_URL => $Api_path,
				CURLOPT_ENCODING => "UTF-8",
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_SSL_VERIFYPEER => false
				//CURLOPT_POST=> true
				//CURLOPT_TIMEOUT => 30
		);
		// header 선언
		if( !empty($params['header'])) 
		{
			$Opts[CURLOPT_HTTPHEADER] = array($params['header']) ;
		}
		
		if($http_method == "POST") $Opts[CURLOPT_POST] = true ;
		
		if( !empty($params['post'])) {
			if( ! isset($Opts[CURLOPT_POST]) ) $Opts[CURLOPT_POST] = true ;
			$Opts[CURLOPT_POSTFIELDS] = $params['post'] ;
		}
		/* echo "-----------------------------------------------<br>";
		echo $Api_path ."<br>";
		echo "-----------------------------------------------<br>";
		echo '■파라미터■ : <pre>';print_r($params);
		echo '■Session■ : <pre>';print_r($_SESSION);
		//---------------------------- */
		$ch = curl_init();
		curl_setopt_array( $ch, $Opts ) ;

		$response = curl_exec( $ch );
		$results = json_decode($response, true);
		$info = curl_getinfo( $ch ); // curl_getinfo($ch, CURLINFO_HTTP_CODE );

		//echo '■결과■ : <pre>';print_r($results);
		
		curl_close( $ch );
		//----------------------------
		return array(
				"info" => $info, // 최종 반환된 모든 전송정보 (배열)
				"response" => $results // 응답정보
		) ;
	}
	/**
	 * ■ Naver 사용자 토큰 받기(발급)
	 * 
	 * @uses POST 방식
	 * @param string $code 사용자 토큰받기로 넘어온 값
	 * @return array
	 * 		{
				 "access_token":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
				 "token_type":"bearer",
				 "refresh_token":"yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
				 "expires_in":43199,
				 "scope":"Basic_Profile"
			 }
	 */
	protected function _getAccessToken($code, $state)
	{
		$QueryString = "grant_type=authorization_code" ;
		$QueryString .= "&client_id=". self::$apiKey[HOST]['clientId'] ;
		$QueryString .= "&client_secret=". self::$apiKey[HOST]['client_secret'] ;
		$QueryString .= "&code=". $code ;
		$QueryString .= "&state=". $state ;

		$result = $this->_Request(
                    static::$OAUTH_HOST . static::$Api_REQ_TOKEN,
                    array("post" => $QueryString)
				) ;
		//echo '토큰발급 : <pre>';print_r($result);
		return $result;
	}
	/**
	 * ■ Naver 사용자 토큰 갱신
	 *
	 * @uses POST 방식
	 * 
	 * @return array|null
	 */
	protected function _setAccessToken()
	{
		$QueryString = "grant_type=refresh_token" ;
		$QueryString .= "&client_id=". self::$apiKey[HOST]['clientId'] ;
		$QueryString .= "&client_secret=". self::$apiKey[HOST]['client_secret'] ;
		$QueryString .= "&refresh_token=".$this->Token["refresh_token"];
		
		$result = $this->_Request( 
					static::$OAUTH_HOST . static::$Api_REQ_TOKEN , //. "?".$QueryString,
		            array("post" => $QueryString)
				) ;
		
		$this->Token['access_token'] = $result['response']['access_token'];
		return $result;
	}
	/**
	 * Naver 토큰 유효성 검사
	 * 
	 * @uses GET 방식
	 */
	protected function getValidAccessToken()
	{
		$HEADER = array(
					"Content-Type : application/x-www-form-urlencoded",
				"Authorization: Bearer ". $this->Token["access_token"]
				);
		$result = $this->_Request(
					static::$OAUTH_HOST . static::$API_REQ_ACCESS_TOKEN_INFO , //. "?".$QueryString,
					array("header" => $HEADER)
				) ;
		//echo '22<pre>';print_r($result);exit;
		if( (int)$result["info"]["http_code"] == 200) return true ;
		else return false ;
	}
	/**
	 * ■ Naver Login Page-URL
	 * @return string URL
	 */
	protected function createAuthUrl()
	{
		$state = "RAMDOM_STATE" ;
		$redirectURI = urlencode(self::$REDIRECT_URI) ;
		
		$authUrl = static::$OAUTH_HOST . static::$API_REQ_AUTHORIZE ;
		$authUrl .= "?response_type=code" ;
		$authUrl .= "&client_id=". self::$apiKey[HOST]['clientId'] ;
		$authUrl .= "&redirect_uri=". $redirectURI ;
		$authUrl .= "&state=". $state ;
		
		return $authUrl?$authUrl:null ;
	}
	
	/**
	 * ■ Naver Callback (redirect uri)
	 *
	 * @param string $_GET["code"]
	 * @param string $_GET["state"]
	 */
	public function oauthCallback_naver()
	{
			if (isset($_GET['code']))
			{
			    $token = $this->_getAccessToken($_GET['code'], $_GET["state"])["response"] ;
				
				if( is_array($token) )
				{
					//$_SESSION["Naver_token"] = array();
					//$_SESSION["Naver_token"] = $token ;
					$this->session_save($token) ;
					
					return $token;
				}
				
			}
		
	}
	/**
	 * ■ Naver 로그인
	 * @return string|null
	 */
	public function Signin(){
		/* if ( isset($_SESSION['Naver_token']) || !empty($_SESSION['Naver_token']) )
		{
			$this->SignOut();
		} */
		if( $authUrl = $this->SignCheck() )
		{
			return filter_var($authUrl, FILTER_SANITIZE_URL);
		}
		
	}
	protected function SignCheck(){
		
		if ( isset($this->Token) && !empty($this->Token['access_token']) )
		{
			$this->_setAccessToken() ;
			/* // 토큰 유효 체크
			if( ! $this->getValidAccessToken() )
			{
				// 토큰 갱신
				$this->_setAccessToken() ;
			} */
		}
		else{
			$authUrl = $this->createAuthUrl();

			return $authUrl?$authUrl:null ;
		}
		
	}
	/**
	 * Naver 로그아웃
	 *
	 * @uses POST 방식
	 * 
	 * @return $authUrl ;
	 * @deprecated
	 */
	public function SignOut()
	{
		//직접 로그아웃시 http://nid.naver.com/nidlogin.logout
		if ( isset($this->Token) && !empty($this->Token) )
		{
		    /* $this->Disconnect($_SESSION["Naver_token"]["access_token"]);
		    exit; */
			//$HEADER = "Authorization: Bearer ". $_SESSION["Naver_token"]["access_token"] ;
			
			$result = $this->_Request(
					static::$API_HOST . static::$API_REQ_LOGOUT
					//array("header" => $HEADER),
			) ;
			if( (int)$result["info"]["http_code"] == 200) {
				foreach($this->Token as &$k) unset($k) ;
			}
			else{
				return false ;
			}
			exit;
		}
		unset($this->Token) ;
		return true ;
	}
	
	/**
	 * Naver 앱연결[앱 가입]
	 *
	 * @tutorial 자동 앱연결시 사용안함 (네이버앱 관리자에서 설정) 
	 * 
	 * @return bool (탈퇴:true / 실패:false)
	 */
	public function Connect()
	{
		if( isset($this->Token) && !empty($this->Token["access_token"]) )
		{
			$HEADER = array(
					"Content-Type : application/x-www-form-urlencoded",
					"Authorization: Bearer ". $this->Token["access_token"]
			);
			$result = $this->_Request(
					static::$API_HOST . static::$API_REQ_SIGNUP,
					array("header" => $HEADER),
					"POST"
					) ;
			echo "Connect<pre>";print_r($result);
			if( (int)$result["info"]["http_code"] == 200) return true ;
			else return false ;
		}
	}
	/**
	 * ■ Naver 연결해제[앱 탈퇴]
	 * 
	 * @return bool (탈퇴:true / 실패:false)
	 */
	public function Disconnect()
	{
		//$HEADER = "Authorization: Bearer ". $_SESSION["Naver_token"]["access_token"] ;
		if( !empty($this->Token["access_token"]) )
		{
		    $QueryString = "grant_type=delete" ;
		    $QueryString .= "&client_id=". self::$apiKey[HOST]['clientId'] ;
		    $QueryString .= "&client_secret=". self::$apiKey[HOST]['client_secret'] ;
		    $QueryString .= "&access_token=".urlencode($this->Token["access_token"]) ;
		    $QueryString .= "&service_provider=NAVER" ;
		    
			$result = $this->_Request(
						static::$OAUTH_HOST . static::$Api_REQ_TOKEN,
			            array("post" => $QueryString),
						"POST"
					) ;
			//echo '<pre>';print_r($result);exit;
			if( (int)$result["info"]["http_code"] == 200) {
				$this->Token = array();
				return true ;
			}
			//else return false ;
		}
		return false ;
	}
	/**
	 * ■ 회원 프로필 정보 가져오기
	 * 
	 * 응답정보 : 
	 * 				[id] => 14222225231
                    [nickname] => yeo****
                    [profile_image] => https://ssl.pstatic.net/static/pwe/address/img_profile.png
                    [age] => 20-39
                    [gender] => M
                    [email] => yesssss@hanmail.net
                    [name] => 홍길동
                    [birthday] => 05-03
	 */
	public function getUserProfile()
	{
		if( isset($this->Token) && !empty($this->Token["access_token"]) )
		{
			$HEADER = "Authorization: Bearer ". $this->Token["access_token"];
			
			$result = $this->_Request(
					static::$API_HOST . static::$API_REQ_ME,
					array("header" => $HEADER)
				) ;
			
			if( (int)$result["info"]["http_code"] == 200) 
			{
				$profile = array(
						"provider" => "naver",
						"email" => $result["response"]["response"]["email"],
						"id" => $result["response"]["response"]["id"], // ex) 13265231
						"picture" => $result["response"]["response"]["profile_image"], // 사진 (https://ssl.pstatic.net/static/pwe/address/img_profile.png)
						"gender" => $result["response"]["response"]["gender"], // "M" 또는 "F" [설명: 성별 male(남성) / female(여성) ]
						"name" => $result["response"]["response"]["name"], // 이름
						//"familyName" => ???, // 이름의 성(youngsu lee -> "lee")
						//"givenName" => ???, // 성을 뺀 이름(youngsu lee -> "youngsu")
						//"locale" => ???, // 국적 (ko)
						//"link" => ??? // 자신의 블로그? 홈페이지?
						"age" => $result["response"]["response"]["age"], // ★ age 값이 "40-49" 이기에 사용할지 말지 미결정
						"birthday" => $result["response"]["response"]["birthday"], // 이름(youngsu lee)
						"nickname" => $result["response"]["response"]["nickname"] // 이름(youngsu lee)
				) ;
				//echo 'getUserProfile1<pre>';print_r($result);
				return $profile ;
			}
			else{
				echo "Naver SDK returned an error " .
					"[".(int)$result["info"]["http_code"]."]". $result["response"]["error_description"] ;
				exit;
			}
			
			 //----------------------------------------------
			 /* $POST = "propertyKeys=[\"name\",\"age\"]";
			  
			 $result = $this->_Request( 
			 		static::$API_HOST . static::$API_REQ_ME,
			 		array(
			 			"post" => $POST,
			 			"header" => $HEADER
	 				)
			  	) ;
			 echo 'getUserProfile2<pre>';print_r($result);
			 exit; */
		}
	}
	
	
}