<?php
require_once "app".
		DIRECTORY_SEPARATOR ."lib".
		DIRECTORY_SEPARATOR ."class.Psr4Autoloader.php" ;
		
/**
 *
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
class Base
{
	/**
	 * 자동로드 Provider
	 * @var object
	 */
	protected $Autoloader ;
	/**
	 * 범용 Provider
	 * @var object
	 */
	public $WebApp ;
	/**
	 * 메뉴 Provider
	 * @var object
	 */
	public $Menu;
	/**
	 * 사용자 정의함수 Provider
	 * @var object
	 */
	public $Func ;
	/**
	 * 파일 관련 Provider
	 * @var object
	 */
	public $File ;
	/**
	 * 라우팅 Provider
	 * @var object
	 */
	public $Router ;
	/**
	 * 템플릿엔진(template_) Provider
	 * @var object
	 */
	public $Display ;
	/**
	 * 유효성 검사 Provider
	 * @var object
	 */
	public $Validation ;
	/**
	 * 예외처리 Provider
	 * @var object
	 */
	public $Exception ;
	
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
	/* public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
	} */
	protected function init()
	{
		$this->ClassAutoload() ;
		
		$this->Router = WebApp::singleton('Router:system', "", "default", true);
	}
	private function systemConfig()
	{
		ini_set('error_reporting', E_ALL ^ E_NOTICE);
		ini_set('display_errors', 'on');
		@ini_set('register_globals', 'off');
		ini_set("include_path", ini_get("include_path").":./app/lib:./app/_model" );
		
		
	}
	private function globalVar()
	{
		define('DOC_CHARSET', WebApp::getConf('doc.charset')); // 테마
		define('THEME', WebApp::getConf('design.theme')); // 테마
		define('TITLE', WebApp::getConf('title')); // 타이틀명
		define('OID', WebApp::getConf('oid')); // 업체명
		define('CNAME', WebApp::getConf('cname')); // 업체명
		define('CTEL', WebApp::getConf('ctel')); // 업체 전화번호
		define('CFAX', WebApp::getConf('cfax')); // 업체 FAX번호
		define('BOARD_SKIN_HOME', WebApp::getConf('board.skin_home')); // 게시판 & 갤러리 스킨 홈디렉토리
		define('MNU_DEPTH', WebApp::getConf('menu.depth')); // 서브메뉴 노출시킬 깊이(depth)
	}
	/**
	 * 오토로드(autoload)
	 * @param array<key,value> $namespace
	 * @return void
	 */
	protected function ClassAutoload( array $namespace = NULL)
	{
		//spl_autoload_extensions(".php");
		$this->Autoloader = new lib\Psr4Autoloader ;
	
		$ns = array(
				'system' => 'app/lib/system',
				'Gajija\lib' => 'app/lib',
				'Gajija\Exceptions' => 'app/_exceptions',
				'Gajija\model' => 'app/_model',
				//'controller' => @realpath( dirname (__FILE__).'/../../').'app/_controller',
				'Gajija\controller' => 'app/_controller',
				'Gajija\service' => 'app/_service',
				'Gajija\interfaces' => 'app/_interface',
				//'app\controller' => 'app/_controller'
				'api' => 'app/lib/Api'
		) ;
		
		if( !empty($namspace) && is_array($namspace) ) $ns = array_merge($ns, $namspace) ;
		$this->Autoloader->addNamespaceArrays($ns, true);
		$this->Autoloader->register();
	}
	
}