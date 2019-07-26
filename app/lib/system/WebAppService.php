<?
//namespace system\core ;
/**
 * 웹서비스 Class Object
 * @author 이영수
 *
 */
class WebAppService
{
	public $Func ;
	public $File ;
	public $Strings ;
	public $Display ;
	public $Validation ;
	/**
	 * 현재 기본 URL1
	 * @var string
	 */
	public static $baseURL = NULL ;
	
	public static $queryString = NULL ;

	public function __construct()
	{
		$this->init();
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k, $obj);
		}
		
	}

	/**
	* 실행~~~~~~~~~~~~~~~~~~~~
	*/
	protected function init()
	{
		$mnu_params = '';
		// router로부터 정보를 받지못하는경우 종료(상위에서 처리했지만 한번더 체크^^)
		$this->Func = &WebApp::singleton('Func');
		$this->Strings = &WebApp::singleton('Strings'); 
		$this->File = &WebApp::singleton('File');
		//$this->ExceptionHandlers = &WebApp::singleton('ExceptionHandlers');

		//$this->GDimage = &WebApp::singleton('GDimage','graphic', "image파일");
		$this->Validation = &WebApp::singleton('Validation');
		$this->Validation->_debug=true;
		$this->Validation->set_error_tags('','');

		if(REQUEST_WITH != 'AJAX') 
		{
			$this->Display = &WebApp::singleton('Display'); // 출력처리 클래스
			$this->Default_intput(); // 기본정보(타이틀명,업체명,전화번호...)
		}
		
		//$this->set_xss_detect() ;
		
	}
	/**
	 * 유효성 검사
	 * 
	 * @param array<key,array> $rules
	 * @param boolean $flag ( true시 배열형 return  )
	 * 
	 * @return string|array
	 *
	 *      return 2가지 방식
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
	public function Validate($rules, $flag=true)
	{
		//$this->Validation->_debug = true ;
		$this->Validation->set_rules($rules);
		
		if(!$this->Validation->passed()){
			$errors=$this->Validation->errors("assoc");
			$msg=$this->Validation->errors();
			
			if( $flag == true ){
				return $errors ;
			}else{
				if( !empty($msg) ){
					foreach($msg as $k => $v) $msgs[] = array_pop($v) ;
					return $msgs[0] ;
				}
			}

		}
		
	}
	/**
	**	아래 두가지 방법으로 처리함
	**
	**		1. http방식 (템플릿 변수 대입) 
	**		2. ajax방식 (비동기식인경우 출력)
	******************************************
	*
	* @param : $data ( 값 또는 배열 데이타 )
	* @param : $request_type ( 응답방식 ) - ajax or http or header
	* @param : $jsonCallback_param ( jsonp인경우 추가파라미터 값 )
	*
	* @return : void형 : Template_ 변수대입 or Json데이타로 출력
	*
	* @tutorial 에러 또는 알림표시는 $data변수에. 예를들면 array("error" => "에러입니다.") 처럼 입력 
	*/
	public function assign($data, $request_type='http', $jsonCallback_param=''){

		if($request_type == 'ajax' || REQUEST_WITH == 'AJAX' )
		{
			echo $this->json_Callback($data, $jsonCallback_param) ;
			exit;
		}
		// http ( Template_ )
		else
		{ 
			if( isset($data['error']) || isset($data['ERROR']) ) 
			{
				if($request_type == 'header' )
				{
					$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1' ;
					//$protocol = 'HTTP/1.1';
					header($protocol." ".($data['error_code']?$data['error_code']:401)." ".rawurlencode($data['error']));
					echo $data['error']."<br><a href='#' onclick='history.back();return false;'>되돌아 가기</a>";
					exit;
				}

				if($data['error']) $errMSG = $data['error'] ;
				else if($data['ERROR']) $errMSG = $data['ERROR'] ;
				
				if( !empty($data['redirect']) ){
					WebApp::redirect($data['redirect'], $errMSG);
				}else if( empty($_SERVER["HTTP_REFERER"]) ){
					header('Location: /');
					exit;
				}else{
					WebApp::moveBack($errMSG);
				}
			}
			else{
				$this->Display->assign($data);
			}
		}
	}
	/**
	*	json, jsonp 출력 ( jsonp 는 콜백함수로 전달함 )
	*
	*	@param : $data ( 값 또는 배열 데이타 )
	*	@param : $kind (create, modify, update .....) - 구분값[jsonp일경우 사용]
	*
	*	@return : json형태 출력
	*                 - 함수(결과값) 또는 json데이타
	*/
	public static function json_Callback($data, $kind=''){
		
		if( isset($data['error']) || isset($data['ERROR']) ) {
			if($data['error']) $errMSG = $data['error'] ;
			else if($data['ERROR']) $errMSG = $data['ERROR'] ;
			
			//header('HTTP/1.0 400 '.rawurlencode($errMSG));
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1' ;
			//$protocol = 'HTTP/1.1';
			header($protocol . " ".($data['error_code']?$data['error_code']:401)." ".rawurlencode($errMSG));
			die();
		}
		
		if( !empty($_GET['callback']) ) 
			return $_GET['callback'] ."(".json_encode($data).", '".$kind."');" ;
		else 
			return json_encode($data) ;
	}
	/*************************
	*	Template_저장 영역
	**************************
	*	@param : $file ( 템플릿파일 : Home기준 Full 파일경로 )
	*	@param : $layout ( 레이아웃명 )
	*/
	public function Output($file, $layout=''){
		if(REQUEST_WITH != 'AJAX')
		{
			//if( !$layout ) $layout = 'blank';
			if( !$layout ) $layout = 'base';
			$this->Display->setLayout($layout);	//위에서 처리예정
	
			if( is_file($file) ){
				$this->Display->define('CONTENT', Display::getTemplate($file) );
			}else{
				$this->Display->define('CONTENT', _APP_PATH.'_html/blank.html' );
			}
		}
	}
	
	/********************************
	*	기본 홈페이지의 업체정보 
	*	템플릿에 저장
	********************************/
	public function Default_intput(){

		$this->assign(array(
			'TITLE' => TITLE,
			'CNAME' => CNAME,
			'CTEL' => CTEL,
			'CFAX' => CFAX,
			'CKEYWORDS' => CKEYWORDS,
		    'IS_MOBILE' => IS_MOBILE
		));
	}
	/************************
	*	출력 ( Template_ )
	************************/
	public function printAll(){
		if(REQUEST_WITH != 'AJAX'){
			
			$this->language_convert();
			$this->Display->printAll();
		}
	}
	public function language_convert()
	{
		if( $_REQUEST['lang']=='eng' || $_REQUEST['lang']=='tw'  ) {
		
			if( !is_dir("html_".$_REQUEST['lang']) ){
				echo 'not language found';
				exit;
			}
			
			foreach($this->Display->tpl_ as $block => &$tpl){
				$tpl['path'] = str_replace('html/', 'html_'.$_REQUEST['lang'].'/', $tpl['path']);
			}
		}
	}
	public static function refValues($arr)
	{
        if (strnatcmp(phpversion(),'5.3') >= 0) // PHP 5.3+이상에서 호환
        {
            $refs = array();
            foreach( $arr as $key => $value)
                $refs[] = &$arr[$key];
                //$refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }
    
    /**
     * 에러 디버깅 확인(실행절차 나열)
     */
    public static function debug_backtrace()
    {
    	echo "<span style='color:red;'>Error<br/>";
    	debug_print_backtrace();
    	echo "</span>";
    }
}
?>