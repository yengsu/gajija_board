<?
use Gajija\controller\_traits\Controller_comm;
use system\traits\DB_NestedSet_Trait;
use Gajija\lib\INI_manager;
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
	
	/**
	 * 모든 테이블 생성( Create Statement ) 을 위한 파일
	 * @var string
	 */
	private static $sql_file = "sql/mysql/tables.sql";
	/**
	 * 데이타베이스 접속정보 파일
	 * @var string
	 */
	private static $db_conf_file = "conf/database.conf.php" ;
	
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
	
	public function index()
	{
		$this->Action_perm() ;
		
	}
	/**
	 * 디렉토리 생성 및 퍼미션 확인 페이지
	 */
	public function Action_perm()
	{
		$error = false ;
		
		$Dirs = array(
				"cache/template" => array("title" => "캐쉬 디렉토리(template)", "result" => null),
				"cache/dynamic" => array("title" => "캐쉬 디렉토리(dynamic)", "result" => null),
				"tmp" => array("title" => "세션저장 및 기타", "result" => null),
				"datas" => array("title" => "사용자 템플릿 및 기타", "result" => null),
				"html/_attach/" => array("title" => "첨부파일 저장", "result" => null),
				"theme/template1/html/_attach/" => array("title" => "테마별 첨부파일 저장", "result" => null)
		);
		
		foreach($Dirs as $dir => &$data)
		{
			if( is_dir($dir) )
			{
				if ( is_writable($dir)) {
					$data['result'] = "OK" ;
				}
				else{
					$data['result'] = " 퍼미션을 777 또는 707로 변경하세요." ;
					$error = true ;
				}
			}
			else{
				$data['result'] = "디렉토리가 존재 않습니다." ;
				$error = true ;
			}
		}
		if( $error === true )
		{
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'queryString' => WebAppService::$queryString, //Func::QueryString_filter()
							'Action' => "permmision"//$this->routeResult['action']
					),
					'DATA' => $Dirs
			)) ;
			
			$this->WebAppService->Output( Display::getTemplate("install.html"), "install");
			//echo '<pre>';print_r($this->WebAppService->Display) ;exit;
			$this->WebAppService->printAll();
		}
		else{
			// 데이타베이스 접속계정 입력 페이지 열기
			$this->Action_db() ;
			
		}
		 
	}
	/**
	 * 데이타베이스 접속계정 입력 페이지
	 */
	private function Action_db()
	{
		$db_conf_file = "conf/database.conf.php" ;
		
		if( is_file($db_conf_file))
		{
			if (! is_writable($db_conf_file)) {
				$error_msg = $file. " 쓰기권한이 없습니다. 퍼미션을 777 또는 707로 변경하세요.";
			}
		}
		else{
			$error_msg = "conf/database.conf.dev.php 를 conf/database.conf.php 로 이름변경하세요.<br/><br/>" ;
			$error_msg .= "쓰기권한인 퍼미션을 777 또는 707로 변경하세요.<br/>";
		}
		
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString, //Func::QueryString_filter()
						'Action' => "post_db"//$this->routeResult['action']
				),
				'ERROR_MSG' => $error_msg
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("install.html"), "install");
		//echo '<pre>';print_r($this->WebAppService->Display) ;
		$this->WebAppService->printAll();
		
	}
	/**
	 * 관리자 접속 계정 입력페이지
	 */
	private function Action_adminAccount()
	{
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString, //Func::QueryString_filter()
						'Action' => "post_adminAccount"//$this->routeResult['action']
				)
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("install.html"), "install");
		//echo '<pre>';print_r($this->WebAppService->Display) ;
		$this->WebAppService->printAll();
		
	}
	/**
	 * DB 저장
	 */
	public function post_db()
	{
		// database 접속정보저장
		$this->set_db_info() ;
		
		// database 전체 테이블 생성
		$this->set_db_tableCreate() ;
		
		// 관리자 접속계정 생성
		$this->Action_adminAccount();
	}
	/**
	 * 관리자 계정 저장
	 */
	public function post_adminAccount()
	{
		
		if( empty($_POST['adm_id']) || ctype_space($_POST['adm_id']) ) $this->WebAppService->assign(array('error'=> "관리자 아이디를 입력하세요."));
		if( ! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $_POST['adm_id']) ){
			$this->WebAppService->assign(array('error'=> "관리자 아이디(email주소)를 입력하세요."));
		}
		
		if( empty($_POST['adm_pwd']) || ctype_space($_POST['adm_pwd']) ) $this->WebAppService->assign(array('error'=> "관리자 비밀번호를 입력하세요."));
		if( empty($_POST['adm_name']) || ctype_space($_POST['adm_name']) ) $this->WebAppService->assign(array('error'=> "관리자 이름을 입력하세요."));
		if( empty($_POST['adm_nick']) || ctype_space($_POST['adm_nick']) ) $this->WebAppService->assign(array('error'=> "관리자 닉네임(별명)을 입력하세요."));
		
		Strings::set_xss_variable($_POST) ;
		
		//#############
		// 회원등급 등록
		//#############
		$sql_member_grade = "insert into member_grade(oid, grade_code, grade_name) ";
		$sql_member_grade .= "values(1, 100, '일반회원')" ;
		$sql_member_grade .= ",(1, 200, '우수회원')" ;
		$sql_member_grade .= ",(1, 300, '프리미엄회원')" ;
		$this->_multiQuery($sql_member_grade) ;

		//#############
		// 회원(관리자) 등록
		//#############
		$this->setTableName("member") ;
		$insert_id = $this->dataAdd(array(
				"oid" => OID,
				"is_admin" => 1,
				"grade" => 300,
				"userid" => (string) $_POST['adm_id'],
				"userpw" => $this->WebAppService->Strings::encrypt_sha256(trim((string) $_POST['adm_pwd'])),
				"username" => (string) $_POST['adm_name'],
				"usernick" => (string) $_POST['adm_nick'],
				"ip" => $_SERVER['REMOTE_ADDR'],
				"regdate" => time()
		));

		if( !empty($insert_id) ){
			
			//#############
			// 게시판 & 댓글 생성
			//#############
			// 게시판 생성
			$this->set_board_append() ;
			// 댓글 생성
			$this->set_comments_append() ;
			
			//#############
			// 기본메뉴 등록
			//#############
			$this->set_menu_append() ;
			
			header('Location:/') ;
			exit;
		}
		else{
			$this->WebAppService->assign(array('error'=> "저장실패. 다시시도해주세요."));
		}
	}
	/**
	 * 게시판 생성
	 */
	private function set_board_append()
	{
		$put_data = array() ;
		
		//자유게시판
		$put_data[0] = array(
				"oid" => (int) OID,
				"bid" => 'free',
				"skin_grp" => "base",
				"skin_name" => "default",
				"title" => "자유게시판",
				"table_name" => "board",
				"listscale" => 10,
				"pagescale" => 10,
				"indent" => 1,
				"comments" => 1,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"regdate" => time()
		);
		//질문과 답변
		$put_data[1] = array(
				"oid" => (int) OID,
				"bid" => 'qna',
				"skin_grp" => "base",
				"skin_name" => "default",
				"title" => "질문과 답변",
				"table_name" => "board",
				"listscale" => 10,
				"pagescale" => 10,
				"indent" => 1,
				"comments" => 1,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"regdate" => time()
		);
		//사진게시판
		$put_data[2] = array(
				"oid" => (int) OID,
				"bid" => 'photo',
				"skin_grp" => "photo",
				"skin_name" => "default",
				"title" => "사진게시판",
				"table_name" => "board",
				"listscale" => 10,
				"pagescale" => 10,
				//"title_len" => (int) $_POST["title_len"],
				"comments" => 1,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"upload_file_cnt" => 1,
				"regdate" => time()
		);
		//자료실
		$put_data[3] = array(
				"oid" => (int) OID,
				"bid" => 'datas',
				"skin_grp" => "base",
				"skin_name" => "default",
				"title" => "자료실",
				"table_name" => "board",
				"listscale" => 10,
				"pagescale" => 10,
				//"title_len" => (int) $_POST["title_len"],
				"comments" => 1,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"upload_file_cnt" => 3,
				"regdate" => time()
		);
		try
		{
			$this->setTableName("board_info");
			foreach($put_data as $board)
			{
				$insert_id = $this->dataAdd( $board ) ;
			}
		}
		catch (BaseException $e) {
			$e->printException('controller');
		}
		catch (Exception $e) {
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
			exit;
		}
	}
	/**
	 * 게시판 생성
	 */
	private function set_comments_append()
	{
		$put_data = array() ;
		
		//자유게시판
		$put_data[0] = array(
				"oid" => (int) OID,
				"bid" => 'free',
				"skin_grp" => "base",
				"skin_name" => "scroll",
				"title" => "[댓글]자유게시판",
				"table_name" => "comments",
				"listscale" => 10,
				"pagescale" => 10,
				"indent" => 1,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"regdate" => time()
		);
		//질문과 답변
		$put_data[1] = array(
				"oid" => (int) OID,
				"bid" => 'qna',
				"skin_grp" => "base",
				"skin_name" => "scroll",
				"title" => "[댓글]질문과 답변",
				"table_name" => "comments",
				"listscale" => 10,
				"pagescale" => 10,
				"indent" => 1,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"regdate" => time()
		);
		//사진게시판
		$put_data[2] = array(
				"oid" => (int) OID,
				"bid" => 'photo',
				"skin_grp" => "photo",
				"skin_name" => "scroll",
				"title" => "[댓글]사진게시판",
				"table_name" => "comments",
				"listscale" => 10,
				"pagescale" => 10,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"upload_file_cnt" => 1,
				"regdate" => time()
		);
		//자료실
		$put_data[3] = array(
				"oid" => (int) OID,
				"bid" => 'datas',
				"skin_grp" => "base",
				"skin_name" => "scroll",
				"title" => "[댓글]자료실",
				"table_name" => "comments",
				"listscale" => 10,
				"pagescale" => 10,
				"sec_pwd" => 1,
				"mbr_type" => 1,
				"editor" => 1,
				"upload_file_cnt" => 3,
				"regdate" => time()
		);
		try
		{
			$this->setTableName("comments_info");
			foreach($put_data as $comments)
			{
				$insert_id = $this->dataAdd( $comments ) ;
			}
		}
		catch (BaseException $e) {
			$e->printException('controller');
		}
		catch (Exception $e) {
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
			exit;
		}
	}
	/**
	 * 기본메뉴 등록
	 */
	private function set_menu_append()
	{
		$this->setTableName("menu") ;
		
		// home 등록
		$home_insertid = $this->TNst_hasData() ;
		
		//=====================================
		//  자유게시판 (depth-2)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "자유게시판",
				"layout" => "sub",
				"used" => 1,
				"imp" => 1
		);
		$insert_id = $this->TNst_add($put_data, $home_insertid);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/board/BoardComm/lst?bid=free&mcode=".$insert_id),
				array("serial" => $insert_id)
			) ;
		//=====================================
		//  질문과 답변 (depth-2)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "질문과 답변",
				"layout" => "sub",
				"used" => 1,
				"imp" => 1
		);
		$insert_id = $this->TNst_add($put_data, $home_insertid);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/board/BoardComm/lst?bid=qna&mcode=".$insert_id),
				array("serial" => $insert_id)
			) ;
		//=====================================
		//  사진게시판 (depth-2)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "사진게시판",
				"layout" => "sub",
				"used" => 1,
				"imp" => 1
		);
		$insert_id = $this->TNst_add($put_data, $home_insertid);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/board/BoardComm/lst?bid=photo&mcode=".$insert_id),
				array("serial" => $insert_id)
			) ;
		//=====================================
		//  자료실 (depth-2)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "자료실",
				"layout" => "sub",
				"used" => 1,
				"imp" => 1
		);
		$insert_id = $this->TNst_add($put_data, $home_insertid);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/board/BoardComm/lst?bid=datas&mcode=".$insert_id),
				array("serial" => $insert_id)
			) ;
		
		
		//=====================================
		// Mypage (depth-2)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "Mypage",
				"used" => 1
		);
		$parent_id = $this->TNst_add($put_data, $home_insertid);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/Member/edit?mcode=".$parent_id),
				array("serial" => $parent_id)
			) ;
		//=====================================
		// 회원정보 변경 (depth-3)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "회원정보 변경",
				"used" => 1
		);
		$insert_id = $this->TNst_add($put_data, $parent_id);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/Member/edit?mcode=".$insert_id),
				array("serial" => $insert_id)
			) ;
		
		
		
		//=====================================
		// 회원 (depth-2)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "회원",
				"used" => 1
		);
		$parent_id = $this->TNst_add($put_data, $home_insertid);
		//=====================================
		// 회원로그인 (depth-3)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "회원로그인",
				"used" => 1
		);
		
		$insert_id = $this->TNst_add($put_data, $parent_id);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/Member/login?mcode=".$insert_id),
				array("serial" => $insert_id)
			) ;
		//=====================================
		// 회원가입 (depth-3)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "회원가입",
				"used" => 1
		);
		$insert_id = $this->TNst_add($put_data, $parent_id);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/Member/join?mcode=".$insert_id),
				array("serial" => $insert_id)
			) ;
		//=====================================
		// ID/PW 찾기 (depth-3)
		//=====================================
		$put_data = array(
				"oid" => (int) OID,
				"title" => "ID/PW 찾기",
				"used" => 1
		);
		$insert_id = $this->TNst_add($put_data, $parent_id);
		//----------------
		$res = $this->dataUpdate(
				array("url" => "/Member/idpw?mcode=".$insert_id),
				array("serial" => $insert_id)
				) ;
	}
	protected function TNst_hasData()
	{
		$exist_cnt = $this->count('serial') ;
		if($exist_cnt < 1)
		{
			$serial = rand(100000, 999999) ;
			$put_data = array(
					"serial" => $serial,
					"title" => 'HOME',
					"imp" => 0
			);
			//$insert_id = $this->dataAdd($put_data) ;
			$insert_id = $this->dataAddFamily($put_data) ;
			
			return $insert_id ;
		}
	}
	/**
	 * DB 접속 정보를 저장
	 */
	private function set_db_info()
	{
		//$db_conf_file = "conf/database.conf.php" ;
		
		if( is_file(self::$db_conf_file))
		{
			if (! is_writable(self::$db_conf_file)) {
				$this->WebAppService->assign(array('error'=>self::$db_conf_file. " 퍼미션을 777 또는 707로 변경하세요."));
			}
		}
		else{
			$this->WebAppService->assign(array('error'=>self::$db_conf_file. " 파일이 없습니다."));
		}
		
		Strings::set_xss_variable($_POST) ;
		
		if( empty($_POST['db_host']) || ctype_space($_POST['db_host']) ) $this->WebAppService->assign(array('error'=> "DB 호스트명을 입력하세요."));
		if( empty($_POST['db_userid']) || ctype_space($_POST['db_userid']) ) $this->WebAppService->assign(array('error'=> "DB 접속 아이디를 입력하세요."));
		//if( empty($_POST['db_userpw']) || ctype_space($_POST['db_userpw']) ) $this->WebAppService->assign(array('error'=> "DB 접속 비밀번호를 입력하세요."));
		if( empty($_POST['db_name']) || ctype_space($_POST['db_name']) ) $this->WebAppService->assign(array('error'=> "DB 명을 입력하세요."));
		
		$ini = new INI_manager;
		$ini->set_entry(self::$db_conf_file, 'default', 'host', $_POST['db_host']);
		$ini->set_entry(self::$db_conf_file, 'default', 'user', $_POST['db_userid']);
		$ini->set_entry(self::$db_conf_file, 'default', 'pass', $_POST['db_userpw']);
		$ini->set_entry(self::$db_conf_file, 'default', 'db', $_POST['db_name']);
	}
	/**
	 * DB 전체 테이블 생성
	 */
	private function set_db_tableCreate()
	{
		if( is_file(self::$sql_file) )
		{
			//ob_start();
			//$json_string = file_get_contents($file) ;
			$fh = fopen(self::$sql_file, 'r');
			$sql_string = fread($fh, filesize(self::$sql_file));
			fclose($fh);
			//ob_end_clean();
		
			$this->_multiQuery($sql_string) ;
		}
		else{
			$this->WebAppService->assign(array('error'=>self::$sql_file. " 파일이 없습니다."));
		}

	}
	
}