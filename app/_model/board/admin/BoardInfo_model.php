<?
namespace Gajija\model\board\admin ;
use Gajija\model\CommNest_model;

//namespace GX\app\_model ;
//use WebApp ;
/**
 * 게시판 관련
 * @author young lee
 * @email yengsu@gmail.com 
 */

/*
CREATE TABLE board (
        cate_idx INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL,
        lft INT NOT NULL,
        rgt INT NOT NULL
);
*/
//namespace Hierarchal;
class BoardInfo_model extends CommNest_model
{
	/* use
		\system\traits\DB_Trait ; */

	//private static $Func ;
	/**
	 * DB 공용 Class Object
	 *
	 * @var object Object
	 */
	//public $DB ;
	/**
	 * 테이블 명 앞의 식별자 선언변수
	 * @var protected static $_prefix ( default : "dgx_")
	 */
	//public static $_prefix = "dgx_";
	/**
	 * 테이블 명
	 * @var protected static $TABLE 
	 */
	//protected static $TABLE ;
	
	//protected static $_where = NULL ;
	
	/* protected function __construct()
	{
	} */
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
	/* protected function _add( $put_data, $idx )
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
		
		return $getInsertId ;
	} */

	/**
	 * 읽기
	 */
	/* protected function _read($column='*')
	{
		$this->DBconn();
		
		//if( !self::$_where ) return false;
		if(!$column) $column = "*" ;
		$sql = "SELECT ".$column." FROM " .self::$TABLE. self::$_where["conditions"] ;
		
		//if( is_array(self::$_where["values"]) ){
		//	foreach(self::$_where["values"] as $v){
		//		array_push($sql_params["values"], $v) ;
		//	}
		//}
		//else{
		//	$sql_param = $sql_params["values"] ;
		//}

		//$sql_params = array( $idx ) ;
		$sql_params = self::$_where["values"] ;
		$data = $this->DB->rawQuery($sql, $sql_params);

		return $data ;
	} */
	
	/**
	 * 생성
	 */
	/*
	public function _create()
	{
		
	}*/
	/**
	 * 삭제
	 */
	/* protected function _delete()
	{
		$this->DBconn();
		
		
		//$this->DB->where('serial', 9);
		//$x = $this->DB->delete('dgx_board') ;
		//echo '<pre>';print_r($x);exit;
		
		if( !self::$_where ) return false;
		//if( empty(self::$_where["conditions"]) || empty(self::$_where["values"]) ) return fase ;

		$sql = "DELETE FROM " .self::$TABLE. " ". self::$_where["conditions"] ;
		$sql_params = self::$_where["values"] ;
		
		return $this->DB->rawQuery($sql, $sql_params);
	} */
	/**
	 * 업데이트
	 */
	/* protected function _update($put_data)
	{
		$this->DBconn();

		$sql_params = self::sql_paramsProcess("update", $put_data);

		$sql = "
				UPDATE
					" .self::$TABLE. "
				SET ". $sql_params["columns"] .
				 self::$_where["conditions"] ;

		if( is_array(self::$_where["values"]) ){
			foreach(self::$_where["values"] as $v){
				array_push($sql_params["values"], $v) ;
			}
		}
		else{ 
			$sql_param = $sql_params["values"] ;
		}
		
		return $this->DB->rawQuery($sql, $sql_params["values"]);
	} */
	
	/**
	 * 레코드 갯수
	 * 
	 * @param string $count_column
	 * @return int 갯수
	 */
	/* protected function _count($column)
	{
		$this->DBconn() ;
	
		if(!$column) $column = "*" ;
		$sql = "SELECT COUNT(". $column .") as cnt FROM " .self::$TABLE. self::$_where["conditions"] ;
		$sql_params = self::$_where["values"] ;

		$data = $this->DB->rawQuery($sql, $sql_params);
		$res = array_pop($data) ;
		return array_pop($res) ;
	} */
	/**
	 * 리스트 조회
	 * @param string $columns ( column....)
	 * @param string $orderByColumns (정렬기준이 되는 Column..)
	 * @param mixed[string or int] $pageBlock ( 가져올 레코드 블럭(?, ?) 또는 갯수
	 * @return array 결과데이타
	 */
	/* protected function _listBase( $Columns='*', $orderByColumns="serial DESC", $pageBlock="0,15" )
	{
		$this->DBconn();
		
		$sql = "
			SELECT
				". $Columns."
			FROM
				". self::$TABLE ." 
				". self::$_where["conditions"] ."
			ORDER BY 
						". $orderByColumns ;
		
		if( !empty($pageBlock) )  
			$sql .= "
			LIMIT
				".$pageBlock;
		//echo nl2br($sql);
		//$sql_params = array( array_pop(self::$_where["values"]) ) ;
		$sql_params = self::$_where["values"] ;
		return $this->DB->rawQuery($sql, $sql_params);
	} */
	
}
?>