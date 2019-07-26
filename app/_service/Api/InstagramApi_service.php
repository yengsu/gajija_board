<?php
namespace Gajija\service\Api;

/**
 * :: Instagram Api 서비스....
 *
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class InstagramApi_service
{
	/**
	 * Api 제공업체 이름
	 * @var string
	 */
	public static $Provider = "instagram" ; 
	
	/**
	 * 토큰정보
	 * @var array
	 */
	public $Token = array() ;
	
	/**
	 * 인스타그램 인증 API 주소
	 * @var string
	 */
	protected static $OAUTH_HOST = "https://api.instagram.com" ;
	
	/**
	* 인스타그램 API 요청 주소
	* @var string
	*/
	protected static $API_HOST = "https://api.instagram.com";
	//==========================================
	/**
	 * [인스타그램 요청] 토큰 요청&갱신 
	 * @var string
	 */
	protected static $Api_REQ_TOKEN = "/oauth/access_token";
	/**
	 * [인스타그램 요청] 토큰발급을 위한 인증코드 받기 ( /oauth/authorize )
	 * @var string
	 */
	protected static $API_REQ_AUTHORIZE = "/oauth/authorize/";
	//==========================================
	
	/**
	 * 인스타그램 콜백 주소
	 * @var string
	 */
	protected static $REDIRECT_URI = "http://".HOST."/Member/oauthCallback_instagram" ;
	
	protected static $apiKey = array(
			/* 
			"test.kr" => array(
					'clientId' => "sdafs3123dafasd2",
					'client_secret' => "ffsx33423423424gh"
			),
			"demo.test.com" => array(
					'clientId' => "xfefcc555fdsafasdF0321376e62",
					'client_secret' => "ef33fds2a2354e5ebcfhasexd4beed6"
			) */
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
	 * Instagram 서버에 요청
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
	 * ■ Instagram 사용자 토큰 받기(발급)
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
		$QueryString .= "&client_secret=". self::$apiKey[HOST]['client_secret'] ;
		$QueryString .= "&redirect_uri=". urlencode(self::$REDIRECT_URI) ;
		$QueryString .= "&code=". $code ;
		//$QueryString .= "&scope=basic";

		$result = $this->_Request(
                    static::$OAUTH_HOST . static::$Api_REQ_TOKEN,
                    array("post" => $QueryString)
				) ;
		//echo '토큰발급 : <pre>';print_r($result);
		return $result;
	}
	/**
	 * ■ Instagram 사용자 토큰 갱신
	 *
	 * @deprecated
	 */
	protected function _setAccessToken()
	{
	}
	/**
	 * Instagram 토큰 유효성 검사
	 * 
	 * @deprecated
	 */
	protected function getValidAccessToken()
	{
	}
	/**
	 * ■ Instagram Login Page-URL
	 * @return string URL
	 */
	protected function createAuthUrl()
	{
		$state = "RAMDOM_STATE" ;
		$redirectURI = urlencode(self::$REDIRECT_URI) ;
		
		$authUrl = static::$OAUTH_HOST . self::$API_REQ_AUTHORIZE ;
		$authUrl .= "?response_type=code" ;
		$authUrl .= "&client_id=". self::$apiKey[HOST]['clientId'] ;
		$authUrl .= "&redirect_uri=". $redirectURI ;
		
		return $authUrl?$authUrl:null ;
	}
	
	/**
	 * ■ Instagram Callback (redirect uri)
	 *
	 * @param string $_GET["code"]
	 * @param string $_GET["state"]
	 */
	public function oauthCallback_instagram()
	{
			if (isset($_GET['code']))
			{
			    $token = $this->_getAccessToken($_GET['code'])["response"] ;
				
				if( is_array($token) )
				{
					$this->session_save($token) ;
					
					return $token;
				}
				
			}
		
	}
	/**
	 * ■ Instagram 로그인
	 * 
	 * @return string|null
	 */
	public function Signin()
	{
		if( $authUrl = $this->SignCheck() )
		{
			return filter_var($authUrl, FILTER_SANITIZE_URL);
		}
		
	}
	protected function SignCheck(){
		
		$authUrl = $this->createAuthUrl();
		
		return $authUrl?$authUrl:null ;
	}
	/**
	 * Instagram 로그아웃
	 *
	 * @uses 직접 로그아웃 : https://instagram.com/accounts/logout/
	 * 
	 * @deprecated
	 */
	public function SignOut()
	{
	}
	/**
	 * Instagram 앱연결[앱 가입]
	 * 
	 * @deprecated
	 */
	public function Connect()
	{
	}
	/**
	 * ■ Instagram 연결해제[앱 탈퇴]
	 * 
	 * @deprecated
	 */
	public function Disconnect()
	{
	}
	/**
	 * ■ 회원 프로필 정보 가져오기
	 * 
	 * 응답정보(oauthCallback_instagram) : 
                    {
					    "access_token": "fb2322e77d.fd14safsd311afsda04cb3a58884d2d",
					    "user": {
					        "id": "26734084",
					        "username": "snoopdogg",
					        "full_name": "Snoop Dogg",
					        "profile_picture": "..."
					    }
					}
	 */
	public function getUserProfile()
	{
		if( isset($this->Token) && !empty($this->Token["access_token"]) )
		{
				$profile = array(
						"provider" => "instagram",
						//"email" => $result["response"]["email"],
						"id" => $this->Token["user"]["id"], // ex) 1574083
						"picture" => $this->Token["user"]["profile_picture"], // 사진 (https://ssl.pstatic.net/static/pwe/address/img_profile.png)
						//"gender" => $result["response"]["gender"], // "M" 또는 "F" [설명: 성별 male(남성) / female(여성) ]
						"name" => $this->Token["user"]["full_name"] // 이름
						//"familyName" => ???, // 이름의 성(youngsu lee -> "lee")
						//"givenName" => ???, // 성을 뺀 이름(youngsu lee -> "youngsu")
						//"locale" => ???, // 국적 (ko)
						//"link" => ??? // 자신의 블로그? 홈페이지?
						//"age" => $result["response"]["age"], // ★ age 값이 "40-49" 이기에 사용할지 말지 미결정
						//"birthday" => $result["response"]["birthday"], // 이름(youngsu lee)
						//"nickname" => $result["response"]["nickname"] // 이름(youngsu lee)
				) ;
				//echo 'getUserProfile1<pre>';print_r($result);
				return $profile ;
			
			 //----------------------------------------------
		}
	}
	
	
}