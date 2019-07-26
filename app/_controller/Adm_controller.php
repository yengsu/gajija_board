<?php
use Gajija\service\CommNest_service;

/**
 * Admin 메인 &
*/
class Adm_controller extends CommNest_service
{
	//use Singleton ;

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
	public $routeResult = array();


	public function __construct($routeResult)
	{
		if($routeResult)
		{
				
			// 라우팅 결과
			$this->routeResult = $routeResult ;
				
			// 웹서비스
			if(!$this->WebAppService)
			{
				// instance 생성
				$this->WebAppService = &WebApp::singleton("WebAppService:system");
				// Query String
				WebAppService::$queryString = Func::QueryString_filter() ;
				// base URL
				//WebAppService::$baseURL = '/'.$this->routeResult["folder"].$this->routeResult["controller"] ;
				WebAppService::$baseURL = $this->routeResult["baseURL"] ;
			}
		}
	}
	/**
	 * 유효성 검사
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
					'Auserid' => array(
							'label' => 'Email',
							//'rules' => 'required|alpha_numeric|min_char[5]|max_char[20]'
							//'rules' => 'required|email|min_char[5]|max_char[20]'
							'rules' => 'required|email'
							//'rules' => 'required|whitespace'
					),
					'Auserpw' => array(
							'label' => 'Password ',
							'rules' => 'required|min_char[6]|max_char[15]'
							//'rules' => 'required|alpha_numeric|min_char[5]|max_char[20]'
					)
					/* 'musername' => array(
							'label' => 'name ',
							'rules' => 'required|whitespace'
							//'rules' => 'required|whitespace'
					),
					'mhp' => array(
							'label' => 'Cell Phone ',
							'rules' => 'required|natural'
							//'rules' => 'required|whitespace'
					) */
			) ;
			
			$rules = array_intersect_key($rule, array_flip($vars));
			$error = $this->WebAppService->Validate($rules, true) ;
			
			if( is_array($error) ) $error = array_pop($error);
			if( is_array($error) ) $error = array_pop($error);
			
			return $error ;
		}
	}
	/**
	 * 세션 변수 저장
	 * @param array<key,value> $vars
	 * @return void
	 */
	private function add_session($vars)
	{
		if( is_array($vars) )
		{
			foreach( $vars as $k => $v){
				if( !empty($v) )
					$_SESSION[strtoupper($k)] = $v;
			}
			//if( !$_SESSION['REMOTE_ADDR'] ) $_SESSION['REMOTE_ADDR'] = getenv('REMOTE_ADDR');
		}
	}
	public function manage()
	{
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => "login",
						'queryString' => WebAppService::$queryString //Func::QueryString_filter(),
				)
		)) ;
		$this->WebAppService->Output( 'html/adm/login.html','adm_login');
		$this->WebAppService->printAll();
	}
	public function login()
	{
		if( REQUEST_METHOD == 'POST')
		{
			if(REQUEST_WITH != 'AJAX') {
				header('Location:/') ;	exit;
			}
			$error = $this->getValidate( array(
					"Auserid",
					"Auserpw"
			)) ;
			if( !empty($error) ){
				$this->WebAppService->assign( array("error"=>$error) );
			}
			
			$this->setTableName("member");
			$res = $this->dataRead(array(
					"columns" => '*',
					"conditions" => array(
							'is_admin' => 1,
							'userid' => (string)$_POST['Auserid'],
					        'userpw' => $this->WebAppService->Strings::encrypt_sha256( (string)$_POST['Auserpw'] )
					)
			));

			if( !empty($res) )
			{
				// login 성공
				$this->add_session( array(
						'mbrSerial' => (int) $res[0]['serial'],
						'mbrId' => (string) $res[0]['userid'],
						'mbrName' => (string) $res[0]['username'],
						'mbrNick' => (string) $res[0]['usernick'],
						'adm' => (int) $res[0]['is_admin']
						//'mbrGrade' => (int) $data[0]['grade'],
						//'mbrGradeName' => (string) $grade_name[0]['grade_name']
				)) ;
				
				/* // login 성공
				$this->add_session( array(
						'admSerial' => $res[0]['serial'],
						'admId' => $res[0]['userid'],
						'admName' => $res[0]['username'],
						//'mbrNick' => $res[0]['usernick'],
						//'mbrGrade' => $res[0]['grade'],
						//'mbrLev' => $res[0]['lev']
				)) ; */
				
				/* if($_POST['redir']) header('Location:'. urldecode($_POST['redir']) ) ;
				 else header('Location:/') ; */
				if($_POST['redir']) $url = urldecode($_POST['redir']);
				else $url = '/adm/Member/lst';
				
				$this->WebAppService->assign( $url ) ;
				exit;
				
			}else{
				// login 실패
				$this->WebAppService->assign(array('error'=>'Id or Password do not match.'));
			}
			
		}
	}
	/**
	 * 회원 로그아웃
	 *
	 * @return void
	 */
	public static function logout()
	{
	    unset( $_SESSION["ADMSERIAL"], $_SESSION["ADMID"], $_SESSION["ADMNAME"] );
		//session_destroy();
		
		header('Location:/Adm/manage') ;
		exit;
	}
	
}