<?php
namespace system\traits ;
/**
 * DB 인스턴스 할당 및 관련(DB관련 공용 클래스)
 *
 * @var public $DB
 */
trait DB_Trait
{
	/**
	 * DB 인스턴스 생성 & Connection
	 * @var object $DB
	 * @return $this->DB
	 */
	protected function DBconn()
	{
		if(!$this->DB) $this->DB = \WebApp::singleton('DB', "", DB_KIND) ;
	}
	/**
	 * 테이블 식별자 설정
	 * @var object static $_prefix
	 * @param string $prefix
	 * @return object WebApp_DB
	 */
	protected function setPrefix($prefix = '')
	{
		self::$_prefix = $prefix;
		return self::$_prefix;
	}
	/**
	 * DB 테이블 명 설정
	 * @param string $TableName (테이블 명)
	 * @var object static $TABLE
	 */
	//protected static $TABLE ;
	public function setTableName($TableName)
	{
		self::$TABLE = self::$_prefix ? self::$_prefix .$TableName : $TableName ;
		return self::$TABLE ;
	}
	/**
	 * 쿼리 데이타(column,value) 가공
	 * 
	 * @param string|array $params
	 * 
	 * @example (String)
	 * 			"userid='tester'" 또는 "userid='tester' AND userpw='12345'" ...외기타
	 * @example (String)
	 * 			"viewcnt=viewcnt+1"
	 * @example 
	 *         array('template' => $_REQUEST['keyword'] ) ;
	 * @example
	 * 	       array('U.'.$_REQUEST['search_field']." like ?" => "%".$_REQUEST['keyword']."%") ;
	 * @example
	 *         array("P.address like CONCAT('%',?,'%')"] => $_POST['keyword']) ;
	 * @example (문자형 인경우)
	 * 	       array("U.t_kind IN ('auction','freestyle')"]) ;
	 * 		   --> ["U.t_kind IN (?)"] => "'auction','freestyle'" ;
	 * @example (숫자형 인경우)
	 * 	       array("U.t_kind IN (1,2,10,20)") ;
	 * 		   --> ["U.t_kind IN (1,2,10,20)"]") ;
	 * @example
	 *		   array("indent BETWEEN 0 AND 1","imp=1") ;
	 *
	 * @return string|array array인경우 array('name' => ??, 'value' => ??)
	 */
	protected static function sql_paramsSet( $params ) 
	{
		if( empty($params) ) return false ;
		
		if( is_string($params) )
		{
			return $params ;
		}
		else if( is_array($params) )
		{	
			$names = array();
			$values = array();
			
			foreach($params as $key => $value){
				
				if(preg_match("/^[0-9]/i", $key) && !empty($value) )
				{
					array_push($names, $value);
				}
				else	if( !empty( $key ) )
				{
					if(preg_match("/[?]/i", $key))
					{
						if( !empty($value) ) {
							array_push($names, $key);
							array_push($values, $value) ;
							//unset($params[$key]);
						}
					}else{
						if ( !preg_match('/[ ]/', trim($key)) )
						{
							array_push($names, $key.'=?');
							
							if( is_numeric($value)){
								array_push($values, $value) ;
							}else if( !empty($value) )
								array_push($values, $value) ;
								else
									array_push($values, NULL) ;
						}
						else{
							array_push($names, $key);
							if( !empty($value) )
								array_push($values, $value) ;
						}
						
					}
				}
				
			}
			
			return array(
					'name' => $names,
					'value' => $values
			) ;
			
		}
		
		return false;
		
	}
	/**
	 * SQL문의 대입할 검색조건절 가공
	 *
	 * @var object static $_where
	 *
	 * @param string|array $search_params
	 * 
	 * @example (String)
	 * 			"userid='tester'" 또는 "userid='tester' AND userpw='12345'" ...외기타
	 * 
	 * @example 
	 *         array('template' => $_REQUEST['keyword'] ) ;
	 * @example
	 * 	       array('U.'.$_REQUEST['search_field']." like ?" => "%".$_REQUEST['keyword']."%") ;
	 * @example
	 *         array("P.address like CONCAT('%',?,'%')"] => $_POST['keyword']) ;
	 * @example (문자형 인경우)
	 * 	       array("U.t_kind IN ('auction','freestyle')"]) ;
	 * 		   --> ["U.t_kind IN (?)"] => "'auction','freestyle'" ;
	 * @example (숫자형 인경우)
	 * 	       array("U.t_kind IN (1,2,10,20)") ;
	 * 		   --> ["U.t_kind IN (1,2,10,20)"]") ;
	 * @example
	 *		   array("indent BETWEEN 0 AND 1","imp=1") ;
	 *
	 * @return array self::$_where
	 *                    array(
	 *                           "conditions"=>column명=? AND column명=?.... ,
	 *                           "values"=>값
	 *                           )
	 */
	protected static function sql_where( $search_params=NULL)
	{
		$params = static::sql_paramsSet($search_params) ;
		
		if( empty($params) ) return false ;
		
		//문자열인 경우
		if( is_string($params) )
		{
			return array("conditions" => " WHERE ".$params) ;
		}
		// 배열인 경우
		else if( !empty($params['name']) && is_array($params['name']) ){

			$where_query = '' ;
			$condition = 'AND';
			
			$where_query = ' WHERE '.implode(' '.$condition.' ', $params['name']) ;
			
			return array( "conditions" => $where_query, "values" => $params['value']) ;
		}
	}
	/**
	 * @param string|array $search_params
	 * @return string[]|array[]|NULL[]|NULL
	 * 
	 * @deprecated
	 */
	protected static function sql_where1( $search_params=NULL)
	{
		//문자열인 경우
		if( is_string($search_params) ) 
			return array("conditions" => " WHERE ".$search_params ) ;
		// 배열 값이 없을 경우
		/* $value_exist = array_values($search_params) ;
		if (empty($value_exist[0])) 
			return " WHERE ".$search_params[0] ; */
		$condition = 'AND';
		$where_query = '' ;
		if( !empty($search_params) && is_array($search_params) ){
			
			$where = array();
			$values = array();
			
			foreach($search_params as $key => $value){
				
				if(preg_match("/^[0-9]/i", $key) && !empty($value) )
				{
					array_push($where, $value);
				}
				else	if( !empty( $key ) ) 
				{
					if(preg_match("/[?]/i", $key))
					{
						if( !empty($value) ) {
							array_push($where, $key);
							array_push($values, $value) ;
							//unset($params[$key]);
						}
					}else{
						if ( !preg_match('/[ ]/', trim($key)) )
						{
							array_push($where, $key.'=?');
						
							if( is_numeric($value)){
								array_push($values, $value) ;
							}else if( !empty($value) )
								array_push($values, $value) ;
							else 
								array_push($values, NULL) ;
						}
						else{
							array_push($where, $key);
							if( !empty($value) )
								array_push($values, $value) ;
						}
						
					}
				}

			}
			
			if( count($where) > 0) $where_query = ' where '.implode(' '.$condition.' ', $where) ;
			else $where_query = NULL ;
		}else{
			$where_query = NULL;
		}
		
		if($where_query)
				return array(	"conditions" => $where_query,		"values" => $values	) ;
		else
			return NULL ;
	}

	/**
	 * SQL문에 대입할 리소스 가공
	 *
	 * @param string $type "insert" | "update"
	 * @param string|array $resource
	 * 
	 * @example (String)
	 * 			"userid='tester'" 또는 "userid='tester' AND userpw='12345'" ...외기타
	 * @example (String)
	 * 			"viewcnt=viewcnt+1"
	 * 
	 * @example 
	 *         array('template' => $_REQUEST['keyword'] ) ;
	 * @example
	 * 	       array('U.'.$_REQUEST['search_field']." like ?" => "%".$_REQUEST['keyword']."%") ;
	 * @example
	 *         array("P.address like CONCAT('%',?,'%')"] => $_POST['keyword']) ;
	 * @example (문자형 인경우)
	 * 	       array("U.t_kind IN ('auction','freestyle')"]) ;
	 * 		   --> ["U.t_kind IN (?)"] => "'auction','freestyle'" ;
	 * @example (숫자형 인경우)
	 * 	       array("U.t_kind IN (1,2,10,20)") ;
	 * 		   --> ["U.t_kind IN (1,2,10,20)"]") ;
	 * @example
	 *		   array("indent BETWEEN 0 AND 1","imp=1") ;
	 *
	 * @return array
	 *		INSERT 일 경우
	 * 				array(
	 *					"columns" => column명, column명, column명
	 *					"sign_values" => ?,?,?... ,
	 *					"values" => 값, 값, 값,
	 *     				"result_values" => '값', 234, '값2', 8000
	 *				) ;
	 *		UPDATE 일 경우
	 *				array(
	 *					"columns" =>  column명=?, column명=?, column명=?
	 *					"values" => 값, 값, 값
	 *				) ;
	 */
	protected static function sql_paramsProcess( string $type, $resource)
	{
		if($type == "insert")
		{
			if(is_array($resource))
			{
				$column_names = $column_values = [];
				
				$column_names = array_keys($resource) ;
				$columns = implode(',', $column_names) ;
				
				$values = array_values($resource) ;
				
				//$result_values = implode(',', $values) ;
				$values_cnt = count($values);
				if($values_cnt > 0)
				{
					$tmp_value = array();
					for($i=0; $i<count($values); $i++)
					{
						if( is_int($values[$i]) || is_float($values[$i]) ) array_push($tmp_value, $values[$i]);
						else array_push($tmp_value, "'".$values[$i]."'");
					}
					if(count($tmp_value) > 0) $result_values = implode(',', $tmp_value) ;
				}
				
				$sign_values = str_repeat("?,", count($column_names)) ;
				$sign_values = substr($sign_values, 0, -1);
				
				return array(
						"columns" => $columns,
						"sign_values" => $sign_values,
						"values" => $values,
						"result_values" => $result_values
				) ;
			}
			else{
				return false ;
			}
		}
		else if($type == "update")
		{
			$params = self::sql_paramsSet( $resource ) ;
			
			if( empty($params) ) return false ;

			return array(
					"columns" => implode(',', $params['name']),
					"values" => $params['value']
			) ;
		}
		
	}
	/**
	 * @param string $type
	 * @param mixed $resource
	 * @return string[]|array[]|boolean|string[]
	 * 
	 * @deprecated
	 */
	protected static function sql_paramsProcess1($type, $resource)
	{
		if($type == "insert")
		{
			if(is_array($resource))
			{
				$column_names = $column_values = [];
					
				$column_names = array_keys($resource) ;
				$columns = implode(',', $column_names) ;

				$values = array_values($resource) ;

				//$result_values = implode(',', $values) ;
				$values_cnt = count($values);
				if($values_cnt > 0)
				{
					$tmp_value = array();
					for($i=0; $i<count($values); $i++)
					{
						if( is_int($values[$i]) || is_float($values[$i]) ) array_push($tmp_value, $values[$i]);
						else array_push($tmp_value, "'".$values[$i]."'");
					}
					if(count($tmp_value) > 0) $result_values = implode(',', $tmp_value) ;
				}

				$sign_values = str_repeat("?,", count($column_names)) ;
				$sign_values = substr($sign_values, 0, -1);
					
				return array(
						"columns" => $columns,
						"sign_values" => $sign_values,
						"values" => $values,
						"result_values" => $result_values
				) ;
			}
			else{
				return false ;
			}
		}
		else if($type == "update")
		{
			if(is_array($resource))
			{
				$column_names = [];

				$column_names = array_keys($resource) ;
				
				foreach($column_names as $k => $v){
					//if( !empty($resource[$v]) )
					$column_names[$k] = $v .= '=?' ;
				}
				$columns = implode(',', $column_names) ;
					
				$values = array_values($resource) ;
					
				return array(
						"columns" => $columns,
						"values" => $values
				) ;
			}
			else if( is_string($resource) && !empty($resource) ){
				return array(
						"columns" => $columns,
						"values" => ''
				) ;
			}
			else{
				return false ;
			}
		}
	}
}