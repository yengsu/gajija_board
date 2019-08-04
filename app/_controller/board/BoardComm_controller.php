<?php
use system\traits\Plugin_Trait;
use Gajija\controller\_traits\Page_comm;
use Gajija\service\board\BoardCommNest_service;
use Gajija\Exceptions\BaseException;
use Gajija\controller\_traits\Controller_comm;

/**
 * 게시판 환경설정 &
 * 기본 게시판 컨트롤러
 */
class BoardComm_controller extends BoardCommNest_service
{
	use Plugin_Trait, Page_comm, Controller_comm ;
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
    
    /**
     * 댓글(Comments) 환경정보 데이타
     * @var array
     */
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
     * 첨부파일 저장 경로
     * @var string ( default :  'html/_attach/board/' )
     */
    public static $attach_basedir = 'theme/'.THEME.'/_attach/board/'; //'html/_attach/popup/' ;
    /**
     * comments-TB 기본 Query옵션
     *
     * @var array ( columns, conditions, join, order...)
     */
    public static $queryOption_comments = array(
        "columns"=> "M.lev, M.usernick, M.profile_photo, B.serial, B.lft, B.rgt, B.userid, B.family, B.parent, B.indent, B.memo, B.sec, B.parent_del, B.firstdate, B.regdate",
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
     
     $this->setTableName("board");
     } */
    
    public function __construct($routeResult=NULL)
    {
        /**
         * 메뉴코드 체크
         */
        if( ! (int)$routeResult["mcode"] )
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
        //if(!$this->WebAppService  || !class_exists('WebAppService'))
		if( ! $this->WebAppService instanceof WebAppService )
        {
            // instance 생성
            $this->WebAppService = &WebApp::singleton("WebAppService:system");
            
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

        // XSS 방어
        Strings::set_xss_variable( array(
        		"GET" => array("search_field" , "search_keyword", "bid"),
        		"POST" => array("frm_writer", "frm_userpw", "frm_sec_pwd", "search_keyword", "bid")
        ) );
        
        // 메뉴[정보 & 접근권한]
        if( !empty($routeResult["mcode"]) ) {
            try
            {
                $this->menu_display_apply($routeResult["mcode"]) ;
            }
            catch (\Exception $e) {
                $this->WebAppService->assign( array(
                    "error" => $e->getMessage(),
                    "error_code" => $e->getCode()
                ));
            }
        }
        
        //게시판 환경 정보 가져오기
        if($_REQUEST["bid"]) $this->get_board_info($_REQUEST["bid"]);
        
        //페이지 레이아웃 가져오기
        if( isset(self::$menu_datas['self']['layout']) && !empty(self::$menu_datas['self']['layout']) ){
            $this->page_layout = self::$menu_datas['self']['layout'] ;
        }
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
         
         
         
         
         //self::$mbr_conf["profile"] = WebApp::getConf_real("member.profile");
         //self::$mbr_conf = WebApp::getConf_real("member");
         
         self::set_syntaxHighlight();
         
         if(!empty($this->boardInfoResult['table_name'])) $this->boardInfoResult['table_name'] = 'board' ;
         
         // DB Table 선언
         //$this->setTableName("board");
         $this->setTableName($this->boardInfoResult['table_name']);
    }
    public function __destruct()
    {
        foreach($this as $k => &$obj){
            unset($this->$k, $obj);
        }
    }
    /**
     * 게시판 접근권한
     *
     * @uses 인증실패시 [로그인페이지] 또는 [경고창] 출력
     *
     * @param string $grant_type (read | write | update | delete)
     * @param string $bid (게시판 ID)
     * @return void
     *
     * @access 응답변수 참조 : array $this->grant_content
     *
     * @throws \Exception
     */
    private function board_access_grant( string $grant_type, string $bid )
    {
        # 컨텐츠 [범용]권한인증(읽기,쓰기, 수정,삭제)
        try
        {
            if( !empty($this->boardInfoResult) ){
                
                // 사용권한 인증 정보
                $this->authen_comm_grant( $grant_type, "board", $bid );
                
            }
            else throw new \Exception("게시판 정보가 없습니다.");
        }
        catch (\Exception $e) {
            $this->WebAppService->assign( array(
                "error" => $e->getMessage(),
                "error_code" => $e->getCode()
            ));
        }
    }
    /**
     * 댓글 접근권한
     *
     * @uses 인증실패시 [로그인페이지] 또는 [경고창] 출력
     *
     * @param string $grant_type (read | write | update | delete)
     * @param string $bid (게시판 ID)
     * @return void
     *
     * @access 응답변수 참조 : array $this->grant_content
     *
     * @throws \Exception
     */
    private function comments_access_grant( string $grant_type, string $bid )
    {
    	# 컨텐츠 [범용]권한인증(읽기,쓰기, 수정,삭제)
    	try
    	{
    		if( !empty($this->boardInfoResult) ){
    			
    			// 사용권한 인증 정보
    			$this->authen_comm_grant( $grant_type, "comments", $bid );
    		}
    		else throw new \Exception("댓글 정보가 없습니다.");
    	}
    	catch (\Exception $e) {
    		$this->WebAppService->assign( array(
    				"error" => $e->getMessage(),
    				"error_code" => $e->getCode()
    		));
    	}
    }
    /**
     * 게시판 환경 정보 가져오기
     * 
     * @param string $bid (게시판 아이디)
     * @return array $this->boardInfoResult
     */
    private function get_board_info($bid)
    {
        $this->setTableName("board_info") ;
        
        $data= $this->dataRead(array(
            "columns" => "*",
            "conditions" => array("bid" => $bid)
        ) ) ;
        if( !empty($data) ){
        	$this->boardInfoResult = $data[0];
        }
        
        /**
         * 게시판 환경정보 체크
         */
        if( empty($this->boardInfoResult))
        {
            $this->WebAppService->assign(array('error'=>'자료를 찾을 수 없습니다.'));//No data found.
            /* WebApp::redirect('/', "자료를 찾을 수 없습니다.");
             exit; */
        }
    }
    /**
     * 댓글 환경 정보 가져오기
     * 
     * @param string $bid (댓글 아이디)
     * @return array $this->commentsInfoResult
     */
    private function get_comment_info($bid)
    {
        $this->setTableName("comments_info") ;
        
        $data= $this->dataRead(array(
            "columns" => "*",
            "conditions" => array("bid" => $bid)
        ) ) ;
        if( !empty($data) ){
        	$this->commentsInfoResult = $data[0];
        }
        
        /**
         * 게시판 환경정보 체크
         */
        if( empty($this->commentsInfoResult) )
        {
            $this->WebAppService->assign(array('error'=>'자료를 찾을 수 없습니다.'));//No data found.
            /* WebApp::redirect('/', "자료를 찾을 수 없습니다.");
             exit; */
		}
    }
    /**
     * 
     * @param array &$args (pass by reference)
     */
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
     * 리스트 목록 출력(http방식)처리
     *
     * @param mixed $out ( 값이 true 이면 게시판만 적용 / array(Router정보)이면 레이아웃 포함 적용
     */
    public function lst($out=null)
    {
        //echo '<pre>';print_r($_SESSION);
        //self::$menu_datas = $this->get_menu_top('shop_cate');
        //echo '<pre>';print_r($this->boardInfoResult);
        $this->pageScale = $this->boardInfoResult["listscale"]?: 10;
        $this->pageBlock = $this->boardInfoResult["pagescale"] ?: 10;
        
        $this->get_board_lst() ; // 게시판 데이타 처리
        
        //리스트에서는 비밀글 변수 삭제
        WebAppService::$queryString = Func::QueryString_removeItem( '__bgp' , WebAppService::$queryString);
        
        // 글 쓰기권한 정보($this->grant_content)
        $this->authen_comm_grant( "write", "board", (string)$_REQUEST['bid'] ) ;
        
        if( !is_bool ($out) )
        {
            $this->WebAppService->assign(array(
                'Doc' => array(
                    'baseURL' => WebAppService::$baseURL,
                    "CODE" => $this->routeResult["code"],
                    'queryString' => WebAppService::$queryString
                )
                //'MENU_TOP' => &self::$menu_datas['childs'],
                
                ,"GRANT" => $this->grant_content //  글 쓰기권한 정보
            )) ;
            
            //$this->WebAppService->Output( Display::getTemplate('html/board/skin/base/boardComm/list.htm'),'sub');
            $template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/list.html" ;
            //$this->WebAppService->Output( Display::getTemplate($template),"sub");
            $this->WebAppService->Output( $template, $this->page_layout);
            
            $this->WebAppService->printAll();
        }
        /* else{
         if($out == TRUE )
         $this->WebAppService->Display->define('BOARD_LIST', Display::getTemplate('html/board/skin/boardComm/list.htm') );
         } */
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
        if(! (int)$this->pageScale) $this->pageScale = 20 ; // record ea
        if(! (int)$this->pageBlock) $this->pageBlock = 5 ; // page block ea
        
        $_REQUEST[self::$pageVariable] = $_GET[self::$pageVariable] ;
        
        if( !empty($_POST['search_keyword']) && strlen($_POST['search_keyword']) < 2 ) {
        	header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
        	exit;
        }
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
								B.serial, B.bid, B.family, B.parent, B.lft, B.rgt, B.indent, B.writer, B.title, B.noti, B.sec, B.regdate, B.viewcnt",
            "conditions" => $ResultQuery["query_condition"],
            "join" => "LEFT",
            "order" => "B.noti DESC, B.serial desc" //, B.serial DESC"
        );
        //echo '<pre>';print_r($queryOption)
        $this->setTableName($this->boardInfoResult['table_name']);
        $datas = $this->getDataAndMbr( $queryOption, $this->boardInfoResult["indent"] ) ;
        if( !empty($datas) )
        {
            foreach($datas as &$data)
            {
                $data["title"] = Strings::text_cut($data["title"], $this->boardInfoResult["title_len"]);
                $data["elapsed_days"] = self::get_elapseDate($data['regdate']) ;
                
                $data["title"] = stripslashes($data["title"]);
                $data['title'] = Strings::html_encode($data['title'], false);
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
        //echo '<pre>';print_r($datas);
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
     * @param array &$data (첨부파일 경로 : attach_path, 첨부파일[,로 구분] : attach_files) (pass by reference)
     * @param array $data
     * @return array
     * @example $data['attachFiles'] => Array
     (
	     [0] => Array
					     (
						     ['exist'] => 1
						     ['file'] => f035dc8_1551421776.jpg
						     ['original_file'] => 고구마.jpg
					     )
	     
	     [1] => Array
					     (
						     ['exist'] => 0
						     ['file'] => Qfsdac8_1563521784.jpg
					         ['original_file'] => 오징어.jpg
					     )
     )
     */
    private function read_attachfile( &$data )
    {
        # 첨부파일
    	if( !empty($data["attach_files"]) ) {
    		$attach_files = explode(",", $data["attach_files"]) ;
    		$attach_orig_files = explode(",", $data["attach_orig_files"]) ;
    	}

        if( !empty($attach_files) )
        {
            $data["attachFiles"] = array();
            
            for($i=0; $i<count($attach_files); $i++)
            {
                //echo $data["attach_path"].$attach_files[$i]."<br>" ;
                if($attach_files[$i] && is_file($data["attach_path"].$attach_files[$i]))
                	array_push($data["attachFiles"], array("exist"=>1, "file"=>$attach_files[$i], "original_file"=>$attach_orig_files[$i]));
				else
					array_push($data["attachFiles"], array("exist"=>0, "file"=>$attach_files[$i], "original_file"=>$attach_orig_files[$i]));
            }
        }//echo '<pre>';print_r($data);
    }
    /**
     * 게시판&회원 데이타 읽어오기(inner join)
     *
     * @param array &$conditions ( string or array(.......) ) (pass by reference)
     * @return array|null
     */
    private function get_board_read( &$conditions ){
    	
        $queryOption = array(
            "columns"=> 'B.*, M.serial as usercode, M.grade as m_grade, M.lev as m_lev, M.userid as m_userid, M.username as m_username, M.usernick as m_usernick',
            "conditions" => $conditions,
            "join" => "LEFT"
        );
        $this->setTableName($this->boardInfoResult['table_name']) ;
        $data = $this->getDataAndMbr( $queryOption ) ;
        
        //if( !empty($data) ) $data = array_pop($data) ;
        return $data ;
    }
    /**
     * 조회수 카운트 증가
     *
     * @param int $serial (게시판 TB의 P.K)
     * @return void
     */
    private function set_visit_count(int $serial)
    {
        $put_data = array(
            'viewcnt=viewcnt+1'
        );
        $this->dataUpdate( $put_data,	array(
            "serial" => (int)$serial
        )) ;
        
    }
    /**
     * 비밀글 비밀번호 체크
     *
     * @param array &$data db 데이타 (pass by reference)
     * @param string $encrypt_pwd Encrypt된 비밀글 암호 문자열
     * @param boolean $return [default:false] 리턴형(boolean) 또는 분기형 or 경고창 인지
     * @return boolean|mixed
     */
    private function sec_pwd_authen(&$data, $encrypt_pwd, $return=false)
    {
        //----------------------------
        //게시물이 비밀글인 경우
        //----------------------------
        $secPwd = '';
        
        if((int)$data['sec'])
        {
            if( ! (int)$_SESSION['ADM'] )
            {
                if( isset($encrypt_pwd) && is_string($encrypt_pwd) && !empty($encrypt_pwd) ) {
                    $secPwd_data = $this->secPwd_parseDecode_string($encrypt_pwd);
                    
                    if( $secPwd_data['expire'] >= time() ) $secPwd = (string) $secPwd_data['sec_pwd'];
                }
                
                if( empty($secPwd) )
                {
                    /* $this->WebAppService->assign(array(
                     'error'=>'비밀글 비밀번호를 입력해주세요.',
                     'redirect'=> WebAppService::$baseURL."/pwd".WebAppService::$queryString
                     )); */
                    if($return) {
			return false ;
                    }
                    else{
			$this->pwd();
			exit;
                    }
                    /* header("Location: ".WebAppService::$baseURL."/pwd".WebAppService::$queryString); // 리스트 페이지 이동
                     exit; */
                }
                else{
                    if( $data['sec_pwd'] != $secPwd){
			
			if($return) {
			    return false ;
			}
			else{
			    $this->WebAppService->assign(array('error'=>'비밀글 비밀번호가 다릅니다.'));
			}
                    }
                }
            }
        }
        return true;
    }
    public function view()
    {
        if(REQUEST_METHOD=="GET")
        {
            //Exception
            if( ! (int)$this->routeResult["code"] ) $this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
            //$this->WebAppService->assign(array('error'=>'The data does not exist.'));

            // 권한정보
            $this->board_access_grant( "read", $_REQUEST["bid"] );
            
            //--------------------
            // 데이타 조회
            //--------------------
            $conditions = array( "serial" => $this->routeResult["code"], "bid" => $_REQUEST["bid"] ) ;

            $this->setTableName($this->boardInfoResult['table_name']);

            $data = $this->dataRead( array(
            		"columns"=> '*',
            		"conditions" => $conditions
            ));
            if( !empty($data) ) $data = $data[0] ;
            else $this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));

            //--------------------

            // 공지사항이면
            if( (int)$data['noti'] && $this->boardInfoResult['noti_grant_apply'] != 1 )
            {
            	$this->grant_content['response']['read']['code'] = 200;
            	$this->grant_content['response']['read']['msg'] = '';
            }
            else{

            	// 게시판 [ 회원용 /  비회원용 ]
            	/* if($this->boardInfoResult["mbr_type"]==1)
            	{
            		//로그인 체크
            		$this->hasMemberLogin();
            	} */

            }
            //echo '<pre>';print_r($this->grant_content);
            // 권한체크
            if($this->grant_content['response']['read']['code'] != 200) {
            	$this->WebAppService->assign(array('error'=> $this->grant_content['response']['read']['msg'] ));
            }

			// 환경설정-비밀글인 경우
            $this->secret_authen( $data['userid'] );

			//----------------------------
			//게시물이 비밀글인 경우
			//----------------------------
			//$secPwd = $this->sec_pwd_authen($data, $_GET['__bgp']) ;
            if($_SESSION['MBRID'] != $data['userid']){
                $this->sec_pwd_authen($data, $_GET['__bgp']) ;
            }
			/* if($data['sec'])
			header("Location: ".WebAppService::$baseURL."/pwdAuthen".WebAppService::$queryString); // 리스트 페이지 이동
			exit; */

			$this->set_visit_count($this->routeResult["code"]) ;

			// 첨부파일 읽기
			$this->read_attachfile($data);

			/* 회원레벨 icon */$data["lev_ico"] = self::$mbr_conf['lev_css'][$data["lev"]] ;
            /* 글내용 *///$data["memo"] = nl2br($data["memo"]);
            //$data["memo"]= '<pre>'.stripslashes($data["memo"]).'</pre>' ;
            //$data["memo"]= str_replace("  ", "&nbsp;&nbsp;", $data["memo"]) ;
            //$data["memo"] = $this->highlightjs_decode($data["memo"]) ;
            /* $data["memo"]= stripslashes($data["memo"]) ;
             $data["memo"] = self::{$this->syntaxHighlight_name."_decode"}($data["memo"]) ; */
            $data["title"] = stripslashes($data["title"]);
            $data['title'] = Strings::html_encode($data['title'], false);
            
            $data["memo"] = stripslashes($data["memo"]);
            //$this->decode_memo($data["memo"]);
            //echo '<pre>';print_r($this->grant_content);
            //$data["memo"]= "<pre>".htmlentities(highlight_string($data["memo"]))."</pre>" ;
            
            
            $board_grants = $this->grant_content ;
            
            // 댓글 권한정보
            $this->comments_access_grant( "write", $_REQUEST["bid"] );
            $comments_grants = $this->grant_content ;
            //-----------------------------------------
            // 댓글 조회
            //-----------------------------------------
            try
            {
                $this->listComments(
					(int) $this->boardInfoResult["comments"],
                    (string) $_REQUEST["bid"],
                    (int) $this->routeResult["code"],
                    "COMMENTS"
				);
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
            //-----------------------------------------
            //$this->WebAppService->Display->setLayout($this->page_layout);

            if( !empty($this->syntaxHighlight_name) ){
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/view." .$this->syntaxHighlight_name. ".html" ;
            }else{
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/view.html" ;
            }
                    
			$this->WebAppService->Display->define( "CONTENT", Display::getTemplate($template));
			//echo '<pre>';print_r($data);exit;
			$this->WebAppService->Output( Display::getTemplate($template), $this->page_layout);
			//-----------------------------------------
			/* $this->setTableName('board');
			 self::$pageVariable = 'page';
			 $this->lst(true); // 리스트 출력시
			 
			 $template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/list.html" ;
			 $this->WebAppService->Display->define('BOARD_LIST', Display::getTemplate($template) ); */
			//-----------------------------------------
			//echo '<pre>';print_r($data) ;
			$this->WebAppService->assign(array(
							'Doc' => array(
							    'baseURL' => WebAppService::$baseURL,
							    //'queryString' => !empty($secPwd) ? Func::QueryString_filter(array('__bgp'=>$secPwd)) : Func::QueryString_filter(),
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
							"GRANT" => $board_grants, //  글쓰기 or 편집 권한정보
							'Board_conf' => $this->boardInfoResult,
							//'SHOP_COMMON' => $this->global_shopAction(), // ◆공용 데이타◆ (쇼핑카트 갯수, 위시리스트 갯수.....)
							"COMMENTS_GRANT" => $comments_grants, //  댓글(글쓰기 or 편집..) 권한정보
							'Comments_conf' => $this->commentsInfoResult,
							'DATA' => $data
					)) ;

			$this->WebAppService->printAll();
        }
    }
    
    /*
     * $this->boardInfoResult["comments"],
     $_REQUEST["bid"],
     $this->routeResult["code"],
     'COMMENTS'
     */
    /**
     * 댓글(Comments) 리스트 출력 & Form
     *
     * @param int $hasView 댓글 노출유무(1 or 0)
     * @param string $Bid 게시판 아이디(id)
     * @param int $Bserial 게시판 TB의 P.K
     * @param string $template_define 템플릿 파일의 아이디를 정의
     */
    private function listComments( int $hasView, string $Bid, int $Bserial, string $template_define)
    {
        if( $hasView )
        {
            $this->commentsInfoResult = $this->getBrdComments_info("comments_info", array("bid" => $Bid)) ;
            //$this->WebAppService->assign(array("Comments_conf" =>$this->commentsInfoResult)) ;
            
            /* $queryOption = array_merge(self::$queryOption_comments, array(
             "conditions" =>  array(
             "B.bid" => $_REQUEST["bid"],
             "B.bserial" => $this->routeResult["code"]
             )
             )) ;
             $comments_datas = $this->get_comments_lst($ResultQuery, $_REQUEST["bid"]);
             */
            
            if( !empty($this->commentsInfoResult) )
            {
                // 검색조건 처리
                //----------------------------------------
                //array(query_condition, queryString)
                $ResultQuery = array();
                $ResultQuery = self::condition_board( array(
											"bid" => $Bid,
											//"search_field" => $_REQUEST['search_field'],
											//"search_keyword" => $_REQUEST['search_keyword']
                				)) ;
                $ResultQuery["query_condition"]['B.bserial'] = $Bserial ;
                
                $comments_datas = $this->get_comments_lst( $ResultQuery, $Bid );
                
                //댓글리스트 출력처리
                $this->WebAppService->assign(array(
											"COMMENTS_LIST"=> &$comments_datas['LIST'],
											'COMMENTS_TOTAL_CNT' => $comments_datas['TOTAL_CNT'],
											'COMMENTS_PAGING' => &$comments_datas['PAGING'],
											'COMMENTS_VIEW_NUM' => $comments_datas['VIEW_NUM'],
											'Comments_conf' => &$this->commentsInfoResult
                				)) ;
                
                if( !empty($this->commentsInfoResult['editor']) ){
					$template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base." .$this->syntaxHighlight_name. ".html" ;
                }else{
					//$template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base.html" ;
					$template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base." .$this->syntaxHighlight_name. ".html" ;
                }
				$this->WebAppService->Display->define( $template_define, Display::getTemplate($template) );
				
            }
			else{
				$this->WebAppService->Display->define( $template_define, '' );
			}
            
		}
        else{
            $this->WebAppService->Display->define( $template_define, '' );
            //$this->WebAppService->Display->define('COMMENTS',Display::getTemplate('blank.htm'));
        }
        
    }
    /**
     * 컨텐츠 내용
     * 
     * @param string &$memo (pass by reference)
     * @return void
     */
    private function encode_memo(&$memo)
    {
    	if( empty($memo)) return false ;
    
    	if($_SESSION['ADM'] != 1)
    	{
    		//에디터(html) 사용할 경우
	        if( $this->boardInfoResult["editor"] == 1)
	        {
				$memo = self::{$this->syntaxHighlight_name."_encode"}($memo) ;
				//$memo = Strings::set_xss_detect($memo) ;
	        	
	        }
	        // text 전용
	        else{
	        	$memo = Strings::html_encode($memo) ;
	        }
    	}
        
        $memo= addslashes($memo) ;
        
        //return $memo ;
    }
    /**
     * 문자열을 디코딩
     * @param string $memo 문자열 (pass by reference)
     * @param string $kind ( view, edit )
     */
    private function decode_memo(&$memo, $kind=null)
    {
    	if( empty($memo)) return false ;
    	
        if( $this->boardInfoResult["editor"] == 1){ //에디터 사용할 경우
        	//$memo = self::{$this->syntaxHighlight_name."_decode"}($memo, $kind) ;
        	if($kind == 'edit') {
        		//CK에디터 편집시에서는  &lt;?이 <?로 되어 주석처리됨. 한번더 가공하여 보이도록 처리 
        		$memo = str_replace( "&lt;?", '&amplt;?' , $memo );
        	}
        }
        else{
        	//$memo = Strings::html_decode($memo) ;
            /* 출력용(내용) */$memo = str_replace("  "," &nbsp;", $memo);
            /* 출력용(내용) */$memo = nl2br($memo);
        }
        
        $memo = stripslashes($memo) ;
        //return $memo ;
    }
    /**
     * [체크] : 모든 게시글을 비밀글 설정한 경우
     * (본인이 작성한글만 조회가능)
     *
     * @param string $userid 회원 아이디
     * @return void
     */
    private function secret_authen( $userid )
    {
        // 환경설정-전체 비밀글인 경우
        if($this->boardInfoResult['sec'] == 1){
            // 회원용 / 비회원용
            if( (int) $this->boardInfoResult["mbr_type"] )
            {
                $this->hasMemberCheck() ;
                
                // 본인이 작성한 글인지 체크
                if(!$_SESSION['MBRID'] || ($_SESSION['MBRID'] != $userid && !$_SESSION['ADM'])){
					$this->WebAppService->assign(array('error'=>'본인이 작성한 글이 아니면 볼 수 없습니다.'));
                }
            }
            
            //echo '<pre>';print_r($this->boardInfoResult);exit;
        }
    }
    /**
     * 비밀글을 볼경우 비밀번호 입력페이지
     */
    private function pwd()
    {
        $this->WebAppService->assign(array(
            'Doc' => array(
                'baseURL' => WebAppService::$baseURL,
                'queryString' => Func::QueryString_filter(),
                'MNU' => self::$menu_datas,
                'Action' => "pwdAuthen",
                //'Action' => "view",
                "CODE" => $this->routeResult["code"],
                /* 'formType' => "편집" */
                'formType' => "edit"
            ),
            'Board_conf' => $this->boardInfoResult
        )) ;
        $template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/pwd.html" ;
        $this->WebAppService->Output( Display::getTemplate($template), $this->page_layout);
        $this->WebAppService->printAll();
        exit;
    }
    /**
     * 비밀글 암호 문자열 encrypt 화
     *
     * @param string $Str 비밀번호
     * @return string Encrypt된 비밀글 암호
     * @uses Expire : 1시간 이용가능
     */
    private function secPwd_parseEncode_string( $Str )
    {
        $curtime = time()+3600; // 1시간만 유지
        $encrypt_sec_pwd = \Strings::encrypt_sha256($Str).$curtime;
        
        return $encrypt_sec_pwd ;
    }
    /**
     * 비밀글 암호 문자열 Parsing
     *
     * @param string $Str Encrypt된 비밀글 암호
     * @return array ('expire'=> (timestamp) 이용 만료시간, 'sec_pwd'=> 비밀번호[encrypt] )
     */
    private function secPwd_parseDecode_string( $Str )
    {
        $expiretime = substr($Str, -10);
        $sec_pwd = substr($Str, 0, -10);
        
        return array(
            'expire' => (int) $expiretime, // 이용 만료시간
            'sec_pwd' => (string) $sec_pwd // 비밀글 비밀번호(encrypt string)
        ) ;
    }
    /**
     * [개발보류] 비회원이 비밀글을 볼경우 비밀번호 인증
     * @param string post로 넘어온 문자열변수 : $_POST['secPwd']
     * @return void  .../view 페이지 redirect 이동
     *
     * @uses 결과값은 Query String[GET]으로 __bgp변수에 담는다.
     */
    public function pwdAuthen()
    {
        /* //echo '<pre>';print_r($_POST);
         $pwd = \Strings::encrypt_sha256($_POST['secPwd']);
         //setcookie("__bgp", $pwd, time()+3600, "/", HOST);//, 1); */
        if(REQUEST_METHOD=="POST")
        {
            //Strings::decrypt_Pcrypt($data['sec_pwd'])
            if( isset($_POST['secPwd']) && is_string($_POST['secPwd']) )
            {
                $secPwd = $this->secPwd_parseEncode_string($_POST['secPwd']) ;
                
                if(!empty($secPwd)) WebAppService::$queryString = Func::QueryString_filter( array('__bgp'=>$secPwd) );
            }
        }
        //echo '서비스준비중 !!';exit;
        header("Location: ".WebAppService::$baseURL."/view/".$this->routeResult["code"].WebAppService::$queryString); // 상세보기 페이지 이동
        exit;
    }
    //============================================
    private function test()
    {
        //$this->WebAppService->Display->setLayout('sub2');
        //$this->WebAppService->Display->define('CONTENT', Display::getTemplate('html/board/skin/boardComm/list.html'),'sub2');
        
        //$this->WebAppService->Display->html_body = "감사합니다.";
        // 1
        //$this->setTableName('board');
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
        //$this->setTableName('board');
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
            if( empty($_REQUEST['bid']) || ctype_space($_REQUEST['bid']) ) $this->WebAppService->assign(array('error'=>"해당 정보를 찾을 수 없습니다."));
            //$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
            //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
            
            if($this->boardInfoResult["mbr_type"]==1) $this->hasMemberLogin() ;
            
            // 권한정보
            $this->board_access_grant( "write", $_REQUEST["bid"] );
            
            // 권한체크
            if($this->grant_content['response']['write']['code'] != 200) {
            	$this->WebAppService->assign(array('error'=> $this->grant_content['response']['write']['msg'] ));
            }
            
            $this->setTableName($this->boardInfoResult['table_name']);
            
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
						            	'GRANT' => $this->grant_content, //  사용 권한정보
						                'Board_conf' => $this->boardInfoResult,
						                'DATA' => $data//,
						                //'SHOP_COMMON' => $this->global_shopAction() // ◆공용 데이타◆ (쇼핑카트 갯수, 위시리스트 갯수.....)
            )) ;
            
            if( !empty($this->syntaxHighlight_name) ){
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/edit." .$this->syntaxHighlight_name. ".html" ;
            }else{
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/edit.html" ;
            }

			$this->WebAppService->Output( Display::getTemplate($template), $this->page_layout);

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
                    'label' => '메뉴코드를',
                    //'label' => 'Menu code',
                    'rules' => 'required|numeric'
                ),
                'bid' => array(
                    'label' => '보드 아이디를',
                    //'label' => 'Board ID',
                    'rules' => 'required|alpha'
                ),
                'frm_mbr_id' => array(
                    'label' => '회원 아이디를',
                    //'label' => 'Member ID',
                    'rules' => 'required|email'
                ),
                'frm_writer' => array(
                    'label' => '작성자명을',
                    //'label' => 'Author Name',
                    'rules' => 'required|whitespace'
                ),
                'frm_title' => array(
                    'label' => '타이틀을',
                    //'label' => 'Title name',
                    'rules' => 'required|whitespace'
                ),
                'frm_memo' => array(
                    'label' => '글 내용을',
                    //'label' => 'Content',
                    'rules' => 'required|whitespace'
                ),
                'frm_mvOrig' => array(
                    'label' => '이동할 Serial',
                    //'label' => 'The serial to move',
                    'rules' => 'required|numeric'
                ),
                'frm_mvTarget' => array(
                    'label' => '이동될 위치 Serial',
                    //'label' => 'Location to be moved Serial',
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
      * return void
      */
     private function attach_delete($files)
     {
         if(is_file($files)) $this->WebAppService->File->delete($files);
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
         $uploadDir .= Strings::shuffle_alphaNum(8)."/" ;
         return $uploadDir ;
     }
     /**
      * 첨부파일 업로드
      *
      * @param string $bid 게시판 아이디
      * @param string $uploadDir ( 업로드파일 저장경로 )
      * @param $_FILES &$Files (업로드 변수: $_FILES[??]) (pass by reference)
      * @param array|string $upload_datas (삭제용: [attach_path(저장경로), (array) attach_files(저장된 파일들)])
      * @return boolean|array
      */
     private function attach_upload($bid, $uploadDir, &$Files, $upload_datas=NULL)
     {
         //echo '<pre>';print_r(func_get_args());//exit;
         if( empty($Files) ) return false;
         
         $result = array();
         if(empty($uploadDir) || ctype_space($uploadDir)) {
             if($this->boardInfoResult["upload_path"]){
                 $uploadDir= self::$attach_basedir . (string) $bid. '/' . $this->boardInfoResult["upload_path"]."/" ;
             }else{
                 $uploadDir= self::$attach_basedir . (string) $bid. '/' ;
             }
             
             $result["dir"] = self::folder_create($uploadDir) ;
         }
         else{
             $result["dir"] = $uploadDir;
         }
         
         if( !is_dir($result["dir"]) ) $this->WebAppService->File->createDirs($result["dir"]);
             
             
             /* echo '<pre>';print_r($Files);
              echo '<pre>';print_r($upload_datas);
              exit; */
		$result["file"] = array();
		$result["original_file"] = array();
		
		if( !empty($upload_datas["attach_orig_files"]) ){
			$result["file"] = $upload_datas["attach_files"] ;
			$result["original_file"] = $upload_datas["attach_orig_files"] ;
		}
		foreach($Files["name"] as $k => $v)
		{
		    if($Files['error'][$k] == 0)
		    {
		        if( $this->WebAppService->Func->fileType_Check($Files['name'][$k]) )
		        {
		        	preg_match('/\.([^\.]*$)/', $Files['name'][$k], $extension);  // ex) array( 0 => ".gif", 1 => "gif" )
		        	/*확장자*/$file_ext = strtolower($extension[1]);
		        	/*파일명*/$file_name = substr($Files['name'][$k], 0, ((strlen($Files['name'][$k]) - strlen($file_ext)))-1);
		        	
		        	$file_name = str_replace(array(' ',','), '', $file_name) ;
		        	$file_name = \Strings::remove_fillter( $file_name ) ;
		        	$file_name = Strings::set_xss_detect($file_name) ;
		        	$upload_originalFileName = $file_name .'.'. $file_ext ;
		        	
		        	$file_rename = Func::fileRename($Files['name'][$k], Strings::shuffle_alphaNum(7)) ;
					
					if( move_uploaded_file( $Files['tmp_name'][$k], $result["dir"] . $file_rename)  )
					{
					    //업로드 성공
						//$upload_datas["attach_files"]
					    if( !empty($upload_datas["attach_orig_files"]) )
					    {
					        // 기존 저장되어있는 파일 삭제(주의 : 현재 업로드한 파일명 또는 폴더포함 파일명과 동일할 경우 삭제안함)
					    	if($result["dir"] . $upload_originalFileName != $upload_datas["attach_path"] . $upload_datas["attach_orig_files"][$k]){
								$this->attach_delete( $upload_datas["attach_path"] . $upload_datas["attach_orig_files"][$k] );
					        }
					        // 교집합 처리 : 기존 저장된 파일 데이타 + 업로드한 파일
					        //$result["original_file"] = array_replace($result["original_file"], $upload_datas["attach_orig_files"], array($k=>$upload_originalFileName)) ;
					        //$result["file"][] = $file_rename ;
					        foreach($result["original_file"] as $okey => $original_file){
					        	if($original_file == $upload_originalFileName){
					        		$result["file"][$okey] = $file_rename ;
					        		break ;
					        	}else{
					        		$result["file"][$k] = $file_rename ;
					        		$result["original_file"][$k] = $upload_originalFileName ;
					        	}
					        	
					        }
					        // 저장된 파일 리스트(배열)에 삭제될 파일이 존재하면 모두 제거
					        //$this->WebAppService->Func->array_searchValue_remove($result["original_file"], $upload_datas["attach_orig_files"][$k]);
					        foreach($result["original_file"] as $okey => $original_file){
					        	if($original_file == $upload_datas["attach_orig_files"][$k]){
					        		unset($result["original_file"][$okey], $result["file"][$okey]);
					        	}
					        }
					        
					    }else{
					        // 신규등록시
					    	$result["file"][$k] = $file_rename ;
					    	
					    	$result["original_file"][$k] = $upload_originalFileName ;
					    	// 원본파일명이 30자 넘으면 파일명명으로 변경
					    	/* if(mb_strlen($upload_originalFileName) > 30){
					    		$result["original_file"][$k] = $file_rename ;
					    	}else{
					    		$result["original_file"][$k] = $upload_originalFileName ;
					    	} */
					    }
					    
					}
		        }
		    }
		}
		//echo '111<pre>';print_r($result);exit;
		return $result ;
     }
     /**
      *
      * @param array|string &$var (pass by reference)
      * @param array $without 필터링 안할 변수명
      */
     private function vars_filter(&$var, $without=array())
     {
		if( is_array($var) )
		{
			foreach($var as $key => &$data)
			{
				if(is_array($data)){
					vars_filter($data, $without) ;
	    			//else $var = trim(stripslashes($d));
				}
				else{
					
		        	if( !empty($without) )
		        	{
			 			if( is_array($without) )
			 			{
			     			if( array_search($key, $without) === FALSE )
			     			{
						         $data = \Strings::remove_fillter( $data ) ;
						         $data = str_replace("\"", "''", $data) ;
						         
						         $data= addslashes($data) ;
						     }
			 			}
						else{
						     if( $key != $without )
						     {
						         $data = \Strings::remove_fillter( $data ) ;
						         $data = str_replace("\"", "''", $data) ;
						         $data= addslashes($data) ;
						     }
						}
					}
		        
				}
			}
			
         }
         else{
			//$var = trim( addslashes($var) );
			$var = \Strings::remove_fillter( $var) ;
			$var = addslashes($var) ;
         }
     }
     /**
      * 게시판 - DB 저장
      */
	public function write()
	{
         if(REQUEST_METHOD=="POST")
         {
             if( empty($_REQUEST['bid']) || ctype_space($_REQUEST['bid']) ) $this->WebAppService->assign(array('error'=>"해당 정보를 찾을 수 없습니다."));
             
             // 회원전용인 경우
             if($this->boardInfoResult["mbr_type"]==1) {
             	if( ! $this->hasMemberLogin(true) ) $this->WebAppService->assign(array('error'=>"로그인 후 이용해주세요."));
             }
             
             // 권한정보 가져오기
             $this->board_access_grant( "write", $_REQUEST["bid"] );
             
             // 권한체크
             if($this->grant_content['response']['write']['code'] != 200) {
             	$this->WebAppService->assign(array('error'=> $this->grant_content['response']['write']['msg'] ));
             }
             
             // 입력값 체크
             $error = $this->getValidate( array(
             		"frm_title",
             		"frm_memo"
             )) ;
             if( !empty($error) ) $this->WebAppService->assign(array('error'=>$error));
             
             /* TINYTEXT 256 bytes
              TEXT 65,535 bytes ~64kb
              MEDIUMTEXT 16,777,215 bytes ~16MB
              LONGTEXT 4,294,967,295 bytes ~4GB */
             if( $_SESSION['ADM'] != 1 ){
             	if(mb_strwidth($_POST["frm_memo"], 'utf-8') > 65535 ) $this->WebAppService->assign(array('error'=>'내용 최대 글자수를 초과했습니다.'));
             }
             
             # 저장할 추가데이타
             $put_add_data = $this->get_write_variable( $this->boardInfoResult );
             
			/* $_POST["frm_memo"] = self::{$this->syntaxHighlight_name."_encode"}($_POST["frm_memo"]) ;
			 $_POST["frm_memo"] = addslashes($_POST["frm_memo"]) ; */
			
			$this->vars_filter($_POST, array('frm_memo')) ;
			$this->encode_memo($_POST["frm_memo"]);
			//$_POST["frm"]["title"] = rand(1,1000).$_POST["frm"]["title"] ;
			
			$put_data = array(
			    "oid" => (int) OID,
			    "mcode" => (int) $_POST["frm_mcode"],
			    "bid" => (string) $_REQUEST["bid"],
			    "cate" => (int) $_POST["frm_cate"],
			    
			    /* "mbr_id" => (string) $mbr_id,
			    "pwd" => (string) $_REQUEST["frm_userpw"],
			    "writer" => (string) $_REQUEST["frm_writer"], */
			    
			    "title" => (string) $_POST["frm_title"],
			    "usehtml" => (int) $_POST["frm_usehtml"],
			    "memo" => (string) $_POST["frm_memo"],
			    "ip" => $_SERVER['REMOTE_ADDR'],
			    "firstdate" => time(),
			    "regdate" => time()
			);
			$put_data = array_merge($put_data, $put_add_data) ;
			
			// 업로드 파일 저장
			$res = $this->attach_upload( $_REQUEST["bid"], $upload_dir='', $_FILES["frm_attachFile"] ) ;
			if( !empty($res["file"]) )
			{
			    $res_files = implode (",", $res["file"]) ;
			    $res_original_files = implode (",", $res["original_file"]) ;
			    
			    $put_data = array_merge($put_data, array(
			        "attach_path" => $res["dir"], // 또는 $data[0]["attach_path"]
			        "attach_files" => $res_files, // 변경된 파일명 리스트
		    		"attach_orig_files" => $res_original_files // 원본 파일명(파일명이 길면 변경된 파일명이 저장됨[attach_upload 함수참조])
			    ));
			}

			try
			{
				$this->setTableName($this->boardInfoResult['table_name']);
			  	if( $this->boardInfoResult["indent"] ) $insert_id = $this->dataAdd( $put_data, $this->routeResult["code"]	) ;
				else $insert_id = $this->dataAdd( $put_data ) ;
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

			if($insert_id)
			{
			    header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
			    exit;
			}
			else{
			    //Exception
			    $this->WebAppService->assign(array('error'=>'저장실패~다시입력해주세요.'));
			    //$this->WebAppService->assign(array('error'=>'Failed to save. Please re-enter.'));
			}

         }

	}
     /**
      * 게시판 편집페이지
      */
	public function edit()
	{
		//$this->SessMember = &WebApp::singleton("SessMember:service");
        //WebApp::import("WebAppService", "system");
		if(REQUEST_METHOD=="GET")
		{
			if( empty($_REQUEST['bid']) || ctype_space($_REQUEST['bid']) ||
				!(int)$this->routeResult["code"] ) $this->WebAppService->assign(array('error'=>"해당 정보를 찾을 수 없습니다."));
				//$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
				//$this->WebAppService->assign(array('error'=>'The data does not exist.'));

			if($this->boardInfoResult["mbr_type"]==1) $this->hasMemberLogin() ;

			// 권한정보
			$this->board_access_grant( "update", $_REQUEST["bid"] );

			// 권한체크
			if($this->grant_content['response']['update']['code'] != 200) {
				$this->WebAppService->assign(array('error'=> $this->grant_content['response']['update']['msg'] ));
			}

			// 데이타 존재하는지 체크
			/* $exist_data = $this->count( "serial", array("serial" => $this->routeResult["code"]) ) ;
			if( $exist_data < 1)
			{
				$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
				//$this->WebAppService->assign(array('error'=>'The data does not exist.'));
			} */

			$this->setTableName($this->boardInfoResult['table_name']);

			// 회원용 / 비회원용
			if($this->boardInfoResult["mbr_type"]==1)
			{
				$conditions = array("B.userid" => $_SESSION['MBRID'], "B.serial" => $this->routeResult["code"], "B.bid" => $_REQUEST["bid"]) ;
				$data = $this->get_board_read($conditions);

				if( $_SESSION['ADM'] != 1)
				{
				    if( empty($data[0]['userid']) || empty($data[0]['m_userid']) || $data[0]['userid'] != $data[0]['m_userid']){
				        //Exception
				        //WebApp::moveBack("작성자가 본인이 아니면 편집할 수 없습니다.");
				        $this->WebAppService->assign(array('error'=>'작성자가 본인이 아니면 편집할 수 없습니다.'));
				        //$this->WebAppService->assign(array('error'=>'If you are not the author, you can not edit it.'));
				    }
				}
				
				/* 회원레벨 icon */$data[0]["lev_ico"] = self::$mbr_conf['lev_css'][$data[0]["lev"]] ;
			}
 			else{
				//$conditions = array("pwd" => $_POST['pwd'], "serial" => $this->routeResult["code"]) ;
				$conditions = array("serial" => $this->routeResult["code"]) ;
			         
				$data = $this->dataRead( array(
					"columns"=> '*',
					"conditions" => $conditions
				));
			}
			if( empty($data) )
			{
				$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
				//$this->WebAppService->assign(array('error'=>'The data does not exist.'));
			}
			
			//----------------------------
			//게시물이 비밀글인 경우
			//----------------------------
			//if($_SESSION['MBRID'] != $data[0]['userid']){
            $this->sec_pwd_authen($data[0], $_GET['__bgp']) ;
			//}
			
			// 첨부파일 읽기
			$this->read_attachfile($data[0]);
			
			
			$data[0]["title"] = stripslashes($data[0]["title"]);
			if( $this->boardInfoResult["editor"] == 1){ //에디터 사용할 경우
			}
			else{
				//$data[0]["memo"] = str_replace("  ", " &nbsp;", $data[0]["memo"]) ;
				//$data[0]["memo"] = str_replace("\n", "<br />", $data[0]["memo"]) ;
			}
			$this->decode_memo($data[0]["memo"], 'edit');
			     
			$this->WebAppService->assign(array(
					'Doc' => array(
							'baseURL' => WebAppService::$baseURL,
							'queryString' => Func::QueryString_filter(),
							'MNU' => self::$menu_datas,
							'Action' => "update",
							"CODE" => $this->routeResult["code"],
							/* 'formType' => "편집" */
							'formType' => "edit"
					),
					'GRANT' => $this->grant_content, //  사용 권한정보
					'Board_conf' => $this->boardInfoResult,
					'DATA' => $data[0]//,
				)) ;
			     
			if( !empty($this->syntaxHighlight_name) ){
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/edit." .$this->syntaxHighlight_name. ".html" ;
			}else{
				$template = "html/board/skin/".$this->boardInfoResult["skin_grp"]."/".$this->boardInfoResult["skin_name"]."/edit.html" ;
			}

			$this->WebAppService->Output( Display::getTemplate($template), $this->page_layout);
			$this->WebAppService->printAll();
		}
		else{
			// exception
		}
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
      * 데이타 가공
      * 
      * @param array &$info ( pass by reference ) 환경설정 정보
      * @return array
      */
	private function get_write_variable( &$info )
	{
     	//if( (string)$_POST["frm_title"] ) $_POST["frm_title"] = Strings::set_xss_detect( (string)$_POST["frm_title"] ) ;
     	//if( (string)$_POST["frm_memo"] ) $_POST["frm_memo"] = Strings::set_xss_detect( (string)$_POST["frm_memo"] ) ;
     	
         # 저장할 추가데이타
         $put_add_data = array();
         //echo '<pre>';print_r($info) ;exit;
         if($info["mbr_type"]==1)
         {
             //if( $this->hasMemberCheck() ) // 비동기식일 경우에 작동
             $put_add_data = array( "userid" => $_SESSION['MBRID'] );
         }
         else{

         	if( empty($_POST["frm_writer"]) || ctype_space($_POST["frm_writer"]) ){
				$this->WebAppService->assign(array('error'=>'작성자명을 입력해주세요.'));
				//$this->WebAppService->assign(array('error'=>'Please enter author name.'));
         	}
         	if( empty($_POST["frm_userpw"]) || ctype_space($_POST["frm_userpw"]) ){
				$this->WebAppService->assign(array('error'=>'비밀번호를 입력해주세요.'));
				//$this->WebAppService->assign(array('error'=>'Please enter a password.'));
         	}
         	
			$put_add_data = array(
						"writer" => (string) $_POST["frm_writer"],
						"pwd" => (string) $_POST["frm_userpw"]
				);
         }
         
         if($this->hasGrantBoard() == TRUE){
             $put_add_data = array_merge( $put_add_data, array("noti"=> (int) $_POST["frm_noti"]) ) ;
         }
         // 비밀글인경우
         if($info["sec_pwd"]==1)
         {
         	if( $_POST["frm_sec"]==1 && !empty($_POST["frm_sec_pwd"]) && ! ctype_space($_POST["frm_sec_pwd"]) ){
				$put_add_data = array_merge( $put_add_data, array("sec"=>1, "sec_pwd"=> (string) $_POST["frm_sec_pwd"]) ) ;
         	}
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
     	/* 
     	$text = preg_replace("/\r\n|\r|\n/",'',$text); // enter replace
     	$text = preg_replace("/<(?:\s|)br(?:\s|)(?:\/|)(?:\s|)>/", "\n", $text) ;
     	 */
     	if(strpos($text, '<code') !== false)
		{
	        preg_match_all('/(.*?)(<code[^>]*>)((?:[^\<\>]++|<code[^>]*>(?3)<\/code>)++)(<\/code>)(.*?)/si', $text, $matchs, PREG_SET_ORDER);

	        $new_text = '';
	        
	        foreach($matchs as $match)
	        {
		        /*  
		        1 : <code> 이전의 앞부분
		        2 : <code ...>
		        3 : code 블럭사이의 내용
		        4 : </code>
		        5 : </code> 다음의 나오는 내용부분
		         */
	        	//$match[1] = Strings::set_xss_detect($match[1]) ;
	        	//------------------------------------
	        	// 매칭조건 : <code> or <code class="language=영문자">
	        	//------------------------------------
	        	$tmps = Strings::get_attributes($match[2], "class") ;
	        	
	        	$has_class = false ;
	        	
	        	if( !empty($tmps[0]) )
	        	{
	        		foreach($tmps[0] as $tmp){
	        			preg_match('/class="language-[a-z]+"/i', $tmp, $class) ;
	        			if( !empty($class)){
	        				$match[2] = "<code ".$class[0].">" ;
	        				$has_class = true ;
	        				break ;
	        			}
	        		}
	        	}
	        	if( $has_class === false ){
	        		$match[2] = "<code>" ;
	        	}
	        	//------------------------------------
	        	//$match[3] = Strings::set_xss_detect($match[3]) ;
	        	//$match[5] = Strings::set_xss_detect($match[5]) ;
	        	
	        	$new_text .= Strings::set_xss_detect($match[1].$match[2].$match[3]."</code>".$match[5]) ;
		        //echo '<pre>';print_r($new_text) ;exit;
	        }
	        
	        $text = $new_text ;
		}
		else{
			$text = Strings::set_xss_detect($text) ;
		}
		
		return $text ;
	}
     /**
      * [Decode] SyntaxHighlighter
      *
      * 소스코드 삽입한 경우 ( <code>....</code> )
      * @param string $text
      * @param string $kind ( view, edit... )
      * @return string
      */
	private static function SyntaxHighlighter_decode($text, $kind = null)
	{
     	//echo $text;exit;
     	$text = Strings::html_decode($text) ;
     	if(strpos($text, '<code') !== false)
     	{
	     	preg_match_all('/(.*?)(<code[^>]*>)((?:[^\<\>]++|<code[^>]*>(?3)<\/code>)++)(<\/code>)(.*?)/si', $text, $matchs, PREG_SET_ORDER);
	     	//preg_match_all('/(.*?)(<code>)((?:[^\<\>]++|<code>(?3)<\/code>)++)(<\/code>)(.*)/si', $text, $match, PREG_SET_ORDER);
	     	//$match = $match[0] ;
	     	
	     	$new_text = '';
	     	foreach($matchs as $match)
	     	{
		     	/*  
		        1 : <code> 이전의 앞부분
		        2 : <code ...>
		        3 : code 블럭사이의 내용
		        4 : </code>
		        5 : </code> 다음의 나오는 내용부분
		         */
		     	//echo '222<pre>';print_r($match);exit;
		     	if($kind == 'edit') {
		     		
		     		//$match[1] = Strings::html_encode($match[1]) ;
		     		//$match[2] = Strings::html_encode($match[2]) ;
		        	$match[3] = Strings::html_encode($match[3]) ;
		        	//$match[4] = Strings::html_encode($match[4]) ;
		        	//$match[5] = Strings::html_encode($match[5]) ;
		        	
		        	/* $match[1] = str_replace("\n", "<br/>", $match[1]) ;
		        	$match[2] = str_replace("\n", "<br/>", $match[2]) ;
		        	$match[4] = str_replace("\n", "<br/>", $match[4]) ;
		        	$match[5] = str_replace("\n", "<br/>", $match[5]) ; */
		        	
		       //echo '<pre>';print_r($match);exit;
		     	}
		     	else{
		     		
		     		$match[1] = Strings::html_decode($match[1]) ;
		     		$match[2] = Strings::html_decode($match[2]) ;
		     		$match[3] = Strings::html_filter($match[3]) ;
		     		$match[4] = Strings::html_decode($match[4]) ;
		     		$match[5] = Strings::html_decode($match[5]) ;
		     	}
		     	//$match[3] = str_replace("  ", " &nbsp;", $match[3]);
		     	
		     	$new_text .= $match[1].$match[2].$match[3].$match[4].$match[5] ;
		        // 편집모드(위지윅에디터) 일경우 newline을 <br>태그로 치환
		        /* if($kind == 'edit') 
		        {
		        	$text = str_replace("\n", "<br />", $text) ;
		        	$text = str_replace("  ", " &nbsp;", $text);
		        } */
	     	}
	     	
	     	$text = $new_text ;
	        //echo '<pre>';print_r($match);exit;
	       
     	}
     	else{
     		$text = Strings::html_decode($text) ;
     		
     		//$text = str_replace("\n"," <br/>", $text);
     		//if($kind == 'edit') $text = str_replace("\n", '<br />', $text) ;
     	}
         return $text;
	}
     /**
      * @deprecated
      * @param unknown $text
      * @return string
      */
	private static function SyntaxHighlighter_decode11($text)
	{
         $res = Strings::get_findStr_tag_between($text, "code");
         echo '<pre>';print_r($res) ;
         exit;
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
             $text = Strings::html_decode($text) ;
             
             $strings = trim( stripslashes($text) );
             
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
             if( empty($_REQUEST['bid']) || ctype_space($_REQUEST['bid']) ||
             		!(int)$this->routeResult["code"] ) {
             			$this->WebAppService->assign(array('error'=>"해당 정보를 찾을 수 없습니다."));
             }

			if($this->boardInfoResult["mbr_type"]==1) {
				if( ! $this->hasMemberLogin(true) ) $this->WebAppService->assign(array('error'=>"로그인 후 이용해주세요."));
			}

			// 권한정보 가져오기
			$this->board_access_grant( "update", $_REQUEST["bid"] );

			// 권한체크
			if($this->grant_content['response']['update']['code'] != 200) {
				$this->WebAppService->assign(array('error'=> $this->grant_content['response']['update']['msg'] ));
			}

			// POST 값 체크
			$error = $this->getValidate( array(
					/* "frm_mcode", */
					"frm_title",
					"frm_memo"
			)) ;
			if( !empty($error) ){
				$this->WebAppService->assign(array('error'=>$error));
			}
			
			# 저장할 추가데이타
			$put_add_data = $this->get_write_variable( $this->boardInfoResult );

			$where_arr["serial"] = (int)$this->routeResult["code"] ;
			$where_arr["bid"] = $_REQUEST['bid'];

			// 회원제인 경우
			if($this->boardInfoResult["mbr_type"]==1)
			{
			    //if( $this->hasMemberCheck() ) // 비동기식일 경우에 작동
			    if( ! (int)$_SESSION['ADM'] )
			    {
					$where_arr['userid'] = $_SESSION['MBRID'] ;
			    }
			}
			else{
			    $where_arr['pwd'] = $_POST["frm_userpw"] ;
			}

			$this->setTableName($this->boardInfoResult['table_name']);
			
			$data = $this->dataRead(array(
			    "columns" => "serial, bid, userid, pwd, sec, sec_pwd, attach_path, attach_files, attach_orig_files",
			    "conditions" => $where_arr
			)) ;
			
			if(empty($data))
			{
				$this->WebAppService->assign(array('error'=>"작성자가 본인이 아니면 수정할 수 없습니다."));
			}
			else{
			    $data = $data[0] ;
			    
			    //----------------------------
			    //게시물이 비밀글인 경우
			    //----------------------------
			    //$secPwd = $this->sec_pwd_authen($data, $_GET['__bgp']) ;
			    if($_SESSION['MBRID'] != $data['userid']){
					$this->sec_pwd_authen($data, $_GET['__bgp']) ;
			    }
			    
			    $upload_datas = array(
						"attach_path" => $data["attach_path"],
						"attach_files" => explode(",", $data["attach_files"]),
			    		"attach_orig_files" => explode(",", $data["attach_orig_files"])
				) ;
			    
			}
			
			if( !(int) $data['serial'] )
			{
				if($this->boardInfoResult["mbr_type"]==1) {
					$this->WebAppService->assign(array('error'=>"작성자가 본인이 아니면 수정할 수 없습니다."));
					//$this->WebAppService->assign(array('error'=>"You can not edit it unless you are the author."));
				}else{
					$this->WebAppService->assign(array('error'=>"비밀번호가 일치하지 않습니다."));
					//$this->WebAppService->assign(array('error'=>"Passwords do not match."));
				}
			}
			
			//$_POST["frm_memo"] = self::{$this->syntaxHighlight_name."_encode"}($_POST["frm_memo"]) ;
			$this->encode_memo($_POST["frm_memo"]);
			
			$this->vars_filter($_POST, array('frm_memo')) ;
			
			$put_data = array(
			    "cate" => (int) $_POST["frm_cate"],
			    "noti" => (int) $_POST["frm_noti"],
			    "title" => (string) $_POST["frm_title"],
			    "usehtml" => (int) $_POST["frm_usehtml"],
			    "memo" => (string) $_POST["frm_memo"],
			    "regdate" => time()
			) ;
			
			$put_data = array_merge($put_data, $put_add_data) ;
			
			// 파일 삭제 ( 삭제요청한 파일들 삭제처리 )
			if( (int)$this->boardInfoResult['upload_file_cnt'] )
			{
				if( !empty($_POST['frm_file_del']) && is_array($_POST['frm_file_del']) )
				{
					foreach($_POST['frm_file_del'] as $k => $del_file)
					{
						if( (int)$k < (int)$this->boardInfoResult['upload_file_cnt'] && !empty($upload_datas['attach_files'][$k]) )
						{
							// 파일이 실제 존재하는지
							$file = $upload_datas['attach_path'].$upload_datas['attach_files'][$k] ;
							if( is_file($file) )
							{
								$this->WebAppService->File->delete($file) ;
							}
							unset($upload_datas['attach_files'][$k], $upload_datas['attach_orig_files'][$k]) ;
						}
					}
				}
			}
			
			// db에 저장된 첨부파일이 실제 존재하는지 체크
			if( !empty($upload_datas['attach_files']) && is_array($upload_datas['attach_files']) )
			{
				foreach($upload_datas['attach_files'] as $k => &$sfile)
				{
					if( !empty($sfile) ){
						if( ! is_file($upload_datas['attach_path'].$sfile) ) unset($sfile, $upload_datas['attach_orig_files'][$k]) ;
					}else{
						unset($sfile, $upload_datas['attach_orig_files'][$k]) ;
					}
				}
			}

			$res = $this->attach_upload( $data['bid'], $data['attach_path'], $_FILES["frm_attachFile"], $upload_datas ) ;
			if( !empty($res["file"]) )
			{
			    $res_files = implode (",", $res["file"]) ;
			    $res_original_files = implode (",", $res["original_file"]) ;
			    
			    $put_data = array_merge($put_data, array(
			    		"attach_path" => $res["dir"], // 또는 $data[0]["attach_path"]
			    		"attach_files" => $res_files, // 변경된 파일명 리스트
			    		"attach_orig_files" => $res_original_files // 원본 파일명(파일명이 길면 변경된 파일명이 저장됨[attach_upload 함수참조])
			    ));
			}
			else{
				$put_data = array_merge($put_data, array(
						"attach_path" => '', // 또는 $data[0]["attach_path"]
						"attach_files" => '', // 변경된 파일명 리스트
						"attach_orig_files" => '' // 원본 파일명(파일명이 길면 변경된 파일명이 저장됨[attach_upload 함수참조])
				));
			}
			
			try
			{
			    $res = $this->dataUpdate( $put_data,	$where_arr	) ;
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
    /**
     * 게시판 - DB 삭제 (첨부파일 포함)
     */
    public function delete()
    {
        if( empty($_REQUEST['bid']) || ctype_space($_REQUEST['bid']) || !(int)$this->routeResult["code"] ) {
        	$this->WebAppService->assign(array('error'=>"해당 정보를 찾을 수 없습니다."));
			//$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
			//$this->WebAppService->assign(array('error'=>'The data does not exist.'));
        }
        
		if($this->boardInfoResult["mbr_type"]==1) {
        	if( ! $this->hasMemberLogin(true) ) $this->WebAppService->assign(array('error'=>"로그인 후 이용해주세요."));
		}
            
        // 권한정보
        $this->board_access_grant( "delete", $_REQUEST["bid"] );
        
        // 권한체크
        if($this->grant_content['response']['delete']['code'] != 200) {
        	$this->WebAppService->assign(array('error'=> $this->grant_content['response']['delete']['msg'] ));
        }
        
        $this->setTableName($this->boardInfoResult['table_name']);
        
        if( !$this->routeResult["code"] ){
            $this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
            //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
        }
		
        //---------------------------
        //  쿼리 조건 생성
        //---------------------------
        
		// 관리자 (O) 경우
		if( (int)$_SESSION['ADM'] == 1)
		{
			$conditions = array("serial" => (int)$this->routeResult["code"]) ;
		}
		// 관리자 (X) 경우
		else{
		    
		    //회원제 경우
		    if($this->boardInfoResult["mbr_type"]==1)
		    {
		        //if( $this->hasMemberCheck() ) // 비동기식일 경우에 작동
		    	$conditions = array("userid" => $_SESSION['MBRID'], "serial" => (int)$this->routeResult["code"]) ;
		    }
		    // 비회원제 일 경우
		    else{
		    	if( empty($_POST["frm_userpw"]) || ctype_space($_POST["frm_userpw"]) ){
					//$this->WebAppService->assign(array('error'=>'비밀번호를 입력해주세요.'));
					//$this->WebAppService->assign(array('error'=>'Please enter your password correctly.'));
		    		$this->pwd();
		    		exit;
		    	}
				
				$conditions = array("pwd" => (string)$_POST['frm_userpw'], "serial" => (int)$this->routeResult["code"]) ;
		    }
		    
		}
		//---------------------------
	    // 등록된 파일데이타가 있을시 기존 파일 삭제를 위해 데이타 가져옴
	    $data = $this->dataRead(array(
	        "columns"=> "pwd, sec, sec_pwd, attach_path, attach_files",
	        "conditions" => $conditions
	    ));
	    if( empty($data) ){
	    	//Exception
	    	if($this->boardInfoResult["mbr_type"]==1){
	    		$this->WebAppService->assign(array('error'=>'작성자가 본인이 아니면 삭제할 수 없습니다.'));
	    		//$this->WebAppService->assign(array('error'=>'If you are not the author, you can not delete it.'));
	    	}
	    	else{
	    		$this->WebAppService->assign(array('error'=>'비밀번호가 일치하지 않습니다.'));
	    		//$this->WebAppService->assign(array('error'=>'Passwords do not match.'));
	    	}
	    }
	    
	    //----------------------------
	    //게시물이 비밀글인 경우
	    //----------------------------
	    //$secPwd = $this->sec_pwd_authen($data, $_GET['__bgp']) ;
	    $this->sec_pwd_authen($data[0], $_GET['__bgp']) ;
	    
	    try
	    {
	        $res = $this->dataDelete( $conditions	) ;
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
	    
	    
	    if($res)
	    {
	    	
	    	if( !empty($data[0]["attach_files"]) )
	    	{
		    	$del_files = explode(",", $data[0]["attach_files"]) ;
		    	if( !empty($del_files) )
		    	{
			    	for($i=0; $i < count($del_files); $i++){
			    		$this->attach_delete( $data[0]["attach_path"] . $del_files[$i] );
			    	}
		    	}
	    	}
	    	
	    }
	    else{
/*
	        //Exception
	        if($this->boardInfoResult["mbr_type"]==1)
			$this->WebAppService->assign(array('error'=>'작성자가 본인이 아니면 삭제할 수 없습니다.'));
			//$this->WebAppService->assign(array('error'=>'If you are not the author, you can not delete it.'));
				else
	     	$this->WebAppService->assign(array('error'=>'비밀번호가 일치하지 않습니다.'));
			//$this->WebAppService->assign(array('error'=>'Passwords do not match.'));
*/
	    }
	    
	    header("Location: ".WebAppService::$baseURL."/lst".WebAppService::$queryString); // 리스트 페이지 이동
	    exit;
    }
    /**
     * 파일 다운로드
     */
    public function download()
    {
        //Exception
        if( !$this->routeResult["code"] ) $this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
        //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
        
        $this->board_access_grant( "read", $_REQUEST["bid"] );
        
        // 회원용 / 비회원용
        if($this->boardInfoResult["mbr_type"]==1)
        {
            $conditions = array("B.userid" => $_SESSION['MBRID'], "B.serial" => $this->routeResult["code"], "B.bid" => $_REQUEST["bid"]) ;
            //self::$_query_debug = 1 ;
            
            $data = $this->get_board_read($conditions);
            //echo '<pre>';print_r(self::$_query_log);exit;
            if( !empty($data) ) $data = array_pop($data) ;
        }
        else{
            $conditions = array( "serial" => $this->routeResult["code"], "bid" => $_REQUEST["bid"] ) ;
            //self::$_query_debug = 1 ;
            $data = $this->dataRead( array(
                "columns"=> '*',
                "conditions" => $conditions
            ));
            //echo '<pre>';print_r(self::$_query_log) ;
            //exit;
            if( !empty($data) ) $data = array_pop($data) ;
        }
        //Exceptionn
        if( empty($data['serial']) || empty($data['bid'])){
            $this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
            //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
        }
        else{
            
            //$secPwd = "";
            
            // 환경설정-비밀글인 경우
            //if( empty($data['userid']) ) $this->secret_authen( $data['userid'] );
            $this->secret_authen( $data['userid'] );
            
            //----------------------------
            //게시물이 비밀글인 경우
            //----------------------------
            //$secPwd = $this->sec_pwd_authen($data, $_GET['__bgp']) ;
            $this->sec_pwd_authen($data, $_GET['__bgp']) ;
            
            //$this->set_visit_count($this->routeResult["code"]) ;
            
            // 첨부파일 읽기
            $this->read_attachfile($data);
            
            (int)$_GET['seq'] -= 1 ;
            
            $download_attachFile = $data['attach_path'] . $data['attachFiles'][(int)$_GET['seq']]['file'] ;
            
            if( ! (int)$data['attachFiles'][(int)$_GET['seq']]['exist'] ) $this->WebAppService->assign(array('error'=>'파일을 찾을 수 없습니다.'));
            
            \Func::download( $download_attachFile, $data['attachFiles'][(int)$_GET['seq']]['original_file'] ) ;
            
            /* $data['attachFiles'] => Array
             (
             [0] => Array
             (
             ['exist'] => 1
             ['file'] => 1496ef7306.jpg
             )
             
             [1] => Array
             (
             ['exist'] => 0
             ['file'] => f035dc8de0908385f7ecc59cc7e4f6e7--korean-v-necks.jpg
             )
             ) */
            
        }
    }
    public function moveComments()
    {
        $error = $this->getValidate( array(
            "frm_mvOrig",
            "frm_mvTarget"
        )) ;
        if( !empty($error) ) $this->WebAppService->assign(array('error'=>$error));
            
		if($this->routeResult["code"])
		{
		    $this->setTableName("comments");
		    //$res = $this->dataMove($_POST["frm_mvOrig"], $_POST["frm_mvTarget"]) ;
		    $node = array(
					"serial" => $_POST["frm_mvOrig"]
		        ,"parent" => $_POST["frm_mvTarget"]
		    );
                
		    try{
		        $res = $this->dataNestMove( $node );
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
            ));
        $data = $this->getDataAndMbr( $queryOption ) ;
        
        # 데이타 가공
        self::data_process($data) ;
        
        if(!empty($data)) $data = array_pop($data); # javascript에서 1차원배열로 처리를위해
        
        if( is_bool($ret) )
        {
            return $data ;
        }
        else{
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
    }
    /**
     * 현재 얼마나 시간이 경과되었는지 가져오기
     *
     * @param int $date timestamp
     * @return string
     */
    private static function get_elapseDate( int $date)
    {
        $elapsed_date = Strings::get_elapsed_date( $date ) ;
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
        return ( !empty($elapse_date) ) ? "[". implode(" ", $elapse_date) . " 전]" : "" ;
    }
    /**
     * comments-TB 데이타를 가져와서 출력을 위해 가공처리
     *
     * @param Array &$datas (DB 데이타) (pass by reference)
     * @param string $kind ( default: "output" [출력용: "output" / 읽기용: "read"] )
     * @return Array
     */
    private function data_process( &$datas, $info=null)
    {
        if( !empty($datas) && is_array($datas) )
        {
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
                //$data["memo"] = $this->decode_memo($data["memo"]) ;
                $data["memo"] = stripslashes($data["memo"]);
                //비밀글(전체), 비밀글썻는지
                if($info['sec']==1) $data["sec"] = 1 ;
                
                if($data["sec"]==1)
                {
                    //echo '<pre>';print_r($data);
                    
                    //--------------------
                    /**
                     * 비밀글 해제
                     * 조건 : 부모글 작성자는 바로 아래의 자식글을 볼 수 있음
                     * @var array $parent_data
                     */
                    // 부모 배열정보 찾기
                    $parent_data = Func::array_search($datas, 'serial', $data['parent']) ;
                    if( !empty($parent_data) ) {
						$parent_data = array_pop($parent_data) ;
						if($_SESSION['MBRID'] == $parent_data['userid']) $data["my_data_chk"] = 1 ;
                    }
                    //--------------------
                    //echo '부모['.$data['parent'].']: <pre>';print_r($parent_data);
                    // 관리자 또는 자신이 작성한 글이 아닌경우
                    if( $_SESSION['ADM'] !=1 && $data["my_data_chk"]==0 && $_SESSION['MBRID'] != $parent_data['userid'])
                    {
						$data["memo"] = '';
                    }
                }
                //unset($data["userid"]);
                
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
                $data["elapsed_days"] = self::get_elapseDate($data['firstdate']) ;
                /* $elapsed_date = Strings::get_elapsed_date($data['firstdate']) ;
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
                 $data["elapsed_days"] = ( !empty($elapse_date) ) ? "[". implode(" ", $elapse_date) . " 전]" : "" ; */
                //----------------------
                $regdate= date('Y-m-d H:i:s', $data["firstdate"]) ;
                $data["regdate"] = substr( $regdate, 0, 10);
                $data["regtime"] = substr( $regdate, 10);
                //echo '<pre>';print_r($data) ;
                
                $data["profile_not_photo"] = "/theme/".THEME."/images/profile_no.png";
                if( !empty($data["profile_photo"]) )
                {
                    self::$mbr_conf = WebApp::getConf_real("member") ;
                    
                    $profile_photo_file = self::$mbr_conf["profile"]["basedir"].$data["profile_photo"] ;
                    $data["profile_photo"] = ( is_file($profile_photo_file) ) ? "/".$profile_photo_file : "/theme/".THEME."/images/profile_no.png";
                }
                else{
                    $data["profile_photo"] = $data["profile_not_photo"] ;
                }
                
            }
            
            //echo '<pre>';print_r($datas);
        }
        //echo '<pre>';print_r($datas);
        //return $datas ;
    }
    /**
     * 게시판 댓글 리스트 조회
     *
     * @param array<key,value> &$queryOption (pass by reference) <br>
     * @param string &$board_id (게시판 아이디) (pass by reference)
     *
     * @example $queryOption = array( <br>
     * 											"columns" => "column-name, column-name", <br>
     * 											"conditions" => string or array(.......) <br>
     * 											"join" => "left" # sql join 형태 <br>
     * 											"order" => "?? desc, ?? asc...." <br>
     * 											"pageBlock" => 데이타 갯수
     * 					 				) ;
     * @return array
     */
    private function get_comments_lst( &$queryOption, &$board_id=NULL )
    {
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
        
        //my_data_chk
        
        $this->setTableName($this->commentsInfoResult['table_name']);
        $datas = $this->getDataAndMbr( array_merge(self::$queryOption_comments,array('conditions'=>$queryOption['query_condition'])), $this->commentsInfoResult["indent"], true ) ;
        
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
    public function Req_delete()
    {
    	if(REQUEST_WITH != 'AJAX') {
    		header('Location:/') ;	exit;
    	}
    	
    	if( empty($_REQUEST['bid']) || ctype_space($_REQUEST['bid']) || !(int)$this->routeResult["code"] ) {
			$this->WebAppService->assign(array('error'=>"해당 정보를 찾을 수 없습니다."));
    	}
		//$this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
		//$this->WebAppService->assign(array('error'=>'The data does not exist.'));
    			
		// 권한정보
		$this->board_access_grant( "delete", $_REQUEST["bid"] );
    		
		echo '<pre>';print_r($this->grant_content) ;exit;
		$this->setTableName($this->boardInfoResult['table_name']);
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
         )) ;
        
         $comments_datas = $this->get_comments_lst($queryOption, $_REQUEST["bid"]);
         */
        
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
        //echo '<pre>';print_r($ResultQuery);exit;
        
        $comments_datas = $this->get_comments_lst($ResultQuery, $_REQUEST["bid"]);
        
        // 템플릿 파일
        if( !empty($this->commentsInfoResult['editor']) ){
			$template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base.lst." .$this->syntaxHighlight_name. ".html" ;
        }else{
			$template = "html/comments/skin/".$this->commentsInfoResult["skin_grp"]."/".$this->commentsInfoResult["skin_name"]."/base.html" ;
        }
        
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
        	if( empty($_REQUEST['bid']) || ctype_space($_REQUEST['bid']) ) $this->WebAppService->assign(array('error'=>"해당 정보를 찾을 수 없습니다."));
        	
            //Exception
            if( !$this->routeResult["code"] ){
                $this->WebAppService->assign(array('error'=>'데이타가 부족하여 조회할 수 없습니다.'));
                //$this->WebAppService->assign(array('error'=>'There is not enough data to query.'));
            }
            // 입력값 체크
            $error = $this->getValidate( array(
            		"frm_memo"
            )) ;
            if( !empty($error) ) $this->WebAppService->assign(array('error'=>$error));
            
            /* TINYTEXT 256 bytes
             TEXT 65,535 bytes ~64kb
             MEDIUMTEXT 16,777,215 bytes ~16MB
             LONGTEXT 4,294,967,295 bytes ~4GB */
            if(mb_strwidth($_POST["frm_memo"], 'utf-8') > 65535 ) $this->WebAppService->assign(array('error'=>'댓글 최대 글자수를 초과했습니다.'));
            
            
            // 댓글 환경정보 가져오기
            $this->get_comment_info($_REQUEST["bid"]) ;
            
            // 회원 전용인 경우
            if($this->commentsInfoResult["mbr_type"]==1) {
            	if( ! $this->hasMemberLogin(true) ) $this->WebAppService->assign(array('error'=>"로그인 후 이용해주세요."));
            }
            
            // 권한정보 가져오기
            $this->comments_access_grant( "write", $_REQUEST["bid"] );
            
            // 권한체크
            if($this->grant_content['response']['write']['code'] != 200) {
            	$this->WebAppService->assign(array('error'=> $this->grant_content['response']['write']['msg'] ));
            }
            
			# 저장할 추가데이타
            $put_add_data = $this->get_write_variable( $this->commentsInfoResult );
			
			//-------------------
			// 게시판 데이타
			//-------------------
			$this->setTableName($this->boardInfoResult['table_name']); // table name 재정의
			$data = $this->dataRead( array(
			    "columns"=> "mcode, bid, cate",
			    "conditions" => array(
			        "serial" => $this->routeResult["code"],
			        "bid" => $_REQUEST["bid"]
			    )
			));
			if( empty($data) ){
			    $this->WebAppService->assign(array('error'=>'원본 게시글이 존재하지 않습니다.'));
			    //$this->WebAppService->assign(array('error'=>'The original post does not exist.'));
			}else{
				$data = $data[0] ;
			}
			//-------------------
			
			$this->vars_filter($_POST, array('frm_memo')) ;
			$this->encode_memo($_POST["frm_memo"]);
			
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
	        $put_data = array_merge($put_data, $put_add_data) ;
	        
	        $this->setTableName( $this->commentsInfoResult["table_name"] );
	        
	        // 삽입할 인접(Adjacency) 정보 등록시 $this->routeResult["code"]
	        if( $this->commentsInfoResult["indent"] )
	        {
	        	//echo '<pre>';print_r($_POST);
	            if( (int)$_POST["serial"] )
	            {
	            	//self::$_query_debug = 1 ;
	                $lastChildRow = $this->dataLastChild( (int)$_POST["serial"] );
	                $insert_id = $this->dataAdd( $put_data, $_POST["serial"] ) ;
	                //echo '<pre>';print_r(self::$_query_log);
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
        	
        	if($this->boardInfoResult["mbr_type"]==1) {
        		if( ! $this->hasMemberLogin(true) ) $this->WebAppService->assign(array('error'=>"로그인 후 이용해주세요."));
        	}
        	
        	// 댓글 환경정보 가져오기
        	$this->get_comment_info($_REQUEST["bid"]) ;
        	
        	// 권한정보 가져오기
        	$this->comments_access_grant( "update", $_REQUEST["bid"] );
        	
        	// 권한체크
        	if($this->grant_content['response']['update']['code'] != 200) {
        		$this->WebAppService->assign(array('error'=> $this->grant_content['response']['update']['msg'] ));
        	}
        	
        	// POST 값 체크
        	$error = $this->getValidate( array(
        			/* "frm_mcode", */
        			//"frm_title",
        			"frm_memo"
        	)) ;
        	if( !empty($error) ){
        		$this->WebAppService->assign(array('error'=>$error));
        	}
        	
        	# 저장할 추가데이타
        	$put_add_data = $this->get_write_variable( $this->commentsInfoResult );
        	//-----------------------------------------------------
        	
            $where_arr = array(
                "serial" => $_REQUEST["serial"],
                "bserial" => $this->routeResult["code"],
                "bid" => $_REQUEST["bid"],
                //"userid" => $_SESSION['MBRID']
            ) ;
            
            // 회원제인 경우
            if($this->boardInfoResult["mbr_type"]==1)
            {
                //$this->hasMemberCheck();
                
                $where_arr = array_merge($where_arr, array("userid" => $_SESSION['MBRID'])) ;
            }
            
            $this->setTableName("comments");
            
            # 데이타 존재유무 체크
            $exist_data = $this->count( "serial", $where_arr) ;
            if( $exist_data < 1 ) $this->WebAppService->assign(array('error'=>'데이타가 존재하지 않습니다.'));
            //$this->WebAppService->assign(array('error'=>'The data does not exist.'));
            
            # 자신이 작성한 데이타인지 체크
            $exist_data = $this->count( "serial", $where_arr) ;
            if( $exist_data < 1){
                $this->WebAppService->assign(array('error'=>'작성자 본인만 수정 가능합니다.'));
                //$this->WebAppService->assign(array('error'=>'Only the author can modify it.'));
            }
                # Validation variable
            if( empty($_POST["frm_memo"]) || ctype_space($_POST["frm_memo"]) ){
                    $this->WebAppService->assign(array('error'=>'내용을 입력해주세요.'));
                    //$this->WebAppService->assign(array('error'=>'Please enter contents.'));
            }
                    $error = $this->getValidate( array(
			"frm_memo"
                    )) ;
                    if( !empty($error) )
			$this->WebAppService->assign(array('error'=>$error));
			
			/* $this->encode_memo($_POST["frm_memo"]) ; */
			$this->vars_filter($_POST, array('frm_memo')) ;
			$this->encode_memo($_POST["frm_memo"]);
			
			try
			{
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
                //$this->hasMemberCheck();
                
                $where = array_merge($where, array("userid" => $_SESSION['MBRID'])) ;
            }
            try
            {
                // 계층형인 경우 바로 아래 자식노드에 기록남김(부모가 삭제되었다고)
                if($this->boardInfoResult["indent"]==1)
                {
                    $del_data = $this->dataRead( array(
			"columns"=> 'serial, indent, memo',
			"conditions" => $where
                    ));
                    if(!empty($del_data)) {
			
			$del_data = array_pop($del_data) ;
			
			$put_data = array( 'parent_del' => 1 );
			$this->dataUpdate($put_data, array(
			    'parent' => $del_data['serial']
			));
                    }
                }
                
                $res = $this->dataDelete( $where ) ;
                
                // 삭제된 자신의 자식노드들 메모내용에 "[삭제된 댓글의 답글]"을 추가
                if($res)
                {
                    /* $put_data = array( 'parent_del' => 1 );
                     $this->dataUpdate($put_data, array(
                     'parent' => $_REQUEST["serial"],
                     //'indent > '.(int)$del_data['indent'],
                     //'indent' => ( (int)$del_data['indent']+1 ),
                     "bid" => $_REQUEST["bid"]
                     )) ; */
                    
                    /* $datas = $this->dataRead( array(
                     "columns"=> 'serial, memo',
                     "conditions" => array(
                     'parent' => $_REQUEST["serial"],
                     //'indent > '.(int)$del_data['indent'],
                     //'indent' => ( (int)$del_data['indent']+1 ),
                     "bid" => $_REQUEST["bid"]
                     )
                     ));
                    
                    
                     if(!empty($datas))
                     {
                     foreach( $datas as &$data )
                     {
                     if( ! preg_match('/([삭제된 댓글의 답글])/', (string)$data['memo']) )
                     {
                     $put_data = array( 'memo' => "[삭제된 댓글의 답글] " .  (string)$data['memo'] );
                     $this->dataUpdate($put_data, array("serial" => $data['serial'])) ;
                     }
                     }
                     unset($data) ;
                     } */
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
     * @return boolean|void (회원이면 true, 회원아니면 경고창)
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