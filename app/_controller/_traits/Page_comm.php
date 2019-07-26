<?php
namespace Gajija\controller\_traits ;

/**
 * Controller용 - 페이지 정보 
 *
 */

trait Page_comm{
	
	protected function __constructor($routeResult)
	{
		if($routeResult)
		{
			// 라우팅 결과
			$this->routeResult = $routeResult ;
		}
		
		// 웹서비스
		//if(!$this->WebAppService || !class_exists('WebAppService', false))
		if( ! $this->WebAppService instanceof WebAppService )
		{
			// instance 생성
			$this->WebAppService = \WebApp::singleton("WebAppService:system");
			// Query String
			\WebAppService::$queryString = \Func::QueryString_filter() ;
			// base URL
			\WebAppService::$baseURL = $this->routeResult["baseURL"] ;
		}
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	/**
	 * 메뉴 - 회원등급 접근권한 체크
	 * 
	 * @param integer $grant_code 허용 가능한 [회원]등급코드
	 * 
	 * @return number 200(권한인증 성공), 406(권한인증 실패), 401(로그인 인증이 필요)
	 */
	protected function menu_access_grant( int $grant_code )
	{
		if( (int)$_SESSION['ADM'] == 1) return 200; // 관리자 인 경우
		//else if( ! isset($_SESSION) || empty($_SESSION) ) return 401 ; // 로그인 인증이 필요
		
		if( isset($_SESSION['MBRGRADE']) )
		{
			if( (int)$_SESSION['MBRGRADE'] >= (int) $grant_code )
				return 200 ; // 권한인증 성공(OK)
			else
				return 406 ; // 권한인증 실패
		}else{
			return 401 ; // 로그인 인증이 필요
		}
	}
	/**
	 * 메뉴 - 권한 인증
	 * 
	 * @param string $kind ( 읽기: read )
	 * @param integer $grant_code ( 허용가능한 등급코드 )
	 * 
	 * @return void
	 */
	protected function menu_access_authen( int $grant_code, $kind="read" )
	{
		
		$this->hasMemberLogin(); //Service_Comm_Trait.php 참조
		
		if($kind == "read" )
		{
			$res = $this->menu_access_grant( $grant_code ) ;
			if( $res != 200 ){
					$this->WebAppService->assign(array(
							//'error_code' => $res,
							'error'=>'['.$res.'] 읽기권한이 없습니다.'
					));
			}
		}
	}
	/**
	 * 노드정보 가져오기
	 * 
	 * @param array &$datas (Pass by Reference)
	 * @param mixed $key
	 * @param mixed $keyValue
	 * @return void
	 * 
	 * @access 
                $datas["childs"] = array(); // 자식 노드들 <br>
        	    $datas["path"] = array(); // 인접한 상위노드 ~ 자신노드 까지의 depth 전체 노드들 <br>
        	    $datas["self"] = array(); // 자기자신 노드 
	 */
	protected function _get_nodes( &$datas, $key, $keyValue )
	{
		
		if( !empty($key) && !empty($keyValue) )  $datas['self'] = \Func::array_searchKeyValue($datas['base'], $key, $keyValue) ;
		
		// 관리자 아니면 노출처리 된것만 출력
		if( ! (int)$_SESSION['ADM'] ) {
		
			if( !empty($datas) )
			{
				for($i=0,$l=count($datas['base']); $i < $l; $i++){
					if( !(int) $datas['base'][$i]['imp'] ) {
						array_splice($datas["base"], $i, 1);//unset($datas["base"][$i]) ;
						
						$l=count($datas['base']);
						$i-- ;
					}
				}
			}
		}
		$datas['base'] = \Func::array_orderby($datas['base'], 'lft', SORT_ASC);
		
	    //if(!empty($menu['self'])) $menu['self'] = array_pop($datas['self']) ;
	    
	    if( !empty($datas['base']) && !empty($datas['self']) )
	    {
	    	foreach( $datas['base'] as $key => $item ) // && $datas[$key]['depth']==$depth
	    	{
	    		if( $item['lft'] <= $datas['self']['lft'] && $item['rgt'] >= $datas['self']['rgt'] ){
	    			$datas['path'][] = $item ;
	    		}
	    	}
	    	if($datas['self']['indent'] < 2 || !isset($datas['path']) ) $Self = $datas['self'] ;
	    	else $Self = $datas['path'][count($datas['path'])-2] ;
	    	
	    	
	    	foreach( $datas['base'] as $key => $item ) // && $datas[$key]['depth']==$depth
    	    { 
    	    	/* if( $item['lft'] >= $datas['self']['lft'] &&  $item['rgt'] <= $datas['self']['rgt'] ){
    	    		$datas['childs'][] = $item ;
    	        } */
    	    	if( $item['lft'] >= $Self['lft'] &&  $item['rgt'] <= $Self['rgt'] ){
    	        	$datas['childs'][] = $item ;
    	        }
    	    }
	    }
	    
	    $this->TNst_renderTree($datas['base']);
	    
	    
	    //$a = $this->TNst_jsTree($datas['base']) ;
	    //echo '<pre>';print_r($a);
	    if( !empty($datas['childs']) ) $this->TNst_renderTree($datas['childs']);
	    
	    //$read = Func::array_searchKeyValue($datas, 'serial', parent) ;
	    
	}
	/**
	 * 
	 * @param string $table
	 * @param string $serial
	 * @param array $conditions
	 * @return array
	 */
	protected function get_menu(string $table, $serial=NULL, $conditions=array() )
	{
		$datas = array() ;
	    
	    $file = 'theme/'.THEME.'/html/menu.json' ;
	    if( is_file($file) )
	    {
	    	//ob_start();
	    	//$json_string = file_get_contents($file) ;
	    	$fh = fopen($file, 'r');
	    	$json_string = fread($fh, filesize($file));
	    	fclose($fh); 
	    	//ob_end_clean();
	    	
	    	//$json_string = utf8_encode($json_string); 
	    	$datas['base'] = json_decode($json_string,true);
	    	
	    	
	    }else{
	    	
	    	$prev_table = static::$TABLE ; // 이전에 선언한 테이블 정보
	    	
		    $this->setTableName($table);
		    $datas['base'] = $this->dataRead(array(
		        "columns" => "serial, parent, indent, lft, rgt, title, layout, tpl, url, url_target, used, imp, grant_read, grant_write, CASE WHEN rgt - lft > 1 THEN 1 ELSE 0 END AS is_branch",
		        "conditions" => array_merge( array("used"=>1), $conditions ) ,
		        "order" => "lft"
		    ));
		    
		    static::$TABLE = $prev_table ; // 이전에 사용하던 테이블로 재선언
	    }
	    
	    
	    array_splice($datas['base'],0, 1) ;
	    
	    $this->_get_nodes($datas, 'serial', (int)$serial) ;
	    
	    //echo '<pre>';print_r($datas) ;exit;
	    if( ! (int)$_SESSION['ADM'] && isset($datas['self']) && ! $datas['self']['used'] )
	    {
	    	$this->WebAppService->assign(array(
	    			//'error_code' => $res,
	    			'error'=>'[406] 권한이 없습니다.',
	    			'redirect' => '/'
	    	));
	    }
	    
	    // 메뉴의 접근권한 인증
	    if( !empty($datas['self']['grant_read']) ) $this->menu_access_authen($datas['self']['grant_read'], 'read') ;
	    
	    return (array) $datas ;
	}
	/**
	 * 메뉴 - 정보 가져오기
	 * @deprecated db의 모든것을 의존
	 * @param string $table (DB 테이블 명)
	 * @param string $mcode ( 메뉴코드 )
	 * 
	 * @return mixed
	 */
	protected function get_menu_orig( string $table, $mcode=NULL, $conditions=array() )
	{
		/**
		 * 웹 페이지 환경 데이타 가져오기
		 */
		//$CommNest_service = &WebApp::singleton("CommNest_service:service");
		//self::$menu_datas = $CommNest_service->getMenu("menu", $routeResult["mcode"]) ;
		
		/* return $this->getMenu($table, array(
				"serial" => $mcode,
				"columns" => "serial, indent, lft, rgt, title, layout, tpl, url, url_target, grant_read, grant_write",
				"conditions" => $conditions
		)) ; */
	    // 관리자 O (모든 메뉴보기) / 관리자 X (노출처리된 메뉴보기)
	    if( ! (int)$_SESSION['ADM'] ) {
	        /* if( !empty($mcode) ) $conditions = array_merge($conditions, array("(used=1 OR imp=1)")) ;
	        else $conditions = array_merge($conditions, array('imp'=>1)) ; */
	        $conditions = array_merge($conditions, array('imp'=>1)) ;
	    }
	    
		$datas =  $this->getMenu($table, array(
				"serial" => $mcode,
				"columns" => "serial, indent, lft, rgt, title, layout, tpl, url, url_target, used, imp, grant_read, grant_write",
				"conditions" => $conditions
		)) ;
		
		if( ! (int)$_SESSION['ADM'] && isset($datas['self']) && ! $datas['self']['used'] ) 
		{
			$this->WebAppService->assign(array(
					//'error_code' => $res,
					'error'=>'[406] 권한이 없습니다.',
					'redirect' => '/'
			));
		}
		// 메뉴의 접근권한 인증
		if( !empty($datas['self']['grant_read']) ) $this->menu_access_authen($datas['self']['grant_read'], 'read') ;
		
		return $datas ;
		//self::$menu_datas = $this->getNodeChilds(1, 'serial, indent, lft, rgt, title, url, url_target') ;
		
		//$relate_menu = $this->WebAppService->Func->array_searchKeyValue(self::$menu_datas['childs'], "serial", $this->routeResult['mcode'] || $_REQUEST['mcode']) ;
		//$selected_menu = count($relate_menu) > 1 ? end($relate_menu) : $relate_menu ;
	}
	/**
	 * 메뉴 - 환경 데이타 출력(템플릿엔진) 적용
	 * 
	 * @param int $mcode 메뉴코드
	 * @param array $conditions array('used'=>1, 'imp'=>1...)
	 * @access self::$menu_datas
	 * 
	 * @return void
	 * 
	 * @throws \Exception
	 */
	protected function menu_display_apply($mcode=0, $conditions=array())
	{
		if( !class_exists('Display') ) return false ;
		/**
		 * 사이트 메뉴 정보 가져오기
		 */
		if($mcode){
			
			if( !class_exists('WebAppService') ) $this->WebAppService->assign(array('error'=>'WebAppService 선언해주세요.'));
			
			self::$menu_datas = $this->get_menu('menu', (int) $mcode, $conditions);
			//if( !empty(self::$menu_datas['childs']) ) $this->TNst_renderTree(self::$menu_datas['childs']);
			if( empty(self::$menu_datas["self"]) ){
				$this->WebAppService->assign(array('error'=>'메뉴정보를 찾을 수 없습니다.'));
			}
			else{
				//if( class_exists('Display') ){
					
					$attach_top_file = self::$menu_datas['self']['attach_basedir']. self::$menu_datas['self']['attach_top'] ;
					$attach_bottom_file = self::$menu_datas['self']['attach_basedir']. self::$menu_datas['self']['attach_bottom'] ;
					
					$this->WebAppService->assign(array(
								'MNU' => self::$menu_datas
						));
				//}
			}
				
		}
		
		if( is_file($attach_top_file) ) $this->WebAppService->Display->define('ATTACH_TOP',  $attach_top_file) ;
		else $this->WebAppService->Display->define('ATTACH_TOP',  '') ;
		
		if( is_file($attach_bottom_file) ) $this->WebAppService->Display->define('ATTACH_BOTTOM',  $attach_bottom_file) ;
		else $this->WebAppService->Display->define('ATTACH_BOTTOM',  '') ;
	}
	
}