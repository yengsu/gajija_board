<?php
// /board/BoardComm/lst?bid=notice
use system\traits\Plugin_Trait;
use Gajija\controller\_traits\Page_comm;
use Gajija\service\board\BoardCommNest_service;

/**
 * 게시판 환경설정 &
 * 기본 게시판 컨트롤러
 */
class Comments_controller extends BoardCommNest_service
{
	use Plugin_Trait, Page_comm ;
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
	 * 게시판 환경정보 데이타
	 * @var mixed
	 */
	public $boardInfoResult = array() ;
	
	public $commentsInfoResult = array() ;
	/**
	 * 사이트 메뉴정보 데이타
	 * @var mixed
	 */
	public static $menu_datas ;
	
	//public $Member_service ;
	
	/**
	 * 회원 환경정보
	 *
	 * @filesource conf/member.conf.php
	 * @var array
	 */
	public static $mbr_conf = array();
	
	/**
	 * comments-TB 기본 Query옵션
	 * 
	 * @var array ( columns, conditions, join, order...)
	 */
	public static $queryOption_comments = array(
					"columns"=> "M.lev, M.usernick, M.profile_photo, B.serial, B.lft, B.rgt, B.userid, B.family, B.parent, B.indent, B.memo, B.sec, B.regdate",
					//"conditions" => $conditions_params,
					"join" => "LEFT");
					//,"order" => "B.serial DESC") ;
	
	/**
	 * 소스코드 문법 highlight 처리
	 * @var string
	 */
	private $syntaxHighlight_name = "";
	
	/**
	 * 페이지 레이아웃명
	 * @var string
	 * @desc conf/layout.conf.php 참조
	 */
	public $page_layout = "";
	/* public function __call($method, $arguments){
	
	switch ($method)
	{
	case 'test':
	echo __CLASS__.' class';
	break;
	
	default :
	return call_user_func_array(array(&$this,$method),self::refValues($arguments));
	break;
	}
	return ;
	} */
	
	/* public function getMenu()
	 {
	 $this->setTableName("menu");
	 $this->getNodeChilds($serial, $Columns) ;
	
	 $this->setTableName("comments");
	 } */
	
	public function __construct($routeResult=NULL)
	{
		/**
		 * 메뉴코드 체크
		 */
		if( empty($routeResult["mcode"]) )
		{
			/* WebApp::redirect("/");
			exit; */
		}
		
		if($routeResult)
		{
			// 라우팅 결과
			$this->routeResult = $routeResult ;
		}
		
		// 웹서비스
		if(!$this->WebAppService  || !class_exists('WebAppService'))
		{
			// instance 생성
			$this->WebAppService = &WebApp::singleton("WebAppService:system");
			
			# 페이지 권한인증(읽기,쓰기, 수정,삭제)
			$this->authen_comm_grant( $routeResult["action"], "comments", $routeResult["bid"] );
			
			/**
			 * bid 체크
			 */
			if( !empty($this->routeResult) && empty($this->routeResult["bid"]) )
			{
				//WebApp::redirect('/', "자료를 찾을 수 없습니다.");
				$this->WebAppService->assign(array('error'=>'자료를 찾을 수 없습니다.'));//No data found.
			}
			
			// Query String
			WebAppService::$queryString = Func::QueryString_filter() ;
			
			// base URL
			WebAppService::$baseURL = $this->routeResult["baseURL"] ;
		}
		/* $this->page_display_apply($routeResult["mcode"]) ;
		if(isset(self::$menu_datas['self']['layout']) && self::$menu_datas['self']['layout']){
			$this->page_layout = self::$menu_datas['self']['layout'] ;
		} */
		/**
		 * 사이트 메뉴 정보 가져오기
		 */
		/* if($routeResult["mcode"]){
			self::$menu_datas = $this->get_menu('menu', $routeResult["mcode"]);
			if( empty(self::$menu_datas["childs"]) )
				$this->WebAppService->assign(array('error'=>'메뉴정보를 찾을 수 없습니다.'));
				
				if( class_exists('Display') ){
					$this->WebAppService->Display->define('ATTACH_TOP',  self::$menu_datas['self']['attach_basedir']. self::$menu_datas['self']['attach_top']) ;
					$this->WebAppService->Display->define('ATTACH_BOTTOM',  self::$menu_datas['self']['attach_basedir']. self::$menu_datas['self']['attach_bottom']) ;
				}
		} */
		
		
		//게시판 환경 정보 가져오기
		if($_REQUEST["bid"]) $this->get_board_info($_REQUEST["bid"]);

		//self::$mbr_conf["profile"] = WebApp::getConf_real("member.profile");
		//self::$mbr_conf = WebApp::getConf_real("member");
		
		self::set_syntaxHighlight();
		
		// DB Table 선언
		$this->setTableName("comments");
	}
	
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k, $obj);
		}
	}
	
	/**
	 * 게시판 환경 정보 가져오기
	 * @param string $bid (게시판 아이디)
	 */
	private function get_board_info($bid)
	{
		$this->setTableName("comments_info") ;
		$data= $this->dataRead(	array(
				"columns" => "*",
				"conditions" => array("bid" => $bid)
		) ) ;
		if( !empty($data) ){
			$this->boardInfoResult = array_pop($data);
		}

		/**
		 * 게시판 환경정보 체크
		*/
		if( empty($this->boardInfoResult))
		{
			//Exception
			$this->WebAppService->assign(array('error'=>'자료를 찾을 수 없습니다.'));//No data found.
			/* WebApp::redirect('/', "자료를 찾을 수 없습니다.");
			exit; */
		}else{
				
			// 전체적용: 회원용인경우 로그인 체크 및 로그인페이지 이동
			if($this->boardInfoResult["mbr_type"]==1)
			{
				//$this->hasMemberLogin() ;
			}
		}
	}
	/**
	 * 댓글 환경 정보 가져오기
	 * @param string $bid (댓글 아이디)
	 */
	private function get_comment_info($bid)
	{
		$this->setTableName("comments_info") ;
		$data= $this->dataRead(	array(
				"columns" => "*",
				"conditions" => array("bid" => $bid)
		) ) ;
		if( !empty($data) ){
			$this->boardInfoResult = array_pop($data);
		}
		
		/**
		 * 게시판 환경정보 체크
		 */
		if( empty($this->boardInfoResult))
		{
			//Exception
			$this->WebAppService->assign(array('error'=>'자료를 찾을 수 없습니다.'));//No data found.
			/* WebApp::redirect('/', "자료를 찾을 수 없습니다.");
			 exit; */
		}else{
			
			// 전체적용: 회원용인경우 로그인 체크 및 로그인페이지 이동
			if($this->boardInfoResult["mbr_type"]==1)
			{
				//$this->hasMemberLogin() ;
			}
		}
	}
	
	
	private function plugin_get_lst(&$args=NULL)
	{
		if($args["table"]) {
			// DB Table 선언
			$_REQUEST['bid'] = $args["bid"] ;
			$this->setTableName($args["table"]);
			$this->Plugin_put_Datas($args, "get_board_lst") ;
		}
	}
	
	public function set_syntaxHighlight()
	{
		
		//$this->syntaxHighlight_name = "highlightjs";
		$this->syntaxHighlight_name = "syntaxHighlighter";
	}
	
	
	/**
	 * 게시판 검색정보
	 * @param array $Params ( bid[게시판아이디], search_field[검색필드명], search_keyword[검색 필드값] )
	 * @return array(
	 * 						query_condition [sql: 검색정보],
	 * 						queryString [http: Query String]
	 * 					)
	 */
	private function condition_board( $Params )
	{
		// 조건검색
		$query_condition = array() ;
		$queryString = array() ;
	
		//if($Params['mcode']) $queryString['mcode'] = $Params['mcode'] ;
	
		$query_condition['B.bid'] = $Params['bid'] ;
		$queryString['bid'] = $Params['bid'] ;
	
		if( $Params['search_field'] && !preg_match("/[[:space:]]+/u", $Params['search_keyword']) ){
			//$params[$params['search_field']." like CONCAT('%',?,'%')"] = $params['keyword'] ;
			$query_condition[$Params['search_field']." like ?"] = "%".$Params['search_keyword']."%" ;
	
			$queryString = array_merge($queryString, array(
					"search_field" => $Params['search_field'],
					"search_keyword" => $Params['search_keyword']
			)) ;
		}
		else{
			$queryString['search_keyword']='';
		}
	
		return array(
				"query_condition" => $query_condition,
				"queryString" => $queryString
		) ;
	}
	
	/**
	 * 리스트 목록(회원정보+게시판) 데이타 가져오기 처리
	 * 
	 * 
	 * @access Ajax, Http(Template_로 적용) 두개 자동으로 식별하여 데이타 반영
	 * 
	 * @global string $_REQUEST['bid'] ( 게시판 아이디 )
	 * @global string $_REQUEST['search_field'] (검색용: Column 명)
	 * @global string $_REQUEST['search_keyword'] (검색용: Column 값)
	 */
	private function get_board_lst()
	{
		if(!$this->pageScale) $this->pageScale = 20 ; // record ea
		if(!$this->pageBlock) $this->pageBlock = 5 ; // page block ea

		$_REQUEST[self::$pageVariable] = $_GET[self::$pageVariable] ;

		// 검색조건 처리
		//----------------------------------------
		//array(query_condition, queryString)
		$ResultQuery = array();
		$ResultQuery = self::condition_board( array(
				"mcode" => $this->routeResult['mcode'],
				"bid" => $_REQUEST['bid'],
				"search_field" => $_REQUEST['search_field'],
				"search_keyword" => $_REQUEST['search_keyword']
		)) ;
		//echo '<pre>';print_r($ResultQuery);exit;
		$queryOption = array(
				"columns" => "
									M.grade, M.lev, M.userid, M.username, M.usernick, M.profile_photo, 
									B.serial, B.bid, B.family, B.parent, B.lft, B.rgt, B.indent, B.writer, B.title, B.noti, B.sec_pwd, B.regdate, B.viewcnt",
				"conditions" => $ResultQuery["query_condition"],
				"join" => "LEFT",
				"order" => "B.noti DESC" //, B.serial DESC"
		);
		//echo '<pre>';print_r($queryOption)
		$this->setTableName('comments');
		$datas = $this->getDataAndMbr( $queryOption, $this->boardInfoResult["indent"] ) ;
		
		if( !empty($datas) )
		{
			foreach($datas as &$data)
			{
				$data["title"] = Strings::text_cut($data["title"], $this->boardInfoResult["title_len"]);
				/* 타이틀 */if( !empty($this->boardInfoResult["indent"]) ) $data["title"] = str_repeat("==>", $data["indent"] ). $data["title"] ;
				/* 닉네임 */if( empty($data["writer"]) ) $data["writer"] = $data["usernick"] ;
				/* 작성일자 */$data["regdate"] = date('Y-m-d', $data["regdate"]) ;
				
				/* 회원등급 */$data["grade"] = self::$mbr_conf['grade'][$v["grade"]] ;
				/* 회원레벨 */$data["lev"] = self::$mbr_conf['lev'][$v["lev"]] ;
				/* 회원레벨 icon */$data["lev_ico"] = self::$mbr_conf['lev_css'][$v["lev"]] ;
			}
		}
		WebAppService::$queryString = Func::QueryString_filter( $ResultQuery["queryString"] );
		$paging = $this->Pagination($_REQUEST[self::$pageVariable], $ResultQuery["queryString"]);
		
		/* if( empty(self::$menu_datas) && $_REQUEST["mcode"] )
			self::$menu_datas = $this->get_menu('menu', $_REQUEST["mcode"]); */
		
		$this->WebAppService->assign(array(
				'LIST' => &$datas,
				'TOTAL_CNT' => self::$Total_cnt, //$this->count(),
				'VIEW_NUM' => self::$view_num,
				'PAGING' => $paging,
				'Board_conf' => &$this->boardInfoResult
		));

	}
	
	/**
	 * 저장된 첨부파일 읽어오기
	 * 
	 * @param array $data (첨부파일 경로 : attach_path, 첨부파일[,로 구분] : attach_files)
	 * @param array $data
	 */
	private function read_attachfile( &$data ){
		# 첨부파일
		$attach_files = explode(",", $data["attach_files"]) ;
		if( !empty($attach_files) )
		{
			$data["attachFiles"] = array();
			for($i=0; $i<count($attach_files); $i++){
				//echo $data["attach_path"].$attach_files[$i]."<br>" ;
				if($attach_files[$i] && is_file($data["attach_path"].$attach_files[$i]))
					array_push($data["attachFiles"], $attach_files[$i]);
			}
		}
	}
	/**
	 * 게시판 데이타 읽어오기
	 * 
	 * @param array $conditions ( string or array(.......) )
	 * @return array|null
	 */
	private function get_board_read( &$conditions ){
		$queryOption = array(
				"columns"=> 'B.*, M.serial as usercode, M.grade, M.lev, M.userid, M.username, M.usernick',
				"conditions" => $conditions,
				"join" => "LEFT"
		);
		return $this->getDataAndMbr( $queryOption ) ;
	}
	
	public function view()
	{
		//echo '<pre>';print_r(self::$menu_datas);
		if(REQUEST_METHOD=="GET")
		{
			//Exception
			if( !$this->routeResult["code"] )
				$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
			    //$this->WebAppService->assign(array('error'=>'The data does not exist.'));

			$conditions = array( "B.serial" => $this->routeResult["code"], "B.bid" => $_REQUEST["bid"] ) ;
			$data = $this->get_board_read($conditions);
			
			//Exceptionn
			if( empty($data) ){
				$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
			    //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
			}else{
			    
			    // 환경설정-비밀글인 경우
			    $this->secret_authen( $data[0]['userid'] );
			    
				// 첨부파일 읽기
				$this->read_attachfile($data[0]);
				
				$data = array_pop($data) ;
				
				/* 회원레벨 icon */$data["lev_ico"] = self::$mbr_conf['lev_css'][$data["lev"]] ;
				/* 글내용 *///$data["memo"] = nl2br($data["memo"]);
			}
			//$data["memo"]= '<pre>'.stripslashes($data["memo"]).'</pre>' ;
			
			//$data["memo"]= str_replace("  ", "&nbsp;&nbsp;", $data["memo"]) ;
			//$data["memo"] = $this->highlightjs_decode($data["memo"]) ;
			/* $data["memo"]= stripslashes($data["memo"]) ;
			$data["memo"] = self::{$this->syntaxHighlight_name."_decode"}($data["memo"]) ; */
			
			$this->decode_memo($data["memo"]);
			
			//echo '<pre>';print_r($data);
			//$data["memo"]= "<pre>".htmlentities(highlight_string($data["memo"]))."</pre>" ;
			//-----------------------------------------
			// 코멘츠 기능 사용체크 및 가져옴
			//-----------------------------------------
			if($this->boardInfoResult["comments"])
			{
				$this->commentsInfoResult = $this->getBrdComments_info("comments_info", array("bid" => $_REQUEST["bid"])) ;
				//$this->WebAppService->assign(array("Comments_conf" =>$this->commentsInfoResult)) ;
				
				$queryOption = array_merge(self::$queryOption_comments, array(
												"conditions" =>  array(
															"B.bid" => $_REQUEST["bid"],
															"B.bserial" => $this->routeResult["code"]
												)
									)) ;
				$comments_datas = $this->get_comments_lst($queryOption, $_REQUEST["bid"]);

				//댓글리스트 출력처리
				$this->WebAppService->assign(array(
					"COMMENTS_LIST"=> &$comments_datas['LIST'],
					'COMMENTS_TOTAL_CNT' => $comments_datas['TOTAL_CNT'],
					'COMMENTS_PAGING' => &$comments_datas['PAGING'],
					'COMMENTS_VIEW_NUM' => $comments_datas['VIEW_NUM'],
					'Comments_conf' => &$this->commentsInfoResult
				)) ;

				if( !empty($this->commentsInfoResult['editor']) )
					$template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base." .$this->syntaxHighlight_name. ".html" ;
				else
					$template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base.html" ;
				
					$this->WebAppService->Display->define('COMMENTS', Display::getTemplate($template));
				
			}else{
				$this->WebAppService->Display->define('COMMENTS','');
				//$this->WebAppService->Display->define('COMMENTS',Display::getTemplate('blank.htm'));
			}
			//-----------------------------------------
			//$this->WebAppService->Display->setLayout($this->page_layout);
			
			if( !empty($this->syntaxHighlight_name) )
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/view." .$this->syntaxHighlight_name. ".html" ;
			else
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/view.html" ;
			
			$this->WebAppService->Display->define( "CONTENT", Display::getTemplate($template));
			//echo '<pre>';print_r($this);exit;
			$this->WebAppService->Output( Display::getTemplate($template), $this->page_layout);
			
			
			//-----------------------------------------
			/* $this->setTableName('comments');
			self::$pageVariable = 'page';
			$this->lst(true); // 리스트 출력시
			
			$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/list.html" ;
			$this->WebAppService->Display->define('BOARD_LIST', Display::getTemplate($template) ); */
			//-----------------------------------------
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'queryString' => Func::QueryString_filter(),
							'MNU' => self::$menu_datas,
							'Action' => "view",
							"CODE" => $this->routeResult["code"],
							'formType' => "보기",
							//'Board_indent' => $this->boardInfoResult["indent"],
							'formHiddenVar' => array(
									'tbl_name'=>'comments'
							)
					),
					//'SHOP_COMMON' => $this->global_shopAction(), // ◆공용 데이타◆ (쇼핑카트 갯수, 위시리스트 갯수.....)
					'Board_conf' => $this->boardInfoResult,
					'Comments_conf' => $this->commentsInfoResult,
					'DATA' => $data
			)) ;
			//echo '<pre>';print_r($this);exit;
			$this->WebAppService->printAll();
		}
	}
	private function encode_memo(&$memo)
	{
		if( $this->boardInfoResult["editor"] == 1){ //에디터 사용할 경우
			$memo = self::{$this->syntaxHighlight_name."_encode"}($memo) ;
		}
		$memo= addslashes($memo) ;
		return $memo ;
	}
	private function decode_memo(&$memo)
	{
		$memo = stripslashes($memo) ;
		if( $this->boardInfoResult["editor"] == 1){ //에디터 사용할 경우
			$memo = self::{$this->syntaxHighlight_name."_encode"}($memo) ;
		}
		else{
			/* 출력용(내용) */$memo = str_replace(" ","&nbsp;", $memo);
			/* 출력용(내용) */$memo = nl2br($memo);
		}
		return $memo ;
	}
	/**
	 * [체크] : 모든 게시글을 비밀글 설정한 경우
	 * (본인이 작성한글만 조회가능) 
	 */
	private function secret_authen( $userid )
	{
	    // 환경설정-비밀글인 경우
	    if($this->boardInfoResult['sec'] == 1){
	        // 회원용 / 비회원용
	        if($this->boardInfoResult["mbr_type"]==1)
	        {
	            $this->hasMemberCheck() ;
	        }
	        // 본인이 작성한 글인지 체크
	        if(!$_SESSION['MBRID'] || ($_SESSION['MBRID'] != $userid && !$_SESSION['ADM'])){
	            $this->WebAppService->assign(array('error'=>'본인이 작성한 글이 아니면 볼 수 없습니다.'));
	        }
	    }
	}
	//============================================
	private function test()
	{
		//$this->WebAppService->Display->setLayout('sub2');
		//$this->WebAppService->Display->define('CONTENT', Display::getTemplate('html/board/skin/boardComm/list.html'),'sub2');

		//$this->WebAppService->Display->html_body = "감사합니다.";
		// 1
		//$this->setTableName('comments');
		self::$pageVariable = 'page';
		$this->test_lst();
		//$this->lst();
		//$this->view();
		//$this->WebAppService->Display->print_('CONTENT');
		//$this->WebAppService->assign("CONTENT", "yeeeeeeeeeeeeeeeeeeeeeee") ;

		//echo $this->WebAppService->Display->layout_list();
		//$this->WebAppService->Display->xprint(Display::getTemplate('html/board/skin/boardComm/list.html'));
		//$this->WebAppService->printAll();
		// 2
		//$aa = Display::push_body('yessssssssssssssss');
		//echo '<pre>';print_r($this->WebAppService->Display->tpl_);
		//$this->setTableName('comments');
		//self::$pageVariable = 'pagx';
		//$this->test_lst();

	}
	//============================================
	/**
	 * [ 신규 / 추가] 작성폼
	 */
	public function add()
	{
		if(REQUEST_METHOD=="GET")
		{
			// 회원용 / 비회원용
			if($this->boardInfoResult["mbr_type"]==1)
			{
				$this->hasMemberCheck() ;
			}

			if( $this->routeResult["code"] )
			{
				$data = $this->dataRead(array(
						"columns" => "*",
						"conditions" => array(
								"serial" => $this->routeResult["code"],
								"bid" => $_REQUEST["bid"]
						)
				));
			}
			//echo '<pre>';print_r($this->boardInfoResult);
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'queryString' => Func::QueryString_filter(),
							'MNU' => self::$menu_datas,
							'Action' => "write",
							"CODE" => $this->routeResult["code"],
							/* 'formType' => "등록" */
					        'formType' => "add"
					),
					'Board_conf' => $this->boardInfoResult,
					'DATA' => $data//,
					//'SHOP_COMMON' => $this->global_shopAction() // ◆공용 데이타◆ (쇼핑카트 갯수, 위시리스트 갯수.....)
			)) ;
			
			if( !empty($this->syntaxHighlight_name) ) 
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/edit." .$this->syntaxHighlight_name. ".html" ;
			else 
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/edit.html" ;
			
				$this->WebAppService->Output( Display::getTemplate($template), $this->page_layout);
			//echo '<pre>';print_r($this->WebAppService->Display) ;
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
	private function getValidate($vars)
	{
		# 스페이스바 또는 엔터 데이타는 인식안함
		#     empty 또는 ctype_space 함수로 먼저 검사해야함
			
		if( is_array($vars) )
		{
			$rule = array(
					'frm_mcode' => array(
							/* 'label' => '메뉴코드를', */
					        'label' => 'Menu code',
							'rules' => 'required|numeric'
					),
					'bid' => array(
							/* 'label' => '보드 아이디를', */
					        'label' => 'Board ID',
							'rules' => 'required|alpha'
					),
					'frm_mbr_id' => array(
							/* 'label' => '회원 아이디를', */
					        'label' => 'Member ID',
							'rules' => 'required|email'
					),
					'frm_writer' => array(
							/* 'label' => '작성자명을', */
					        'label' => 'Author Name',
							'rules' => 'required|whitespace'
					),
					'frm_title' => array(
							/* 'label' => '타이틀 명을', */
					        'label' => 'Title name',
							'rules' => 'required'//|whitespace'
					),
					'frm_memo' => array(
							/* 'label' => '글 내용을', */
					        'label' => 'Content',
							'rules' => 'required'
					),
					'frm_mvOrig' => array(
					        /* 'label' => '이동할 Serial', */
					        'label' => 'The serial to move',
							'rules' => 'required|numeric'
					),
					'frm_mvTarget' => array(
					        /* 'label' => '이동될 위치 Serial', */
					        'label' => 'Location to be moved Serial',
							'rules' => 'required|numeric'
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
	 * 게시판 회원형인 경우 로그인체크
	 */
	/*
	public function hasMemberLogin()
	{
		//$this->SessMember = &WebApp::singleton("SessMember:service");
		//WebApp::import("WebAppService", "system");
		WebApp::import("Member", "service");
		$this->Member_service = new Member_service ;
	
		//if(!$this->SessMemeber_service->hasLogin(array('flag'=>1, 'mcode'=>$this->routeResult['mcode'])))
		 $this->Member_service->hasLogin(array('flag'=>1, 'queryString'=>REQUEST_URI)) ;
		
			//exit;
		
	}*/
	/**
	 * 첨부파일 삭제
	 * @param string $files (삭제파일)
	 */
	private function attach_delete($files)
	{
		if(is_file($files))
			$this->WebAppService->File->delete($files);
	}
	
	/**
	 * 디렉토리 생성
	 * 
	 * @param string $uploadDir (기본 저장 디렉토리위치:상대(relative)경로)
	 * @return string 생성할 full 디렉토리
	 */
	private function folder_create( $uploadDir )
	{
		if( mb_substr($uploadDir, -1) != "/" ) $uploadDir .= "/" ;
		
		// 추가 폴더명 정의
		$curtime = time();
		$uploadDir .= date('Y', $curtime).'/' ;
		$uploadDir .= date('m', $curtime).'/' ;
		$uploadDir .= date('d', $curtime).'/' ;
		$uploadDir .= date('H', $curtime).'/' ;
		
		return $uploadDir ;
	}
	/**
	 * 첨부파일 업로드
	 * 
	 * @param string $uploadDir ( 업로드파일 저장경로 )
	 * @param $_FILES $Files (업로드 변수: $_FILES[??])
	 * @param array|string $upload_datas (삭제용: [attach_path(저장경로), (array) attach_files(저장된 파일들)])
	 * @return boolean|array
	 */
	private function attach_upload($uploadDir, $Files, $upload_datas=NULL)
	{
		if( !empty($uploadDir) && !empty($Files) )
		{
			$result = array();
			
			$result["dir"] = self::folder_create($uploadDir) ;
		}
		else{
			return false;
		}
		
		if( !is_dir($result["dir"]) ) 
			$this->WebAppService->File->createDirs($result["dir"]);
		
		$result["file"] = array();
		foreach($Files["name"] as $k => $v)
		{
			if($Files['error'][$k] == 0)
			{
				if( $this->WebAppService->Func->fileType_Check($Files['name'][$k]) )
				{
					if( move_uploaded_file( $Files['tmp_name'][$k], $result["dir"] . basename($Files['name'][$k]) )  )
					{
						//업로드 성공
						
						// 기존 저장되어있는 파일 삭제(주의 : 현재 업로드한 파일명 또는 폴더포함 파일명과 동일할 경우 삭제안함) 
						if($result["dir"] . basename($Files['name'][$k]) != $upload_datas["attach_path"] . $upload_datas["attach_files"][$k]){
							$this->attach_delete( $upload_datas["attach_path"] . $upload_datas["attach_files"][$k] );
						}
						
						if( !empty($upload_datas["attach_files"]) ){
							// 교집합 처리 : 기존 저장된 파일 데이타 + 업로드한 파일
							$result["file"] = array_replace($result["file"], $upload_datas["attach_files"], array($k=>$Files['name'][$k])) ;
							// 저장된 파일 리스트(배열)에 삭제될 파일이 존재하면 모두 제거
							$this->WebAppService->Func->array_searchValue_remove($result["file"], $upload_datas["attach_files"][$k]);
						}else{
							// 신규등록시 
							$result["file"][$k] = $Files['name'][$k] ;
						}
						
					}
				}
			}
		}
		return $result ;
	}

	/**
	 * 게시판 - 권한  체크
	 * @tutorial board_info-TABLE 과 회원세션(session) 정보 체크
	 * @return boolean
	 */
	private function hasGrantBoard()
	{
		# 회원세션정보가 있는지
		if( isset($_SESSION['MBRLEV']) )
		{
			# 게시판 공지 작성 권한
			if( isset($_POST["frm_noti"]) ){
				if( (int)$_SESSION['MBRLEV'] >= (int)$this->boardInfoResult['noti_lev'] ) return true ;
			}
		}
		return false ;
	}
	/**
	 * 게시판 편집시 권한체크 & 데이타 가공
	 */
	private function get_mbr_type()
	{
		# 저장할 추가데이타
		$put_add_data = array();

		if($this->boardInfoResult["mbr_type"]==1)
		{
			if( $this->hasMemberCheck() ) // 비동기식일 경우에 작동
				$put_add_data = array( "userid" => $_SESSION['MBRID'] );
		}
		else{

			if( empty($_POST["frm_writer"]) || ctype_space($_POST["frm_writer"]) )
				$this->WebAppService->assign(array('error'=>'작성자명을 입력해주세요.'));
			    //$this->WebAppService->assign(array('error'=>'Please enter author name.'));

			if( empty($_POST["frm_userpw"]) || ctype_space($_POST["frm_userpw"]) )
				$this->WebAppService->assign(array('error'=>'비밀번호를 입력해주세요.'));
			    //$this->WebAppService->assign(array('error'=>'Please enter a password.'));

			$put_add_data = array(
					"writer" => (string) $_POST["frm_writer"],
					"pwd" => (string) $_POST["frm_userpw"]
			);
		}
		
		if($this->hasGrantBoard() == TRUE){
			$put_add_data = array_merge( $put_add_data, array("noti"=> (int) $_POST["frm_noti"]) ) ;
		}
		if($this->boardInfoResult["sec_pwd"]==1)
		{
		    $put_add_data = array_merge( $put_add_data, array("sec_pwd"=> (int) $_POST["frm_sec_pwd"]) ) ;
		}
		return $put_add_data ;
	}
	/**
	 * [Encode] SyntaxHighlighter
	 * 
	 * 소스코드 삽입한 경우 ( <code>....</code> )
	 * @param string $text
	 * @return string
	 */
	private static function SyntaxHighlighter_encode($text)
	{
		//$pattern = "/(<pre class="brush:)(.*?)(<\/code>)(.*)/is" ;
		//preg_match('/(.*?)(<pre\s+class=[\"|\']brush\s:\s(.*?)[\"|\'];\s>)(.*?)(<\/pre>)(.*)/is', $matches, $text) ;
		//preg_match('/(.*?)(<pre class="brush:([a-zA-Z]+)";>)(.*?)(<\/pre>)(.*)/is', $matches, $text) ;
		// '<pre class="brush:$1">',
		//	$text);
		
		/* $text = htmlspecialchars($text);
		 $text = preg_replace('/&lt;pre\s+class=&quot;brush:(.*?)&quot;&gt;(.*?)(&lt;/pre&gt;)/',
		 '<pre class="brush:$1">',
		 $text); */
		//preg_match("/(.*?)<pre class=\"brush:[a-zA-Z]+\;\">(.*?)<\/pre>(.*)/s", $text, $mat ) ;
		/* if( preg_match("/<pre class=\"brush:(.*?);\">/s", $text) ){
		 $text = preg_replace("/<pre class=\"brush:(.*?);\">(.*?)<\/pre>/s",
		 '<code>$2</code>', $text) ;
		 } */
	    //$text = str_replace("<br />", "<br>", $text) ;
		if( preg_match("/<code>(.*?)<\/code>/s", $text) )
		{
			$pattern = "/(.*?)(<code>)(.*?)(<\/code>)(.*)/is" ;
			preg_match($pattern, $text, $match) ;
			
			/* text */$match[1]= Strings::tag_remove( $match[1]) ;
			/* text *///$match[1]= htmlspecialchars($match[1], ENT_QUOTES) ;
			
			/* source code */$match[3] = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $match[3]) ;
			/* source code */$match[3] = htmlspecialchars_decode($match[3]) ;
			/* source code */$match[3]= Strings::tag_remove($match[3]) ;
			/* source code *///$match[3]= htmlspecialchars($match[3]) ;
			
			/* text */$match[5]= Strings::tag_remove( $match[5]) ;
			/* text *///$match[5]= htmlspecialchars($match[5], ENT_QUOTES) ;
			
			if( !empty($match[1]) ) $strings = $match[1] ;
			if( !empty($match[3]) ) $strings .= "\n". $match[2] .$match[3] .$match[4]."\n" ;
			if( !empty($match[5]) ) $strings .= $match[5] ;
		}
		else{
		    $strings = Strings::tag_remove( $text ) ;
		}
		//echo '<pre>';print_r($strings);exit;
		return $strings ;
	}
	/**
	 * [Decode] SyntaxHighlighter
	 *
	 * 소스코드 삽입한 경우 ( <code>....</code> )
	 * @param string $text
	 * @return string
	 */
	private static function SyntaxHighlighter_decode($text)
	{
		if( preg_match("/<code>(.*?)<\/code>/s", $text) )
		{
			$pattern = "/(.*?)(<code>)(.*?)(<\/code>)(.*)/is" ;
			preg_match($pattern, $text, $match) ;
			
			// source code
			$match[3] = htmlspecialchars_decode($match[3]) ;
			$match[3]= "<pre class=\"brush:php\">".$match[3]."</pre>" ;
			
			/* $match[1] = '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($match[1])).'</p>';
			$match[5] = '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($match[5])).'</p>'; */
							
							$strings = $match[1] .$match[3] .$match[5] ;
		}
		else{
			/* $strings= '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($text)).'</p>'; */
			$strings = trim($text);
		}
		return $strings;
	}
	
	/**
	 * [Encode] highlightjs
	 * 
	 * 소스코드 삽입한 경우 ( <pre><code>....</code></pre> )
	 * @param string $text
	 * @return string
	 * @deprecated
	 */
	private static function highlightjs_encode($text)
	{
		if( preg_match("/<pre>(|\r\n)<code[^>]*?>(.*?)<\/code>(|\r\n)<\/pre>/s", $text) )
		{
			$pattern = "/(.*?)(<pre>(|\r\n)<code[^>]*?>)(.*?)(<\/code>(|\r\n)<\/pre>)(.*)/is" ;
			preg_match($pattern, $text, $match) ;
			//echo '<pre>';print_r($match);exit;
			/* text */$match[1]= Strings::tag_remove( $match[1]) ;
			/* text */$match[2]= "<pre><code>";
			/* source code *///$match[4]= htmlspecialchars($match[4]) ;
			/* source code */$match[4]= Strings::tag_remove($match[4]) ;
			/* text */$match[5]= "</code></pre>" ;
			/* text */$match[7]= Strings::tag_remove( $match[7]) ;
			
			if( !empty($match[1]) ) $strings = $match[1] ;
			if( !empty($match[4]) ) $strings .= "\n". $match[2] .$match[4] .$match[5] ."\n" ;
			if( !empty($match[7]) ) $strings .= $match[7] ;
		}
		else{
			$strings = Strings::tag_remove( $text) ;
		}

		return $strings ;
	}
	/**
	 * [Encode] SyntaxHighlighter
	 *
	 * 소스코드 삽입한 경우 ( <code>....</code> )
	 * @param string $text
	 * @return string
	 * @deprecated
	 */
	private static function SyntaxHighlighter_encode1($text)
	{
		//$pattern = "/(<pre class="brush:)(.*?)(<\/code>)(.*)/is" ;
		//preg_match('/(.*?)(<pre\s+class=[\"|\']brush\s:\s(.*?)[\"|\'];\s>)(.*?)(<\/pre>)(.*)/is', $matches, $text) ;
		//preg_match('/(.*?)(<pre class="brush:([a-zA-Z]+)";>)(.*?)(<\/pre>)(.*)/is', $matches, $text) ;
		// '<pre class="brush:$1">',
		//	$text);
		
		/* $text = htmlspecialchars($text);
		 $text = preg_replace('/&lt;pre\s+class=&quot;brush:(.*?)&quot;&gt;(.*?)(&lt;/pre&gt;)/',
		 '<pre class="brush:$1">',
		 $text); */
		//preg_match("/(.*?)<pre class=\"brush:[a-zA-Z]+\;\">(.*?)<\/pre>(.*)/s", $text, $mat ) ;
		if( preg_match("/<pre class=\"brush:(.*?);\">/s", $text) ){
			$text = preg_replace("/<pre class=\"brush:(.*?);\">(.*?)<\/pre>/s",
					'<code>$2</code>', $text) ;
		}
		
		if( preg_match("/<code>(.*?)<\/code>/s", $text) )
		{
			$pattern = "/(.*?)(<code>)(.*?)(<\/code>)(.*)/is" ;
			preg_match($pattern, $text, $match) ;
			
			/* text *///$match[1]= Strings::tag_remove( $match[1]) ;
			/* text */$match[1]= filter_var($match[1], FILTER_SANITIZE_STRING);
			
			/* source code *///$match[3]= htmlspecialchars($match[3]) ;
			/* source code */$match[3]= Strings::tag_remove($match[3]) ;
			
			/* text *///$match[5]= Strings::tag_remove( $match[5]) ;
			$match[5]= filter_var($match[5], FILTER_SANITIZE_STRING);
			
			
			if( !empty($match[1]) ) $strings = $match[1] ;
			if( !empty($match[3]) ) $strings .= "\n". $match[2] .$match[3] .$match[4]."\n" ;
			if( !empty($match[5]) ) $strings .= $match[5] ;
		}
		else{
			/* $strings= '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($text)).'</p>'; */
		    $strings = filter_var(trim($text), FILTER_SANITIZE_STRING);
		    
		}
		echo '111<pre>';print_r($strings) ;exit;
		return $strings ;
	}
	private static function syntaxHightlight_callback( $matches )
	{
		echo '111<pre>';print_r($matches) ;exit;
		// source code
		$match[3]= "<pre class=\"brush:php;\">".$match[3]."</pre>" ;
		
		$match[1] = '<p>'.preg_replace(
				array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
				array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
				trim($match[1])).'</p>';
				$match[5] = '<p>'.preg_replace(
						array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
						array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
						trim($match[5])).'</p>';
						
						$strings = $match[1] .$match[3] .$match[5] ;
						
						return $strings ;
	}
	/**
	 * [Decode] SyntaxHighlighter
	 *
	 * 소스코드 삽입한 경우 ( <code>....</code> )
	 * @param string $text
	 * @return string
	 * @deprecated
	 */
	private static function SyntaxHighlighter_decode1($text)
	{
		if( preg_match("/<code>(.*?)<\/code>/s", $text) )
		{
			$pattern = "/(.*?)(<code>)(.*?)(<\/code>)(.*)/is" ;
			$pattern = "/(<code>)(.*?)(<\/code>)/is" ;
			preg_match($pattern, $text, $match ) ;
			echo '<pre>';print_r($match) ;exit;
			$strings= preg_replace_callback($pattern, __CLASS__."::syntaxHightlight_callback", $text) ;
			echo '<pre>';print_r($strings) ;exit;
			
			// source code
			$match[3]= "<pre class=\"brush:php;\">".$match[3]."</pre>" ;
			
			$match[1] = '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($match[1])).'</p>';
					$match[5] = '<p>'.preg_replace(
							array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
							array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
							trim($match[5])).'</p>';
							
							$strings = $match[1] .$match[3] .$match[5] ;
		}
		else{
			$strings= '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($text)).'</p>';
		}
		return $strings;
	}
	/**
	 * [Decode] highlightjs
	 *
	 * 소스코드 삽입한 경우 ( <pre><code>....</code></pre> )
	 * @param string $text
	 * @return string
	 */
	private static function highlightjs_decode($text)
	{
		if( preg_match("/<pre><code>(.*?)<\/code><\/pre>/s", $text) )
		{
			$pattern = "/(.*?)(<pre><code>)(.*?)(<\/code><\/pre>)(.*)/is" ;
			preg_match($pattern, $text, $match) ;
			
			/* source code */$match[3]= "<pre><code class=\"abnf\">".$match[3]."</code></pre>" ;
			
			$match[1] = '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($match[1])).'</p>';
					$match[5] = '<p>'.preg_replace(
							array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
							array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
							trim($match[5])).'</p>';
							
							$strings = $match[1] .$match[3] .$match[5] ;
		}
		else{
			$strings= '<p>'.preg_replace(
					array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
					array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
					trim($text)).'</p>';
		}
		//echo $strings;exit;
		return $strings;
	}
	
	
	/**
	 * 게시판 - DB 업데이트
	 */
	public function update()
	{
		if(REQUEST_METHOD=="POST")
		{
			# 저장할 추가데이타
			$put_add_data = $this->get_mbr_type();

			$error = $this->getValidate( array(
					/* "frm_mcode", */
					"frm_title",
					"frm_memo"
			)) ;
			if( !empty($error) ){
				$this->WebAppService->assign(array('error'=>$error));
			}
			
			// 회원제인 경우
			if($this->boardInfoResult["mbr_type"]==1)
			{
				if( $this->hasMemberCheck() ) // 비동기식일 경우에 작동
					$where_arr['userid'] = $_SESSION['MBRID'] ;
			}
			else{
				$where_arr['pwd'] = $_POST["frm_userpw"] ;
			}
			
			// 데이타 존재하는지 체크
			$exist_data = $this->count( "serial", array_merge(array("serial" => $this->routeResult["code"]),$where_arr) ) ;
			if( $exist_data < 1){
				if($this->boardInfoResult["mbr_type"]==1)
					$this->WebAppService->assign(array('error'=>"작성자가 본인이 아니면 수정할 수 없습니다."));
				    //$this->WebAppService->assign(array('error'=>"You can not edit it unless you are the author."));
				else
					$this->WebAppService->assign(array('error'=>"비밀번호가 일치하지 않습니다."));
					//$this->WebAppService->assign(array('error'=>"Passwords do not match."));
			}
			
			//$_POST["frm_memo"] = $this->highlightjs_encode($_POST["frm_memo"]) ;
			$_POST["frm_memo"] = self::{$this->syntaxHighlight_name."_encode"}($_POST["frm_memo"]) ;
			$_POST["frm_memo"] = addslashes($_POST["frm_memo"]) ;
			//echo $_POST["frm_memo"];exit;
			$put_data = array(
									"cate" => (int) $_POST["frm_cate"],
									"noti" => (int) $_POST["frm_noti"],
									"title" => (string) $_POST["frm_title"],
									"usehtml" => (int) $_POST["frm_usehtml"],
									"memo" => (string) $_POST["frm_memo"],
									"regdate" => time()
							) ;
			
			$put_data = array_merge($put_data, $put_add_data) ;
			//echo '<pre>';print_r($put_data);exit;
			
			// 등록된 파일데이타가 있을시 기존 파일 삭제를 위해 데이타 가져옴
			$where_arr["serial"] = $this->routeResult["code"] ;
			$data = $this->dataRead(array(
					"columns"=> "attach_path, attach_files",
					"conditions" => $where_arr
			));
			// 업로드 파일 읽기(삭제용도)
			if( !empty($data) ){
				$upload_datas = array(
						"attach_path" => $data[0]["attach_path"],
						"attach_files" => explode(",", $data[0]["attach_files"])
				) ;
			}
			$res = $this->attach_upload( $this->boardInfoResult["upload_path"], $_FILES["frm_attachFile"], $upload_datas ) ;
			if( !empty($res["file"]) ) 
			{
				$res_files = implode (",", $res["file"]) ;
				$put_data = array_merge($put_data, array(
						"attach_path" => $res["dir"], // 또는 $data[0]["attach_path"]
						"attach_files" => $res_files
				));
			}

			$res = $this->dataUpdate( $put_data,	$where_arr	) ;

			if(!$res){
				//Exception
				// 회원제인 경우
				/* if($this->boardInfoResult["mbr_type"]==1)
					$this->WebAppService->assign(array('error'=>"작성자가 본인이 아니면 수정할 수 없습니다."));
				else 
					$this->WebAppService->assign(array('error'=>"비밀번호가 일치하지 않습니다.")); */
			}

			header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
			exit;
		}

	}

	public function moveComments(){
		
		$error = $this->getValidate( array(
				"frm_mvOrig",
				"frm_mvTarget"
		)) ;
		if( !empty($error) )
			$this->WebAppService->assign(array('error'=>$error));
		
		if($this->routeResult["code"])
		{
			$this->setTableName("comments");
			//$res = $this->dataMove($_POST["frm_mvOrig"], $_POST["frm_mvTarget"]) ;
			$node = array(
					"serial" => $_POST["frm_mvOrig"]
					,"parent" => $_POST["frm_mvTarget"]
			);
			$res = $this->dataNestMove( $node );
		}
		
		header("Location: ".WebAppService::$baseURL."/view/".$this->routeResult["code"].WebAppService::$queryString); // 리스트 페이지 이동
		exit;
	}
	/**
	 * 댓글 게시글 가져오기
	 */
	public function readComments($ret=NULL, $search_params=NULL)
	{
    	    if(REQUEST_WITH != 'AJAX') {
    	        header('Location:/') ;	exit;
    	    }
	    
			$this->setTableName("comments");
			
			// P.K 코드 값이 없을경우
			if( ! $this->routeResult["code"] )
			{	// exception
				//header("Location: /".WebAppService::$baseURL."/add"); // 신규작성 폼으로 이동
				$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
			    //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
			}
			
			# 조건에 맞는 데이타 읽기
			if( !empty($search_params) ){
				$conditions_params = $search_params ;
			}
			# 정해진 조건 데이타 읽기
			else{
				$conditions_params = array(
						"B.serial" => $_REQUEST["serial"],
						"B.bserial" => $this->routeResult["code"],
						"B.bid" => $_REQUEST["bid"]
				) ;
			}
			
			/* $queryOption = array_merge(self::$queryOption_comments, 
					array(
						"conditions" => $conditions_params,
						"which" => "BM"
					)
			);
			$data = $this->dataRead($queryOption); */

			$queryOption = array_merge(self::$queryOption_comments, 
					array(
						"conditions" => $conditions_params
					)
			);
			$data = $this->getDataAndMbr( $queryOption ) ;

			# 데이타 가공
			self::data_process($data) ;
			
			if(!empty($data)) $data = array_pop($data); # javascript에서 1차원배열로 처리를위해
			
			if( is_bool($ret) )
				return $data ;
			else
				$this->WebAppService->assign($data) ;
			/* $this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'Action' => "update",
							"CODE" => $this->routeResult["code"],
							'queryString' => Func::QueryString_filter(),
							'formType' => "편집"
					),
					'DATA' => $data
			)) ; */
	}
	/**
	 * comments-TB 데이타를 가져와서 출력을 위해 가공처리
	 * 
	 * @param Array $datas (DB 데이타)
	 * @param string $kind ( default: "output" [출력용: "output" / 읽기용: "read"] )
	 * @return Array
	 */
	private function data_process( &$datas, $info=null)
	{
		if( !empty($datas) && is_array($datas) )
		{
			//echo '<pre>';print_r($datas);
			foreach($datas as &$data)
			{
				if( !empty($_SESSION['MBRID']) ){
					$data["my_data_chk"] = ($data["userid"] == $_SESSION['MBRID']) ? 1 : 0 ;
				}else{
					$data["my_data_chk"] = 0 ;
				}
				//unset($data["userid"]);
				
				/* 회원레벨 */$data["lev"] = self::$mbr_conf['lev'][$v["lev"]] ;
				/* 회원레벨 icon */$data["lev_ico"] = self::$mbr_conf['lev_css'][$v["lev"]] ;
				
				//$data[$k]["title"] = Strings::text_cut($data[$k]["title"], $this->boardInfoResult["title_len"]);
				//$data["memo"] = "<p>".str_replace("\n", "</p><p>", $data["memo"])."</p>" ;
				//$data["memo"] = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $data["memo"]);
				//$data["memo"]= stripslashes($data["memo"]) ;
				$data["memo"] = $this->decode_memo($data["memo"]) ;
				
				//비밀글(전체), 비밀글썻는지
				if($info['sec']==1) $data["sec"] = 1 ;
				
				if($data["sec"]==1)
				{
					//echo '<pre>';print_r($data);
					// 관리자 또는 자신이 작성한 글이 아닌경우
					if( $_SESSION['ADM'] !=1 && $_SESSION['MBRID'] != $data["userid"] )
					{
						$data["memo"] = '';
					}
				}
				unset($data["userid"]);
				
				/* if( $data["editor"] != 1){ //에디터 사용할 경우
					$data["memo"] = htmlspecialchars_decode($data["memo"]) ;
				} */
				if($data["memo"])
				{
					/* 출력용(내용) */$data["memo_output"] = str_replace(" ","&nbsp;", $data["memo"]);
					/* 출력용(내용) */$data["memo_output"] = nl2br($data["memo_output"]);
					//if( !empty($this->boardInfoResult["indent"]) ) $data[$k]["title"] = str_repeat("==>", $data[$k]["indent"] ). $data[$k]["title"] ;
				}
				//----------------------
				//$data["elapsed_days"] = Strings::get_elapsed_days($data['regdate']) ;
				$elapsed_date = Strings::get_elapsed_date($data['regdate']) ;
				$elapse_date = array() ;
				if($elapsed_date->y) {
					$elapse_date[] = $elapsed_date->y."년";
				}
				else if(!$elapsed_date->y && $elapsed_date->m) {
					$elapse_date[] = $elapsed_date->m."개월";
				}
				else if(!$elapsed_date->y && !$elapsed_date->m && $elapsed_date->d){
					$elapse_date[] = $elapsed_date->d."일";
				}
				else if(!$elapsed_date->y && !$elapsed_date->m && !$elapsed_date->d && $elapsed_date->h){
					$elapse_date[] = $elapsed_date->h."시간";
				}
				else if(!$elapsed_date->y && !$elapsed_date->m && !$elapsed_date->d && !$elapsed_date->h && $elapsed_date->i){
					$elapse_date[] = $elapsed_date->i."분";
				}
				else if(!$elapsed_date->y && !$elapsed_date->m && !$elapsed_date->d && !$elapsed_date->h && !$elapsed_date->m && $elapsed_date->s){
					$elapse_date[] = $elapsed_date->s."초";
				}
				//if($elapsed_date->days) $elapse_date[] = $elapsed_date->days." 일수";
				$data["elapsed_days"] = "[". implode(" ", $elapse_date) . " 전]";
				//----------------------
				
				$regdate= date('Y-m-d H:i:s', $data["regdate"]) ;
				$data["regdate"] = substr( $regdate, 0, 10);
				$data["regtime"] = substr( $regdate, 10);
				
				
				if( !empty($data["profile_photo"]) ){
					$profile_photo_file = self::$mbr_conf["profile"]["basedir"].$data["profile_photo"] ;
					$data["profile_photo"] = ( is_file($profile_photo_file) ) ? "/".$profile_photo_file : "";
				}
			}
		}
		//echo '<pre>';print_r($datas);
		//return $datas ;
	}
	/**
	 * 게시판 댓글 리스트 조회
	 *
	 * @param array<key,value> $queryOption
	 * 		value : array( 
	 * 						"columns"=>"", # 칼럼명, 칼럼명...
	 * 						"order"=>"", # 정렬
	 * 						"conditions"=>"", # 조건문
	 * 						"join" => NULL # sql join(LEFT, INNER...) 형태
	 * 						"pageBlock" => NULL # LIMIT문( 가져올 레코드 블럭(?, ?) 또는 갯수 )
	 * 					)
	 * @param string $board_id (게시판 아이디)
	 * 
	 * @example $queryOption = array(
	 * 											"columns" => "column-name, column-name",
	 * 											"conditions" => string or array(.......)
	 * 											"join" => "left" # sql join 형태
	 * 											"order" => "?? desc, ?? asc...."
	 * 											"pageBlock" => 15
	 * 					 				) ;
	 * @return array
	 */
	private function get_comments_lst( &$queryOption, &$board_id=NULL )
	{
		echo '<pre>';print_r($ResultQuery);
		//Exception
		/* if( empty(self::$TABLE) ) {
			echo 'comment not table';
			exit;
		} */
		if( !$board_id ) return false ;
		
		if(empty($this->commentsInfoResult)){
			$this->commentsInfoResult = $this->getBrdComments_info("comments_info", array("bid" => $board_id)) ;
		}
		
		//$this->WebAppService->assign(array("Comments_conf" =>$this->commentsInfoResult)) ;
		$this->pageScale = $this->commentsInfoResult["listscale"]?: 0;
		$this->pageBlock = $this->commentsInfoResult["pagescale"] ?: 0;
		
		self::$pageVariable = 'cpage';
		$_REQUEST[self::$pageVariable] = $_REQUEST[self::$pageVariable] ;
		
		
		$this->setTableName("comments");
		$datas = $this->getDataAndMbr( array_merge(self::$queryOption_comments, $ResultQuery["query_condition"]), $this->commentsInfoResult["indent"], true ) ;
		
		# 데이타 가공
		self::data_process($datas, $this->commentsInfoResult) ;
		
		WebAppService::$queryString = Func::QueryString_filter( $ResultQuery["queryString"] );
		$paging = $this->Pagination($_REQUEST[self::$pageVariable], $ResultQuery["queryString"]);
		
		//echo '<pre>';print_r($datas);
		//echo '<pre>';print_r($queryOption);
		/* $queryOption = array(
				"columns" => "M.username, M.usernick, M.profile_photo, B.serial, B.family, B.parent, B.lft, B.rgt, B.indent, B.writer, B.memo, B.regdate",
				"conditions" => $search_params,
				"join" => "LEFT",
				"order" => "B.serial DESC"
		);
		$datas = $this->getDataAndMbr( $queryOption, $comments_info["indent"], true ) ;
		
		if( is_array($datas) )
		{
			foreach($datas as $k => $v)
			{
				$datas[$k]["memo"] = Strings::text_cut($datas[$k]["memo"], $comments_info["title_len"]);
				//if( !empty($comments_info["indent"]) ) $datas[$k]["memo"] = str_repeat("==>", $datas[$k]["indent"] ). $datas[$k]["memo"] ;
				if( !empty($comments_info["indent"]) ) $datas[$k]["memo"] = nl2br($datas[$k]["memo"]) ;
			
				$datas[$k]["regdate"] = date('Y-m-d', $datas[$k]["regdate"]) ;
				$datas[$k]["regtime"] = date('H:i:s', $datas[$k]["regdate"]) ;
				
				if( !empty($datas[$k]["profile_photo"]) ){
					$profile_phto_file = self::$mbr_conf["profile"]["basedir"].$datas[$k]["profile_photo"] ;
					$datas[$k]["profile_photo"] = ( is_file($profile_phto_file) ) ? "/".$profile_phto_file : "";
				}
			}
		} */
		//댓글리스트 출력처리
		return array(
				"LIST"=> &$datas,
				'TOTAL_CNT' => self::$Total_cnt, //$this->count(),
				'PAGING' => &$paging,
				'VIEW_NUM' => self::$view_num,
				'Comments_conf' => &$this->commentsInfoResult
		) ;

	}
	
	public function Req_getComments()
	{
		if(REQUEST_WITH != 'AJAX') {
			header('Location:/') ;	exit;
		}
		/* $queryOption = array_merge(self::$queryOption_comments, array(
				"conditions" =>  array(
						"B.bid" => $_REQUEST["bid"],
						"B.bserial" => $this->routeResult["code"]
						
				)
		)) ; */
		
		// 검색조건 처리
		//----------------------------------------
		//array(query_condition, queryString)
		$ResultQuery = array();
		$ResultQuery = self::condition_board( array(
				"bid" => $_REQUEST['bid'],
				"search_field" => $_REQUEST['search_field'],
				"search_keyword" => $_REQUEST['search_keyword']
		)) ;
		$ResultQuery["query_condition"]['B.bserial'] = $this->routeResult["code"];
		
		$comments_datas = $this->get_comments_lst($ResultQuery, $_REQUEST["bid"]);
		
		// 템플릿 파일
		if( !empty($this->commentsInfoResult['editor']) )
            $template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base.lst." .$this->syntaxHighlight_name. ".html" ;
        else 
          $template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base.html" ;
	      
        $template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base.lst." .$this->syntaxHighlight_name. ".html" ;
        
        
        $tpl = &WebApp::singleton('Display');
        $tpl->assign(array(
        		"COMMENTS_LIST"=> &$comments_datas['LIST'],
        		//'COMMENTS_TOTAL_CNT' => $comments_datas['TOTAL_CNT'],
        		'COMMENTS_PAGING' => &$comments_datas['PAGING'],
        		//'COMMENTS_VIEW_NUM' => $comments_datas['VIEW_NUM'],
        		'Comments_conf' => &$this->commentsInfoResult
        ));
        
        $tpl->define("CONTENT", Display::getTemplate($template));
        //echo '<pre>';print_r($tpl);
        $content = $tpl->fetch('CONTENT');

        $this->WebAppService->assign(array(
            'datas' => $content,
        	'paging' => &$comments_datas['PAGING'],
        ));
        
	}
	
	/**
	 * [ 신규 / 추가] 작성폼
	 */
	/* public function addComments()
	{
		if(REQUEST_METHOD=="GET")
		{
			// [답변형 or 계층형]
			if( $this->routeResult["code"] )
			{
				$this->setTableName("comments");
				$data = $this->dataRead(
						"*",
						array(
								"bserial" => $this->routeResult["code"],
								"serial" => $_REQUEST["serial"],
								"bid" => $_REQUEST["bid"]
						)
				);
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
					'DATA' => $data
			)) ;
			$this->WebAppService->Output( Display::getTemplate('html/board/skin/boardComm/edit.htm'), "sub2");
			$this->WebAppService->printAll();
		}
	} */
	/**
	 * 댓글 - DB 저장
	 */
	public function writeComments()
	{
	    if(REQUEST_WITH != 'AJAX') {
	        header('Location:/') ;	exit;
	    }
		if(REQUEST_METHOD=="POST")
		{
			// 회원제인 경우
			if($this->boardInfoResult["mbr_type"]==1)
			{
				$this->hasMemberCheck();
			}
			
			//Exception
			if( !$this->routeResult["code"] )
				$this->WebAppService->assign(array('error'=>'데이타가 부족하여 조회할 수 없습니다.'));
			    //$this->WebAppService->assign(array('error'=>'There is not enough data to query.'));
			
			//Validation variable
			if( empty($_POST["frm_memo"]) || ctype_space($_POST["frm_memo"]) )
				$this->WebAppService->assign(array('error'=>'내용을 입력해주세요.'));
			    //$this->WebAppService->assign(array('error'=>'Please enter contents.'));
			
			$error = $this->getValidate( array(
					"frm_memo"
			)) ;
			if( !empty($error) )
				$this->WebAppService->assign(array('error'=>$error));

			$this->setTableName("comments_info") ;
			$data= $this->dataRead(	array(
					"columns" => "*",
					"conditions" => array("bid" => $_REQUEST["bid"])
			) ) ;
			if( !empty($data) ){
				$comments_info = array_pop($data);
			}

			$this->setTableName("comments"); // table name 재정의
			//$data = $this->getDataAndMbr( array(
			$data = $this->dataRead( array(
					"columns"=> "mcode, bid, cate",
					"conditions" => array(
							"serial" => $this->routeResult["code"],
							"bid" => $_REQUEST["bid"]
					)
			));
			//echo '<pre>';print_r($data);
			if( !$data )
				$this->WebAppService->assign(array('error'=>'원본 게시글이 존재하지 않습니다.'));
			    //$this->WebAppService->assign(array('error'=>'The original post does not exist.'));
			else
				$data = array_pop($data) ;

			//$_POST["frm_memo"] = $this->sourceCode($_POST["frm_memo"]) ;
			//$_POST["frm_memo"] = addslashes($_POST["frm_memo"]) ;
			
			$_POST["frm_memo"] = self::{$this->syntaxHighlight_name."_encode"}($_POST["frm_memo"]) ;
			$_POST["frm_memo"] = addslashes($_POST["frm_memo"]) ;
			
			$put_data = array(
								"oid" => (int) OID,
								"mcode" => (int) $data["mcode"],
								"bid" => (string) $data["bid"],
								"cate" => (int) $data["cate"],
								"bserial" => (int) $this->routeResult["code"],
								"userid" => (string) $_SESSION['MBRID'],
								"usehtml" => (int) $_POST["frm_usehtml"],
								"memo" => (string) $_POST["frm_memo"],
								"sec" => (int) $_POST["frm_sec"],
								"ip" => $_SERVER['REMOTE_ADDR'],
								"firstdate" => time(),
								"regdate" => time()
						);
			
			$this->setTableName("comments");
			// 삽입할 인접(Adjacency) 정보 등록시 $this->routeResult["code"]
			//if( $comments_info["indent"] && $_POST["serial"])
			if( $comments_info["indent"] )
			{
				if( (int)$_POST["serial"] )
				{
					$lastChildRow = $this->dataLastChild( (int)$_POST["serial"] );
					$insert_id = $this->dataAdd( $put_data, $_POST["serial"] ) ;
				}
				else{ 
					$insert_id = $this->dataAdd( $put_data );
				}
			}
			else{
				$insert_id = $this->dataAdd( $put_data );
			}
			
			
			if($insert_id)
			{
				// 새로 저장된(실제로 저장된) 데이타 가져오기
				$_REQUEST['serial'] = $insert_id ;
					
				$readRow = $this->readComments(true) ;
				//$readRow = array_pop($readRow) ;
				$this->WebAppService->assign(array(
							'data' => $readRow,
							'last' => array("serial" => $lastChildRow[0]["serial"]) 
						)) ;
					
				 
				if( REQUEST_WITH != 'AJAX'){
					header("Location: ".WebAppService::$baseURL."/view/".$this->routeResult["code"].WebAppService::$queryString); // 리스트 페이지 이동
					exit;
				}
			}
			else{
				$this->WebAppService->assign(array('error'=>'저장실패~다시입력해주세요.'));
			    //$this->WebAppService->assign(array('error'=>'Failed to save. Please re-enter.'));
			}

		}
	
	}
	public function updateComments()
	{
	    if(REQUEST_WITH != 'AJAX') {
	        header('Location:/') ;	exit;
	    }
		if(REQUEST_METHOD=="POST")
		{
			$where_arr = array(
					"serial" => $_REQUEST["serial"],
					"bserial" => $this->routeResult["code"],
					"bid" => $_REQUEST["bid"],
					//"userid" => $_SESSION['MBRID']
			) ;
			
			// 회원제인 경우
			if($this->boardInfoResult["mbr_type"]==1)
			{
				$this->hasMemberCheck();
				
				$where_arr = array_merge($where_arr, array("userid" => $_SESSION['MBRID'])) ;
			}
			
			$this->setTableName("comments");
				
			# 데이타 존재유무 체크
			$exist_data = $this->count( "serial", $where_arr) ;
			if( $exist_data < 1)
				$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
			    //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
				
			# 자신이 작성한 데이타인지 체크
			$exist_data = $this->count( "serial", $where_arr) ;
			if( $exist_data < 1)
				$this->WebAppService->assign(array('error'=>'작성자 본인만 수정 가능합니다.'));
			    //$this->WebAppService->assign(array('error'=>'Only the author can modify it.'));
			
			# Validation variable
			if( empty($_POST["frm_memo"]) || ctype_space($_POST["frm_memo"]) )
				$this->WebAppService->assign(array('error'=>'내용을 입력해주세요.'));
			    //$this->WebAppService->assign(array('error'=>'Please enter contents.'));

			$error = $this->getValidate( array(
					"frm_memo"
			)) ;
			if( !empty($error) )
				$this->WebAppService->assign(array('error'=>$error));
			
			/* $comments_info = $this->getBrdComments_info(
					array("bid" => $_REQUEST["bid"])
			) ; */
			//$_POST["frm_memo"] = $this->sourceCode($_POST["frm_memo"]) ;
			//$_POST["frm_memo"] = addslashes($_POST["frm_memo"]) ;
			/* $_POST["frm_memo"] = self::{$this->syntaxHighlight_name."_encode"}($_POST["frm_memo"]) ;
			$_POST["frm_memo"] = addslashes($_POST["frm_memo"]) ; */
			$this->encode_memo($_POST["frm_memo"]) ;
			//echo '<pre>';print_r($_POST["frm_memo"]);
			$res = $this->dataUpdate(
					array(
							"cate" => (int) $_POST["frm_cate"],
							"title" => (string) $_POST["frm_title"],
							"usehtml" => (int) $_POST["frm_usehtml"],
							"memo" => (string) $_POST["frm_memo"],
							"sec" => (int) $_POST["frm_sec"],
							"regdate" => time()
							
					),
					$where_arr
				) ;
			# 조건에 맞는 데이타가 없을 경우
			/* if( empty($res) )
				$this->WebAppService->assign(array('error'=>'수정할 내용이 없습니다.')); */

			if( REQUEST_WITH == 'AJAX')
			{
				//$_REQUEST['serial'] = $insert_id ;
				$this->readComments() ;
				exit;
			}

			header("Location: ".WebAppService::$baseURL."/view/".$this->routeResult["code"].WebAppService::$queryString); // 리스트 페이지 이동
			exit;
		}
	}
	public function deleteComments()
	{
	    if(REQUEST_WITH != 'AJAX') {
	        header('Location:/') ;	exit;
	    }
		if($this->routeResult["code"])
		{

			$this->setTableName("comments");
			
			$where = array(
					//"userid" => $_SESSION['MBRID'],
					"serial" => $_REQUEST["serial"],
					"bserial" => $this->routeResult["code"],
					"bid" => $_REQUEST["bid"]
			) ;
			
			// 회원제인 경우
			if($this->boardInfoResult["mbr_type"]==1)
			{
				$this->hasMemberCheck();
				
				$where = array_merge($where, array("userid" => $_SESSION['MBRID'])) ;
			}
			
			$res = $this->dataDelete( $where ) ;

			if( empty($res) )
				$this->WebAppService->assign(array('error'=>'작성자 본인만 삭제 가능합니다.'));
			    //$this->WebAppService->assign(array('error'=>'Only the author can delete it.'));
			else 
				$this->WebAppService->assign(array('result'=>true)) ;
			
		}
		header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
		exit;
	}
	
	/**
	 * 게시판이 회원용인지 체크
	 * 
	 * @return boolean (회원이면 true)
	 */
	private function hasMemberCheck()
	{
		if( ! $this->hasMemberLogin() ) // 비동기식일 경우에 작동
		{
			$this->WebAppService->assign(array('error'=>'로그인 후 이용해주세요.'));
			//$this->WebAppService->assign(array('error'=>'Please try again after logging in.'));
			return false ;
		}	
		return true ;

	}
	
	
}