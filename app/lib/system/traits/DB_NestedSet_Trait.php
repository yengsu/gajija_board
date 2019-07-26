<?php
namespace system\traits ;
/**
 * NestedSet 공용 클래스
 *
 */
trait DB_NestedSet_Trait
{
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
	protected function TNst_getValidate($vars)
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
	protected function TNst_Validation( $vars )
	{
		$error = $this->TNst_getValidate( $vars ) ;
		
		if( !empty($error) ){
			$this->WebAppService->assign(array(
					"error" => $error
			));
			//exit ;
		}
	}
	/**
	 * 추가
	 *
	 * @param array $put_data
	 * @param integer $parent_code
	 */
	protected function TNst_add( $put_data, $parent_code )
	{
		$this->TNst_Validation(array(
				"parent"
		)) ;
		
		if( empty($put_data) || !is_array($put_data) ){
			$this->WebAppService->assign(array(
					"error" => $error
			));
			//exit ;
		}
		else{
			/* $put_data = array(
			 "oid" => (int) OID,
			 "title" => $_POST["title"]
			 ); */
			
			$insert_id = $this->dataAdd( $put_data, $parent_code	) ;
			if($insert_id)
			{
				$this->WebAppService->assign(array(
						"serial" => $insert_id
				));
			}
			
		}
		return $insert_id;
	}
	
	/**
	 * 업데이트
	 *
	 * @param array $put_data (업데이트 데이타)
	 * @param array $conditions ( 조건문 )
	 */
	protected function TNst_update($put_data, $conditions)
	{
		$this->TNst_Validation(array(
				"serial"
		)) ;
		
		$res = $this->dataUpdate( $put_data, $conditions );
		return $res ;
		/* if(!$res){
		 //Exception
		 $this->WebAppService->assign(array(
		 "error" => "업데이트할 자료가 존재하지 않습니다."
		 ));
		 exit ;
		 } */
	}
	
	/**
	 * 삭제(자기 자신만)
	 * @param integer $serial (P.K)
	 */
	protected function TNst_delete( $serial )
	{
		$this->TNst_Validation(array(
				"serial"
		)) ;
		$this->dataNestDelete( $serial ) ;
	}
	
	/**
	 * 삭제 (자식노드 포함)
	 * @param integer $serial (P.K)
	 */
	protected function TNst_deleteContainChild( $serial )
	{
		$this->TNst_Validation(array(
				"serial"
		)) ;
		//$this->dataNestDelete($_POST['serial']) ;
		$this->dataNestDeleteContainChild( $serial );
	}
	
	/**
	 * 노드 이동
	 * @param array<key,value> $node
	 * @example $node = array(
	 * 									"serial" => (int) P.K
	 * 									"parent" => (int) 변경될 위치의 부모노드 P.K,
	 * 									"old_parent" => (int) 기존의 부모노드 P.K,
	 * 									"previous" => (int) 변경될 위치의 이전(prev)노드 P.K
	 * 							) ;
	 * @return void
	 */
	protected function TNst_move($node)
	{
		$this->TNst_Validation( array(
				"serial",
				"parent",
				"old_parent",
				"previous"
		)) ;
		
		if( empty($node) ) return ;
		
		if($node['previous'])
			$siblind_serial = $node['previous'] ;
			else
				$siblind_serial = $node['parent'] ;
				
				// 형제노드인 경우
				if($node['parent'] == $node['old_parent'])
				{
					$this->dataNestMoveSibling($node['serial'], $node['parent'], $siblind_serial);
				}
				else{
					$orig = $this->dataRead( array(
							"columns" => 'serial, family, indent, lft, rgt',
							"conditions" => array("serial" => $node['serial'])
					));
					$orig = array_pop($orig) ;
					$new_parent = $this->dataRead( array(
							"columns" => 'serial, family, indent, lft, rgt',
							"conditions" => array("serial" => $node['parent'])
					));
					$new_parent = array_pop($new_parent) ;
					
					$this->dataNestMoveChild($orig, $new_parent) ;
					//if( $node['previous'] ) $d = $this->dataNestMoveSibling($node['serial'], $node['parent'], $node['previous']);
					$d = $this->dataNestMoveSibling($node['serial'], $node['parent'], $siblind_serial);
					
				}
	}
	
	protected function TNst_create()
	{
		$this->TNst_hasData() ;
		
		$s = $this->dataList(
				array(
						"columns" => "serial, title, lft, rgt"
				)
				, true, true);
		
		$x = $this->TNst_jsTree($s);
		/* $pp = array(
		 'text' => 'HOME',
		 'id' => 'root',
		 'children' => $x
		 ); */
		//echo '<pre>';print_r($x);exit;
		$res = $this->WebAppService->json_Callback($x);
		
		return $res;
	}
	
	/**
	 * 트리구조 변환
	 *
	 * @param array &$tree ("indent" => 깊이값[depth], .......)
	 * @return array
	 *
	 * @access nestedSet인경우 사용전에 ★정렬( lft 항목 )★ 되었는지 확인바람
	 * @uses 정렬 함수 : Func::array_orderby($datas, 'lft', SORT_ASC);
	 */
	protected static function TNst_renderTree(&$tree) {
		$current_depth = 0;
		$counter = 0;
		
		//$result = '<ul>';
		$tree[0]['tag_first_start_g'] = 1;
		foreach($tree as $k => $node){
			$node_depth = $node['indent'];
			
			if($node_depth == $current_depth){
				if($counter > 0) {
					$tree[$k]['tag_close_d'] = 1 ;
					//$result .= '</li>';
				}
			}
			elseif($node_depth > $current_depth){
				//$result .= '<ul>';
				$tree[$k]['tag_start_g'] = 1;
				$current_depth = $current_depth + ($node_depth - $current_depth);
			}
			elseif($node_depth < $current_depth){
				//$result .= str_repeat('</li></ul>',$current_depth - $node_depth).'</li>';
				$tree[$k]['tag_close_dg'] = $current_depth - $node_depth ;
				$tree[$k]['tag_close_g'] = 1 ;
				$current_depth = $current_depth - ($current_depth - $node_depth);
			}
			//$result .= '<li id="c'.$node_id.'"';
			//$result .= $node_depth < 2 ?' class="open"':'';
			//$result .= '><a href="#"><ins>&nbsp;</ins>'.$node_name.'</a>';
			++$counter;
		}
		//$result .= str_repeat('</li></ul>',$node_depth).'</li>';
		//$result .= '</ul>';
		
		$tree[$k]['tag_end_close_dg'] = $node_depth ;
		$tree[$k]['tag_end_close_g'] = 1;
		
		return $tree; // 반환하지 않아도 됨
	}
	
	protected static function TNst_flattenList($source_arr, &$target_arr, $depth=0, $endInfo=array()) {
		
		$i = 0;
		$s = sizeof($source_arr);
		
		array_unshift($endInfo, $depth);
		$currentEndInfo = array();
		
		foreach ($source_arr as $k=>$v) {
			$target_arr[$k] = $v;
			$target_arr[$k]['depth'] = $depth;
			$target_arr[$k]['isFirst'] = (0==$i);
			$children = $target_arr[$k]['children'];
			unset($target_arr[$k]['children']);
			
			if (++$i==$s) {
				$currentEndInfo = $endInfo;
			}
			
			if (empty($children)) {
				$target_arr[$k]['endInfo'] = $currentEndInfo;
			} else {
				$target_arr[$k]['hasChild'] = true; // 추가된 부분
				self::TNst_flattenList($children, $target_arr, $depth+1, $currentEndInfo);
			}
		}
	}
	/**
	 * 트리구조 변환
	 *
	 * @param array $datas
	 * @param number $lft 입력시 자식(child) 노드 가져옴
	 * @param number $rgt 입력시 이전(prev) 노드 가져옴
	 */
	protected static function TNst_createTree($datas, $lft = 0, $rgt = null) {
		$tree = array();
		foreach ($datas as $cat => $range) {
			if ($range['lft'] == $lft + 1 && (is_null($rgt) || $range['rgt'] < $rgt)) {
				$tree[$cat]= $range;
				if($range['rgt']-$range['lft']>1){
					$tree[$cat]['children'] = self::TNst_createTree($datas, $range['lft'], $range['rgt']);
				}
				$lft = $range['rgt'];
			}
		}
		return $tree;
	}
	
	/**
	 * 트리구조 변환
	 *
	 * @param array $arrData ("serial"=>int, "title"=>string, "lft"=>int, "rgt"=>int, ....)
	 * @return array
	 */
	protected static function TNst_jsTree($arrData)
	{
		$stack = array();
		$arraySet = array();
		
		foreach( $arrData as $intKey=>$arrValues)
		{
			
			$stackSize = count($stack);
			while($stackSize > 0 && $stack[$stackSize-1]['rgt'] < $arrValues['lft']) {
				array_pop($stack);
				$stackSize--;
			}
			
			$link =& $arraySet;
			for($i=0;$i<$stackSize;$i++) {
				$link =& $link[$stack[$i]['id']]["children"]; //navigate to the proper children array
			}
			
			if( $arrValues['title'] == 'HOME' )
			{
				$arrValues['id'] = $arrValues['serial'] ;
				$arrValues['text'] = 'HOME' ;
			}else{
				$arrValues['id'] = 'mnu_'.$arrValues['serial'] ;
				//$arrValues['id'] = $arrValues['serial'] ;
				//$arrValues['text'] = $arrValues['id']."__".$arrValues['title'] ;
				$arrValues['text'] = $arrValues['title'] ;
				//$arrValues['path'] .= $arrValues['title']." / " ;
				//$child_cnt = round( (($arrValues['rgt']-$arrValues['lft'])-1)/2, 0) ;
				$child_cnt = floor( (($arrValues['rgt']-$arrValues['lft'])-1)/2 ) ;
				if( $child_cnt > 0 ) {
					$arrValues['childs'] = $child_cnt ;
					//$arrValues['attr'] = array( "rel" => "folder" )  ;
					//$arrValues['type'] = "folder"  ;
				}
			}
			
			$x = array_merge($arrValues, array ('children'=>array())) ;
			//echo '<pre>';print_r($x) ;
			$tmp = array_push($link,  $x);
			
			//$tmp = array_push($link,  array ('item'=>$arrValues,'children'=>array()));
			array_push($stack, array('id' => $tmp-1, 'rgt' => $arrValues['rgt']));
			
		}
		//비어있는 children 삭제
		//Func::array_searchKeyValue_remove($arraySet, "children", null ) ;
		
		return $arraySet;
	}
	/**
	 *	부모,자식노드 그룹처리( 계층형처리)
	 *
	 *	@param array $items array(serial=>Unique값, parent=>부모값[serial]......)
	 *	@return array
	 */
	protected static function buildMenu($items) {
		$childs = array();
		
		foreach($items as &$item) $childs[(int)$item['parent']][] = &$item;
		unset($item);
		foreach($items as &$item) if (isset($childs[$item['serial']]))
			$item['children'] = $childs[$item['serial']];
			
			$datas = array();
			foreach($childs as $data) array_push($datas, $data);
			$datas = array_unique($datas);
			unset($items);
			return array_pop($datas);
	}
	
	/**
	 *
	 * 트리구조 변환
	 *
	 * @param array $arrData ("lft" =>int, "rgt"=>int, .......)
	 * @return array
	 *
	 * @access nestedSet인경우 사용전에 ★정렬( lft 항목 )★ 되었는지 확인바람
	 * @uses 정렬 함수 : Func::array_orderby($datas, 'lft', SORT_ASC);
	 */
	protected static function TNst_buildTree($arrData)
	{
		$stack = array();
		$arraySet = array();
		
		foreach( $arrData as $intKey=>$arrValues) {
			
			$stackSize = count($stack);
			while($stackSize > 0 && $stack[$stackSize-1]['rgt'] < $arrValues['lft']) {
				array_pop($stack);
				$stackSize--;
			}
			
			$link =& $arraySet;
			for($i=0;$i<$stackSize;$i++) {
				$link =& $link[$stack[$i]['id']]["children"]; // 하위 배열로 이동
				//echo '<br>'.$link[$i]['title'].'<pre>';print_r(count($link));
			}
			$arrValues['indent'] = $stackSize ;
			//$child_cnt = round( (($arrValues['rgt']-$arrValues['lft'])-1)/2, 0) ;
			$child_cnt = round( (($arrValues['rgt']-$arrValues['lft'])-1)/2,0 ) ;
			if( $child_cnt > 0 ) {
				$arrValues['childs'] = $child_cnt ;
				//$arrValues['attr'] = array( "rel" => "folder" )  ;
				//$arrValues['type'] = "folder"  ;
				
			}else{
				
			}
			$x = array_merge($arrValues, array ('children'=>array()) ) ;
			$tmp = array_push($link,  $x);
			//if( isset($x['children']) ) $x['parent'] .= $arrValues['title']; //$x['path'] .= $arrValues['path'] ;
			//$tmp = array_push($link,  array ('item'=>$arrValues,'children'=>array()));
			array_push($stack, array('id' => $tmp-1, 'rgt' => $arrValues['rgt']));
			
		}
		
		return $arraySet;
	}
	
	/**
	 * [실험중] 사용금지
	 *
	 * @deprecated
	 * @param unknown $result
	 * @param string $prefix
	 * @param string $path
	 * @param unknown $level
	 * @return number|unknown[]|array
	 */
	protected static function TNst_nestedArray(&$result, $prefix='', $path = '', $level = -1) {
		$new = array();
		if(is_array($result)) {
			while(list($n, $sub) = each($result)) {
				$subId = $prefix.$sub['serial'];
				$new[$subId] = $sub;
				$new[$subId]['_path'] = $path.'/'.$sub['title'];
				$new[$subId]['_indent'] = $level + 1;
				
				if($sub['rgt'] - $sub['lft'] != 1) {
					
					$new[$subId]['_childs'] = floor( (($sub['rgt']-$sub['lft'])-1)/2 ) ;
					// recurse ($result is manipulated by reference!)
					$new[$subId]['children'] = self::TNst_nestedArray($result, $prefix,
							$new[$subId]['_path'], $new[$subId]['_indent']);
				}
				
				$next_id = key($result);
				if($next_id && $result[$next_id]['parent'] != $sub['parent']) {
					return $new;
				}
			}
		}
		return $new;
	}
	/**
	 * 데이타가 하나도 존재하지않는경우 생성
	 * --> HOME 메뉴(Root)를 생성
	 *
	 * @return void
	 */
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
		}
	}
	
	/**
	 * 카테고리 노드(node) 리스트조회
	 *
	 * @param String $prefix_name 식별 변수명 : ex) array(식별변수명=>결과값)
	 * @return void
	 * 		dataType[ajax] : json형 데이타 <br>
	 * 		dataType[http] : template_의 assign에 데이타 대입
	 */
	protected function TNst_getNodes( $prefix="" )
	{
		/* $s = $this->nodeChilds(
		 $this->routeResult["code"],
		 "serial, parent, family, memo, indent, lft, rgt"
		 ) ; */
		$this->TNst_hasData() ;
		
		$this->pageScale = 0 ; // 출력갯수( 0 이면 전체 출력됨 )
		
		//$s = $this->dataRead("serial, title,lft,rgt");
		$s = $this->dataList(
				array(
						"columns" => "serial, title,lft,rgt,
												FORMAT((((rgt - lft) -1) / 2),0) AS childs_cnt,
												CASE WHEN rgt - lft > 1 THEN 1 ELSE 0 END AS is_branch"
				)
				, 1, true);
		
		$x = self::TNst_jsTree($s);
		
		//if(REQUEST_WITH != 'AJAX' ) echo '<pre>';print_r($x);
		$x = (!empty($prefix)) ? array($prefix => array_pop($x)) : $x ;
		
		$this->WebAppService->assign( $x );
	}
	
}