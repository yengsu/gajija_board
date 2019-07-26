<?
namespace Gajija\model ;
/**
 * 회원 관련
 * @author young lee
 * @email yengsu@gmail.com 
 */
class Member_model extends CommNest_model
{
	/**
	 * 회원등급설정 & 회원 정보 조회
	 * @filesource TABLE( INNER JOIN - member_grade G, member M )
	 * @param string $Columns( column명,column명,column명... ) ; 내장함수 사용금지
	 * @param string $GroupBy
	 * @param string $orderByColumns
	 * @param string $pageBlock
	 * @return array
	 */
	protected function _GradeMember($Columns="", $GroupBy="", $orderByColumns="") //$pageBlock="0,15"
	{
		if(!$Columns)
			return false ;
		
		$sql = "
			SELECT
				".$Columns."
			FROM
				member_grade G -- 회원등급 설정정보
			LEFT JOIN
				member M -- 회원 정보
			ON
				G.grade_code=M.grade
			" ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"];
		
		if( !empty($GroupBy) )
			$sql .= " GROUP BY ". $GroupBy ;
				
		if( $orderByColumns ){
			$sql .= " ORDER BY ". $orderByColumns ;
		}
		
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->DBconn();
		$data = $this->DB->rawQuery($sql, $sql_params);
		self::$_where = NULL ;
		
		
		return $data ;
	}
	
	/**
	 * 회원정보 및 회원 포인트 적립/사용 내역 조회
	 * @filesource TABLE( INNER JOIN - member M, member_grade G, member_point_history H )
	 * @param string $Columns( column명,column명,column명... ) ; 내장함수 사용금지
	 * @param string $GroupBy
	 * @param string $orderByColumns
	 * @param string $pageBlock
	 * @return array
	 */
	protected function _PointHistoryMember($Columns="", $GroupBy="", $orderByColumns="", $pageBlock=NULL) //$pageBlock="0,15"
	{
		if(!$Columns)
			return false ;
			
		$sql = "
		SELECT
			".$Columns." 
		FROM 
				member M -- 회원정보 
			INNER JOIN 
				member_grade G -- 회원등급 설정 정보 
			ON 
				M.grade=G.grade_code 
			
				INNER JOIN 
					member_point_history H -- 회원 포인트 적립 및 사용 내역 
				ON 
					M.userid=H.userid 
		" ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"];
			
		if( !empty($GroupBy) )
			$sql .= " GROUP BY ". $GroupBy ;
			
		if( $orderByColumns ){
			$sql .= " ORDER BY ". $orderByColumns ;
		}
		if( !empty($pageBlock) )
			$sql .= " LIMIT ".$pageBlock;
		
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->DBconn();
		$data = $this->DB->rawQuery($sql, $sql_params);
		
		return $data ;
	}
	
	/**
	 * 회원(자신) 포인트 적립/사용 내역 조회
	 * 
	 * @filesource TABLE( INNER JOIN - member M, member_grade G, member_point_history H )
	 * @param string $Columns( column명,column명,column명... ) ; 내장함수 사용금지
	 * @param string $GroupBy
	 * @param string $orderByColumns
	 * @param string $pageBlock
	 * @return array
	 */
	protected function _PointMyHistory($Columns="", $orderByColumns="", $pageBlock=NULL) //$pageBlock="0,15"
	{
		if(!$Columns)
			return false ;
			
		$sql = "
		SET @sum := 0, @val:=null;
		SELECT
			".$Columns."
			,CASE
				WHEN @val is NULL THEN
					@sum:= point
				ELSE
					@sum:= @sum + point
			END as cur_point,
			CASE
				WHEN @val is NULL THEN
					@val:= userid
			END as rep
		FROM
				member_point_history 
		" ;
			
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"];
				
		if( !empty($GroupBy) )
			$sql .= " GROUP BY ". $GroupBy ;
		
		$sql .= " ORDER BY rep " ;
		if( $orderByColumns ){
			//$sql .= " ORDER BY rep, ". $orderByColumns ;
			$sql .= ", ". $orderByColumns ;
		}
		
		if( !empty($pageBlock) )
			$sql .= " LIMIT ".$pageBlock;
		$sql .= ';';
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->DBconn();
		//$data = $this->DB->rawQuery($sql, $sql_params);
		$data = $this->DB->multiQuery($sql, $sql_params);
		
		return $data ;
	}
	
}