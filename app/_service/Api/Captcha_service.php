<?
namespace Gajija\service\Api ;


/**
 * @desc CAPTCHA Service
 * 
 * @tutorial 
 * 
 * @author yengsu lee
 * @email yengsu@gmail.com
 */
class Captcha_service
{
	/**
	 * @desc Api 제공업체 이름
	 * @var string
	 */
	public static $Provider = "google" ; 
	
	/**
	 * @desc Google-CAPTCHA API 요청 주소
	 * @var string
	 */
	protected static $API_HOST = "https://www.google.com/recaptcha/api/siteverify";
	
	public static $apiKey = array(
			/* 
			"www.test.com" => array(
					'site_key' => "6Ldt61MUAAAAAHfUyH0ZrKb",
					'secret_key' => "6Ldt61MUAAAAAAg8RHPv"
			),
			"demo.test.com" => array(
					'site_key' => "6Ldt61MUAAAAAHfUyH0ZrKb0KK",
					'secret_key' => "6Ldt61MUAAAAAAg8RHPvbh"
			)
			 */
	);
	
	public function __construct()
	{
		//throw new \Exception ( "SMS 서비스준비중입니다..", 501);
	}
	
	/**
	 * @desc KAKAO 서버에 요청
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
	 * @desc Captcha Google에 요청
	 * 
	 * @tutorial https://developers.google.com/recaptcha/docs/verify
	 * @param string $recaptcha_response 클라이언트로 부터 받은값($_REQUEST['g-recaptcha-response'])
	 * @return array
	 * @example return {
			  "success": true|false,
			  "challenge_ts": timestamp(2018-04-18T08:19:58Z),  // timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
			  "hostname": string,         // the hostname of the site where the reCAPTCHA was solved
			  "error-codes": [...]        // optional
			}
	 */
	public function send($recaptcha_response)
	{
		$QueryString = "secret=". self::$apiKey[HOST]['secret_key'] ;
		$QueryString .= "&response=". $recaptcha_response ;
		$QueryString .= "&remoteip=". $_SERVER["REMOTE_ADDR"] ;
		
		$result = $this->_Request(
				static::$API_HOST,
				array("post" => $QueryString)
				) ;
		
		//echo '<pre>';print_r($result);exit;
		//if( (int)$result["info"]["http_code"] == 200)
		if( !empty($result["response"]["success"]) ) return true ;
		else return false ;
	}

}
