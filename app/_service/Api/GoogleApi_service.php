<?php
namespace Gajija\service\Api;


/**
 * :: Googe Api 서비스....
 *
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class GoogleApi_service 
{
    /**
     * Api 제공업체 이름
     * @var string
     */
    public static $Provider = "google" ; 
    
    /**
     * 토큰정보
     * @var array
     */
    public $Token = array() ;
    
	protected $client ;
	
	public static $apiKey = array(
	    /* "www.test.kr" => array(
	        'clientId' => "xxdfe-r77fatest3hn7j1fhe8ias2slo45xfewjsfl.apps.googleusercontent.com",
	        'clientSecret' => "L466swpV5Hd3iPwee6TLo2foCgtTFZ"
	    ),
		"demo.test.kr" => array(
			'clientId' => "fffdsaf-eh3qo8a14spvovp7dl0ncc22e8onua65.apps.googleusercontent.com",
			'clientSecret' => "Z77xd0MkrqffQ2JMMApjVGrgghHcWkY"
		) */
	);
	
	public function __construct()
	{
		require_once "Api/google/vendor/autoload.php" ;
		
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
	private function load(){
		
		$clientId = self::$apiKey[HOST]['clientId'];
		$clientSecret = self::$apiKey[HOST]['clientSecret'];
		$redirectUri = "http://".HOST."/Member/oauthCallback_google";
		//new \Google_Service_Oauth2($client)
		$this->client = new \Google_Client() ;
		$this->client->setClientId($clientId);
		$this->client->setClientSecret($clientSecret);
		$this->client->setRedirectUri($redirectUri);
		$this->client->addScope(array("profile", "email")) ;
		//$this->client->setScopes([\Google_Service_Oauth2::PLUS_ME, \Google_Service_Oauth2::USERINFO_PROFILE, \Google_Service_Oauth2::USERINFO_EMAIL]);
		$this->client->setAccessType("offline");        // offline access
		//$this->client->setAccessType("online");        // offline access
		//$this->client->addScope("email") ;
		//$this->client->setIncludeGrantedScopes(true);   // incremental auth
		
		//$service = new Google_Service_YouTube($client);
		//$service = new \Google_Service_YouTube($client);
		/* $this->client->addScope(GOOGLE_SERVICE_YOUTUBE::YOUTUBE_READONLY);
		$service = new \Google_Service_YouTube($client);
		 */
	}
	/**
	 * 구글 로그인
	 */
	public function SignIn()
	{
		$this->hasLoaded() ;
		
		//----------------------
/* 		if ($this->client->getAccessToken()) {
			$token_data = $this->client->verifyIdToken();
		} */
		if( $authUrl = $this->SignCheck() ){
			//header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
			return filter_var($authUrl, FILTER_SANITIZE_URL);
		}
	}
	/**
	 * google 로그아웃
	 * 
	 * @return $authUrl ;
	 */
	public function SignOut()
	{
		$this->hasLoaded();
		
		if ( !empty($this->Token)
		    && isset($this->Token['id_token'])
			) {
			// 회원탈퇴 시 - 구글 계정 구독해지시
			    $this->client->revokeToken($this->Token['access_token']);
			
			    unset($this->Token);
		}
	}
	
	protected function hasLoaded(){
		if( ! $this->client instanceof \Google_Client )
			$this->load();
	}
	protected function SignCheck(){
		
		//$this->hasLoaded() ;
		
		if (
		    !empty($this->Token)
		    && isset($this->Token['id_token'])
		) {
			$this->client->setAccessToken($_SESSION['id_token_token']);
		} else {
			$authUrl = $this->client->createAuthUrl();
			
			return $authUrl?$authUrl:null ;
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
	 * [Google] callback Response 
	 * 
	 * @return Array
        (
            [access_token] => ya29.GlvsBLWOYs0pXlj-pW....
            [token_type] => Bearer
            [expires_in] => 3600
            [id_token] => eyJhbGciOiJSUzI1NiIsImtpZCI6IjZkMjJkO....
            [created] => 1508667466
        )
	 */
	public function oauthCallback_google(){
		
		if (isset($_GET['code'])) {

			$this->hasLoaded() ;
			
			$token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
			$access_token = $this->client->getAccessToken();
			/* $tokens_decoded = json_decode($access_token);
			$refreshToken = $tokens_decoded->refresh_token; */
			//$refreshToken = $access_token->refresh_token;
			if($this->client->isAccessTokenExpired()){  // if token expired
				//$refreshToken = json_decode($access_token)->refresh_token;
				$refreshToken = $access_token->refresh_token;
				
				// refresh the token
				$this->client->refreshToken($refreshToken);
				$access_token = $this->client->getAccessToken();
			}
			//$_SESSION['id_token_token'] = $token;
			//echo $this->client->createAuthUrl() ;exit;
			$this->session_save($token) ;
			
			return $access_token ;
		}
	}
	
	/**
	 * 방법2: 회원 프로필 정보 가져오기
	 * 
	 * @return Array
					(
					    [email] => yengsu@gmail.com
					    [id] => 106451349300000096862
					    [gender] => male
					    [name] => youngsu lee
					    [familyName] => lee
					    [givenName] => youngsu
					    [locale] => ko
					    [picture] => https://lh3.googleusercontent.com/-XdUI........AAAAAAI/AAAA......v5M/photo.jpg
					    [link] => https://plus.google.com/106451349300000096862
					    [hd] => 
					    [verifiedEmail] => 1
					)
	 */
	public function getUserProfile()
	{
		$this->hasLoaded() ;

		if(!$this->client->getAccessToken()) {
		    if( !empty($this->Token) && isset($this->Token['id_token']) ) {
		        $this->client->setAccessToken($this->Token);
			}
		}
		/* if ($this->client->getAccessToken()) {
			$token_data = $this->client->verifyIdToken($_SESSION['id_token_token']['id_token']);
		} */
		/* if($_SESSION['id_token_token'] ){
			$info = $this->client->verifyIdToken($_SESSION['id_token_token']['id_token']);
		} */
		$oauth2 = new \Google_Service_Oauth2($this->client);
		$this->Token = $this->client->getAccessToken();
		$userInfo = $oauth2->userinfo_v2_me->get();
		
		$profile = array(
				"provider" => "google",
				"email" => $userInfo->email,
				"id" => $userInfo->id, // ex) 106451349300000096862
				"gender" => $userInfo->gender, // 성별 male(남성) / female(여성)
				"name" => $userInfo->name, // 이름(youngsu lee)
				"familyName" => $userInfo->familyName, // 이름의 성(youngsu lee -> "lee")
				"givenName" => $userInfo->givenName, // 성을 뺀 이름(youngsu lee -> "youngsu")
				"locale" => $userInfo->locale, // 국적 (ko)
				"picture" => $userInfo->picture, // 사진 (https://lh3.googleusercontent.com/-XdUIqdMkCWA/AAA............AAAAA/4252rscbv5M/photo.jpg)
				"link" => $userInfo->link, // 자신의 블로그? 홈페이지?
				"hd" => $userInfo->hd, // 
				"verifiedEmail" => $userInfo->verifiedEmail // email 검증 (1)
		) ;
		return $profile ;
	}
	
	/**
	 * 방법[default]: 회원 프로필정보 가져오기
	 * 
	 * @tutorial https://firebase.google.com/docs/auth/admin/verify-id-tokens?hl=ko
	 * 
	 * @return Array
					(
					    [azp] => 353217231421-icop9g...
					    [aud] => 353217231421-icop9g...
					    [sub] => 1067743498000...
					    [email] => abcdef@gmail.com
					    [email_verified] => 1
					    [at_hash] => pe1kQtglX...
					    [iss] => https://accounts.google.com
					    [iat] => 1508666952
					    [exp] => 1508670552
					    [name] => abcdef lee
					    [picture] => https://lh6.googleusercontent.com/-XT3_7....jpg
					    [given_name] => abcdef
					    [family_name] => lee
					    [locale] => ko
					)
	 */
	public function GetUserProfileInfo(){
		
		$this->hasLoaded() ;
		
		if ($this->client->getAccessToken()) {
			$token_data = $this->client->verifyIdToken();
		}
		if( !empty($this->Token) ){
		    $info = $this->client->verifyIdToken($this->Token['id_token']);
		}
		
		return $info ;
	}
	
	
	
	
}