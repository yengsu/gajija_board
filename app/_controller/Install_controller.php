<?
use Gajija\controller\_traits\Controller_comm;
use system\traits\DB_NestedSet_Trait;
use Gajija\service\CommNest_service;
use Gajija\controller\_traits\Page_comm;

class Install_controller extends CommNest_service
{
	use Controller_comm, DB_NestedSet_Trait, Page_comm ;
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
	
	/**
	 * 공용 서비스
	 * 
	 * @var object
	 */
	public $CommNest_service ;
	
	public function __construct($routeResult)
	{
		if($routeResult)
		{
			// 라우팅 결과
			$this->routeResult = $routeResult ;

			// 웹서비스
			if( ! $this->WebAppService instanceof WebAppService )
			{
					// instance 생성
					$this->WebAppService = &WebApp::singleton("WebAppService:system");
					// Query String
					WebAppService::$queryString = Func::QueryString_filter() ;
					// base URL
					WebAppService::$baseURL = $this->routeResult["baseURL"] ;
					
					//self::$img_conf = WebApp::getConf_real("upload_img") ;
			}
		}
	}
	public function __destruct()
	{
		//unset($this);
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	/**
	 * 팝업 (window창)
	 */
	public function popupWin()
	{
		if(REQUEST_METHOD=="GET")
		{
			// P.K 코드 값이 없을경우
			if( ! (int)$_GET["code"] )
			{	// exception
				exit;
			}
			$this->setTableName("popups") ;
			$data = $this->dataRead( array(
					"columns"=> '*',
					"conditions" => array("serial" => (int)$_GET["code"], 'output'=> 'win')
			));
			if( !empty($data) )
			{
				$data = array_pop($data);
				$file = $data['attach_basedir'].$data['attach_file'];
				
				if( is_file($file) )
				{
					$this->WebAppService->Output( $file, "base");
					$this->WebAppService->printAll();
				}
			}
		}
	}
	
	public function index()
	{
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString //Func::QueryString_filter()
				)
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("install.html"), "install");
		//echo '<pre>';print_r($this->WebAppService->Display) ;
		$this->WebAppService->printAll();
	}
	
	public function post()
	{
		$file = 'sql/mysql/tables.sql' ;
		if( is_file($file) )
		{
			//ob_start();
			//$json_string = file_get_contents($file) ;
			$fh = fopen($file, 'r');
			$sql_string = fread($fh, filesize($file));
			fclose($fh);
			//ob_end_clean();
		
			$this->_multiQuery($sqls) ;
		}
		else{
			$this->WebAppService->assign(array('error'=>$file. " 파일이 없습니다."));
		}
		
	}
	
}