<?
use system\traits\DB_NestedSet_Trait;
use Gajija\controller\_traits\AdmController_comm;
use Gajija\service\CommNest_service;

/**
 * 기본 게시판 컨트롤러
 */

class BoardAdminCateNest_controller extends CommNest_service
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
	 * 선택형: 상품 카테고리 생성할 항목 깊이 (mean : 단계 or heriarchy)
	 * @var integer
	 */
	private static $category_depth = 4 ;
	
	/**
	 * DB 테이블명
	 * 
	 * @var string
	 */
	private static $table_name = "board_cate" ;
	
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
	 * @deprecated
	 */
	public function Req_getNestDatas_bak()
	{
		// DB Table 선언
		$this->setTableName(self::$table_name);
		
		$this->TNst_hasData();
		
		$queryOption = array(
				"columns" => "serial, parent, indent, title,"
				." CASE WHEN rgt - lft > 1 THEN 1 ELSE 0 END AS is_branch",
				"conditions" => array(
						"indent BETWEEN 0 AND ".(int) self::$category_depth
				),
				"order" => "lft"
		);
		$shopCategory_data = $this->dataList( $queryOption ) ;
		//===================================================
		$Category_data = array() ;
		foreach( $shopCategory_data as &$val){
			$data = $val ;
			$serial = $val['serial'] ;
			unset( $data['serial'], $data['title'], $data['indent'], $data['parent'], $data['is_branch'] );
			$Category_data[$serial] = $data ;
		}
		$JS_shopCategory_data= json_encode( $Category_data) ;
		//echo '<pre>';print_r($Category_data);exit;
		//===================================================
		if( !empty($shopCategory_data) ) $res = $this->TNst_renderTree($shopCategory_data);
		//===================================================
		
		
		$category_config['base_maxDepth'] = (int) self::$category_depth;
		
		// javascript 추가
		$JS_option = array(
				"base" => array(
						"id" => 1,
						"indent" => (int) self::$category_depth+1,
						"use" => 1,
						"type" => 2
				)
		) ;
		$JS_shop_category_opt = json_encode( $JS_option) ;
		//===================================================
		//echo '<pre>';print_r($shopCategory_data) ;
		//------------------------------------
		$tpl = &WebApp::singleton('Display');
		$tpl->assign(array(
				'DATA' => $category_config,
				"CATEGORY_LIST"=> &$shopCategory_data,
				'JS_CATEGORY_OPT' => $JS_shop_category_opt,
				'JS_CATEGORY_DATA' => $JS_shopCategory_data,
		));
		
		$tpl->define("CONTENT", Display::getTemplate("adm/board/boardCateNest.load.html"));
		//$content = $tpl->fetch('CONTENT');
		//$this->WebAppService->assign( array('datas' => $content) ) ;
		$content = $tpl->print_('CONTENT');
		ob_flush();
		flush();
		exit;
	}
	public function Req_getNestDatas()
	{
		$shopCategory_data = $this->get_cateGroupList();
		
		$Category_data = array() ;
		foreach( $shopCategory_data as &$val){
			$data = $val ;
			$serial = $val['serial'] ;
			unset( $data['serial'], $data['title'], $data['indent'], $data['parent'], $data['is_branch'] );
			$Category_data[$serial] = $data ;
		}
		$JS_shopCategory_data= json_encode( $Category_data) ;
		//echo '<pre>';print_r($Category_data);exit;
		//===================================================
		if( !empty($shopCategory_data) ) $res = $this->TNst_renderTree($shopCategory_data);
		//===================================================
		
		
		$category_config['base_maxDepth'] = (int) self::$category_depth;
		
		// javascript 추가
		$JS_option = array(
				"base" => array(
						"id" => 1,
						"indent" => (int) self::$category_depth+1,
						"use" => 1,
						"type" => 2
				)
		) ;
		$JS_shop_category_opt = json_encode( $JS_option) ;
		//===================================================
		//echo '<pre>';print_r($shopCategory_data) ;
		//------------------------------------
		$tpl = &WebApp::singleton('Display');
		$tpl->assign(array(
				'DATA' => $category_config,
				"CATEGORY_LIST"=> &$shopCategory_data,
				'JS_CATEGORY_OPT' => $JS_shop_category_opt,
				'JS_CATEGORY_DATA' => $JS_shopCategory_data,
		));
		
		$tpl->define("CONTENT", Display::getTemplate("adm/board/boardCateNest.load.html"));
		//$content = $tpl->fetch('CONTENT');
		//$this->WebAppService->assign( array('datas' => $content) ) ;
		$content = $tpl->print_('CONTENT');
		ob_flush();
		flush();
		exit;
	}
	/**
	 * @desc 카테고리 & 정보 조회
	 * 
	 * @param number $serial
	 * @return \Gajija\service\_traits\db\multitype:
	 */
	private function get_cateGroupList($serial = 0)
	{
		/* $queryOption = array(
				"cateTable" => "board_cate",
				"dataTable" => "board",
				"columns" => "parent.serial, "
									."parent.parent, "
									."parent.indent, "
									."parent.title,"
									." COUNT(D.serial) as datas_cnt," // 카테고리에 해당하는 데이타 총갯수
									." CASE WHEN parent.rgt - parent.lft > 1 THEN 1 ELSE 0 END AS is_branch",
				"groupBy" => "parent.serial",
				"order" => "parent.lft"
		); */
		$queryOption = array(
				"cateTable" => "board_cate",
				"dataTable" => "board_info",
				"columns" => "parent.serial, "
									."parent.parent, "
									."parent.indent, "
									."parent.title,"
									." COUNT(D.serial) as board_qty," // 카테고리에 해당하는 데이타 총갯수
									." CASE WHEN parent.rgt - parent.lft > 1 THEN 1 ELSE 0 END AS is_branch",
				"groupBy" => "parent.serial",
				"order" => "parent.lft"
		);
		if( (int)$serial ){
			$queryOption['conditions'] = array( "parent.serial" => $_POST['serial'] );
		}
		
		$datas = $this->CateGroupyList( $queryOption ) ;
		
		//echo '<pre>';print_r($datas);
		
		return $datas ;
	}
	public function main()
	{
		
		/* 
		// DB Table 선언
		$this->setTableName(self::$table_name);
		
		$this->TNst_hasData();
		
		$queryOption = array(
				"columns" => "serial, parent, indent, title,"
				." CASE WHEN rgt - lft > 1 THEN 1 ELSE 0 END AS is_branch",
				"conditions" => array(
						"indent BETWEEN 0 AND ".(int) self::$category_depth
				),
				"order" => "lft"
		);
		$shopCategory_data = $this->dataList( $queryOption ) ;
		 */
		$shopCategory_data = $this->get_cateGroupList();
		
		//===================================================
		$Category_data = array() ;
		if(!empty($shopCategory_data))
		{
			foreach( $shopCategory_data as &$val){
				$data = $val ;
				$serial = $val['serial'] ;
				unset( $data['serial'], $data['title'], $data['indent'], $data['parent'], $data['is_branch'] );
				$Category_data[$serial] = $data ;
			}
		}
		$JS_shopCategory_data= json_encode( $Category_data) ;
		//echo '<pre>';print_r($Category_data);exit;
		//===================================================
		if( !empty($shopCategory_data) ) $res = $this->TNst_renderTree($shopCategory_data);
		//===================================================
		
		
		$category_config['base_maxDepth'] = (int) self::$category_depth;
		
		// javascript 추가
		$JS_option = array(
				"base" => array(
						"id" => 1,
						"indent" => (int) self::$category_depth+1,
						"use" => 1,
						"type" => 2
				)
		) ;
		$JS_shop_category_opt = json_encode( $JS_option) ;

		//echo '<pre>';print_r($shopCategory_data);exit;
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'queryString' => WebAppService::$queryString
				),
				'CKEYWORDS' => CKEYWORDS,
				'DATA' => $category_config,
				'CATEGORY_LIST' => $shopCategory_data,
				'JS_CATEGORY_OPT' => $JS_shop_category_opt,
				'JS_CATEGORY_DATA' => $JS_shopCategory_data,
				
				'BOARDS' => $this->get_boards(),
				'MBR_GRADES' => $this->get_grades()//self::$mbr_conf["grade"]
		));
		
		$this->WebAppService->Output( Display::getTemplate("adm/board/boardCateNest.main.html"),"admin_sub");
		$this->WebAppService->printAll();
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
					'serial' => array(
							'label' => '코드를',
							'rules' => 'required|numeric'
					),
					'parent' => array(
							'label' => '부모코드를',
							'rules' => 'required|numeric'
					),
					'old_parent' => array(
							'label' => '이전 부모코드를',
							'rules' => 'required|numeric'
					),
					'mcode' => array(
							'label' => '메뉴코드를',
							'rules' => 'required|numeric'
					),
					'title' => array(
							'label' => '타이틀 명을',
							'rules' => 'required'
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
	 * @desc 게시판 리스트 가져오기
	 * 
	 * @return array (
	 * 		0 => array(bid, title), 
	 * 		1 => array(bid, title)
	 * 	)
	 * 
	 */
	private function get_boards()
	{
		// DB Table 선언
		$this->setTableName("board_info");
		
		$datas = $this->dataRead( array(
				"columns"=> 'bid, title',
				//"conditions" => array( "serial" => $_POST['serial'])
				"order" => "title"
		));
		return $datas ;
	}
	/**
	 * Ajax 요청 처리 (tree)
	 * --> 이동,추가,업데이트,삭제 처리
	 */
	public function Req_MenuUpdate()
	{
		// DB Table 선언
		$this->setTableName(self::$table_name);
		
		if( $_POST['property'] == "move" )
		{
			$this->TNst_move( array(
					"serial" => (int) $_POST['serial'],
					"parent" => (int) $_POST['parent'],
					"old_parent" => (int) $_POST['old_parent'],
					"previous" => (int) $_POST['previous']
			)) ;
		}
		else if( $_POST['property'] == "add" )
		{
			$put_data = array(
					"oid" => (int) OID,
					"title" => $_POST["title"],
					"imp" => 0
			);
			$insert_id = $this->TNst_add($put_data, $_POST['parent']);
			//$insert_id = $this->dataAddFamily( $put_data, $_POST['parent']	) ;
			if($insert_id)
			{
			}
			else{
				//Exception
				$this->WebAppService->assign(array(
						"error" => "저장되지 않았습니다."
				));
				exit ;
			}
		}
		else if( $_POST['property'] == "update" )
		{
			/* $put_data = array(
					"title" => $_POST["title"],
					"imp" => 1,
					"orderBy" => $_POST["orderBy"],
			);
			$conditions = array(
					"serial" => $_POST['serial']
			);
			$this->TNst_update($put_data, $conditions) ; */
			$getData = $this->dataRead(array(
					"columns" => "serial, lft, rgt",
					"conditions" => array("serial" => $_POST['serial'])
			));
			
			if( empty($getData) ) {
				//Exception
				$this->WebAppService->assign(array(
						"error" => "업데이트할 자료가 존재하지 않습니다."
				));
				exit ;
			}
			
			$res = $this->dataUpdate(
					array(
							"title" => (string) $_POST["title"],
							"grant_read" => (int) $_POST["grant_read"],
							"imp" => (int) $_POST['imp']
					),
					array(
							"serial" => $_POST['serial']
					)
					) ;
			
			$res_child = $this->dataUpdate(
					array(
							"grant_read" => (int) $_POST["grant_read"],
							"imp" => (int) $_POST['imp']
					),
					"lft between " . $getData[0]['lft'] . " AND " . $getData[0]['rgt']
					) ;
			
			$resData = $this->dataRead(array(
					"columns" => "*",
					"conditions" => array("serial" => $_POST['serial'])
			));
			
			$this->WebAppService->assign($resData[0]);
			exit;
		}
		else if( $_POST['property'] == "delete" )
		{
				$this->TNst_deleteContainChild($_POST['serial']);
				//$this->TNst_delete($_POST['serial']);
		}
		echo 1; // ajax 응답처리용
		exit;
	}
	/**
	 * @desc Ajax 요청  (tree) - 해당 데이타 가져오기
	 * 
	 * @param mixed $ret
	 */
	public function Req_getMenu($ret)
	{
		// DB Table 선언
		$this->setTableName(self::$table_name);
		
		// P.K 코드 값이 없을경우
		if( ! $_POST['serial'])
		{	// exception
			//header("Location: /".WebAppService::$baseURL."/add"); // 신규작성 폼으로 이동
			exit;
		}
		
		$data = $this->dataRead( array(
			"columns"=> '*',
		 	"conditions" => array( "serial" => $_POST['serial'])
		 ));
		if(!empty($data))
		{
			// DB Table 선언
			$this->setTableName("board_info");
			
			$board_qty = $this->count("serial", "cate=".$_POST['serial']) ;
			if(!empty($board_qty)) $data[0]['board_qty'] = number_format($board_qty) ;
		}
		/* $queryOption = array(
				"columns" => "parent.*, COUNT(G.serial) as goods_cnt",
				"groupBy" => "parent.serial",
				"conditions" => array(
						"parent.serial" => $_POST['serial']
				)
		);
		$data= $this->GoodsCateGroupyList( $queryOption ) ;
		$this->WebAppService->assign($data[0]) ; */
		if( is_bool($ret) )
		 //return $data ;
			$this->WebAppService->assign($data[0]) ;
		else
			$this->WebAppService->assign($data[0]) ;
	}
	
	/**
	 * Ajax 요청 (tree)
	 */
	public function Req_MenuReadNodes()
	{
		// DB Table 선언
		$this->setTableName(self::$table_name);
		
		$this->TNst_getNodes();
	}
	
}