<?
use system\traits\DB_NestedSet_Trait;
use Gajija\service\CommNest_service;
use Gajija\controller\_traits\AdmController_comm;

/**
 * 기본 게시판 컨트롤러
 */

class CateCommNest_controller extends CommNest_service
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
	//private $attach_basedir = 'html/_attach/' ;
	private $attach_basedir = 'theme/'.THEME.'/html/_attach/';
	
	/**
	 * 선택형: 상품 카테고리 생성할 항목 깊이 (mean : 단계 or heriarchy)
	 * @var integer
	 */
	private static $shop_category_depth = 4 ;
	
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
			unset($this->$k);
		}
	}
	public function Req_getNestDatas()
	{
		// DB Table 선언
		$this->setTableName("menu");
		
		$this->TNst_hasData();
		
		$queryOption = array(
				"columns" => "serial, parent, indent, title,"
				." CASE WHEN rgt - lft > 1 THEN 1 ELSE 0 END AS is_branch",
				"conditions" => array(
						"indent BETWEEN 0 AND ".(int) self::$shop_category_depth
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
		
		
		$category_config['base_maxDepth'] = (int) self::$shop_category_depth;
		
		// javascript 추가
		$JS_option = array(
				"base" => array(
						"id" => 1,
						"indent" => (int) self::$shop_category_depth+1,
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
		
		$tpl->define("CONTENT", Display::getTemplate("adm/menu/nest/menu.nest.load.html"));
		//$content = $tpl->fetch('CONTENT');
		//$this->WebAppService->assign( array('datas' => $content) ) ;
		$content = $tpl->print_('CONTENT');
		ob_flush();
		flush();
		exit;
	}
	
	public function main()
	{
		// DB Table 선언
		$this->setTableName("menu");
		
		$this->TNst_hasData();
		
		$queryOption = array(
				"columns" => "serial, parent, indent, title,"
				." CASE WHEN rgt - lft > 1 THEN 1 ELSE 0 END AS is_branch",
				"conditions" => array(
						"indent BETWEEN 0 AND ".(int) self::$shop_category_depth
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
		
		
		$category_config['base_maxDepth'] = (int) self::$shop_category_depth;
		
		// javascript 추가
		$JS_option = array(
				"base" => array(
						"id" => 1,
						"indent" => (int) self::$shop_category_depth+1,
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
				
				'LAYOUTS' => $this->get_conf('layout',true),
				'ATTACH_BaseDir' => $this->attach_basedir,
				'MBR_GRADES' => $this->get_grades()//self::$mbr_conf["grade"]
		));
		
		
		$this->WebAppService->Output( Display::getTemplate("adm/menu/nest/menu.nest.html"),"admin_sub");
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
	 * Ajax 요청 처리 (tree)
	 * --> 이동,추가,업데이트,삭제 처리
	 */
	public function Req_MenuUpdate()
	{
		// DB Table 선언
		$this->setTableName("menu");
		
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
					"imp" => 1
			);
			$insert_id = $this->TNst_add($put_data, $_POST['parent']);
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
					"columns" => "serial, lft, rgt, attach_top, attach_bottom",
					"conditions" => array("serial" => $_POST['serial'])
			));
			
			if( empty($getData) ) {
				//Exception
				$this->WebAppService->assign(array(
						"error" => "업데이트할 자료가 존재하지 않습니다."
				));
				exit ;
			}
			
			$this->attach_delete(array(
					'attach_top'=>$getData[0]['attach_top'],
					'attach_bottom'=>$getData[0]['attach_bottom']
			));
			$attach = $this->attach_write($getData[0]['serial']);
			
			$res = $this->dataUpdate(
					array(
							"mcode" => (int) $_POST["mcode"],
							"title" => $_POST["title"],
							"url" => str_replace(' ','', $_POST["url"]),
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
							"serial" => $_POST['serial']
					)
					) ;
			
			$res_child = $this->dataUpdate(
					array(
							"used" => (int) $_POST['used'],
							"imp" => (int) $_POST['imp'],
							"grant_read" => (int) $_POST["grant_read"]
							//,"grant_write" => (int) $_POST["grant_write"]
					),
					"lft between " . $getData[0]['lft'] . " AND " . $getData[0]['rgt']
					) ;
			
			$resData = $this->dataRead(array(
					"columns" => "*",
					"conditions" => array("serial" => $_POST['serial'])
			));
			$resData[0]['attach_top_cont'] = $_POST['attach_top_cont'] ;
			$resData[0]['attach_bottom_cont'] = $_POST['attach_bottom_cont'] ;
			
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
	 * Ajax 요청  (tree)
	 * @param mixed $ret
	 */
	public function Req_getMenu($ret)
	{
		// DB Table 선언
		$this->setTableName("menu");
		
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
		$this->setTableName("menu");
		
		$this->TNst_getNodes();
	}
	
	/**
	 * Ajax 요청 (select box)
	 */
	/* public function Req_getCategorys()
	 {
	 # 쇼핑 카테고리 노드리스트
	 $this->setTableName("menu");
	 $shop_cateNodes = self::get_cateNodes() ;
	 $this->WebAppService->assign( $shop_cateNodes );
	 } */
	
	//------------------------------
	
	
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
				/* if( is_array($data) ) {
				 foreach($data as &$value) array_push($types, $value) ;
				 }
				 unset($data); */
				//return $types;
				//return @array_keys($this->INI_manager->get_ini_array('./conf/layout.conf.php'));
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
	 * @param array $data ("attach_top"=>??, "attach_bottom"=>??)
	 * @return void
	 */
	private function attach_delete($data){
		if( is_file($data['attach_top']) ) $this->WebAppService->File->delete($data['attach_top']);
		if( is_file($data['attach_bottom']) ) $this->WebAppService->File->delete($data['attach_bottom']);
		$this->WebAppService->File->close();
		unset($data);
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