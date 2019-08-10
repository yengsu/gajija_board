<?php
namespace Gajija\service\_traits\db ;

/**
 * Service-데이타(DB) 공용 메서드
 * 
  */
trait Service_DBCommNest_Trait
{
		/**
		 *  페이지당 출력 게시물 수
		 * @var integer ( default : 10 )
		 */
		protected $pageScale = 0 ;
		/**
		 * 노출시킬 페이징 블럭수
		 * @var integer ( default : 10 )
		 */
		protected $pageBlock = 0 ;
		/**
		 * Pagination - paging class Object
		 * @var object
		 */
		protected $Paging;
		/**
		 * 총 레코드 갯수(게시물 갯수)
		 * @var integer ( default : 0 )
		 */
		protected static $Total_cnt = 0 ;
		/**
		 * 게시물 출력번호(총갯수)
		 * @var integer ( default : 0 )
		 */
		protected static $view_num = 0 ;
		/**
		 * 페이지 변수
		 * @var string ( default : page )
		 */
		protected static $pageVariable = "page" ;
		
		
		public function __construct() 
		{
		}
		public function __destruct()
		{
			foreach($this as $k => &$obj){
				unset($this->$k);
			}
		}
		/**
		 * 총갯수
		 * @param mixed $column ( P.K )
		 * @param array<key,value> $conditions (조건절)
		 * @return integer 조회한 총 갯수
		 */
		protected function count($column, $conditions=null)
		{
			$this->Conditions( $conditions ) ;

			return $this->_count($column) ;		
		}
		
		/**
		 * 형제노드 간의 게시물 이동하기
		 * 
		 * Warning !! :: Nested Model( left, right ) 데이타인 경우 사용가능
		 * 
		 * @param integer $org_serial
		 * @param integer $parent_serial
		 * @param integer $siblind_serial
		 */
		protected function dataNestMoveSibling($org_serial, $parent_serial, $siblind_serial)
		{
			return $this->_nodeMoveSibling($org_serial, $parent_serial, $siblind_serial) ;
		}
		/**
		 * 선택된 노드의 자식노드로 이동(Append) 하기
		 * 
		 * Warning !! :: Nested Model( left, right ) 데이타인 경우 사용가능
		 * 
		 * @param array $orig ( key: serial, family, parent, lft, rgt.. )
		 * @param array $new_parent ( key: serial, family, parent, lft, rgt.. )
		 * @return boolean
		 */
		protected function dataNestMoveChild($orig, $new_parent)
		{
			return $this->_nodeMoveChild($orig, $new_parent) ;
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
		protected function dataNestMove($node)
		{
			if( empty($node) ) return ;
		
			// 형제노드인 경우
			if($node['parent'] == $node['old_parent'])
			{
				if($node['previous'])
					$siblind_serial = $node['previous'] ;
				else
					$siblind_serial = $node['parent'] ;
					
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
				
				if( $node['previous'] ) 
					$this->dataNestMoveSibling($node['serial'], $node['parent'], $node['previous']);
		
			}
		}
		/**
		 * 사용보류( 작업중~~~ )
		 * 노드 or 게시물 이동시키기
		 * 
		 * Warning !! :: Nested Model( left, right ) 데이타인 경우 사용가능
		 * 
		 * @param integer $origSerial (P.K or UNIQUE)
		 * @param integer $refSerial (P.K or UNIQUE)
		 * @throws \Exception
		 * @return array|null
		 * @deprecated
		 */
		protected function dataNestMoveTable($origSerial, $refSerial)
		{
			return $this->_nodeMoveComplex(
					array(
							"serial" => $origSerial,
							"table" => "comments"
					), 
					array(
							"serial"=>$refSerial,
							"table" => "comments"
					)
			);
			exit;

			try
			{
				$element = $this->dataRead("serial, bserial, indent, family, lft, rgt", array("serial"=>$origSerial) ) ;
				$reference = $this->dataRead("serial, bserial, indent, family, lft, rgt", array("serial"=>$refSerial) ) ;

				if( empty($element) )
					throw new \Exception('이동시킬 노드가 존재 하지 않습니다.', 500);
				else if( empty($reference) )
					throw new \Exception('이동될 대상 노드가 존재 하지 않습니다.', 500);
				
			}catch(\Exception $e){
				echo $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
				exit;
			}

			$res = $this->_nodeMove($element[0], $reference[0]) ;
			if( !empty($res) )
				$res = end( $res ) ;
				
			return $res ;
		}

		protected function dataNestDeleteContainChild($serial)
		{
			return $this->_nodeDeleteContainChild($serial) ;
		}
		
		protected function dataNestDelete($serial)
		{
			return $this->_nodeDeleteSelect($serial) ;
		}
		
		/**
		 * Service - 레코드 추가(신규등록)
		 * 
		 * @param array<key,value> $put_data ( 추가 데이타 )
	 	 * @param array<key,value> $idx ( serial... )
	 	 * @return int insert_id(P.K) 또는 결과값(1:true/0:false)
		 * @tutorial
		 * 			Return 값 관련정보 - 
		 * 			Primary Key 가 Auto increment 이면 => insert id 리턴.
		 * 														아니면 => 성공여부(1:성공 / 0:실패) 리턴
		 */
		protected function dataAdd($put_data, $idx=NULL)
		{
			if( !empty($idx) && (int)$idx > 0 )
			{
				$Adjacency = $this->dataAdjacency($idx) ;
				if( !empty($Adjacency) )
				{
					$put_data = array_merge($put_data, $Adjacency["put"]) ;
					
					$insert_id = $this->_addNested($put_data, $idx) ;
					if( !empty($insert_id) ) 
						$insert_id = end( $insert_id ) ;
					
					return $insert_id ;
				}
				else{
					//thrown
					//데이타가 존재하지 않습니다.
				}	
			}
			
			return $this->_add($put_data, $idx);
		}
		/**
		 * Service - 레코드 추가(신규등록)
		 *
		 * @param array<key,value> $put_data ( 추가 데이타 )
		 * @param array<key,value> $idx ( serial... )
		 * @return int insert_id(P.K) 또는 결과값(1:true/0:false)
		 * @tutorial
		 * 			Return 값 관련정보 -
		 * 			Primary Key 가 Auto increment 이면 => insert id 리턴.
		 * 														아니면 => 성공여부(1:성공 / 0:실패) 리턴
		 */
		protected function dataAddFamily($put_data, $idx=NULL)
		{
			if( !empty($idx) && (int)$idx > 0 )
			{
				$Adjacency = $this->dataAdjacency($idx) ;
				if( !empty($Adjacency) )
				{
					$put_data = array_merge($put_data, $Adjacency["put"]) ;
					
					$insert_id = $this->_addNested($put_data, $idx) ;
					if( !empty($insert_id) )
						$insert_id = end( $insert_id ) ;
						
						return $insert_id ;
				}
				else{
					//thrown
					//데이타가 존재하지 않습니다.
				}
			}
			
			return $this->_addFamily($put_data, $idx);
		}
		/**
		 * 게시물 인접데이타 등록위해 가공
		 * @param integer $idx
		 * @return multitype:number multitype:unknown  mixed
		 */
		protected function dataAdjacency($idx)
		{
			$parent_data = $this->dataRead(array(
					"columns" => "serial, family, indent", 
					"conditions" => array("serial"=>$idx)
			)) ;

			if( !empty($parent_data) )
			{
				$parent_data = array_pop($parent_data) ;
					
				$Adjacency["put"] = array(
						"family" => $parent_data["family"],
						"parent" => (int) $parent_data["serial"],
						"indent" => (int) $parent_data["indent"]+1
				) ;
			}
			return $Adjacency ;
		}
		/**
		 * Service - 업데이트 
		 * 
		 * @param array<key,value> $put_data ( 업데이트 데이타 )
		 * @param array<key,value> $conditions ( 검색 조건 데이타 )
		 * @return boolean
		 */
		protected function dataUpdate($put_data, $conditions)
		{
			$this->Conditions($conditions) ;
			return $this->_update($put_data) ;
		}
		/**
		 * Service - 삭제
		 *
		 * @param array<key,value> $conditions ( 검색 조건 데이타 )
		 * @return void
		 */
		protected function dataDelete($conditions)
		{
			$this->Conditions($conditions) ;
			return $this->_delete() ;
		}
		/**
		 * Service - 검색 조건
		 * 
		 * @param array $search_params
		 * @return self::$_where
		 */
		protected function Conditions($search_params)
		{	
			self::$_where = NULL;
			if( !empty($search_params) ) 
				self::$_where = $this->sql_where($search_params);	
		}
		
		/**
		 * Service - 데이타 읽어오기
		 *
		 * @param array<key,value> $queryOption ( "columns"=>"", "groupBy" => "", "order"=>"", "conditions"=>"")
		 * 
		 * @example $queryOption = array(
		 * 											"columns" => "column-name, column-name",
		 * 											"groupBy" => "column-name, column-name",
		 * 											"order" => "?? desc, ?? asc....",
		 * 											"conditions" => string or array(.......)
		 * 					 				) ;
		 * @return array 데이타
		 */
		protected function dataRead($queryOption)
		{
			$this->Conditions( $queryOption["conditions"] ) ;
			return $this->_read($queryOption["columns"], $queryOption["groupBy"], $queryOption["order"]);
		}
		
		/**
		 * 선택된 노드의 최하위 자식노드 정보 추출
		 *
		 * @param integer $code (family, serial)
		 * @param array<key,value> $queryOption ( "columns"=>"", "conditions"=>"")
		 * 	@example			ex) $queryOption = array(
		 * 											"columns" => "column-name, column-name",
		 * 											"conditions" => string or array(.......),
		 * 					 				) ;
		 * @example $conditions = array( family값, serial값 )
		 * @return boolean|multitype:
		 */
		protected function dataLastChild( $code, $queryOption=NULL )
		{
			if( empty($code)) return false ;

			$this->Conditions( $queryOption["conditions"] ) ;
			return $this->_nodeGetChild( $code, $queryOption["columns"], "last" );
		}
		/**
		 * Service - 리스트
		 * 
		 * @param array<key,value> $queryOption ( "columns"=>"", "order"=>"", "conditions"=>"")
		 * 				ex) $queryOption = array(
		 * 											"columns" => "column-name, column-name",
		 * 											"order" => "?? desc, ?? asc....",
		 * 											"conditions" => string or array(.......),
		 * 					 				) ;
		 * @param boolean $hierarchy (계층형인경우 1 )
		 * @param boolean $comments ( 댓글 인경우 true [sorting 변경]
		 * @return array
		 */
		protected function dataList( $queryOption, $hierarchy=0, $comments=false) //$Columns='*', $orderByColumns="serial")
		{
			self::$_where = '';
			self::$view_num = self::$Total_cnt = 0 ;
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;
			
			$this->Conditions( $queryOption["conditions"] ) ;
			//----------------------------------------------------------
			$limit_pageBlock = "" ;
			if( (int) $this->pageScale > 0)
			{
				$limit_pageBlock = $this->pageScale ;
				
				if($this->pageBlock){
					self::$Total_cnt = $this->_count("serial") ;
				
					$AllPage = self::$Total_cnt > 0 ? ceil( self::$Total_cnt / $this->pageScale ) : 0;
					if( !$AllPage || $AllPage < (int)$_REQUEST[self::$pageVariable] || !(int)$_REQUEST[self::$pageVariable]) $_REQUEST[self::$pageVariable] = 1 ;
					$perpage = ($_REQUEST[self::$pageVariable]-1) * $this->pageScale ;
					
					self::$view_num = self::$Total_cnt - $perpage ;
					
					$limit_pageBlock = $perpage.", ".$limit_pageBlock ;
				}
			}
			//----------------------------------------------------------
			$queryOption["orderByColumns"] = array();
			if( $hierarchy == 1 )
			{
				if( $comments ){
					// 등록한 순서로 정렬
					array_push($queryOption["orderByColumns"], "family, lft") ; // Asc
				}else{
					// 2 depth: 최신등록 순서로 정렬:
					array_push($queryOption["orderByColumns"], "family DESC, lft") ;
				}
			}
			else{
				//$queryOption["orderByColumns"] = "serial DESC" ;
			}

			if($queryOption["order"]) array_push($queryOption["orderByColumns"], $queryOption["order"]) ;
			//----------------------------------------------------------

			$data = $this->_listBase(
											$queryOption["columns"],
											join(",", $queryOption["orderByColumns"]),
											$limit_pageBlock
									);

			return $data ;
		}
		/**
		 * Service - [두개의 테이블 JOIN] 데이타 가져오기
		 * 
		 * @param array<key,value> $queryOption
		 * 		value : array(
		 * 						"tableA" => A 테이블명,
		 * 						"tableB" => B 테이블명,
		 * 						"columns"=>"", # 칼럼명, 칼럼명...
		 * 						"order"=>"", # 정렬
		 * 						"conditions"=>"", # 조건문
		 * 						"join" => NULL # sql join(LEFT, INNER...) 형태
		 * 						"on" => "A.serial=B.join_code",
		 * 						"pageBlock" => NULL # LIMIT문( 가져올 레코드 블럭(?, ?) 또는 갯수 )
		 * 					)
		 *
		 * @example $queryOption = array(
		 * 											"tableA" => "board_cate",
		 * 											"tableB" => "board",
		 * 											"columns" => "column-name, column-name",
		 * 											"conditions" => string or array(.......)
		 * 											"join" => "left" (sql join 형태)
		 * 											"on" => "A.serial=B.join_code",
		 * 											"order" => "?? desc, ?? asc...."
		 *
		 * 					 				) ;
		 * @param boolean $hierarchy (계층형인경우 1 )
		 * @param boolean $comments ( 댓글 인경우 true [sorting 변경]
		 * @return array
		 */
		protected function dataJoin( $queryOption, $hierarchy=0, $comments=false) //$Columns='*', $orderByColumns="serial")
		{
			self::$_where = '';
			self::$view_num = self::$Total_cnt = 0 ;
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;
			
			//----------------------------------------------------------
			$limit_pageBlock = "" ;
			if( (int)$this->pageScale > 0 )
			{
				$limit_pageBlock = $this->pageScale ;
				
				if($this->pageBlock){
					$data_cnt = $this->_readJoin(
														$queryOption["tableA"],
														$queryOption["tableB"],
														"COUNT(A.serial) AS cnt", 
														$queryOption["conditions"],
														$queryOption["join"],
														$queryOption["on"]
													);
					if(!empty($data_cnt)) 
					{
						$res_cnt = array_pop($data_cnt) ;
						self::$Total_cnt = array_pop($res_cnt) ;
					}
					
					$AllPage = self::$Total_cnt > 0 ? ceil( self::$Total_cnt / $this->pageScale ) : 0;
					if( !$AllPage || $AllPage < (int)$_REQUEST[self::$pageVariable] || !(int)$_REQUEST[self::$pageVariable]) $_REQUEST[self::$pageVariable] = 1 ;
					$perpage = ($_REQUEST[self::$pageVariable]-1) * $this->pageScale ;
					
					self::$view_num = self::$Total_cnt - $perpage ;
					
					$limit_pageBlock = $perpage.", ".$limit_pageBlock ;
				}
			}
			//----------------------------------------------------------
			$queryOption["orderByColumns"] = array();
			if($queryOption["order"]) array_push($queryOption["orderByColumns"], $queryOption["order"]) ;
			//----------------------------------------------------------
			$data = $this->_readJoin(
					$queryOption["tableA"],
					$queryOption["tableB"],
					$queryOption["columns"],
					$queryOption["conditions"],
					$queryOption["join"],
					$queryOption["on"],
					join(",", $queryOption["orderByColumns"]),
					$limit_pageBlock
					);
			
			return $data ;
			
		}
		/**
		 * Service - 업체&리스트
		 *
		 * @param array<key,value> $queryOption
		 * 		value : array(
		 * 						"columns"=>"", # 칼럼명, 칼럼명...
		 * 						"order"=>"", # 정렬
		 * 						"conditions"=>"", # 조건문
		 * 						"join" => NULL # sql join(LEFT, INNER...) 형태
		 * 						"pageBlock" => NULL # LIMIT문( 가져올 레코드 블럭(?, ?) 또는 갯수 )
		 * 					)
		 *
		 * @example $queryOption = array(
		 * 											"columns" => "column-name, column-name",
		 * 											"conditions" => string or array(.......)
		 * 											"join" => "left" # sql join 형태
		 * 											"order" => "?? desc, ?? asc...."
		 * 											
		 * 					 				) ;
		 * @param boolean $hierarchy (계층형인경우 1 )
		 * @param boolean $comments ( 댓글 인경우 true [sorting 변경]
		 * @return array
		 */
		protected function dataListAndOid( &$queryOption, $hierarchy=0, $comments=false) //$Columns='*', $orderByColumns="serial")
		{
			self::$_where = '';
			self::$view_num = self::$Total_cnt = 0 ;
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;

			//----------------------------------------------------------
			$limit_pageBlock = "" ;
			if( (int)$this->pageScale > 0 ) //&& (int)$_REQUEST[self::$pageVariable] > 0)
			{
				$limit_pageBlock = $this->pageScale ;
				
				if($this->pageBlock){
					$data_cnt = $this->_readAndOid("COUNT(B.serial) AS cnt", $queryOption["conditions"], $queryOption["join"]);//, $queryOption["order"]);
					$res_cnt = array_pop($data_cnt) ;
					self::$Total_cnt = array_pop($res_cnt) ;
					
					$AllPage = self::$Total_cnt > 0 ? ceil( self::$Total_cnt / $this->pageScale ) : 0;
					if( !$AllPage || $AllPage < (int)$_REQUEST[self::$pageVariable] || !(int)$_REQUEST[self::$pageVariable]) $_REQUEST[self::$pageVariable] = 1 ;
					$perpage = ($_REQUEST[self::$pageVariable]-1) * $this->pageScale ;
					
					self::$view_num = self::$Total_cnt - $perpage ;
				
					$limit_pageBlock = $perpage.", ".$limit_pageBlock ;
				}
			}
			//----------------------------------------------------------
			$queryOption["orderByColumns"] = array();
			if( $hierarchy == 1 )
			{
				if( $comments ){
					// 등록한 순서로 정렬
					 array_push($queryOption["orderByColumns"], "B.family, B.lft") ; // Asc
				}else{ 
					// 2 depth: 최신등록 순서로 정렬: 
					array_push($queryOption["orderByColumns"], "B.family DESC, B.lft") ;
					
				}
			}
			else{
				//$queryOption["orderByColumns"] = "B.serial DESC" ;
			}
			
			if($queryOption["order"]) array_push($queryOption["orderByColumns"], $queryOption["order"]) ;
			//----------------------------------------------------------
				
			$data = $this->_readAndOid(
					$queryOption["columns"],
					$queryOption["conditions"],
					$queryOption["join"],
					join(",", $queryOption["orderByColumns"]),
					$limit_pageBlock
			);
		
			return $data ;
				
		}
		
		/**
		 * Service - 회원&리스트
		 *
		 * @param array<key,value> $queryOption
		 * 		value : array(
		 * 						"columns"=>"", # 칼럼명, 칼럼명...
		 * 						"order"=>"", # 정렬
		 * 						"conditions"=>"", # 조건문
		 * 						"join" => NULL # sql join(LEFT, INNER...) 형태
		 * 						"pageBlock" => NULL # LIMIT문( 가져올 레코드 블럭(?, ?) 또는 갯수 )
		 * 					)
		 *
		 * @example $queryOption = array(
		 * 											"columns" => "column-name, column-name",
		 * 											"conditions" => string or array(.......)
		 * 											"join" => "left" # sql join 형태
		 * 											"order" => "?? desc, ?? asc...."
		 * 											
		 * 					 				) ;
		 * @param boolean $hierarchy (계층형인경우 1 )
		 * @param boolean $comments ( 댓글 인경우 true [sorting 변경]
		 * @return array
		 */
		protected function getDataAndMbr( $queryOption, $hierarchy=0, $comments=false) //$Columns='*', $orderByColumns="serial")
		{
			self::$_where = '';
			self::$view_num = self::$Total_cnt = 0 ;
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;
		
			//----------------------------------------------------------
			$limit_pageBlock = "" ;
			if( (int)$this->pageScale > 0 )
			{
				$limit_pageBlock = $this->pageScale ;
		
				if($this->pageBlock){
					$data_cnt = $this->_readAndMbr("COUNT(B.serial) AS cnt", $queryOption["conditions"], $queryOption["join"]);//, $queryOption["order"]);
					$res_cnt = array_pop($data_cnt) ;
					self::$Total_cnt = array_pop($res_cnt) ;
						
					$AllPage = self::$Total_cnt > 0 ? ceil( self::$Total_cnt / $this->pageScale ) : 0;
					if( !$AllPage || $AllPage < (int)$_REQUEST[self::$pageVariable] || !(int)$_REQUEST[self::$pageVariable]) $_REQUEST[self::$pageVariable] = 1 ;
					$perpage = ($_REQUEST[self::$pageVariable]-1) * $this->pageScale ;
						
					self::$view_num = self::$Total_cnt - $perpage ;
		
					$limit_pageBlock = $perpage.", ".$limit_pageBlock ;
				}
			}
			//----------------------------------------------------------
			$queryOption["orderByColumns"] = array();
			if($queryOption["order"]) array_push($queryOption["orderByColumns"], $queryOption["order"]) ;
			if( $hierarchy == 1 )
			{
				if( $comments ){
					// 등록한 순서로 정렬
					array_push($queryOption["orderByColumns"], "B.family, B.lft") ; // Asc
				}else{
					// 2 depth: 최신등록 순서로 정렬:
					array_push($queryOption["orderByColumns"], "B.family DESC, B.lft") ;
				}
			}
			else{
				//$queryOption["orderByColumns"] = "B.serial DESC" ;
			}
			//----------------------------------------------------------
			$data = $this->_readAndMbr(
					$queryOption["columns"],
					$queryOption["conditions"],
					$queryOption["join"],
					join(",", $queryOption["orderByColumns"]),
					$limit_pageBlock
			);
		
			return $data ;
				
		}
		/**
		 * 최상위부터 해당 노드의 깊이(path)까지 관련 단일노드 뽑기
		 *
		 * @param array<key,value> $queryOption ( "columns"=>array<value>, "conditions"=>array<key,value>)
		 * 
		 * @example $queryOption = array(
		 * 											"columns" => array("column-name, column-name"),
		 * 											"conditions" => string or array(.......)
		 * @return array
		 */
		protected function getNodePath( $queryOption )
		{
			if( empty($queryOption["conditions"]) || !is_array($queryOption["conditions"]) ) return false ;
			
			foreach($queryOption["conditions"] as $k => $v)
			{
				if( empty($v) ) return false;
				
				if(preg_match("/[.]/i", $k) ) continue ;
				$queryOption["conditions"]['N.'.$k] =$v ;
				unset($queryOption["conditions"][$k]);
			}
			$this->Conditions( $queryOption["conditions"] ) ;
			
			if( !is_array($queryOption["columns"]) ){
				$Columns = explode(",", $queryOption["columns"]) ;
				$queryOption["columns"] = $Columns ;
			}
			return $this->_nodeGetPath( $queryOption["columns"] );
		}
		/**
		 * 자식노드 추출
		 * @param integer $serial ( P.K )
		 * @param array<key,value> $queryOption ( "columns"=>array<value>, "conditions"=>string or array<key,value>)
		 * @param string $opt ( 최하위노드 추출시 'last'.......)
		 * @return multitype:
		 */
		protected function getNodeChilds($serial, $queryOption, $opt='')
		{
			if( !empty($queryOption["conditions"]) )
			{
				foreach($queryOption["conditions"] as $k => $v)
				{
					if( empty($v) ) return false;
						
					if(preg_match("/[.]/i", $k) ) continue ;
					$queryOption["conditions"]['N.'.$k] =$v ;
					unset($queryOption["conditions"][$k]);
				}
			}
			$this->Conditions( $queryOption["conditions"] ) ;
			
			if( !is_array($queryOption["columns"]) ){
				$Columns = explode(",", $queryOption["columns"]) ;
				$queryOption["columns"] = $Columns ;
			}
			return $this->_nodeGetChild( $serial, $queryOption["columns"], $opt) ;
		}
		/**
		 * 
		 * @param array $datas ('lft'=> int, 'rgt'=> int,  .....)
		 * @param array $read ('lft'=> int, 'rgt'=> int)
		 * @return array
		 */
		protected function getPath($datas, $read)
		{
			//$read = Func::array_searchKeyValue($datas, $key, $keyValue) ;
			
			$res = array();
			//$depth = $read['depth'] -- ;
			foreach( $datas as &$item ){ // && $datas[$key]['depth']==$depth
				if( $item['lft'] <= $read['lft'] && $item['rgt'] >= $read['rgt'] ){
					
					array_push($res, $item) ;
					
				}
			}
			
			return $res ;
		}
		/**
		 * 메뉴 데이타를 가져옴
		 *
		 * @param string $table (테이블명)
		 * @param array<key,value> $queryOption ( "columns"=>"", "order"=>"", "conditions"=>"")
		 * 				ex) $queryOption = array(
		 * 											"columns" => "column-name, column-name",
		 * 											"serial" => integer value
		 * 					 				) ;
		 * @return mixed self::$menu_datas = array("root" =>array, "base" => array, 'childs'=>array, 'until'=>array, 'path'=>array)
		 *
		 * @example self::$menu_datas = array(
		 * 						"root" => array,	// Root 노드
		 * 						"base" => array,	// 1차메뉴
		 * 						"childs" => array,	// 자신을 포함한 자식노드
		 * 						"until" => array,	// 최상위부터 찾으려는 위치까지의 모든노드
		 * 						"path" => array,	// 최상위부터 찾으려는 위치까지의 단일노드
		 * 					);
		 */
		protected function getMenu( $table, $queryOption )
		{
			if( empty($queryOption["columns"]) ) $queryOption["columns"] = "*" ;
			
			$prev_table = static::$TABLE ; // 이전에 선언한 테이블 정보
			
			$this->setTableName($table);
			
			if( !empty($queryOption['conditions']) && is_array($queryOption['conditions']) )
			{
				//$conditions = array_merge( array("indent"=>1, "used"=>1), $queryOption['conditions'] ) ;
				
				/* 
				 * indent 칼럼을 선언했는지 체크 
				 * 선언이 안되있으면 기본적으로 1차메뉴만 추출
				 */
				$exist_indent = false ; 
				foreach($queryOption["conditions"] as $k => $val){
					if( strpos(strtolower($k), "indent") !== false || strpos(strtolower($val), "indent") !== false) {
						$exist_indent = true ;
						break ;
					}
				}
				if( $exist_indent === true ) $conditions = $queryOption['conditions'] ;
				else $conditions = array_merge( array("indent"=>1), $queryOption['conditions'] ) ;
			}
			else{ 
				//$conditions = array( "indent"=>1, "used"=>1, "imp"=>1 ) ;
				$conditions = array( "indent"=>1) ;
			}
			
			$menu['base'] = $this->dataRead(array(
					"columns" => $queryOption["columns"],
					"conditions" => $conditions,
					"order" => "lft"
			));
			
			if( !empty($queryOption["serial"]) )
			{
				// 대메뉴 누르면 서브페이지 1차메뉴 자식노드 뽑기
				$indent_1 = $this->getNodePath(array(
						"columns" => "serial",
						//"conditions" => array("serial"=>$queryOption["serial"], "P.indent"=>1, "P.imp"=>1)
						//"conditions" => array("serial"=>$queryOption["serial"], "P.indent"=>1, "P.used"=>(int) $conditions['used'])
						"conditions" => array("serial"=>$queryOption["serial"], "P.indent"=>1)
				));

				//if( isset($queryOption['conditions']['used']) ) $child_conditions['used'] = $queryOption['conditions']['used'] ;
				//if( isset($queryOption['conditions']['imp']) ) $child_conditions['imp'] = $queryOption['conditions']['imp'] ;
				$child_conditions['used'] = 1 ;
				//echo '<pre>';print_r($child_conditions) ;
				$menu['childs'] = $this->getNodeChilds(
						$indent_1[0]['serial'],
						//$queryOption["serial"],
						
						array(
								"columns" => $queryOption["columns"]
						       ,"conditions" => $child_conditions // array("used"=>1, "imp"=>1)
								//"conditions" => array("used"=>1)
								//,"conditions" => $conditions
						)) ;
				
				/* $menu['until'] = $this->getNodeUntil(array(
				 "columns" => "serial, indent, lft, rgt, title, url, url_target",
				 "conditions" => array("serial" => $mcode)
				 )); */
				if( !empty($menu['childs']) && is_array($menu['childs']) )
				{
					$Func = \WebApp::singleton('Func');
					$read = $Func::array_searchKeyValue($menu['childs'], 'serial', $queryOption["serial"]) ;
										
					if( !empty($read) ){
						$menu['path'] = $this->getPath($menu['childs'], $read);
						$Func->array_searchKeyValue_remove($menu['path'], "indent",0); // home remove
					}
					
					/* $menu['path'] = $this->getNodePath(array(
							"columns" => $queryOption["columns"],
							//"conditions" => array("serial"=>$mcode)
							"conditions" => array("serial"=>$queryOption["serial"], "imp"=>1)
					)); */
					

					foreach($menu['childs'] as $k => $v){
						
						if( !empty($menu['path']) ){
							if($Func::array_exist_multi_search($v['serial'], $menu['path']))
								$menu['childs'][$k]['is_select'] = 1;
						}
						
						if($queryOption["serial"] == $v['serial']) $menu['self'] = $v ;
						
						$path[] = $v ;
						if($v['serial'] == $queryOption["serial"])
							break;
					}
					
					
					if( !empty($menu['childs']) ) $this->TNst_renderTree($menu['childs']);
					$Func->array_searchKeyValue_remove($menu['childs'], "indent",0);
				}
				
				
			}
			//echo '33<pre>';print_r($menu);exit;
			unset( $read );
			/* ['path']는
			 * ==> 아래소스를 이용하여 DB로부터 직접 가져와도 됨
			 *
			 self::$menu_datas['path'] = $this->getNodePath(array(
			 "columns" => array("serial", "indent", "lft", "rgt", "title", "url", "url_target"),
			 "conditions" => array("serial"=>$mcode)
			 ));
			 */
			static::$TABLE = $prev_table ; // 이전에 사용하던 테이블로 재선언
			
			return $menu ;
		}
		
		/**
		 * auto_increment 생성해서 가져오기
		 *
		 * @param integer $lastNum ( 최근 auto_increment [값이 없으면 기존 키값에서 생성] )
		 * @return integer insert_id값
		 */
		protected function getInsertID( $lastNum )
		{  
			return $this->_get_insertID($lastNum) ;
		}
		/**
		 * Service - 페이지네이션( paging )
		 * 
		 * @return array $pagings
		 */
		protected function Pagination($page, $qs='')
		{
			$this->Paging = \WebApp::singleton('Paging');
			$this->Paging->config["pageVariable"] = self::$pageVariable ;
			$this->Paging->index(self::$Total_cnt, $qs, (int)$page);// (int)$page);
		
			$this->Paging->config['itemPerPage'] = $this->pageScale ; // 리스트 목록수
			$this->Paging->config['pagePerView'] = $this->pageBlock ; // 페이지당 네비게이션 항목수
		
			$pagings = $this->Paging->output();

			return $pagings ;
		}
		
		/**
		 * 신규등록&저장 두가지 처리
		 * 
		 * @tutorial 데이타가 존재하면 update, 없으면 insert (단! UNIQUE 설정되어있어야함)
		 * 
		 * @param array $put_data
		 *        array(
		 *                  칼럼명=>값,
		 *                  칼럼명=>값......
		 *        )
		 * @param String $update_values
		 * 			"
		 * 			skin_width=VALUES(skin_width),
		 *			skin_height=VALUES(skin_height)
		 *			"
		 */
		protected function dataInsertUpdate($put_data, $update_values)
		{
			try {
				return $this->_insertUpdate($put_data, $update_values) ;
			} catch (\Exception $e) {
				//echo '------<pre>';print_r($e);
				echo $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
				exit;
			}
			
		}
		
		/**
		 *  Root노드부터 찾으려는 노드까지 뽑기
		 *
		 * @param array<key,value> $queryOption ( "columns"=>"", "conditions"=>"")
		 *
		 * @example $queryOption = array(
		 * 											"columns" => "column-name, column-name",
		 * 											"conditions" => string or array(.......)
		 * 					 				) ;
		 * @return boolean|array
		 */
		protected function getNodeUntil( $queryOption )
		{
			if( empty($queryOption["conditions"]) ) return false ;
		
			$this->Conditions( $queryOption["conditions"] ) ;
			return $this->_nodeGetUntil($queryOption["columns"]) ;
		}
		/**
		 * 카테고리 & 정보 조회
		 *
		 * @filesource TABLE( 카테고리테이블명 parent )
		 * @tutorial (★ 필수 ★) groupBy 
		 * @tutorial (★ 필수 ★) 테이블(dataTable명)의 column "cate" 필수로 존재해야함
		 * 
		 * @param array<key,value> $queryOption ( "columns"=>"", "conditions"=>"", "groupBy" =>"", "order"=>"")
		 * 				ex) $queryOption = array(
		 * 											"cateTable" => category table-name(★ 필수 ★),
		 * 											"dataTable" => data table-name(★ 필수 ★),
		 * 											"columns" => "column-name, column-name...",
		 * 											"conditions" => string or array(.......),
		 * 											"groupBy" => "column-name, column-name...",
		 * 											"order" => "?? desc, ?? asc...."
		 * 					 				) ;
		 * @return multitype:
		 */
		protected function CateGroupyList( $queryOption )
		{
			if(empty($queryOption['cateTable']) || empty($queryOption['dataTable'])) return false ;
			
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;
			self::$_where = "";
			$this->Conditions( $queryOption["conditions"] ) ;
			
			//----------------------------------------------------------
			if( (int) $this->pageScale > 0)
			{
				$this->setTableName($queryOption['cateTable']);
				$cnt = $this->_count("serial") ;
				
				self::$Total_cnt = $cnt ;
				
				if((!$AllPage = ceil( self::$Total_cnt / $this->pageScale )) || $AllPage < (int)$_REQUEST[self::$pageVariable] || !(int)$_REQUEST[self::$pageVariable]) $_REQUEST[self::$pageVariable] = 1 ;
				$perpage = ($_REQUEST[self::$pageVariable]-1) * $this->pageScale ;
				
				self::$view_num = self::$Total_cnt - $perpage ;
				
				if($this->pageScale && $this->pageBlock)
					$limit_pageBlock = $perpage.", ".$this->pageScale ;
			}
			//----------------------------------------------------------
			$queryOption["orderByColumns"] .= ($queryOption["orderByColumns"]) ? "," . $queryOption["order"] : $queryOption["order"] ;
			//----------------------------------------------------------
			$data = $this->_CateGroupyList(
					$queryOption['cateTable'], // 카테고리 테이블명
					$queryOption['dataTable'], // 데이타 테이블명
					$queryOption["columns"],
					$queryOption["groupBy"],
					$queryOption["orderByColumns"],
					$limit_pageBlock
					);
			
			
			return $data ;
		}
}