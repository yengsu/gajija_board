<?php
/**********************************************
* 파일명: class.DB.php
* 설  명: 
* 날  짜: 
* 작성자: 이영수
***********************************************
* 
*/

class DB {
	public static $bindObj;

	public function __construct($dsn='default') {

		$thisObj = &$this;
		$thisObj = self::dsn($dsn);

		$this->bindObj = $thisObj;
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	public function __call($method,$args) {
		
		switch ($method) 
		{ 
            case 'num_rows': 
            echo 123;
                break; 

            default : 
				if (is_object($this->bindObj)) {
					$_CLASS = &$this->bindObj;
					return call_user_func_array(array(&$_CLASS,$method),$args);
				}
                break;
		}

	}
	public function __get($varname) {
		switch ($varname) {
			case 'error':
				return $this->bindObj->error;
		}
	}
	public function __set($property, $value) {
		
        if (property_exists($this, $property)) {
            return $this->{$property} = $value;
        }
        else if (property_exists($this->bindObj, $property)) {
        	return $this->bindObj->{$property} = $value;
        }
      	$this->{$property} = $value;
    }

	private static function dsn($conf) {
		$info = WebApp::getConf_real("database.${conf}");
		$ret = &self::singleton($info['dbms'],$info['host'],$info['user'],$info['pass'],$info['db'],$info['port']);
		return $ret;
	}

	public static function Connection($dsn) {
		$info = @parse_url($dsn);
		$info['db'] = substr($info['path'],1);
		$ret = self::singleton($info['dbms'],$info['host'],$info['user'],$info['pass'],$info['db'],$info['port']);
		return $ret;
	}

	private static function &singleton($scheme,$host,$user,$pass,$db) {

		static $instance;
		$signature = serialize(array($scheme, $host, $user, $pass, $db));
		$class = 'DB_'.$scheme;

		if (is_object($instance[$signature])) {
			return $instance[$signature];
		} else {
			if(is_file(_APP_PATH."lib/DB/".$scheme.".php"))	{
				require_once _APP_PATH."lib/DB/".$scheme.".php";
				$lo_db = new $class($host,$user,$pass,$db);
				$instance[$signature] = &$lo_db;
			}
			return $instance[$signature];
		}
	}
	
	// 직접쿼리 실행
	public function Real_query($sql){
		return $this->bindObj->_mysqli->query($sql);
	}
	/**
	 * 쿼리절 실행 추적 
	 * 
	 * @uses 쿼리 실행전에 $this->DB->traceEnabled= 1 선언해야함
	 * @return array|null
	 */
	public function trace(){
		return $this->bindObj->trace;
	}
}