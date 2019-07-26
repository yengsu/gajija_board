<?
namespace Gajija\model\board;
use Gajija\model\CommNest_model;

/**
 * 게시판 관련
 * @author youngsu lee
 * @email yengsu@gmail.com 
 */

class BoardCommNest_model extends CommNest_model
{
	
	/**
	 * 보드 신규추가
	 *
	 * @param array $put_data
	 *        array(
	 *                  칼럼명=>값,
	 *                  칼럼명=>값......
	 *        )
	 * @return int insert_id(P.K)
	 */
	public function _add( $put_data, $idx )
	{
		$this->DBconn();
	
		$sql_params = self::sql_paramsProcess("insert", $put_data);
		$sql = "
				INSERT INTO
					" .self::$TABLE. "
				( ". $sql_params["columns"] ." )
				VALUES
				( ". $sql_params["sign_values"]." )" ;
	
		$this->DB->rawQuery($sql, $sql_params["values"]);
		$getInsertId = $this->DB->getInsertId();
	
		if(!empty($idx)) $family = $idx;
		else $family = $getInsertId ;
		
		// 확장해서 계층형으로 데이타처리를 위해 필요한 필드수정
		if(self::$TABLE != "board_info" && self::$TABLE != "comments_info")
		{
			$sql = "UPDATE " .self::$TABLE. " SET family=? WHERE serial=?" ;
			$this->DB->rawQuery( $sql, array($family, $getInsertId) );
		}
		
		return $getInsertId ;
	}
	
	
	/**
	 *  게시판 카테고리+게시판+회원정보 조회
	 *
	 * @filesource JOIN - board_cate C, board B, member M
	 * @param string $Columns
	 * @param string $GroupBy
	 * @param string $orderByColumns
	 * @param string $pageBlock
	 * @return array
	 */
	protected function _BoardCateMember($Columns="", $GroupBy="", $orderByColumns="", $pageBlock=NULL)
	{
		//self::$_where = $this->sql_where($conditions) ;
		if( ! self::$_where ) return false ;
		
		if(!$Columns)
			return false ;
		
		$sql = "
			SELECT
				".$Columns."
			FROM
					board B
				INNER JOIN
					board_cate C
				ON
					C.serial=B.cate AND C.indent > 0
					
					INNER JOIN 
						member M
					ON 
						B.userid=M.userid
				" ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"];
			
		if( !empty($GroupBy) )
			$sql .= " GROUP BY ". $GroupBy ;
			
		if( $orderByColumns )
			$sql .= " ORDER BY ". $orderByColumns .PHP_EOL;
			
		if( !empty($pageBlock) )
			$sql .= " LIMIT ".$pageBlock;
			
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->DBconn();
		//echo '<pre>';print_r($sql) ;
		//echo '<pre>';print_r(self::$_where) ;
		$data = $this->DB->rawQuery($sql, $sql_params);
		//self::$_where = NULL ;
		
		return $data ;
	}
		
		
	
}