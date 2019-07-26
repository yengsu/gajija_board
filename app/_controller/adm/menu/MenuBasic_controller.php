<?
use Gajija\service\CommNest_service;
use Gajija\controller\_traits\AdmController_comm;

/**
 * 게시판 환경설정 &
 * 기본 게시판 컨트롤러
 */

//include_once _APP_PATH.'_service/CommNest_service.php';

class MenuBasic_controller extends CommNest_service
{
	//use Singleton ;
	use AdmController_comm ;

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
	private $attach_basedir = 'html/_attach/' ;
	
	/**
	 * 회원 환경정보
	 *
	 * @filesource conf/member.conf.php
	 * @var array
	 */
	public static $mbr_conf = array();
	
	public function __construct($routeResult)
	{

		if($routeResult)
		{
			self::$mbr_conf["grade"] = WebApp::getConf("member.grade");

			// DB Table 선언
			$this->setTableName("menu");
			
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
					WebAppService::$baseURL = '/'.$this->routeResult["folder"].$this->routeResult["controller"] ;
					
					if(!self::adm_hasLogin(array('flag'=>true, 'queryString'=>REQUEST_URI)) ){
						//You have been signed out. Please login again.
						$this->WebAppService->assign( array("error"=>"로그아웃되었습니다. 다시 로그인해주세요.") );
					}
			}
		}
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k, $obj);
		}
	}
	/**
	 * 게시판 - 리스트 목록 페이지
	 * 
	 */
	public function lst($out)
	{

		$this->pageScale = 50;
		$this->pageBlock = 10;

		// 조건검색
		$search_params = array() ;
		if($_REQUEST['bid']) $search_params['bid'] = $_REQUEST['bid'] ;
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
		
		$_REQUEST[self::$pageVariable] = $_GET[self::$pageVariable] ;
		$queryOption = array(
								"columns" => "serial, family, parent, lft, rgt, indent, mcode, title, layout, tpl, used, imp",
								"conditions" => $search_params
								//, "order" => ""
							);

		$datas = $this->dataList( $queryOption, 1 ) ;
		
		if( is_array($datas) ){
			foreach($datas as $k => $v)
			{
				$datas[$k]["used"] = ($datas[$k]["used"]) ? "사용" : "사용안함";
				$datas[$k]["imp"] = ($datas[$k]["imp"]) ? "노출" : "노출안함";
				$datas[$k]["title"] = str_repeat("--->", $datas[$k]["indent"] ). $datas[$k]["title"] ;
			}
		}

		
		$paging = $this->Pagination($_REQUEST[self::$pageVariable], $queryString);

		WebAppService::$queryString = Func::QueryString_filter( $queryString );

		$this->WebAppService->assign(array(
												'Doc' => array(
														'baseURL' => WebAppService::$baseURL,
														'queryString' => WebAppService::$queryString
												),
												'LIST' => $datas,
												'TOTAL_CNT' => self::$Total_cnt, //$this->count(),
												'VIEW_NUM' => self::$view_num,
												'PAGING' => $paging,
												'CTEL' => CTEL,
												'CFAX' => CFAX,
												'CKEYWORDS' => CKEYWORDS
		));

		
		if( is_array($out) )
		{
			$this->WebAppService->Output( Display::getTemplate('html/adm/menu/basic/list.html'),'admin_sub');
			$this->WebAppService->printAll();
		}
		else{ 
			$this->WebAppService->Display->define('BOARD_LIST', 'html/adm/menu/basic/list.html' );
		}
	}
	public function view()
	{
		if(REQUEST_METHOD=="GET")
		{
			//Exception
			if( !$this->routeResult["code"] )
				WebApp::moveBack("데이타가 존재하지 않습니다") ;
			
			$data = $this->dataRead(array(
									"columns"=> '*',
									"conditions" => array(
															"serial" => $this->routeResult["code"],
															"bid" => $_REQUEST["bid"]
													)
					));

			//Exceptionn
			if( empty($data) )
				WebApp::moveBack("데이타가 존재하지 않습니다") ;
			else 
				$data = array_pop($data) ;

			$data["memo"] = nl2br($data["memo"]);
			
			// 코멘츠 기능 사용체크 및 가져옴
			if($this->boardInfoResult["comments"])
			{
					
					$this->setTableName("comments");
					$comments_datas = $this->getBrdCommentsList(
														array("bid" => $_REQUEST["bid"]), 
														array(
																"bid" => $_REQUEST["bid"],
																"bserial" => $this->routeResult["code"]
														) 
									);
					
					/* $this->WebAppService->assign(array( "COMMENTS_LIST"=> $comments_datas) ) ;
					$this->WebAppService->Display->define('COMMENTS', 'html/components/comments/skin/default/base.html'); */
			}


			
			$this->WebAppService->Display->setLayout('admin');
			
			$this->WebAppService->Display->define('CONTENT', 'html/adm/menu/basic/view.html');
			//$this->WebAppService->Output( Display::getTemplate('html/board/skin/boardComm/view.htm'), "sub2");

			$this->setTableName('board');
			self::$pageVariable = 'page';
			$this->lst(true);

			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'Action' => "edit",
							"CODE" => $this->routeResult["code"],
							'queryString' => Func::QueryString_filter(),
							'formType' => "보기",
							'Board_indent' => $this->boardInfoResult["indent"],
							'formHiddenVar' => array(
									'tbl_name'=>'comments'
							)
					),
					'DATA' => $data
			)) ;
			
			$this->WebAppService->printAll();
			//echo '<pre>';print_r($this->WebAppService->Display);
			//$this->WebAppService->Display->html_body = $this->WebAppService->Display->fetch('CONTENT');
			//$this->WebAppService->Display->html_body = $this->WebAppService->Display->fetch('CONTENT');

			//file_put_contents($save_JSONfile, $unescaped);
			//ob_flush();
			//$this->setTableName("board");
			//$this->lst();
		}
	}
	/**
	 * [ 신규 / 추가] 작성폼
	 */
	public function add()
	{
		if(REQUEST_METHOD=="GET")
		{
			// [답변형 or 계층형]
			if( $this->routeResult["code"] )
			{
				$data = $this->dataRead( array(
									"columns"=> '*',
									"conditions" => array(
															"serial" => $this->routeResult["code"]
														)
					));

				$data = array_pop($data) ;
			}
				
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'Action' => "write",
							"CODE" => $this->routeResult["code"],
							'queryString' => Func::QueryString_filter(),
							'formType' => "등록"
					),
					'LAYOUTS' => $this->get_conf('layout',true),
					'ATTACH_BaseDir' => $this->attach_basedir,
					'DATA' => $data,
			        'MBR_GRADES' => $this->get_grades()//self::$mbr_conf["grade"]
			)) ;
			$this->WebAppService->Output( Display::getTemplate('html/adm/menu/basic/edit.html'), "admin_sub");
			$this->WebAppService->printAll();
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
	public function getValidate($vars)
	{
		if( is_array($vars) )
		{
			$rule = array(
					'mcode' => array(
							'label' => '메뉴코드를',
							'rules' => 'required|numeric'
					),
					'title' => array(
							'label' => '메뉴 명을',
							'rules' => 'required|whitespace'
					)
			) ;
			
			$rules = array_intersect_key($rule, array_flip($vars));
			$error = $this->WebAppService->Validate($rules) ;
			if( is_array($error) ) $error = array_pop($error);
			if( is_array($error) ) $error = array_pop($error);
			
			return $error ;
		}
	}
	/**
	 * 게시판 - DB 저장
	 */
	public function write()
	{
		if(REQUEST_METHOD=="POST")
		{
			$error = $this->getValidate( array(
					//"mcode",
					"title"
			)) ;
			
			if( !empty($error) ){
				WebApp::moveBack($error);
			}
			
			$code = $this->getInsertID(1) ;
			
			if(!$code) {
				//Exception
				$this->WebAppService->assign(array(
						"error" => "저장되지 않았습니다."
				));
				exit ;
			}
			
			$attach = $this->attach_write($code);
			
			//$_POST["frm"]["title"] = rand(1,1000).$_POST["frm"]["title"] ;
			$put_data = array(
								"serial" => $code,
								"oid" => (int) OID,
								"mcode" => (int) $_POST["mcode"],
								"title" => $_POST["title"],
								"url" => $_POST["url"],
								"url_target" => $_POST["url_target"],
								"layout" => $_POST['layout'],
								"tpl" => str_replace(' ','', $_POST['tpl']),
								"used" => (int) $_POST['used'],
								"imp" => (int) $_POST['imp'],
								"attach_basedir" => $this->attach_basedir,
								"attach_top" => str_replace(' ', '', $attach["body_top_file"]),
								"attach_bottom" => str_replace(' ', '', $attach["body_bottom_file"]),
								"grant_read" => (int) $_POST["grant_read"]
								//,"grant_write" => (int) $_POST["grant_write"]
								
						);

			$insert_id = $this->dataAdd( $put_data, $this->routeResult["code"]	) ;
			
			if($insert_id)
			{
				header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
				exit;
			}
			else{
				//Exception
				WebApp::moveBack("저장실패~다시입력해주세요.");
			}

		}

	}
	/**
	 * 게시판 편집페이지
	 */
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
			
			/* if( !empty($data[0]['tpl']) && is_file($data[0]['tpl']) ){
				$this->WebAppService->File->file($data[0]['tpl'], 'r');
				$data[0]['tpl_cont'] = $this->WebAppService->File->readfile();
			} */
			$menu_datas = $this->getMenu('menu', array(
					"serial" => $this->routeResult["code"],
					"columns" => "serial, indent, lft, rgt, title, layout, tpl, url, url_target, grant_read, grant_write",
					//"conditions" => $conditions
			)) ;
			//if( !empty($menu_datas['childs']) ) $this->TNst_renderTree($menu_datas['childs']);
			//echo '<pre>';print_r($menu_datas);
			// attach-file Read
			if( !empty($data[0]['attach_top']) && is_file($this->attach_basedir.$data[0]['attach_top']) ){
				$this->WebAppService->File->file($this->attach_basedir.$data[0]['attach_top'], 'r');
				$data[0]['attach_top_cont'] = $this->WebAppService->File->readfile();
			}
			if( !empty($data[0]['attach_bottom']) && is_file($this->attach_basedir.$data[0]['attach_bottom']) ){
				$this->WebAppService->File->file($this->attach_basedir.$data[0]['attach_bottom'], 'r');
				$data[0]['attach_bottom_cont'] = $this->WebAppService->File->readfile();
				$this->WebAppService->File->close();
			}
			
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'Action' => "update",
							"CODE" => $this->routeResult["code"],
							'queryString' => Func::QueryString_filter(),
							'formType' => "편집"
					),
					'LAYOUTS' => $this->get_conf('layout',true),
					'ATTACH_BaseDir' => $this->attach_basedir,
					'DATA' => $data[0],
			     	'MBR_GRADES' => $this->get_grades(),//self::$mbr_conf["grade"]
					'MNU' => &$menu_datas['path']
			)) ;
			$this->WebAppService->Output( Display::getTemplate('html/adm/menu/basic/edit.html'), "admin_sub");
			$this->WebAppService->printAll();
		}
		else{
			// exception
		}
	}
	
	/**
	 * 게시판 - DB 업데이트
	 */
	public function update()
	{
		if(REQUEST_METHOD=="POST")
		{
			
			$error = $this->getValidate( array(
					"mcode",
					"title"
			)) ;
			if( !empty($error) ){
				WebApp::moveBack($error);
			}
			
			$getData = $this->dataRead(array(
					"columns" => "serial, attach_top, attach_bottom",
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
					'attach_basedir' => $this->attach_basedir,
					'attach_top'=>$getData[0]['attach_top'],
					'attach_bottom'=>$getData[0]['attach_bottom']
			));
			$attach = $this->attach_write($getData[0]['serial']);
			
			
			$res = $this->dataUpdate(
							array(
									"mcode" => (int) $_POST["mcode"],
									"title" => $_POST["title"],
									"url" => $_POST["url"],
									"url_target" => $_POST["url_target"],
									"layout" => $_POST['layout'],
									"tpl" => str_replace(' ','', $_POST['tpl']),
									"used" => (int) $_POST['used'],
									"imp" => (int) $_POST['imp'],
									"attach_basedir" => $this->attach_basedir,
									"attach_top" => str_replace(' ', '', $attach["body_top_file"]),
									"attach_bottom" => str_replace(' ', '', $attach["body_bottom_file"]),
									"grant_read" => (int) $_POST["grant_read"]
									//,"grant_write" => (int) $_POST["grant_write"]
							),
							array(
									"serial" => $this->routeResult["code"]
							)
					) ;
			if(!$res){
				//Exception
				WebApp::moveBack("업데이트할 자료가 존재하지 않습니다.");
			}

			header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
			exit;
		}
		
	}
	/**
	 * DB 삭제
	 * --> 본문 첨부파일은 수동으로 삭제해야함
	 * --> 이유 : 다른 본문에서 사용할 수 있기때문
	 */
	public function delete()
	{
		
		if($this->routeResult["code"])
		{

			$res = $this->dataDelete( 
							array(
									"serial" => $this->routeResult["code"]
							) 
					) ;
			
			if(!$res){
				//Exception
				WebApp::moveBack("삭제할 자료가 존재하지 않습니다.");
			}
		}
		header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
		exit;
	}
	/**
	 * 본문 첨부파일 저장
	 * @param integer $code
	 * @return multitype:string
	 */
	private function attach_write($code){
		//상단-header저장
		if($_POST['attach_top_cont']){
			if($_POST['attach_top']) $body_top_file = $_POST['attach_top'];
			else $body_top_file = $code.'.body.top.htm' ;
	
			$this->WebAppService->File->file($this->attach_basedir.$body_top_file, 'w');
			$this->WebAppService->File->write($_POST['attach_top_cont']);
		}
		//하단-footer저장
		if($_POST['attach_bottom_cont']){
			if($_POST['attach_bottom']) $body_bottom_file = $_POST['attach_bottom'];
			else $body_bottom_file = $code.'.body.bottom.htm' ;
	
			$this->WebAppService->File->file($this->attach_basedir.$body_bottom_file, 'w');
			$this->WebAppService->File->write($_POST['attach_bottom_cont']);
		}
		$this->WebAppService->File->close();
	
		return array(
				'body_top_file' => (string)$body_top_file,
				'body_bottom_file' => (string)$body_bottom_file
		) ;
	}
	/**
	 * 본문 첨부파일 제거
	 * @param array $data ("attach_basedir"=>??, "attach_top"=>??, "attach_bottom"=>??)
	 * @return void
	 */
	private function attach_delete($data){
		if( is_file($data['attach_basedir'].$data['attach_top']) ) $this->WebAppService->File->delete($this->attach_basedir.$data['attach_top']);
		if( is_file($data['attach_basedir'].$data['attach_bottom']) ) $this->WebAppService->File->delete($this->attach_basedir.$data['attach_bottom']);
		$this->WebAppService->File->close();
		unset($data);
	}
	/**
	 * conf파일 설정정보 가져오기
	 *
	 * @param string $INI ( $INI.conf.php )
	 * @param boolean $get_key ( 키값만 추출할건지 )
	 * @return array|null
	 */
	public function get_conf($INI, $get_key){
		$data = WebApp::getConf_real($INI);
		ksort($data) ;
	
		if($get_key)
			return array_keys($data);
		else
			return $data;
	}
	
	/**
	 * 본문 첨부파일 내용읽기
	 * 
	 * ajax(비동기 처리)
	 * @return string json
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