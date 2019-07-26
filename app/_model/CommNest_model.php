<?php
namespace Gajija\model ;

/**
 * common model
 * 
 * @author 이영수
 * @email yengsu@hanmail.net 
 */

class CommNest_model
{
	use
		\system\traits\DB_Trait ;

	/**
	 * DB 공용 Class Object
	 *
	 * @var object Object
	 */
	public $DB ;
	/**
	 * DB 테이블 명 앞의 식별자 선언변수
	 */
	public static $_prefix = "";
	/**
	 * DB 테이블 명
	 */
	protected static $TABLE ;
	/**
	 * DB Query 조건절
	 * @var array|NULL
	 */
	protected static $_where = NULL ;
	/**
	 * DB 실행 쿼리절 디버깅 유무
	 * 
	 * @uses true 일경우 static::$_query_log 또는 self::$_query_log 에서 쿼리절 실행문을 확인가능
	 * @var int (0:사용안함, 1:현재 실행하는 쿼리만 확인, 2:전체 쿼리 확인
	 */
	public static $_query_debug = 0 ;
	/**
	 * DB 실행 쿼리절 정보
	 * @var array|NULL
	 */
	public static $_query_log = array();
	/**
	 * 보드 신규추가
	 * 
	 * @param array $put_data
	 *        array(
	 *                  칼럼명=>값,
	 *                  칼럼명=>값......
	 *        )
	 * @return int insert_id(P.K) 또는 결과값(1:true/0:false)
	 * @tutorial
	 * 			Return 값 관련정보 - 
	 * 			Primary Key 가 Auto increment 이면 => insert id 리턴.
	 * 														아니면 => 성공여부(1:성공 / 0:실패) 리턴
	 */
	protected function _add( $put_data, $idx )
	{
		$this->DBconn();
		
		$sql_params = self::sql_paramsProcess("insert", $put_data);
		$sql = "
				INSERT INTO
					" .self::$TABLE. "
				( ". $sql_params["columns"] ." )
				VALUES
				( ". $sql_params["sign_values"]." )" ;
		
		$this->has_Trace();
		$res = $this->DB->rawQuery($sql, $sql_params["values"]);
		$this->Trace();
		
		$getInsertId = $this->DB->getInsertId();

		return $getInsertId?$getInsertId:$res ;
	}
	/**
	 * 신규추가
	 *
	 * @param array $put_data
	 *        array(
	 *                  칼럼명=>값,
	 *                  칼럼명=>값......
	 *        )
	 * @return int insert_id(P.K) 또는 결과값(1:true/0:false)
	 * @tutorial
	 * 			Return 값 관련정보 -
	 * 			Primary Key 가 Auto increment 이면 => insert id 리턴.
	 * 														아니면 => 성공여부(1:성공 / 0:실패) 리턴
	 */
	protected function _addFamily( $put_data, $idx )
	{
		
		$this->DBconn();
		
		$sql_params = self::sql_paramsProcess("insert", $put_data);
		$sql = "
				INSERT INTO
					" .self::$TABLE. "
				( ". $sql_params["columns"] ." )
				VALUES
				( ". $sql_params["sign_values"]." )" ;
		
		$this->has_Trace();
		
		$res = $this->DB->rawQuery($sql, $sql_params["values"]);
		
		$getInsertId = $this->DB->getInsertId();
		
		if(!empty($idx)) $family = $idx;
		else $family = $getInsertId ;
		
		// 확장해서 계층형으로 데이타처리를 위해 필요한 필드수정
		$sql = "UPDATE " .self::$TABLE. " SET family=? WHERE serial=?" ;
		$this->DB->rawQuery( $sql, array($family, $getInsertId) );
		
		$this->Trace();
		
		return $getInsertId?$getInsertId:$res ;
	}
	/**
	 * 계층형(Nested) 추가
	 * 
	 * @param array<key,value> $put_data
	 * @param array<family, depth> $Adj
	 * @return array
	 */
	protected function _addNested( $put_data, $serial )
	{
		$this->DBconn();
		
		$sql_params = self::sql_paramsProcess("insert", $put_data);
		if( !empty($serial) )
		{
			$sql = "SELECT @myFamily := family, @myLeft := lft, @myRight := rgt FROM " .self::$TABLE. " WHERE serial = ".$serial.";" ;
			$sql .= "UPDATE " .self::$TABLE. " SET rgt = rgt + 2 WHERE family=@myFamily AND rgt >= @myRight;" ;
			$sql .= "UPDATE " .self::$TABLE. " SET lft = lft + 2 WHERE family=@myFamily AND lft > @myRight;" ;
			$sql .= "
				INSERT INTO
					" .self::$TABLE. "
				( ". $sql_params["columns"] .", lft, rgt)
				VALUES
				( ". $sql_params["result_values"].", @myRight, @myRight + 1 );" ;
		}
		$this->has_Trace();
		$ids = $this->DB->multiQuery($sql);
		$this->Trace();
		
		return $ids ;
	}
	/**
	 * Board데이타 가져오기
	 * 
	 * @param string $Columns (칼럼명, 칼럼명....)
	 * @param string $orderByColumns (정렬: serial DESC....)
	 * @param mixed[string or int] $pageBlock (ex: 0,15 /  가져올 레코드 블럭(?, ?) 또는 갯수 )
	 * 
	 * @return array
	 */
	protected function _read($Columns='*', $GroupBy="", $orderByColumns="serial DESC", $pageBlock=NULL)
	{
		$this->DBconn();
		
		if(!$column) $column = "*" ;
		$sql = "SELECT ".
						$Columns.
					" FROM " .
						self::$TABLE ;

		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"] ;

		if( !empty($GroupBy) )
			$sql .= " GROUP BY ". $GroupBy ;
		
		if( $orderByColumns ){
			$sql .= " ORDER BY ". $orderByColumns ;
		}
		if( !empty($pageBlock) )
			$sql .= "
			LIMIT
				".$pageBlock;
		
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		return $data ;
	}
	/**
	 * [두개의 테이블 JOIN] 데이타 가져오기
	 * 
	 * @param string $Table_A 테이블명(별칭 A)
	 * @param string $Table_B 테이블명(별칭 B)
	 * @param string $Columns (칼럼명, 칼럼명....)
	 * @param array<key,value> $conditions
	 * 							array(
	 * 									"B.oid"=>????,   <== 입력 필수사항●
	 * 									.....
	 * 							)
	 * @param string $join (쿼리 조인형태 : INNER, LEFT, LEFT OUTER.......)
	 * @param string $on 쿼리 조인칼럼 : ex) ON A.serial=B.join_code
	 * @param string $orderByColumns 정렬 (ex: A.serial DESC....)
	 * @param mixed[string or int] $pageBlock (ex: 15 /  가져올 레코드 블럭(?, ?) 또는 갯수 )
	 *
	 * @return array
	 */
	protected function _readJoin($Table_A, $Table_B, $Columns='*', $conditions, $join="INNER", $on = "", $orderByColumns="", $pageBlock=NULL) //$pageBlock="0,15"
	{
		if(!$Table_A || !$Table_B || !$Columns || !$join || !$on) return false ;

		self::$_where = $this->sql_where($conditions) ;
		
		if(!$column) $column = "*" ;
		$sql = "SELECT ".
					$Columns.
				" FROM " .
					self::$_prefix . $Table_A." A 
				".$join." JOIN 
					". self::$_prefix . $Table_B." B 
				ON 
					".$on." ". 
				$oid ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"] ;
			
		if( $orderByColumns ){
			$sql .= " ORDER BY ". $orderByColumns ;
		}
		if( !empty($pageBlock) )
			$sql .= " LIMIT ".$pageBlock;

		
		/* echo '<pre>';print_r($sql);//exit;
		echo '<pre>';print_r(self::$_where); */
		$this->DBconn();
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		self::$_where = NULL ;
		
		return $data ;
	}
	/**
	 * 업체정보와 매칭되는 데이타 가져오기
	 * 
	 * @param string $Columns (칼럼명, 칼럼명....)
	 * @param array<key,value> $conditions  
	 * 							array(
	 * 									"B.oid"=>????,   <== 입력 필수사항● 
	 * 									.....
	 * 							) 
	 * 
	 * @param string $join (쿼리 조인형태 : INNER, LEFT, LEFT OUTER.......)
	 * @param string $orderByColumns (정렬: serial DESC....)
	 * @param mixed[string or int] $pageBlock (ex: 15 /  가져올 레코드 블럭(?, ?) 또는 갯수 )
	 * 
	 * @return array
	 */
	protected function _readAndOid($Columns='*', $conditions, $join="INNER", $orderByColumns="B.serial DESC", $pageBlock=NULL) //$pageBlock="0,15"
	{
		$this->DBconn();

		if($conditions["B.oid"]){
			$oid = " AND B.oid = '".$conditions["B.oid"]."'";
			unset($conditions["B.oid"]);
		}
		
		self::$_where = $this->sql_where($conditions) ;
		
		if(!$column) $column = "*" ;
		$sql = "SELECT ".
				$Columns.
				" FROM " .
						self::$TABLE ." B
					".$join." JOIN 
						". self::$_prefix ."oid O
						ON 
								B.oid=O.oid " .
							$oid ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"] ;

		if( $orderByColumns ){
			$sql .= " ORDER BY ". $orderByColumns ;
		}
		if( !empty($pageBlock) )
			$sql .= "
			LIMIT
				".$pageBlock;
	
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		self::$_where = NULL ;

		return $data ;
	}
	/**
	 * 회원정보와 매칭되는 Board데이타 가져오기
	 * 
	 * @param string $Columns (칼럼명, 칼럼명....)
	 * @param array<key,value> $conditions  
	 * 							array(
	 * 									"B.userid"=>????,   <== 입력 필수사항● 
	 * 									.....
	 * 							) 
	 * 
	 * @param string $join (쿼리 조인형태 : INNER, LEFT, LEFT OUTER.......)
	 * @param string $orderByColumns (정렬: serial DESC....)
	 * @param mixed[string or int] $pageBlock (ex: 15 /  가져올 레코드 블럭(?, ?) 또는 갯수 )
	 * 
	 * @return array
	 */
	//protected function _readAndMbr($Columns='*', $conditions, $join="INNER", $orderByColumns="B.serial DESC", $pageBlock=NULL) //$pageBlock="0,15"
	protected function _readAndMbr($Columns='*', $conditions, $join="INNER", $orderByColumns="", $pageBlock=NULL) //$pageBlock="0,15"
	{
		$this->DBconn();

		if( !$conditions ) return false;

		if($conditions["B.userid"]){
			$userid = " AND B.userid = '".$conditions["B.userid"]."'";
			unset($conditions["B.userid"]);
		}
		
		self::$_where = $this->sql_where($conditions) ;
		
		if(!$column) $column = "*" ;
		$sql = "SELECT ".
				$Columns.
				" FROM " .
						self::$TABLE ." B
					".$join." JOIN 
						". self::$_prefix ."member M
						ON 
								B.userid=M.userid " .
							$userid ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"] ;

		if( $orderByColumns ){
			$sql .= " ORDER BY ". $orderByColumns ;
		}
		if( !empty($pageBlock) )
			$sql .= "
			LIMIT
				".$pageBlock;
		
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		/* echo '<pre>';print_r($sql);//exit;
		echo '<pre>';print_r($sql_params); */
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		//self::$_where = NULL ;

		return $data ;
	}
	
	/**
	 * 삭제
	 */
	protected function _delete()
	{
		$this->DBconn();
		
		if( !self::$_where ) return false;

		$sql = "DELETE FROM " .self::$TABLE. " ". self::$_where["conditions"] ;
		$sql_params = self::$_where["values"] ;
		
		$this->has_Trace();
		$res = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		return $res ;
	}
	/**
	 * 업데이트
	 */
	protected function _update($put_data)
	{
		$this->DBconn();
		
		if( !self::$_where ) return false;
		
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
		
		$this->has_Trace();
		$res = $this->DB->rawQuery($sql, $sql_params["values"]);
		$this->Trace();
		
		return $res ;
	}
	
	/**
	 * Root노드부터 찾으려는 노드까지 뽑기
	 *   
	 * @param string $Columns (칼럼명 ==> column-name, column-name....)
	 * @return array
	 */
	protected function _nodeGetUntil( $Columns )
	{
		if( !self::$_where ) return false;
		
		$this->DBconn();
		
		$this->has_Trace();
		
		$data = $this->_read("family, lft, rgt", $orderByColumns);
		
		if( empty($data) ) 
			//Exception
			return false ;

		if(self::$_where["conditionss"])
		{
			$Where = self::$_where["conditions"]. 
					" AND family=".$data[0]['family']."
					AND indent > 0 
					AND lft BETWEEN 0 AND ".$data[0]['rgt'] ;  
		}else{
			$Where = " WHERE 
								family=".$data[0]['family']."  
							AND indent > 0 
							AND lft BETWEEN 0 AND ".$data[0]['rgt'] ;  
		}	 
		$sql = "
				SELECT 
					".$Columns."
				FROM 
					". self::$TABLE .
					$Where ."
				ORDER BY 
					lft" ;
		
		$sql_params = self::$_where["values"] ;
		
		$res = $this->DB->rawQuery($sql, $sql_params);
		
		$this->Trace();
		
		return $res ;
	}
	/**
	 * 최상위부터 해당 노드의 깊이(path)까지 관련 단일노드 뽑기
	 * 
	 * @param array<value> $Columns [칼럼명] => array(column-name, column-name....)
	 * @return array
	 */
	protected function _nodeGetPath( $Columns)
	{
		$this->DBconn();

		if( !self::$_where ) return false;
		
		if( empty($Columns) || !is_array($Columns) ){
			$Columns = "P.*" ;
		}else if( is_array($Columns) ){
			$comma_separated = implode(" ,P.", $Columns);
			$Columns = "P.". $comma_separated ;
		}
		$sql = "
			SELECT 
				". $Columns ."
			FROM 
				". self::$TABLE ." AS N,
				". self::$TABLE ." AS P ".
			
			self::$_where["conditions"]." 
					AND N.family=P.family
					AND N.lft BETWEEN P.lft AND P.rgt  
			ORDER BY
				P.lft" ;

			//echo 'aaa<pre>';print_r($sql);
			//echo 'bbb<pre>';print_r(self::$_where);
			
		$sql_params = self::$_where["values"] ;
		
		$this->has_Trace();
		$res = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		return $res ;
	}
	/**
	 * 선택된 노드의 자식노드 정보 추출
	 *
	 * @param integer $serial (P.K)
	 * @param array<value> $Columns [칼럼명] => array(column-name, column-name....)
	 * @param string $opt ( 최하위노드 추출시 'last'.......)
	 * @return array
	 * 
	 */
	protected function _nodeGetChild( $serial, $Columns, $opt )
	{
		if( empty($Columns) || !is_array($Columns) ){ 
			$Columns = "N.*" ;
		}else if( is_array($Columns) ){ 
			$comma_separated = implode(" ,N.", $Columns);
			$Columns = "N.". $comma_separated ;
		}
		if(self::$_where["conditions"]) $WHERE = self::$_where["conditions"]." AND " ;
		else $WHERE = " WHERE " ;
		
		$this->DBconn();
	
		//최하위 자식노드 정보 추출시
		if( $opt == "last" )
			$orderLimit = "desc limit 1" ;

		$sql = "
		select
			". $Columns .",
			FORMAT((((N.rgt - N.lft) -1) / 2),0) AS cnt_children,
			CASE WHEN N.rgt - N.lft > 1 THEN 1 ELSE 0 END AS is_branch
		FROM
				" .self::$TABLE. " N
			INNER JOIN
				" .self::$TABLE. " P
			ON
					P.serial = ?
				AND
					P.family=N.family 
		".$WHERE." 
					N.lft
				BETWEEN
					P.lft AND P.rgt
		ORDER BY
			N.lft ".$orderLimit;

		if( !empty(self::$_where["values"]) ) $sql_params = array_merge( array($serial), self::$_where["values"]); //array( $serial );
		else $sql_params = array($serial) ;
		
		//echo '<pre>';print_r($sql);
		//echo '<pre>';print_r($sql_params);
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		return $data ;
	}
	
	
	protected function _nodeGetChild_indent( $conditions, $opt )
	{
		$this->DBconn();
	
		//최하위 자식노드 정보 추출시
		if( $opt == "last" )
			$orderLimit = "desc limit 1" ;

		$sql = "
		select
			N.*
		FROM
				" .self::$TABLE. " N
			INNER JOIN
				" .self::$TABLE. " P
			ON
					P.serial = ?
				AND
					N.indent = ?
				AND
					P.family=N.family
		WHERE
					N.lft
				BETWEEN
					P.lft AND P.rgt
		ORDER BY
			N.lft desc limit 1";
	
		$sql_params = $conditions;
	
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		return $data ;
	}	
	/**
	 * 자기 자신의 left와 right 폭값
	 * 
	 * @param integer $serial
	 * @return array row데이타
	 */
	protected function _nodeGetWidth( $serial )
	{
		$this->DBconn();
		
		$sql = "
			SELECT
				rgt - lft + 1 
			FROM 
				" .self::$TABLE. "
			WHERE 
				serial=?" ;

		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, array($serial) );
		$this->Trace();
		
		$res = array_pop($data) ;
		return array_pop($res) ;
	}
	/**
	 * 
	 * @param integer $to_move_serial
	 * @param integer $parent_serial
	 * @param integer $sibling_serial
	 */
	protected function _nodeMoveSibling($to_move_serial, $parent_serial, $sibling_serial )
	{
		$this->DBconn();

		$sql = "SELECT @to_move_lft := lft, @to_move_rgt := rgt, @to_move_indent := indent FROM " .self::$TABLE. " WHERE serial = $to_move_serial ;" ;
		
		if($parent_serial == $sibling_serial)
		{
			$sql .= "SELECT 
							@parent_serial := serial, @parent_lft := lft, @parent_rgt := rgt, @parent_indent := indent,
							@sibling_serial := serial, @sibling_lft := lft, @sibling_rgt := rgt,	@sibling_indent := indent
						FROM " .self::$TABLE. " WHERE serial = ".$parent_serial." ;" ;
		}else{
			$sql .= "SELECT @parent_serial := serial, @parent_lft := lft, @parent_rgt := rgt, @parent_indent := indent FROM " .self::$TABLE. " WHERE serial = ".$parent_serial." ;" ;
			$sql .= "SELECT @sibling_serial := serial, @sibling_lft := lft, @sibling_rgt := rgt,	@sibling_indent := indent FROM " .self::$TABLE. " WHERE serial = ".$sibling_serial." ;" ;
		}

		$sql .= "
	    UPDATE " .self::$TABLE. " 
				SET 
		    indent = indent + CASE
		        WHEN lft BETWEEN @to_move_lft AND @to_move_rgt THEN (- @to_move_indent) + 1 + @parent_indent
		        ELSE 0
		    END,
		    lft = CASE
		        WHEN
					@sibling_serial = @parent_serial
		        THEN
		            CASE
		                WHEN lft BETWEEN @parent_lft + 1 AND @to_move_lft - 1 THEN lft + (@to_move_rgt - @to_move_lft) + 1
		                WHEN lft BETWEEN @to_move_lft AND @to_move_rgt THEN lft - (@to_move_lft - (@parent_lft + 1))
		                ELSE lft
		            END
		        ELSE CASE
		            WHEN
		                @to_move_lft > @sibling_lft
		            THEN
		                CASE
		                    WHEN lft BETWEEN @sibling_rgt AND @to_move_lft - 1 THEN lft + (@to_move_rgt - @to_move_lft) + 1
		                    WHEN lft BETWEEN @to_move_lft AND @to_move_rgt THEN lft - (@to_move_lft - (@sibling_rgt + 1))
		                    ELSE lft
		                END
		            ELSE CASE
		                WHEN lft BETWEEN @to_move_rgt + 1 AND @sibling_rgt THEN lft - ((@to_move_rgt - @to_move_lft) + 1)
		                WHEN lft BETWEEN @to_move_lft AND @to_move_rgt THEN lft + (@sibling_rgt - @to_move_rgt)
		                ELSE lft
		            END
		        END
		    END,
		    rgt = CASE
		        WHEN
		            @sibling_serial = @parent_serial
		        THEN
		            CASE
		                WHEN rgt BETWEEN @parent_lft + 1 AND @to_move_lft - 1 THEN rgt + (@to_move_rgt - @to_move_lft) + 1
		                WHEN rgt BETWEEN @to_move_lft AND @to_move_rgt THEN rgt - (@to_move_lft - (@parent_lft + 1))
		                ELSE rgt
		            END
		        ELSE CASE
		            WHEN
		                @to_move_rgt > @sibling_lft
		            THEN
		                CASE
		                    WHEN rgt BETWEEN @sibling_rgt + 1 AND @to_move_lft - 1 THEN rgt + (@to_move_rgt - @to_move_lft) + 1
		                    WHEN rgt BETWEEN @to_move_lft AND @to_move_rgt THEN rgt - (@to_move_lft - (@sibling_rgt + 1))
		                    ELSE rgt
		                END
		            ELSE CASE
		                WHEN rgt BETWEEN @to_move_rgt + 1 AND @sibling_rgt + 1 THEN rgt - ((@to_move_rgt - @to_move_lft) + 1)
		                WHEN rgt BETWEEN @to_move_lft AND @to_move_rgt THEN rgt + (@sibling_rgt - @to_move_rgt)
		                ELSE rgt
		            END
		        END
		    END
		WHERE
		    lft BETWEEN @parent_lft + 1 AND @parent_rgt;" ;

		$this->has_Trace();
		$ids = $this->DB->multiQuery($sql);
		$this->Trace();
		
		return $ids ;
		
	}
	/**
	 * 노드 이동
	 * 
	 * --> child into move
	 * --> $this->_nodeMoveComplex 메서드에 비해 속도는 빠름
	 * 
	 * @param array $orig ( key: serial, family, parent, lft, rgt.. )
	 * @param array $new_parent ( key: family, parent, lft, rgt.. )
	 * @throws \Exception
	 * @return boolean
	 */
	protected function _nodeMoveChild($orig, $new_parent)
	{
		if( empty($orig) || !is_array($orig) ||
				empty($new_parent) || !is_array($new_parent)) return false ;

		$this->DBconn();
		
		if($new_parent["rgt"] < $orig["lft"])		
		{
			$this->has_Trace();
			
			$sql = "
				UPDATE ".self::$TABLE." SET
					parent=".$new_parent["serial"]."
				WHERE 
					serial=".$orig["serial"] ;
			$this->DB->rawQuery($sql);
			
			$sql = "
				UPDATE ".self::$TABLE." SET
				
				indent = indent +
						
					CASE
						WHEN lft BETWEEN ".$orig["lft"]." AND ".$orig["rgt"]." THEN
							( - ".$orig["indent"].")+1+".$new_parent["indent"]."
					ELSE
						0
					END,
									
				lft = lft +
				
				CASE
					WHEN lft BETWEEN ".$orig["lft"]." AND ".$orig["rgt"]." THEN
						".$new_parent["rgt"]." - ".$orig["lft"]."
					WHEN lft BETWEEN ".$new_parent["rgt"]." AND ".$orig["lft"]." - 1 THEN
						".$orig["rgt"]." - ".$orig["lft"]." + 1
					ELSE
						0
				END,
					
				rgt = rgt +
				 
				CASE
					WHEN rgt BETWEEN ".$orig["lft"]." AND ".$orig["rgt"]." THEN
						".$new_parent["rgt"]." - ".$orig["lft"]."
					WHEN rgt BETWEEN ".$new_parent["rgt"]." AND ".$orig["lft"]." - 1 THEN
						".$orig["rgt"]." - ".$orig["lft"]." + 1
					ELSE
						0
					END
				
				WHERE
					family=".$orig["family"]." and
					lft BETWEEN ".$new_parent["rgt"]." AND ".$orig["rgt"]."
					OR rgt BETWEEN ".$new_parent["rgt"]." AND ".$orig["rgt"].";
				";
			
			$res = $this->DB->rawQuery($sql);
			
			$this->Trace();
			
			return $res ;
		}
		else if($new_parent["rgt"] > $orig["rgt"] )
		{
			$this->has_Trace();
			
			$sql = "
				UPDATE ".self::$TABLE." SET
					parent=".$new_parent["serial"]."
				WHERE
					serial=".$orig["serial"] ;
			$this->DB->rawQuery($sql);
			
			$sql = "
				UPDATE ".self::$TABLE." SET
					
					indent = indent +
						
					CASE
						WHEN lft BETWEEN ".$orig["lft"]." AND ".$orig["rgt"]." THEN
							( - ".$orig["indent"].")+1+".$new_parent["indent"]."
					ELSE
						0
					END,
									
					lft = lft +
					
					CASE
						WHEN lft BETWEEN ".$orig["lft"]." AND ".$orig["rgt"]." THEN
							".$new_parent["rgt"]." - ".$orig["rgt"]." - 1
						WHEN lft BETWEEN ".$orig["rgt"]." + 1 AND ".$new_parent["rgt"]." - 1 THEN
							".$orig["lft"]." - ".$orig["rgt"]." - 1
					ELSE
						0
					END,
				
					rgt = rgt +
				
					CASE
						WHEN rgt BETWEEN ".$orig["lft"]." AND ".$orig["rgt"]." THEN
							".$new_parent["rgt"]." - ".$orig["rgt"]." - 1
						WHEN rgt BETWEEN ".$orig["rgt"]." + 1 AND ".$new_parent["rgt"]." - 1 THEN
							".$orig["lft"]." - ".$orig["rgt"]." - 1
					ELSE
						0
					END
					
				WHERE
					-- bserial=".$orig["bserial"]." and
					family=".$orig["family"]." and
					lft BETWEEN ".$orig["lft"]." AND ".$new_parent["rgt"]."
					OR rgt BETWEEN ".$orig["lft"]." AND ".$new_parent["rgt"]."
				";
			
			$res = $this->DB->rawQuery($sql);
			
			$this->Trace();
			
			return $res ;
		}
		
	}
	/**
	 * 다른 그룹간의 노드 이동
	 * 
	 *  --> 다른 그룹간의 이동 및 자신의 그룹간의 이동
	 *  --> [경고] 부모노드가 있어야 됨
	 *  --> 대량의 데이타 이동인 경우 성능테스트가 필요하거나 튜닝필요
	 *  
	 * @param array<key,value> $orig (이동시킬 노드:  array("table"=>테이블명, "serial"=>코드)
	 * @param array<key,value> $new_parent (이동될 위치 노드 : array("table"=>테이블명, "serial"=>코드)
	 * @param unknown
	 */
	protected function _nodeMoveComplex( $orig, $new_parent )
	{
		if( empty($orig) || !is_array($orig) ||
				empty($new_parent) || !is_array($new_parent)) return false ;
	
		$this->DBconn();
		
		$this->has_Trace();
		
		// 이동시킬 노드 정보(자식노드 포함)
		if( !empty($orig["table"]) )
			$this->setTableName( $orig["table"] );
	
		$org_childs = $this->_nodeGetChild(	$orig["serial"]) ;
	
		// 이동될 위치 노드정보
		if( !empty($new_parent["table"]) )
			$this->setTableName( $new_parent["table"] );
	
		self::$_where = $this->sql_where( array(
				"serial" => $new_parent["serial"]
		));
		$parent_row = $this->_read("*") ;
		
		/**
		 * 같은 그룹내에서 노드 이동인경우 
		 * 아래 로직을 태우지 않고 
		 * _nodeDeleteContainChild 메서드로 분기하고 Return
		 */
		if(
			( !empty($orig["table"]) || !empty($new_parent["table"]) ) ||
			( $org_childs[0]["family"] == $parent_row[0]["family"] )
		){
			$res = $this->_nodeMovechild($org_childs[0], $parent_row[0]) ;
			$this->Trace();
			
			return $res ;
		}
		
		if($row = $this->_nodeGetChild_indent( array($new_parent["serial"], $parent_row[0]["indent"]+1)) ){
			$new_parent_row = $row ;
			$new_parent_row[0]["indent"] = $parent_row[0]["indent"] ;
			$new_parent_row[0]["family"] = $parent_row[0]["family"] ;
		}else{
			$new_parent_row = $parent_row ;
		}
		
		$this->_nodeDeleteContainChild($orig["serial"]) ;
		
		$org_childs_cnt = count($org_childs) ;
		$oc_acc = ($org_childs_cnt)*2 ;
	
		$sql = "UPDATE " .self::$TABLE. " SET rgt = rgt + ".($oc_acc)." WHERE family=".$new_parent_row[0]["family"]." AND rgt >= ".$new_parent_row[0]["rgt"].";" ;
		$sql .= "UPDATE " .self::$TABLE. " SET lft = lft + ".$new_parent_row[0]["lft"]." + 1 WHERE family=".$new_parent_row[0]["family"]." AND lft > ".$new_parent_row[0]["rgt"].";" ;
		$ids = $this->DB->multiQuery($sql);
	
		// 노드 lft,rgt 가공
		if( ! empty($org_childs) && is_array($org_childs) )
		{
			$org_indent = $org_childs[0]["indent"] ;
			$org_lft = $org_childs[0]["lft"] ;
				
			$parent_family = $new_parent_row[0]["family"] ;
				
				
			foreach($org_childs as $k => $v)
			{
				$org_childs[$k]["bserial"] = $new_parent_row[0]["bserial"] ;
				$org_childs[$k]["family"] = $parent_family ;
				$org_childs[$k]["indent"] = ( $org_childs[$k]["indent"] - $org_indent ) + 1 + $new_parent_row[0]["indent"] ;
				$org_childs[$k]["lft"] = ($v["lft"] - $org_lft) + $new_parent_row[0]["rgt"];
				$org_childs[$k]["rgt"] = ($v["rgt"] - $org_lft) + $new_parent_row[0]["rgt"];
	
				unset($org_childs[$k]["serial"]);

				$this->_add($org_childs[$k], $new_parent["serial"]) ;
			}
		}
		
		$this->Trace();
	}
	
	/**
	 * 선택된 노드의 자식노드를 추가
	 *
	 * @param array<key,value> $put_data
	 * @param array<family, depth> $Adj
	 * @return array
	 */
	protected function _nodeAddChildren( $serial, $put_data )
	{
		$sql_params = self::sql_paramsProcess("insert", $put_data);

		if( !empty($serial) )
		{
			$sql = "SELECT @myFamily := family, @myLeft := lft, @myRight := rgt FROM " .self::$TABLE. " WHERE serial = ".$serial.";" ;
			$sql .= "UPDATE " .self::$TABLE. " SET rgt = rgt + 2 WHERE family=@myFamily AND rgt >= @myRight;" ;
			$sql .= "UPDATE " .self::$TABLE. " SET lft = lft + 2 WHERE family=@myFamily AND lft > @myRight;" ;
			$sql .= "
				INSERT INTO
					" .self::$TABLE. "
				( ". $sql_params["columns"] .", lft, rgt)
				VALUES
				( ". $sql_params["result_values"].", @myRight, @myRight + 1 )" ;
		}

		$this->DBconn();
		
		$this->has_Trace();
		$ids = $this->DB->multiQuery($sql);
		$this->Trace();
		
		return $ids ;
	}

	/**
	 * 선택된 형제노드에 삽입
	 * @param string $option ( 이전: "before", 이후: "after" )
	 * @param integer $serial (해당 노드)
	 * @param array<key,value> $put_data (저장할 데이타)
	 * @return mixed<boolean or array>
	 */
	protected function _nodeAddSibling($option, $serial, $put_data)
	{
		if( strtolower($option) != "before" && strtolower($option) != "after" ) return false ;
		if( !is_numeric($serial) || $serial < 1 ) return false ;
		if( empty($put_data) || !is_array($put_data) ) return false ;
			
		
		$put_data = array_diff_key($put_data, array_flip(
				array( "indent","lft", "rgt" )
		));
	
		$this->DBconn();
	
		$sql_params = self::sql_paramsProcess("insert", $put_data);
	
		$sql = "
			SELECT
				@mySerial := serial, @myIndent := indent, @myLeft := lft, @myRight := rgt
			FROM
				" .self::$TABLE. "
			WHERE
				serial=".$serial.";" ;

		if($option == "before")
		{
			$sql .= "UPDATE " .self::$TABLE. " SET rgt = rgt + 2 WHERE rgt >= @myLeft;" ;
			$sql .= "UPDATE " .self::$TABLE. " SET lft = lft + 2 WHERE lft >= @myLeft;" ;
		}
		else if($option == "after"){
			$sql .= "UPDATE " .self::$TABLE. " SET rgt = rgt + 2 WHERE rgt > @myRight;" ;
			$sql .= "UPDATE " .self::$TABLE. " SET lft = lft + 2 WHERE lft > @myRight;" ;
		}
		
		$sql .= "
				INSERT INTO
					" .self::$TABLE. "(". $sql_params["columns"] .", indent, lft, rgt )
				VALUES(". $sql_params["result_values"].", @myIndent, @myLeft, @myLeft+1);" ;
	
		$this->has_Trace();
		$res = $this->DB->multiQuery($sql) ;
		$this->Trace();
		
		return $res ;
	}
	
	/**
	 * 자신 포함해서 자식노드 모두 삭제
	 * 
	 * @param integer $serial
	 * @return array
	 */
	protected function _nodeDeleteContainChild($serial)
	{
		if( !is_numeric($serial) || (int)$serial < 1 ) return false ;
		
		//자신 포함해서 자식노드 모두 삭제하고 left, right 번호 정렬처리
		$sql = "
		SELECT @mySerial := serial, @myIndent := indent, @myLeft := lft, @myRight := rgt FROM " .self::$TABLE. "
		WHERE serial=".$serial.";
		
		DELETE FROM " .self::$TABLE. " WHERE lft BETWEEN @myLeft AND @myRight;
		UPDATE " .self::$TABLE. " SET lft=lft-ROUND((@myRight-@myLeft+1)) WHERE lft>@myRight;
		UPDATE " .self::$TABLE. " SET rgt=rgt-ROUND((@myRight-@myLeft+1)) WHERE rgt>@myRight;";
		
		$this->DBconn();
		
		$this->has_Trace();
		$res = $this->DB->multiQuery($sql) ;
		$this->Trace();
		
		return $res ;
	}
	/**
	 * 선택한 자신의 노드만 삭제 (자식노드 삭제 안함)
	 * 
	 *  --> 자식노드가 존재하면 자식노드들을 한단계 위의 indent로 올리기 
	 *  
	 * @param integer $serial
	 * @return array
	 */
	protected function _nodeDeleteSelect($serial)
	{
		if( !is_numeric($serial) || $serial < 1 ) return false ;
		
		$sql = "
				SELECT @mySerial := serial, @myIndent := indent, @myLeft := lft, @myRight := rgt FROM " .self::$TABLE. "
				WHERE serial=".$serial.";
				UPDATE " . self::$TABLE . " SET 
					indent = 
						CASE 
							WHEN lft BETWEEN @myLeft AND @myRight THEN indent - 1 
							ELSE indent 
							END,
					rgt = 
						CASE 
							WHEN rgt BETWEEN @myLeft AND @myRight THEN	rgt - 1
							WHEN rgt > @myRight THEN rgt - 2	
							ELSE rgt 
							END,
					lft = 
						CASE 
							WHEN lft BETWEEN @myLeft AND @myRight THEN lft - 1
							WHEN lft > @myRight THEN lft - 2 
							ELSE lft 
							END
				WHERE 
					rgt > @myLeft;";
		
		$this->DBconn();
		
		$this->has_Trace();
		$res = $this->DB->multiQuery($sql) ;
		$this->Trace();
		
		return $res ;
	}
	
	
	/**
	 * 레코드 갯수
	 * 
	 * @param string $count_column
	 * @return int 갯수
	 */
	protected function _count($column)
	{
		$this->DBconn() ;
	
		if(!$column) $column = "*" ;
		$sql = "SELECT COUNT(". $column .") as cnt FROM " .self::$TABLE ;

		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"] ;
		
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		if(!empty($data)){
			$res = array_pop($data) ;
			return array_pop($res) ;
		}else{
			return 0;
		}
	}

	/**
	 * 리스트 조회
	 * @param string $columns ( column....)
	 * @param string $orderByColumns (정렬기준이 되는 Column..)
	 * @param mixed[string or int] $pageBlock ( 가져올 레코드 블럭(?, ?) 또는 갯수
	 * @return array 결과데이타
	 */
	protected function _listBase( $Columns='*', $orderByColumns="serial DESC", $pageBlock="0,15" )
	{
		$this->DBconn();
		
		$sql = "
			SELECT
				". $Columns."
			FROM
				". self::$TABLE ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"] ;
		
		
		if( $orderByColumns ){
			$sql .= "
			ORDER BY 
						". $orderByColumns ;
		}
		
		if( !empty($pageBlock) )  
			$sql .= "
			LIMIT
				".$pageBlock;

		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->has_Trace();
		$res = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		return $res ;
	}
	/**
	 * @desc 카테고리 & 정보 조회
	 * @tutorial TABLE( 카테고리테이블 parent )
	 * @tutorial (★ 필수 ★) 테이블($DataTable명)의 column "cate" 필수로 존재해야함
	 * 
	 * @param string $CateTable 카테고리 테이블명 : [필수::Columns] serial, indent, lft, rgt, cate
	 * @param string $DataTable 데이타 테이블명
	 * @param string $Columns( column명,column명,column명... ) ; 내장함수 사용금지
	 * @param string $GroupBy
	 * @param string $orderByColumns
	 * @param string $pageBlock
	 * @return array
	 */
	protected function _CateGroupyList($CateTable, $DataTable, $Columns="", $GroupBy="", $orderByColumns="", $pageBlock=NULL) //$pageBlock="0,15"
	{
		if(!$CateTable || !$DataTable|| !$Columns) return false ;
			
			/* SELECT
			 parent.*, COUNT(G.serial)
			 FROM
			 	카테고리테이블 AS node
			 INNER JOIN
				카테고리테이블 AS parent
			 ON
			 	node.lft BETWEEN parent.lft AND parent.rgt
			 
				LEFT JOIN
					데이타 테이블 D
				ON
					node.serial = G.gcate
			WHERE
				parent.serial=322757
			GROUP BY
				parent.serial
			ORDER BY
				node.lft; */
			
		$sql = "
		SELECT
			". $Columns ."
		FROM
				".$CateTable." AS node
			LEFT JOIN
				".$CateTable." AS parent
			ON
				-- node.indent > 0 AND
				node.lft BETWEEN parent.lft AND parent.rgt
				LEFT JOIN
					".$DataTable." D
				ON  node.serial = D.cate " ;
		
		if( isset(self::$_where["conditions"]) )
			$sql .= self::$_where["conditions"];
			
		if( !empty($GroupBy) )
			$sql .= " GROUP BY ". $GroupBy . " HAVING parent.serial > 0";
			
		if( $orderByColumns ){
			$sql .= " ORDER BY ". $orderByColumns ;
		}
		if( !empty($pageBlock) )
			$sql .= " LIMIT ".$pageBlock;
			
		$sql_params = isset(self::$_where["values"]) ? self::$_where["values"] : "" ;
		
		$this->DBconn();
		/* echo '<pre>';print_r($sql);
		echo '<pre>';print_r($sql_params);//exit; */
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		//self::$_where = NULL ;
		
		return $data ;
	}
	/**
	 * auto_increment 생성해서 가져오기
	 * 
	 * @param integer $lastNum ( 최근 auto_increment [값이 없으면 기존 키값에서 생성] )
	 * @return integer insert_id값
	 */
	protected function _get_insertID($lastNum)
	{
		$this->DBconn();
		
		$this->has_Trace();
		$res = $this->DB->create_insert_id(self::$TABLE, $lastNum) ;
		$this->Trace();
		
		return (int) $res;
	}
	
	/**
	 * INSERT AND UPDATE
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
	 * @param array $put_data
	 */
	protected function _insertUpdate($put_data, $update_values)
	{
		$this->DBconn();
		
		$sql_params = self::sql_paramsProcess("insert", $put_data);
		
		$sql = "
			INSERT INTO 
				". self::$TABLE ." 
				( ".$sql_params["columns"]." ) 
			VALUES
				( ".$sql_params["sign_values"]." ) 
			ON DUPLICATE KEY UPDATE ".$update_values ;

		$this->has_Trace();
		$res = $this->DB->rawQuery($sql, $sql_params['values']);
		$this->Trace();
		
		return $res ;
	}
	
	protected function _multiQuery($sql)
	{
		$this->DBconn();
		
		$this->has_Trace();
		$ids = $this->DB->multiQuery($sql);
		$this->Trace();
		
		return $ids ;
	}
	
	protected function _rawQuery($sql, $sql_params=NULL)
	{
		$this->DBconn();
		
		$this->has_Trace();
		$data = $this->DB->rawQuery($sql, $sql_params);
		$this->Trace();
		
		return $data ;
	}
	/**
	 * 쿼리절 실행 로그확인 유무
	 * 
	 * @uses self::$_query_debug = 1 일경우 실행
	 */
	protected function has_Trace()
	{
		if( (bool) self::$_query_debug ) {
			if($this->DB) $this->DB->traceEnabled= 1 ;
		}else{
			self::$_query_log = array();
		}
	}
	/**
	 * 쿼리절 실행 로그저장
	 * 
	 * @uses self::$_query_debug = 1 (현재쿼리만) or 2(전체) 일경우 실행
	 * @return array()
	 */
	protected function Trace()
	{
		if($this->DB) {
			if($this->DB->bindObj->traceEnabled){
				
				if( self::$_query_debug == 1 ){
					self::$_query_log = array();
					return array_push(self::$_query_log, $this->DB->trace());
				}
				else if( self::$_query_debug == 2 ){
					return array_push(self::$_query_log, $this->DB->trace());
				}
				
			}
		}
		else self::$_query_log = array();
		/* if( (bool) self::$_query_debug ){
			array_push(self::$_query_log, array(
					"SQL" => $sql,
					"PARAMS" => $val
			));
		} */
	}
}