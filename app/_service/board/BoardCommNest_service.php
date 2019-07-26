<?php
namespace Gajija\service\board;
use Gajija\service\_traits\Service_Comm_Trait;
use Gajija\service\_traits\db\Service_DBCommNest_Trait;
use system\traits\DB_NestedSet_Trait;
use Gajija\model\board\BoardCommNest_model;


/**
 * 게시판(Board)
 * :: 일반형, 계층형.... 
 * 
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class BoardCommNest_service extends BoardCommNest_model 
{
		use Service_Comm_Trait, Service_DBCommNest_Trait, DB_NestedSet_Trait ;
		//use Service_Comm_Trait, Service_DBCommNest_Trait ;
		
		/**
		 * 첨부파일 저장 경로
		 * @var string ( default :  'html/_attach/board/' )
		 */
		//public static $attach_basedir = 'html/_attach/board/' ;
		//public static $attach_basedir = 'theme/'.THEME.'/_attach/board/';
		
		/**
		 * 게시판 스킨 기본경로
		 * @var string
		 */
		public static $skin_basedir = 'html/board/skin/' ;
		
		/**
		 * Service - 데이타 가져오기( 데이타 & 회원 )
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
		 * 											"pageBlock" => 15
		 * 					 				) ;
		 * @param integer $hierarchy (계층형인경우 1 )
		 * @param boolean $comments ( 댓글 인경우 true [sorting 변경]
		 * @return array
		 */
		
		/**
		 * 게시판 환경정보
		 * @param string $tableName (테이블명)
		 * @param array<key,value> $search_params (검색조건)
		 * @return array
		 */
		public function getBrd_info($tableName, $search_params)
		{
			if( !empty($tableName) && !empty($search_params) && is_array($search_params))
			{
				$this->setTableName($tableName);

				self::$_where = $this->sql_where($search_params);
				
				if(empty(self::$_where) ) return false;
				
				if( $res = $this->_read('*') )
					$res = array_pop($res);
				
				self::$_where = null ;

				return $res  ;
			}
		}
		
		/**
		 * 게시판 댓글정보
		 * @param string $tableName (테이블명)
		 * @param array<key,value> $search_params (검색조건)
		 * @return array
		 */
		public function getBrdComments_info($tableName, $search_params)
		{
			
			if( !empty($search_params) && !empty($search_params) && is_array($search_params))
			{
				
				$this->setTableName($tableName);
				
				self::$_where = $this->sql_where($search_params);
				
				if(empty(self::$_where) ) return false;

				if( $res = $this->_read('*') )
					$res = array_pop($res);
				
				self::$_where = null ;
				
				return $res  ;
			}
		}
		
		/**
		 * 게시판 카테고리+게시판+회원정보 조회
		 *
		 * @filesource JOIN - board_cate C, board B, member M
		 *
		 * @param array<key,value> $queryOption ( "columns"=>"", "conditions"=>"", "groupBy" =>"", "order"=>"")
		 * 				ex) $queryOption = array(
		 * 											"columns" => "column-name, column-name...",
		 * 											"conditions" => string or array(.......),
		 * 											"groupBy" => "column-name, column-name...",
		 * 											"order" => "?? desc, ?? asc...."
		 * 					 				) ;
		 */
		public function BoardCateMember( $queryOption )
		{
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;
			self::$_where = "";
			$this->Conditions( $queryOption["conditions"] ) ;
			
			//----------------------------------------------------------
			if( (int) $this->pageScale > 0)
			{
				/* $this->setTableName("shop_timesale T");
				 $cnt = $this->_count("J.t_serial") ; */
				$cnt = $this->_BoardCateMember("COUNT(B.serial) as cnt") ;
				if(!empty($cnt)) {
					$cnt = array_pop($cnt) ;
					$cnt = array_pop($cnt) ;//array_pop( array_pop($cnt) ) ;
				}
				
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
			$data = $this->_BoardCateMember(
					$queryOption["columns"],
					$queryOption["groupBy"],
					$queryOption["orderByColumns"],
					$limit_pageBlock
					);
			
			
			return $data ;
		}

}