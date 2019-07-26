<?
use system\traits\DB_NestedSet_Trait;
use Gajija\controller\_traits\AdmController_comm;
use Gajija\service\CommNest_service;

/**
 * 
 * 
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
class Popup_controller extends CommNest_service
{
	use DB_NestedSet_Trait, AdmController_comm;
	
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
	 * 첨부파일 기본경로
	 * 
	 * @var string
	 */
	private $attach_basedir = 'theme/'.THEME.'/_attach/popup/'; //'html/_attach/popup/' ;
	
	public function __construct($routeResult)
	{
		
		if($routeResult)
		{
			// DB Table 선언
			$this->setTableName("popups");
			
			// 라우팅 결과
			$this->routeResult = $routeResult ;
		}
		// 웹서비스
		if(!$this->WebAppService  || !class_exists('WebAppService'))
		{
				// instance 생성
				$this->WebAppService = &WebApp::singleton("WebAppService:system");
				
				// Query String
				WebAppService::$queryString = Func::QueryString_filter() ;
				// base URL
				WebAppService::$baseURL = $this->routeResult["baseURL"] ;
				
				if(!self::adm_hasLogin(array('flag'=>true, 'queryString'=>REQUEST_URI)) ){
				    //You have been signed out. Please login again.
				    $this->WebAppService->assign( array("error"=>"로그아웃되었습니다. 다시 로그인해주세요.") );
				}
		}

	}
	
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k, $obj);
		}
	}

	public function add()
	{
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => "write",
						'queryString' => Func::QueryString_filter(),
						/* 'formType' => "등록" */
				        'formType' => "add"
				),
				'ATTACH_BaseDir' => $this->attach_basedir
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("adm/popup/popup_edit.html"), "admin_sub");
		$this->WebAppService->printAll();
	}
	/**
	 * 데이타 가공
	 * 
	 * @param POST
	 */
	private function data_process()
	{
		// 시작일자, 만료일자 가공
		if( preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['sdate']) &&
				preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['edate']) )
		{
		//if( (string)$_POST['sdate'] && (string)$_POST['edate'] ){
			$sdate = explode('-', $_POST['sdate']) ;
			$edate = explode('-', $_POST['edate']) ;
			$_POST['sdate'] = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]);
			$_POST['edate'] = mktime(23, 59, 59, $edate[1], $edate[2], $edate[0]);
		}else{
			// 무제한
			/* $t_sdate = date('Y-m-d', time()) ;
			$t_edate = date('Y-m-d', strtotime ("+100 years")) ;
			$sdate = explode('-', $t_sdate) ;
			$edate = explode('-', $t_edate) ;
			
			$_POST['sdate'] = mktime(0, 0, 0, $sdate[1], $sdate[2], $sdate[0]) ;
			$_POST['edate'] = mktime(23, 59, 59, $edate[1], $edate[2]) ; */
		}
	}
	/**
	 * 본문 첨부파일 저장
	 * @param integer $code
	 * @return multitype:string
	 */
	private function attach_write($code)
	{
		if($_POST['attach_file_cont']){
			if($_POST['attach_file']) $attach_file = $_POST['attach_file'];
			else $attach_file = 'popup.'.OID . '.'. $code.'.html' ;
			
			$file = $this->attach_basedir.$attach_file ;
			if( !is_file($file) )
			{
				$this->WebAppService->File->file($file, 'w');
				$this->WebAppService->File->write($_POST['attach_file_cont']);
				$this->WebAppService->File->close();
			}
			else{
				//$this->WebAppService->assign(array('error'=> $file.' 이미 존재합니다.'));
			}
		}
		
		
		return array(
				'attach_file' => (string)$attach_file,
		) ;
	}
	/**
	 * 본문 첨부파일 제거
	 * @param array $data ("attach_basedir"=>??, "attach_top"=>??, "attach_bottom"=>??)
	 * @return void
	 */
	private function attach_delete($data){
		if( is_file($data['attach_basedir'].$data['attach_file']) ) $this->WebAppService->File->delete($this->attach_basedir.$data['attach_file']);
		$this->WebAppService->File->close();
		unset($data);
	}
	public function write()
	{
		if(REQUEST_METHOD=="POST")
		{
			if( empty($_POST["title"]) ) $this->WebAppService->assign(array('error'=>'제목을 입력해주세요.'));
			
			$this->data_process() ;
			
			$code = $this->getInsertID(1) ;
			$attach = $this->attach_write($code);
			
			$put_data = array(
					"oid" => (int)OID,
					"title" => (string) $_POST["title"],
					
					"width" => (int) $_POST["width"],
					"height" => (int) $_POST["height"],
					"output" => (string) $_POST["output"],
					
					"imp" => (int) $_POST["imp"],
					"sdate" => (int) $_POST["sdate"],
					"edate" => (int) $_POST["edate"],
					
					"attach_basedir" => $this->attach_basedir,
					"attach_file" => str_replace(' ', '', $attach["attach_file"]),
					//"memo" => (string) $_POST["memo"],
					"regdate" => time()
			) ;
			
			$insert_id = $this->dataAdd( $put_data	) ;
			if($insert_id)
			{
				header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
				exit;
			}
			else{
			    WebApp::moveBack("저장실패~다시입력해주세요.");
			    //WebApp::moveBack("Failed to save. Please re-enter.");
			}
		}
	}
	
	public function edit()
	{
		if(REQUEST_METHOD=="GET")
		{
			// P.K 코드 값이 없을경우
			if( ! $this->routeResult["code"] )
			{	// exception
				header("Location: /".WebAppService::$baseURL."/add"); // 신규작성 폼으로 이동
				exit;
			}
			
			$data = $this->dataRead( array(
					"columns"=> '*',
					"conditions" => array("serial" => $this->routeResult["code"])
			));
			if( !empty($data) )
			{
				$data = array_pop($data);
				
				$data['sdate'] = ( (int)$data['sdate'] ) ? date('Y-m-d', $data['sdate']) : ''; 
				$data['edate'] = ( (int)$data['edate'] ) ? date('Y-m-d', $data['edate']) : '';
				
				if( !empty($data['attach_file']) && is_file($this->attach_basedir.$data['attach_file']) ){
					$this->WebAppService->File->file($this->attach_basedir.$data['attach_file'], 'r');
					$data['attach_file_cont'] = $this->WebAppService->File->readfile();
				}
			}
			else
			{
				WebApp::moveBack();
				//header("Location: /".WebAppService::$baseURL."/add"); // 신규작성 폼으로 이동
				//exit;
			}
		}
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => "update",
						"CODE" => $this->routeResult["code"],
						'queryString' => Func::QueryString_filter(),
						/* 'formType' => "편집" */
				        'formType' => "edit"
				)
				,'ATTACH_BaseDir' => $this->attach_basedir
				,'DATA' => $data
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("adm/popup/popup_edit.html"), "admin_sub");
		$this->WebAppService->printAll();
	}
	
	public function update()
	{
		if( ! (int) $this->routeResult["code"] )
		{	// exception
			WebApp::moveBack();
		}
		
		if( empty($_POST["title"]) ) $this->WebAppService->assign(array('error'=>'제목을 입력해주세요.'));
		
		$getData = $this->dataRead(array(
				"columns" => "serial, attach_basedir, attach_file",
				"conditions" => array("serial" => $this->routeResult["code"])
		));
		
		if( empty($getData) ) {
			//Exception
			$this->WebAppService->assign(array(
					"error" => "업데이트할 자료가 존재하지 않습니다."
			));
			exit ;
		}
		
		$this->attach_delete(array(
				'attach_basedir' => $getData[0]['attach_basedir'], //$this->attach_basedir,
				'attach_file'=>$getData[0]['attach_file'],
		));
		$attach = $this->attach_write($getData[0]['serial']);
		
		$this->data_process() ;
		
		$put_data = array(
				"title" => (string) $_POST["title"],
				
				"width" => (int) $_POST["width"],
				"height" => (int) $_POST["height"],
				"output" => (string) $_POST["output"],
				
				"imp" => (int) $_POST["imp"],
				"sdate" => (int) $_POST["sdate"],
				"edate" => (int) $_POST["edate"],
				
				"attach_basedir" => $this->attach_basedir,
				"attach_file" => str_replace(' ', '', $attach["attach_file"])
				//"memo" => (string) $_POST["memo"]
		) ;

		$res = $this->dataUpdate($put_data, array(
				"serial" => $this->routeResult["code"]
		)) ;
		//if($res){}
		//header("Location: ".WebAppService::$baseURL."/edit/".$this->routeResult["code"].WebAppService::$queryString); // 리스트 페이지 이동
		header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
		exit;
		
	}
	public function delete()
	{
		if( (int) $this->routeResult["code"])
		{
			$res = $this->dataDelete( array(
					"serial" => (int)$this->routeResult["code"]
			)) ;
			if( !empty($res) ) WebApp::redirect(WebAppService::$baseURL."/lst".WebAppService::$queryString, '삭제되었습니다.') ;
		}
		else{
			header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
			exit;
		}
	}
	
	/**
	 * 팝업 리스트
	 * 
	 * @return array
	 */
	private function get_popups()
	{
	    $this->pageScale = 15;
	    $this->pageBlock = 10 ;
	    
	    // 조건검색
	    $search_params = array() ;
	    if( $_REQUEST['search_field'] && !preg_match("/[[:space:]]+/u", $_REQUEST['search_keyword']) ){
	    	//$params[$_POST['search_field']." like CONCAT('%',?,'%')"] = $_POST['keyword'] ;
	    	$search_params[$_REQUEST['search_field']." like ?"] = "%".$_REQUEST['search_keyword']."%" ;
	    	
	    	$queryString = array(
	    			"search_field" => $_REQUEST['search_field'],
	    			"search_keyword" => $_REQUEST['search_keyword']
	    	) ;
	    }
	    else{
	    	$_REQUEST['search_keyword']='';
	    }
	    
		$queryOption = array(
				"columns" => "*", // serial, oid, title, sdate, edate, imp, regdate",
				"conditions" => $search_params,
				"order" => "regdate desc"
		);
		try{
			$datas = $this->dataList( $queryOption);
			if(!empty($datas))
			{
				foreach($datas as &$data)
				{
					// 첨부파일(내용)
					if( !empty($data['attach_file']) && is_file($data['attach_basedir'].$data['attach_file']) ){
						$data['attach'] = $data['attach_basedir'].$data['attach_file'];
					}
					// 노출기간
					if($data['sdate'] && $data['sdate']){
						$data['sdate'] = date('Y-m-d H:i', $data['sdate'])	;
						$data['edate'] = date('Y-m-d H:i', $data['edate'])	;
						$data['imp_date'] =  $data['sdate']. "~". $data['edate'] ;
					}
					$data['regdate'] = date('Y-m-d H:i', $data['regdate'])	;
					
					unset($data['attach_basedir'], $data['attach_file'], $data['sdate'], $data['edate']);
				}
				unset($data);
			}
		}catch(Exception $e){
			$this->WebAppService->assign( array(
					"error"=>$e->getMessage(),
					"error_code" => $e->getCode()
			) );
		}
		
		$paging = $this->Pagination($_REQUEST[self::$pageVariable], $queryString);
		
		WebAppService::$queryString = Func::QueryString_filter( $queryString );
		
		return array(
				'LIST' => &$datas,
				'TOTAL_CNT' => self::$Total_cnt,
				'VIEW_NUM' => self::$view_num,
				'PAGING' => &$paging
			) ;
	}
	
	public function lst()
	{
		$datas = $this->get_popups() ;
		
		$this->WebAppService->assign(array_merge(
				array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'queryString' => WebAppService::$queryString
				)), 
				(array)$datas
			
		));
		//echo '<pre>';print_r($this->WebAppService->Display);
		$this->WebAppService->Output( Display::getTemplate("adm/popup/popup_list.html"), "admin_sub");
		$this->WebAppService->printAll();
	}
	public function preview()
	{
		if(REQUEST_METHOD=="GET")
		{
			// P.K 코드 값이 없을경우
			if( ! $this->routeResult["code"] )
			{	// exception
				exit;
			}
			
			$data = $this->dataRead( array(
					"columns"=> '*',
					"conditions" => array("serial" => $this->routeResult["code"])
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
	/**
	 * 데이타 가져오기
	 *
	 * ajax(비동기 처리)
	 * @return object
	 */
	public function Req_getData()
	{
		// P.K 코드 값이 없을경우
		if( ! $this->routeResult["code"] )
		{	// exception
			$this->WebAppService->assign( array("error"=>"데이타를 찾을 수 없습니다.") );
		}
		
		$data = $this->dataRead( array(
				"columns"=> '*',
				"conditions" => array("serial" => $this->routeResult["code"])
		));
		if( !empty($data) )
		{
			$data = array_pop($data) ;
			
			if( !empty($data['attach_file']) && is_file($this->attach_basedir.$data['attach_file']) ){
				$this->WebAppService->File->file($this->attach_basedir.$data['attach_file'], 'r');
				$data['attach_file_cont'] = $this->WebAppService->File->readfile();
			}
			$data['url'] = "/adm/Popup/preview/". $data['serial'] ;
			
			
			$this->WebAppService->assign($data);
			exit;
		}
		else{
			$this->WebAppService->assign('');
			exit;
		}
	}
	/**
	 * 본문 첨부파일 내용읽기
	 *
	 * ajax(비동기 처리)
	 * @return object
	 */
	public function getFile()
	{
		// attach-file Read
		if( !empty($_POST['file']) ){
			$this->WebAppService->File->file($this->attach_basedir.$_POST['file'], 'r');
			//$this->WebAppService->File->readfile();
			$file_content = $this->WebAppService->File->readfile();
			$this->WebAppService->File->close();
			
			$this->WebAppService->assign($file_content);
			exit;
		}
	}
	
	
}