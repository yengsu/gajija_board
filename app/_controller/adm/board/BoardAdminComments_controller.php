<?php
//use Gajija\service\board\admin\BoardInfo_service;
use Gajija\service\_traits\Service_Board_Trait;
use Gajija\controller\_traits\AdmController_comm;
use Gajija\service\board\BoardCommNest_service;
use Gajija\Exceptions\BaseException;

/**
 * 기본 게시판 컨트롤러
 */
class BoardAdminComments_controller extends BoardCommNest_service//BoardInfo_service
{
    use Service_Board_Trait, AdmController_comm ;
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
	 * 댓글 환경설정 DB테이블
	 * @var string
	 */
	public static $table_info = "comments_info" ;
	
	/**
	 * 댓글 기본 DB테이블명 & 권한(grants)테이블의 그룹명
	 * @var string
	 */
	public static $table_default = "comments" ;
	
	public function __construct($routeResult)
	{
		if($routeResult)
		{
			// DB Table 선언
			$this->setTableName(self::$table_info);
			
			// 라우팅 결과
			$this->routeResult = $routeResult ;
			// 웹서비스
			if(!$this->WebAppService)
			{		//echo '<pre>';print_r(WebApp::$provider_dir);exit;
					// instance 생성
					$this->WebAppService = &WebApp::singleton("WebAppService:system");
					// Query String
					//echo '<Pre>';print_r($this);
					WebAppService::$queryString = Func::QueryString_filter() ;
					// base URL
					//WebAppService::$baseURL = '/'.$this->routeResult["folder"].$this->routeResult["controller"] ;
					WebAppService::$baseURL = $this->routeResult["baseURL"] ;

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
	 */
	public function lst()
	{
		$this->pageScale = 20;
		$this->pageBlock = 3;

		// 조건검색
		//$queryString = array();
		if( $_REQUEST['search_field'] && !preg_match("/[[:space:]]+/u", $_REQUEST['search_keyword']) ){
			$search_params = array() ;
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
		//$queryString = array_merge($queryString, array("bid" => $_REQUEST["bid"]) ) ;

		$queryOption = array(
								"columns" => "bid, oid, mcode, cate, skin_grp, title, FROM_UNIXTIME(regdate) as regdate",
								"conditions" => $search_params
								//, "order" => ""
							);
		try{
			$datas = $this->dataList( $queryOption, false);//true ) ;
			if( !empty($datas) )
			{
				foreach($datas as $k => $v)
				{
					$datas[$k]["bid"] = preg_replace('/[^A-Za-z0-9\.-]/', '', $datas[$k]["bid"]);
					//$datas[$k]["bid"] = rawurlencode($datas[$k]["bid"]);
					$datas[$k]["title"] = Strings::text_cut($datas[$k]["title"], 23);
					$datas[$k]["title"] = str_repeat("==>", $datas[$k]["indent"] ). $datas[$k]["title"] ;
				}
			}
			
		}catch(Exception $e){
			echo $e->getMessage(), "\n";
			exit;
		}

		$paging = $this->Pagination($_REQUEST[self::$pageVariable], $queryString);

		WebAppService::$queryString = Func::QueryString_filter( $queryString );

		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString
				),
				'LIST' => $datas,
				'TOTAL_CNT' => self::$Total_cnt,
				'VIEW_NUM' => self::$view_num,
				'PAGING' => $paging,
				'CTEL' => CTEL,
				'CFAX' => CFAX,
				'CKEYWORDS' => CKEYWORDS
		));

		$this->WebAppService->Output( Display::getTemplate("html/adm/board/commentsInfo_list.html"),"admin_sub");
		$this->WebAppService->printAll();
	}
	/**
	 * [ 신규 / 추가] 작성폼
	 */
	public function add()
	{
		if(REQUEST_METHOD=="GET")
		{
			# 댓글 스킨그룹 리스트
			$comments_skin_group = self::get_skins("html/comments/skin") ;
			
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'Action' => "write",
							'queryString' => Func::QueryString_filter(),
							'formType' => "등록",
					),
					'DATA' => array( 'bid' => $this->routeResult["code"] ),
					'MBR_GRADES' => $this->get_grades(),//self::$mbr_conf["grade"],
					'COMMENTS_SKIN_BASEDIR' => self::$comments_skin_basedir,
					'COMMENTS_SKIN_GROUPS' => $comments_skin_group
			)) ;
			$this->WebAppService->Output( Display::getTemplate("html/adm/board/commentsInfo_edit.html"), "admin_sub");
			$this->WebAppService->printAll();
		}
	}
	/**
	 * 게시판 - DB 저장
	 */
	public function write()
	{
		if(REQUEST_METHOD=="POST")
		{
			//$_POST["title"] = rand(1,1000).$_POST["title"] ;
			if( empty($_POST["bid"]) || ctype_space($_POST["bid"]) ) $this->WebAppService->assign(array('error'=>"아이디를 입력해주세요."));
			if( empty($_POST["title"]) || ctype_space($_POST["title"]) ) $this->WebAppService->assign(array('error'=>"타이틀명을 입력해주세요."));
			
			if( empty($_POST["skin_grp"]) || empty($_POST["skin_name"]) ) $this->WebAppService->assign(array('error'=>"스킨명을 선택해주세요."));
			//-------------------
			
			
			if( !empty($_POST["table_name"]) && $_POST["table_name"] != self::$table_default)
			{
				$table_name = $this->create_boardTable( (string)$_POST["table_name"], (string) $_POST["title"] ) ;
				if( ! $table_name  ) $this->WebAppService->assign(array('error'=>"DB테이블 생성 실패 !!"));
			}
			else{
				$table_name = self::$table_default ;
			}
			
			$put_data = array(
					"oid" => (int) OID,
					"bid" => (string) $_POST["bid"],
					"mcode" => (int) $_POST["mcode"],
					"cate" => (int) $_POST["cate"],
					"skin_grp" => (string) $_POST["skin_grp"],
					"skin_name" => (string) $_POST["skin_name"],
					"title" => (string) $_POST["title"],
					"table_name" => (string) $table_name,
					"listscale" => (int) $_POST["listscale"] ?: 10,
					"pagescale" => (int) $_POST["pagescale"] ?: 10,
					"title_len" => (int) $_POST["title_len"],
					"indent" => (int) $_POST["indent"],
					"comments" => (int) $_POST["comments"],
					"sec" => (int) $_POST["sec"],
					"sec_pwd" => (string) $_POST["sec_pwd"],
					"mbr_type" => (int) $_POST["mbr_type"],
					"editor" => (int) $_POST["editor"],
					"upload_path" => (string) $_POST["upload_path"],
					"upload_file_cnt" => (int) $_POST["upload_file_cnt"],
					"attach_path" => (string) $_POST["attach_path"],
					"attach_top" => (string) $_POST["attach_top"],
					"attach_bottom" => (string) $_POST["attach_bottom"],
					"noti_lev" => (int) $_POST["noti_lev"],
					"noti_grant_apply" => (int) $_POST["noti_grant_apply"],
					"memo" => (string) $_POST["memo"],
					"regdate" => time()
			);
			// 삽입할 인접(Adjacency) 정보 등록시 $this->routeResult["code"]
			/* set_time_limit(0);
			for($i=1; $i<=100000;$i++)
				$insert_id = $this->brdAdd( $put_data, $this->routeResult["code"]	) ; */
			//echo '<pre>';print_r($put_data);exit;
			
			try
			{
				$insert_id = $this->dataAdd( $put_data	) ;
				
				if($insert_id)
				{
					/* $a = file_get_contents("sql/mysql/board.sql");
					 $this->DBconn();
					 $this->DB->rawQuery($a); */
					
					//---------------------------
					# 권한 설정
					//---------------------------
					$this->set_grants($_POST["bid"]);
					//---------------------------
					
					header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
					exit;
				}
				else{
					WebApp::moveBack("저장실패~다시입력해주세요.");
				}
			}
			catch (BaseException $e) {
				$e->printException('controller');
				/* $this->WebAppService->assign( array(
				 "error" => $e->getMessage(),
				 "error_code" => $e->getCode()
				 )); */
			}
			catch (Exception $e) {
				$this->WebAppService->assign( array(
						"error" => $e->getMessage(),
						"error_code" => $e->getCode()
				));
				exit;
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
				header("Location: ".WebAppService::$baseURL."/add"); // 신규작성 폼으로 이동
				exit;
			}

			$data = $this->dataRead(array(
					"columns" => '*',
					"conditions" => array("bid" => $this->routeResult["code"]) 
			));
			$data_grant = $this->get_grant(self::$table_default, $this->routeResult["code"]) ;
			
			if( !empty($data) ) 
			{
				if( !empty($data_grant[0]) ) $data[0] = array_merge($data[0], $data_grant[0]) ;
				$data = array_pop($data) ;
			}
			else{
				header("Location: ".WebAppService::$baseURL."/add/".$this->routeResult["code"] ); // 신규작성 폼으로 이동
				exit;
			}
			
			# 댓글 스킨그룹 리스트
			$comments_skin_group = self::get_skins("html/comments/skin") ;
			
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'Action' => "update",
							"CODE" => $this->routeResult["code"],
							'queryString' => Func::QueryString_filter(),
							'formType' => "편집"
					),
					'DATA' => $data,
					'MBR_GRADES' => $this->get_grades(),//self::$mbr_conf["grade"],
					'COMMENTS_SKIN_BASEDIR' => self::$comments_skin_basedir,
					'COMMENTS_SKIN_GROUPS' => $comments_skin_group
			)) ;
			$this->WebAppService->Output( Display::getTemplate("html/adm/board/commentsInfo_edit.html"), "admin_sub");
			$this->WebAppService->printAll();
		}
		else{
			// exception
		}
	}
	/**
	 * 권한설정
	 * 
	 * @param string $kind_code
	 */
	public function set_grants($kind_code)
	{
		if( empty($kind_code) || ctype_space($kind_code) ) return false ;
		
		$this->setTableName("grants");
		
		if( (int) $_POST["grant_read"] || (int) $_POST["grant_write"] || (int) $_POST["grant_update"] || (int) $_POST["grant_delete"] )
		{
			try
			{
				$this->dataInsertUpdate(
						array(
								"oid" => (int) OID,
								"group_name" => self::$table_default,
								"kind_code" => $kind_code, // 게시판 아이디
								"grant_read" => (int) $_POST["grant_read"], // [권한] 읽기
								"grant_write" => (int) $_POST["grant_write"], // [권한] 쓰기
								"grant_update" => (int) $_POST["grant_update"], // [권한] 수정(업데이트)
								"grant_delete" => (int) $_POST["grant_delete"] // [권한] 삭제
						),
						"grant_read=VALUES(grant_read),".
						"grant_write=VALUES(grant_write),".
						"grant_update=VALUES(grant_update),".
						"grant_delete=VALUES(grant_delete)"
						) ;
			}
			catch (BaseException $e) {
				$e->printException('model');
				/* $this->WebAppService->assign( array(
				 "error" => $e->getMessage(),
				 "error_code" => $e->getCode()
				 )); */
			}
			catch (Exception $e) {
				$this->WebAppService->assign( array(
						"error" => $e->getMessage(),
						"error_code" => $e->getCode()
				));
				exit;
			}
		}
		else{
			$this->dataDelete(array(
					"oid" => (int) OID,
					"group_name" => self::$table_default, // 게시판
					"kind_code" => $this->routeResult["code"], // 게시판 아이디
			));
		}
	}
	/**
	 * 게시판 - DB 업데이트
	 */
	public function update()
	{
		if(REQUEST_METHOD=="POST")
		{
			//echo '<pre>';print_r($_POST);exit;
			if( empty($_POST["title"]) || ctype_space($_POST["title"]) ) $this->WebAppService->assign(array('error'=>"타이틀명을 입력해주세요."));
			if( empty($_POST["skin_grp"]) || empty($_POST["skin_name"]) ) $this->WebAppService->assign(array('error'=>"스킨명을 선택해주세요."));
			
			//---------------------------
			# 권한 설정
			//---------------------------
			// DB Table 선언
			$this->set_grants($this->routeResult["code"]);
			//---------------------------
			
			// DB Table 선언
			$this->setTableName(self::$table_info);
			try
			{
				$x = $this->dataUpdate(
								array(
										"oid" => (int) OID,
										"mcode" => (int) $_POST["mcode"],
										"cate" => (int) $_POST["cate"],
										"skin_grp" => (string) $_POST["skin_grp"],
										"skin_name" => (string) $_POST["skin_name"],
										"title" => (string) $_POST["title"],
										"listscale" => (int) $_POST["listscale"],
										"pagescale" => (int) $_POST["pagescale"],
										"title_len" => (int) $_POST["title_len"],
										"indent" => (int) $_POST["indent"],
										"comments" => (int) $_POST["comments"],
										"sec" => (int) $_POST["sec"],
										"sec_pwd" => (string) $_POST["sec_pwd"],
										"mbr_type" => (int) $_POST["mbr_type"],
										"editor" => (int) $_POST["editor"],
										"upload_path" => (string) $_POST["upload_path"],
										"upload_file_cnt" => (int) $_POST["upload_file_cnt"],
										"attach_path" => (string) $_POST["attach_path"],
										"attach_top" => (string) $_POST["attach_top"],
										"attach_bottom" => (string) $_POST["attach_bottom"]
								),
								array(
										"bid" => $this->routeResult["code"]
								)
				) ;
			} catch (BaseException $e) {
				$e->printException('controller');
				/* $this->WebAppService->assign( array(
				 "error" => $e->getMessage(),
				 "error_code" => $e->getCode()
				 )); */
			}
			catch (Exception $e) {
				$this->WebAppService->assign( array(
						"error" => $e->getMessage(),
						"error_code" => $e->getCode()
				));
				exit;
			}
			
			header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
			exit;
		}
		
	}
	/**
	 * 게시판 - DB 삭제
	 */
	public function delete()
	{
		if($this->routeResult["code"])
		{
			try
			{
				$this->setTableName(self::$table_info);
				$this->dataDelete(array(
						"bid" => $this->routeResult["code"]
				)) ;
				
				$this->setTableName("grants");
				$this->dataDelete(array(
						"oid" => (int) OID,
						"group_name" => self::$table_default, // 게시판
						"kind_code" => $this->routeResult["code"], // 게시판 아이디
				));
			}
			catch (BaseException $e) {
				$e->printException('controller');
				/* $this->WebAppService->assign( array(
				 "error" => $e->getMessage(),
				 "error_code" => $e->getCode()
				 )); */
			}
			catch (Exception $e) {
				$this->WebAppService->assign( array(
						"error" => $e->getMessage(),
						"error_code" => $e->getCode()
				));
				exit;
			}
		}
		header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
		exit;
	}
	/**
	 * 게시판 스킨 검색
	 */
	public function Req_getSkins()
	{
		//$skin_group = base64_decode($_POST['grpcode']) ;
		if( is_dir(self::$comments_skin_basedir. $_POST['grpcode']) ){
			$comments_skin_group = self::get_skins(self::$comments_skin_basedir. $_POST['grpcode']) ;
		}
		$this->WebAppService->assign($comments_skin_group) ;
	}
	/**
	 * 게시판 DB 테이블 제거
	 *
	 * @param string $table_name
	 * @return void
	 *
	 * @uses $table_name="free" 일경우 DB테이블명은 "brdFree"
	 */
	private function drop_boardTable(string $table_name)
	{
		if( empty($table_name) || ! preg_match('/^(comments)/', (string) $table_name) ) return false ;
		
		try
		{
			$sql = "DROP TABLE " . $table_name ;
			
			$this->DBconn();
			$this->DB->rawQuery($sql) ;
			
			return true ;
		}
		catch (BaseException $e) {
			$e->printException('controller');
			/* $this->WebAppService->assign( array(
			 "error" => $e->getMessage(),
			 "error_code" => $e->getCode()
			 )); */
		}
		catch (Exception $e) {
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
			exit;
		}
		return false ;
	}
	/**
	 * 게시판 DB 테이블 생성
	 *
	 * @param string $table_name DB테이블명
	 * @param string $title DB테이블 타이틀명
	 * @return mixed $table_name OR false
	 *
	 * @uses $table_name="free" 일경우 DB테이블명은 "brdFree"
	 */
	private function create_boardTable(string $table_name, string $title)
	{
		$sql = file_get_contents("sql/mysql/comments.sql");
		if( !empty($sql) && !ctype_space($table_name) )
		{
			$table_name = ucfirst((string) $table_name) ;
			$sql = str_replace( array("#ID", "#TITLE"), array(ucfirst((string) $table_name), (string)$title), $sql) ;
			//echo '<pre>';print_r($table_board) ;
			
			try {
				$this->DBconn();
				$res = $this->DB->rawQuery($sql) ;
				
				return "comments" . $table_name ;
			}
			catch (BaseException $e) {
				$e->printException('controller');
				/* $this->WebAppService->assign( array(
				 "error" => $e->getMessage(),
				 "error_code" => $e->getCode()
				 )); */
			}
			catch (Exception $e) {
				$this->WebAppService->assign( array(
						"error" => $e->getMessage(),
						"error_code" => $e->getCode()
				));
				exit;
			}
			
		}
		return false ;
	}
}