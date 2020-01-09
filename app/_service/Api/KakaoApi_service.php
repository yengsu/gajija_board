<?php
namespace Gajija\service\Api;

/**
 * :: Kakao Api 서비스....
 *
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class KakaoApi_service
{
	/**
	 * Api 제공업체 이름
	 * @var string
	 */
	public static $Provider = "kakao" ; 
	
	/**
	 * 토큰정보
	 * @var array
	 */
	public $Token = array() ;
	
	/**
	 * 카카오 인증 API 주소
	 * @var string
	 */
	protected static $OAUTH_HOST = "https://kauth.kakao.com" ;
	
	/**
	 * 카카오 API 요청 주소
	 * @var string
	 */
	protected static $API_HOST = "https://kapi.kakao.com";
	
	//==========================================
	/**
	 * [카카오 요청] 토큰 요청&갱신 ( /oauth/token )
	 * @var string
	 */
	protected static $Api_REQ_TOKEN = "/oauth/token";
	/**
	 * [카카오 요청] 토큰발급을 위한 인증코드 받기 ( /oauth/authorize )
	 * @var string
	 */
	protected static $API_REQ_AUTHORIZE = "/oauth/authorize";
	/**
	 * [카카오 요청] 로그아웃 ( /v1/user/logout )
	 * @var string
	 */
	protected static $API_REQ_LOGOUT = "/v1/user/logout";
	/**
	 * [카카오 요청] 앱 연결 ( /v1/user/signup )
	 * @var string
	 */
	protected static $API_REQ_SIGNUP= "/v1/user/signup";
	/**
	 * [카카오 요청] 연결해제[회원탈퇴] ( /v1/user/unlink )
	 * @var string
	 */
	protected static $API_REQ_UNLINK= "/v1/user/unlink";
	/**
	 * [카카오 요청] 회원 정보[profile] 얻어오기 ( /v1/user/me )
	 * @var string
	 */
	protected static $API_REQ_ME = "/v1/user/me";
	/**
	 * [카카오 요청] 사용자 토큰 유효성 검사 ( /v1/user/access_token_info )
	 * @var string
	 */
	protected static $API_REQ_ACCESS_TOKEN_INFO= "/v1/user/access_token_info";
	//==========================================
	
	/**
	 * 카카오 콜백 주소
	 * @var string
	 */
	protected static $REDIRECT_URI = SCHEME."://".HOST."/Member/oauthCallback_kakao" ;
	
	protected static $apiKey = array(
			
			"www.test.kr" => array(
					'clientId' => "43234bbf075cfdsafsdab10zzdf08b6b7"
			),
			"demo.test.com" => array(
					'clientId' => "bfdsafsahjf0rewb72uytrytr2rewrwqqa1"
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
			$this->Token = & $_SESSION['api_token'][static::$Provider] ;
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
	 * KAKAO 서버에 요청
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
	 * KAKAO 사용자 토큰 받기
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
	protected function _getAccessToken($code)
	{
		$QueryString = "grant_type=authorization_code" ;
		$QueryString .= "&client_id=". self::$apiKey[HOST]['clientId'] ;
		$QueryString .= "&code=". $code;

		$result = $this->_Request(
                    static::$OAUTH_HOST . static::$Api_REQ_TOKEN,
                    array("post" => $QueryString)
				) ;
		return $result;
	}
	/**
	 * KAKAO 사용자 토큰 갱신
	 *
	 * @uses POST 방식
	 * 
	 * @return array|null
	 */
	protected function _setAccessToken()
	{
		$QueryString = "grant_type=refresh_token" ;
		$QueryString .= "&client_id=". self::$apiKey[HOST]['clientId'] ;
		$QueryString .= "&refresh_token=".$this->Token["refresh_token"];
		
		$result = $this->_Request( 
					static::$OAUTH_HOST . static::$Api_REQ_TOKEN , //. "?".$QueryString,
		            array("post" => $QueryString)
				) ;
		
		$this->Token['access_token'] = $result['response']['access_token'];
		return $result;
	}
	/**
	 * KAKAO 토큰 유효성 검사
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
	 * KAKAO Login Page-URL
	 * @return string URL
	 */
	protected function createAuthUrl()
	{
		$authUrl = static::$OAUTH_HOST . static::$API_REQ_AUTHORIZE;
		$authUrl .= "?client_id=". self::$apiKey[HOST]['clientId'] ;
		$authUrl .= "&redirect_uri=". self::$REDIRECT_URI ;
		$authUrl .= "&response_type=code" ;
		
		return $authUrl?$authUrl:null ;
	}
	
	/**
	 * KAKAO Callback (redirect uri)
	 *
	 * @param string $_GET["code"]
	 */
	public function oauthCallback_kakao()
	{
			if (isset($_GET['code']))
			{
				$token = $this->_getAccessToken($_GET['code'])["response"] ;
				
				if( is_array($token) )
				{
					//$_SESSION["kakao_token"] = array();
					//$_SESSION["kakao_token"] = $token ;
					$this->session_save($token) ;
					return $token ;
				}
				
			}
		
	}
	/**
	 * KAKAO 로그인
	 * @return string|null
	 */
	public function Signin(){
		/* if ( isset($_SESSION['kakao_token']) || !empty($_SESSION['kakao_token']) )
		{
			$this->SignOut();
		} */
		if( $authUrl = $this->SignCheck() )
		{
			return filter_var($authUrl, FILTER_SANITIZE_URL);
		}
		
	}
	protected function SignCheck(){
		
		if( isset($this->Token) && !empty($this->Token['access_token']) )
		{
			// 토큰 유효 체크
			if( ! $this->getValidAccessToken() )
			{
				// 토큰 갱신
				$this->_setAccessToken() ;
			}
		}
		else{
			$authUrl = $this->createAuthUrl();

			return $authUrl?$authUrl:null ;
		}
		
	}
	/**
	 * KAKAO 로그아웃
	 *
	 * @uses POST 방식
	 * 
	 * @return $authUrl ;
	 */
	public function SignOut()
	{
		if ( isset($this->Token) && !empty($this->Token) )
		{
			$HEADER = "Authorization: Bearer ". $this->Token["access_token"] ;
			
			$result = $this->_Request(
					static::$API_HOST . static::$API_REQ_LOGOUT,
					array("header" => $HEADER),
			        "POST"
			) ;
			if( (int)$result["info"]["http_code"] == 200) {
				foreach($this->Token as &$k) unset($k) ;
			}
			else{
				return false ;
			}
			//exit;
		}
		unset($this->Token) ;
		return true ;
	}
	
	/**
	 * KAKAO 앱연결[앱 가입]
	 *
	 * @tutorial 자동 앱연결시 사용안함 (카카오앱 관리자에서 설정) 
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
	 * KAKAO 연결해제[앱 탈퇴]
	 * 
	 * @return bool (탈퇴:true / 실패:false)
	 */
	public function Disconnect()
	{
		if( !empty($this->Token["access_token"]) )
		{
			$HEADER = "Authorization: Bearer ". $this->Token["access_token"] ;
			
			$result = $this->_Request(
						static::$API_HOST . static::$API_REQ_UNLINK,
						array("header" => $HEADER),
						"POST"
					) ;
			if( (int)$result["info"]["http_code"] == 200){
				$this->Token = array();
				return true ;
			}
		}
		return false ;
	}
	/**
	 * 회원 프로필 정보 가져오기
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
						"provider" => "kakao",
						"email" => $result["response"]["kaccount_email"],
						"id" => $result["response"]["id"], // ex) 826841730
						"picture" => $result["response"]["properties"]["thumbnail_image"] // 사진 (http://k.kakaocdn.net/dn/abcde/xxxxxxx/Kf5sjN272i64231G7MRrl0/profile_110x110c.jpg)
						//"gender" => ???, // 성별 male(남성) / female(여성)
						//"name" => ???, // 이름(youngsu lee)
						//"familyName" => ???, // 이름의 성(youngsu lee -> "lee")
						//"givenName" => ???, // 성을 뺀 이름(youngsu lee -> "youngsu")
						//"locale" => ???, // 국적 (ko)
						//"link" => ??? // 자신의 블로그? 홈페이지?
				) ;
				//echo 'getUserProfile1<pre>';print_r($result);
				return $profile ;
			}
			else{
				echo "Kakao SDK returned an error " .
					"[".(int)$result["info"]["http_code"]."]". $result["response"]["msg"] ;
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