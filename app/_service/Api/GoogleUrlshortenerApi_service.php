<?php
namespace Gajija\service\Api;


/**
 * @desc :: Googe URL Shortener Api 서비스 (url 단축링크 만들기)
 *
 * @author youngsu lee
 * @email yengsu@gmail.com
 * 
 * @tutorial https://goo.gl/
 * @tutorial https://developers.google.com/url-shortener/v1/getting_started#APIKey
 * @tutorial key 인증만 받으면 바로 사용가능
 */
class GoogleUrlshortenerApi_service
{
    /**
     * Api 제공업체 이름
     * @var string
     */
    public static $Provider = "google" ; 
    
	protected $client ;
	
	public static $apiKey = array(
			/* 
	       "www.test.com" => array(
    	               'clientId' => "IzaSyDC6IZxCnZZQ0FDL9ojP"
    	    ),
			"demo.test.com" => array(
					'clientId' => "IzaSyDC6IZxCnZZQ0FDL9ojP"
			)
			 */
	);
	
	public function __construct()
	{
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	/**
	 * @desc 실행하여 인증받기
	 * 
	 * @param string $url (단축링크 만들 url)
	 * @return string 단축링크 URL (ex : https://goo.gl/F9nhTX )
	 */
	public function shortURL( string $url=HOST)
	{
	    // 원하는 주소 구글에 등록후 url 입력하면됨
	    $post = array('longUrl' => "http://".$url);
	    $json = json_encode($post);
	    
	    $curlObj = curl_init();
	    
	    curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key='.self::$apiKey[HOST]['clientId']);
	    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($curlObj, CURLOPT_HEADER, 0);
	    curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
	    curl_setopt($curlObj, CURLOPT_POST, 1);
	    curl_setopt($curlObj, CURLOPT_POSTFIELDS, $json);
	    
	    $json = json_decode(curl_exec($curlObj));
	    
	    curl_close($curlObj);
	    
	    return $json->id;
	    
	}
	
}