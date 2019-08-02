<?php
namespace Gajija\service\Api;


use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use GuzzleHttp\Client;

/**
 * :: Facebook Api 서비스....
 *
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class FacebookApi_service
{
    /**
     * Api 제공업체 이름
     * @var string
     */
    public static $Provider = "facebook" ;
    
    /**
     * 토큰정보
     * @var array
     */
    public $Token = array() ;
    
	protected $client ;
	
	public static $apiKey = array(
			/* "www.test.kr" => array(
					'app_id' => "2063459063323485",
					'app_secret' => "a585ba3ffe2hj16af39501fghae9fc1b"
			),
    	    "demo.test.kr" => array(
    	        'app_id' => "2005685073325116",
    	        'app_secret' => "ac23fdsx61c10b16303a1sx33bbb177f1"
    	    ) */
	);
	
	public function __construct()
	{
	    $this->init();
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
	private function load()
	{
		require_once _APP_LIB."Api/facebook/vendor/autoload.php" ;
		
		$this->client = new Facebook([
				'app_id'  => self::$apiKey[HOST]['app_id'],
				'app_secret' => self::$apiKey[HOST]['app_secret'],
				'default_graph_version' => 'v2.10',
				//'default_access_token' => '{access-token}', // optional
		]);
	}
	private function hasLoaded(){
		if( ! $this->client instanceof Facebook)
			$this->load();
	}
	
	/**
	 * oauth 토큰 세션 생성
	 *
	 * @param array $token
	 * @return array $_SESSION['api_token'][Api이름]
	 */
	protected function session_save( $token )
	{
	    if( !isset($_SESSION['api_token']) ) $_SESSION['api_token'] = array();
	    $_SESSION['api_token'][static::$Provider] = $token ;
	    $this->Token = & $_SESSION['api_token'][static::$Provider] ;
	}
	
	public function Signin(){
		/* if (isset($_REQUEST['logout'])) {
			unset($_SESSION['FBRLH_state']);
			unset($_SESSION['fb_access_token']);
		} */
	    unset($this->Token);
		
		$this->hasLoaded();
		
		if( $authUrl = $this->SignCheck() ){
			return filter_var($authUrl, FILTER_SANITIZE_URL);
		}
		
	}
	/**
	 * fackbook 로그아웃
	 *
	 */
	public function SignOut()
	{
		$this->hasLoaded();
		
		if ( isset($this->Token['access_token']) && !empty($this->Token['access_token']) )
		{
			$helper = $this->client->getRedirectLoginHelper();
			$url = $helper->getLogoutUrl($this->Token['access_token'], "http://".HOST."/Member/logout");
			
			//$url = 'https://www.facebook.com/logout.php?next=http://'.HOST.'/Member/loginFacebook&access_token='.$_SESSION['fb_access_token'];
			//session_destroy();
			unset($this->Token) ;
			
			//header('Location: '.$url);
			//exit;
		}
	}
	
	
	
	protected function SignCheck(){
		
		//$this->hasLoaded() ;
		
	    if ( !empty($this->Token['access_token']) && isset($this->Token['access_token']) )
		{
		    $this->client->setDefaultAccessToken($this->Token['access_token']);
		}
		else{
				$helper = $this->client->getRedirectLoginHelper();
				$permissions = ['email', 'public_profile']; // Optional permissions
				$loginUrl = $helper->getLoginUrl("https://". HOST. "/Member/oauthCallback_facebook", $permissions);
				//echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
				
				return $loginUrl?$loginUrl:null ;
		}
		
	}
	public function oauthCallback_facebook(){

		$this->hasLoaded() ;

		$helper = $this->client->getRedirectLoginHelper();
		//$this->Token['FBRLH_state'] = $_GET['state'];
		//$this->session_save( array('FBRLH_state' => $_GET['state']) ) ;
		try {
			$accessToken = $helper->getAccessToken('https://'.HOST.'/Member/oauthCallback_facebook');
			
		}catch(FacebookResponseException $e){
			$this->SignOut();
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		}catch(FacebookSDKException $e){
			$this->SignOut();
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		
		if (! isset($accessToken)) {
			if ($helper->getError()) {
				header('HTTP/1.0 401 Unauthorized');
				echo "Error: " . $helper->getError() . "\n";
				echo "Error Code: " . $helper->getErrorCode() . "\n";
				echo "Error Reason: " . $helper->getErrorReason() . "\n";
				echo "Error Description: " . $helper->getErrorDescription() . "\n";
			} else {
				header('HTTP/1.0 400 Bad Request');
				echo 'Bad request';
			}
			exit;
		}

		// The OAuth 2.0 client handler helps us manage access tokens
		$oAuth2Client = $this->client->getOAuth2Client();
		
		// Get the access token metadata from /debug_token
		$tokenMetadata = $oAuth2Client->debugToken($accessToken);
		
		// Validation (these will throw FacebookSDKException's when they fail)
		$tokenMetadata->validateAppId(self::$apiKey[HOST]['app_id']); // Replace {app-id} with your app id
		// If you know the user ID this access token belongs to, you can validate it here
		//$tokenMetadata->validateUserId('123');
		$tokenMetadata->validateExpiration();
		
		if (! $accessToken->isLongLived()) {
			// Exchanges a short-lived access token for a long-lived one
			try {
				$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			} catch (FacebookSDKException $e) {
				echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
				exit;
			}
			
			//echo '<h3>Long-lived</h3>';
			//var_dump($accessToken->getValue());
		}
		//echo '111<pre>';print_r( (string) $accessToken);exit;
		//$_SESSION['fb_access_token'] = (string) $accessToken;
		//$this->session_save( (string)$accessToken) ;
		$this->session_save( array('access_token' => (string)$accessToken) ) ;
		/* 
		// Logged in
		echo '<h3>Access Token</h3>';
		var_dump($accessToken->getValue());
		echo '<h3>Metadata</h3>';
		var_dump($tokenMetadata);
		echo '<pre>';print_r($tokenMetadata);
		// User is logged in with a long-lived access token.
		// You can redirect them to a members-only page.
		//header('Location: https://example.com/members.php');
		 */
		return (string)$accessToken ;
		
	}
	
	/**
	 * 방법2: 회원 프로필 정보 가져오기
	 *
	 * @return Array
	 (
	 [email] => yengsu@gmail.com
	 [id] => 106451343232001096862
	 [gender] => male
	 [name] => youngsu lee
	 [familyName] => lee
	 [givenName] => youngsu
	 [locale] => ko
	 [picture] => https://lh3.googleusercontent.com/-XdUI........AAAAAAI/AAAA......v5M/photo.jpg
	 [link] => https://plus.google.com/106451343232001096862
	 [hd] =>
	 [verifiedEmail] => 1
	 )
	 */
	public function getUserProfile()
	{
		$this->hasLoaded() ;
		//======================================================
		// Use one of the helper classes to get a Facebook\Authentication\AccessToken entity.
		//   $helper = $fb->getRedirectLoginHelper();
		//   $helper = $fb->getJavaScriptHelper();
		//   $helper = $fb->getCanvasHelper();
		//   $helper = $fb->getPageTabHelper();
		if( $this->Token['access_token'] )
		{
			try {
				// Get the \Facebook\GraphNodes\GraphUser object for the current user.
				// If you provided a 'default_access_token', the '{access-token}' is optional.
			    $response = $this->client->get('/me?fields=name,first_name,last_name,email,link,gender,locale,picture', $this->Token['access_token']);
				$userInfo = $response->getGraphUser();
				
				$profile = array(
						"provider" => "facebook",
						"email" => $userInfo->getEmail(),
						"id" => $userInfo->getId(), // ex) 106451349300000096862
						"gender" => $userInfo->getGender(), // 성별 male(남성) / female(여성)
						"name" => $userInfo->getName(), // 이름(youngsu lee)
						"familyName" => $userInfo->getFirstName(), // 이름의 성(youngsu lee -> "lee")
						"givenName" => $userInfo->getLastName(), // 성을 뺀 이름(youngsu lee -> "youngsu")
						"locale" => $userInfo->getField('locale'), // 국적 (ko)
						"picture" => $userInfo->getPicture()->getUrl(), // 사진 (https://lh3.googleusercontent.com/-XdUIqdMkCWA/AAA............AAAAA/4252rscbv5M/photo.jpg)
						"link" => $userInfo->getLink() // 자신의 블로그? 홈페이지?
				) ;

				return $profile ;
				
			} catch(FacebookResponseException $e) {
				// When Graph returns an error
				echo 'Graph returned an error: ' . $e->getMessage();
				//exit;
			} catch(FacebookSDKException $e) {
				// When validation fails or other local issues
				echo 'Facebook SDK returned an error: ' . $e->getMessage();
				//exit;
			}
		}
	}
	
	
}