<?php
namespace Gajija\controller\_traits ;
use Gajija\service\Member_service;

/**
 * Controller용 - 공용 메서드
 *
 */

trait Controller_comm{
	
	/**
	 * 회원형인 경우 로그인체크 및 리다이렉션
	 * 
	 * @param bool $return (true:로그인 유무 리턴 / false: 로그인페이지 redirection 이동)
	 */
	public function hasMemberLogin($return=false)
	{
	    if( ! $this->Member_service instanceof Member_service ){
			//$this->Member_service = new Member_service();//\service\Member_service ;
			$this->Member_service = Member_service ;
		}
		//$ret = $this->Member_service->hasLogin(array('flag'=>$flag, 'queryString'=>REQUEST_URI)) ;
		$ret = Member_service::hasLogin(array('return'=>$return, 'queryString'=>REQUEST_URI)) ;
		if( is_bool($ret) ) return $ret ;
	}
	
	/**
	 * 할인 계산 ( 소수점 올림처리 )
	 * 
	 * @param array $data  array('price'=>??, 'rate'=> ??)
	 */
	public static function discount_Calculate( $data )
	{
		return ceil( $data['price'] * (100 - $data["rate"])/100 ) ;
	}
}