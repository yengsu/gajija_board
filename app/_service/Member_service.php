<?php
namespace Gajija\service ;
use Gajija\model\Member_model;
use Gajija\service\_traits\Service_Comm_Trait;
use Gajija\service\_traits\db\Service_DBCommNest_Trait;
/**
 * 게시판 모델
 */
/**
 * 회원 서비스
 *  
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class Member_service extends Member_model
{
		use Service_Comm_Trait, Service_DBCommNest_Trait ;
		//use Singleton;
		
		
		
		/**
		 * 회원등급설정 & 회원 정보 조회
		 *
		 * @filesource TABLE( INNER JOIN - member_grade G, member M )
		 *
		 * @param array<key,value> $queryOption ( "columns"=>"", "order"=>"", "conditions"=>"")
		 * 				ex) $queryOption = array(
		 * 											"columns" => "column-name, column-name...",
		 * 											"order" => "?? desc, ?? asc....",
		 * 											"conditions" => string or array(.......),
		 * 											"groupBy" => "column-name, column-name..."
		 * 					 				) ;
		 * @return multitype:
		 */
		public function GradeMember( $queryOption, $hierarchy=false )
		{
			self::$_where = "";
			$this->Conditions( $queryOption["conditions"] ) ;
			
			//----------------------------------------------------------
			$queryOption["orderByColumns"] .= ($queryOption["orderByColumns"]) ? "," . $queryOption["order"] : $queryOption["order"] ;
			//----------------------------------------------------------
			$data = $this->_GradeMember(
					$queryOption["columns"],
					$queryOption["groupBy"],
					$queryOption["orderByColumns"]
				);
			
			return $data ;
		}
		
		/**
		 * 회원정보 및 회원 포인트 적립/사용 내역 조회
		 *
		 * @filesource TABLE( INNER JOIN - member M, member_grade G, member_point_history H )
		 *
		 * @param array<key,value> $queryOption ( "columns"=>"", "order"=>"", "conditions"=>"")
		 * 				ex) $queryOption = array(
		 * 											"columns" => "column-name, column-name...",
		 * 											"order" => "?? desc, ?? asc....",
		 * 											"conditions" => string or array(.......),
		 * 											"groupBy" => "column-name, column-name..."
		 * 					 				) ;
		 * @return multitype:
		 */
		public function PointHistoryMember( $queryOption, $hierarchy=false )
		{
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;
			self::$_where = "";
			$this->Conditions( $queryOption["conditions"] ) ;
			
			//----------------------------------------------------------
			if( (int) $this->pageScale > 0)
			{
				$cnt = $this->_PointHistoryMember("COUNT(H.serial) as cnt") ;
				if(!empty($cnt)) {
					$cnt = array_pop($cnt) ;
					$cnt = array_pop($cnt) ;//array_pop( array_pop($cnt) ) ;
				}
				/* $this->setTableName("member_point_history H");
				$cnt = $this->_count("H.serial") ; */
				
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
			$data = $this->_PointHistoryMember(
					$queryOption["columns"],
					$queryOption["groupBy"],
					$queryOption["orderByColumns"],
					$limit_pageBlock
				);
			
			return $data ;
		}
		
		/**
		 * 회원(자신) 포인트 적립/사용 내역 조회
		 *
		 * @filesource TABLE( member_point_history )
		 *
		 * @param array<key,value> $queryOption ( "columns"=>"", "order"=>"", "conditions"=>"")
		 * 				ex) $queryOption = array(
		 * 											"columns" => "column-name, column-name...",
		 * 											"order" => "?? desc, ?? asc....",
		 * 											"conditions" => string or array(.......),
		 * 											"groupBy" => "column-name, column-name..."
		 * 					 				) ;
		 * @return multitype:
		 */
		public function PointMyHistory( $queryOption, $hierarchy=false )
		{
			if(is_numeric($_REQUEST['itemPerPage'])) $this->pageScale = $_REQUEST['itemPerPage'] ;
			self::$_where = "";
			$this->Conditions( $queryOption["conditions"] ) ;
			
			//----------------------------------------------------------
			if( (int) $this->pageScale > 0)
			{
				/* $cnt = $this->_PointMyHistory("COUNT(H.serial) as cnt") ;
				if(!empty($cnt)) {
					$cnt = array_pop($cnt) ;
					$cnt = array_pop($cnt) ;//array_pop( array_pop($cnt) ) ;
				} */
				$this->setTableName("member_point_history");
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
			$data = $this->_PointMyHistory(
					$queryOption["columns"],
					$queryOption["orderByColumns"],
					$limit_pageBlock
					);
			
			return $data ;
		}
		
		
		/**
		 * 회원 로그인 유무 체크
		 *
		 * @param boolean $param( array('flag'=> boolean, 'mcode'=>메뉴코드, 'queryString'=>'......')
		 * @return boolean|void
		 * return값이 true이면 bool 리턴.
		 * return값이 false 또는 로그인 페이지이동.
		 */
		public static function hasLogin($param=NULL)
		{
			if($request_type == 'ajax' || REQUEST_WITH == 'AJAX' ) $param['flag'] = null ;

			if( !isset($_SESSION['MBRID']) || !trim($_SESSION['MBRID']) )
			{
				if( !$param['return'] )
				{
					$queryStr = '';
					if( !empty($param['queryString']) )
					{
						if( preg_match("/(^[?]+)/", $param['queryString']) ) $queryStr = $param['queryString'] ;
						else $queryStr = '?redir='.$param['queryString'] ;
					}
						
					if( $param['mcode'] )
						header("Location: ".$param['mcode']."/member/login".$queryStr);
					else
						header("Location: /member/login".$queryStr);
		
					exit;
				}else{
					return false ;
				}
			}
			return true ;
			//return ( !isset($_SESSION['MBRID']) ||  empty($_SESSION['MBRID']) ) ? false : true ;
		}
		
		/**
		 * 회원 로그아웃
		 *
		 * @return void
		 */
		public static function logout()
		{
			unset( $_SESSION );
		
			session_destroy();
		}
		/**
		 * 회원 등급에 따른 주문금액 할인율
		 *
		 * @param int $userGrade (회원등급 코드)
		 * @return array|null
		 */
		public function get_memberGrade_rate($userGrade)
		{
			$this->setTableName("member_grade");
			$data_grade = $this->dataRead(array(
					"columns" => "grade_name, benefit_discount_rate",
					"conditions" => array("grade_code" => $userGrade)
			)) ;
			if( !empty($data_grade) ) $data_grade = array_pop($data_grade);
			
			return $data_grade ;
		}
		/**
		 * 회원 사용가능한 총 포인트
		 * 
		 * @param int $userserial (회원P.K)
		 * @return number
		 */
		public function get_memberTotalPoint(int $userserial)
		{
			$this->setTableName("member_point_history");
			$point_usable_total = $this->dataRead(array(
					"columns"=> 'sum(point) as sum',
					"conditions" => "serial=".$userserial
			));
			return (!empty($point_usable_total)) ? array_pop(array_pop($point_usable_total)) : 0;
		}
}