<?php
/**
* 파일명: lib/class.Display.php
* 작성일: 
* 설  명: 디스플레이 클래스 (Template_ 확장)
*****************************************************************
*/
require_once dirname(__FILE__)."/Template_.class.php";

class Display extends Template_{

 	private $doc ;
	var $template_dir = '.';

	var $compile_dir = '/cache/template';
	var $cache_dir = '/cache/output';
	var $prefilter = 'emulate_include|adjustPath & .|customtag';
	var $postfilter = 'arrangeSpace';
    var $html_head = '';
    var $html_body = '';
    var $layout = '';



	public function __construct(){
		$this->doc = _DOC_ROOT;
		$this->compile_dir = $this->doc.'/cache/template';
		$this->cache_dir = $this->doc.'/cache/output';
		
		if (func_num_args()) 
		{
			$aa = func_get_arg();
			$this->setLayout(func_get_arg());
		}
	}
	public function __destruct(){
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	public function __call($method, $arguments){
		return call_user_func_array(array(__CLASS__, $method), self::refValues($arguments));
	}

	public function setLayout($conf) 
	{
		
		if ($conf{0} == '@') 
		{
			$_file = self::getTemplate('conf/layout.conf.php');
			$conf = substr($conf,1);
		}
		else 
		{
			$_file = self::getTemplate('conf/layout.conf.php');
			if(!$conf) $conf = 'main';
		}

		$layout_conf = parse_ini_file($_file,true);

		if( array_key_exists($conf, $layout_conf) ){
		
			$this->layout = $conf;
			$layers = $layout_conf[$this->layout];
		}else {
			$this->layout = 'main';
		 	$layers = '';

		}
		@array_walk($layers,array(&$this,'cb_apply_theme'));

		if ($this->layout == 'frameset') 
		{
			$this->define('LAYOUT', _APP_PATH.'_theme/'.THEME.'/_layout.blank.html');
		}
		else 
		{
			$this->define($layers);
		}

	}
	public function define($arg, $path='') {
		if ( ! is_array($arg) ) {
			$path = $this->cb_apply_theme($path);
			$this->_define($arg, $path);
		} else {
			foreach ((array)$arg as $fid => $path) {
				$path = $this->cb_apply_theme($path);
				$this->_define($fid, $path);
			}
		}
	}

	public static function define_doc($area,$str) {
		$this->define('#'.$area,$str);
	}

	/**
	* WebApp::getTemplate()
	* 템플릿 경로를 리턴한다.
	* 
	* @param string $filename
	* @return string
	*/
	public static function getTemplate($filename, $THEME = THEME, $HOST = HOST) {
		if(!$filename) return NULL;

		$THEME = THEME;

		$tpl_order = array(
			$filename,
			'theme/'.THEME.'/html/'.$filename,
			'html/'.$filename,
			_APP_PATH.'_html/'.$filename,
			_APP_PATH.'_theme/'.$THEME.'/'.$filename,
			_APP_PATH.'_theme_lib/skin/'.$filename
		);
		//echo '<pre>';print_r($tpl_order);
		foreach($tpl_order as $template){
			if (!is_file($template)) continue;
			return $template;
			break;
		}
		if (!is_file($template)) return 'html/blank.html';
	}

	/**
	* WebApp::array_merge_recursive2()
	* 다차원 배열을 병합한다.
	*/
	public static function array_merge_recursive2($arr1, $arr2) {
		if (!is_array($arr1) or !is_array($arr2)) return $arr2;
		foreach ($arr2 AS $key2 => $val2) $arr1[$key2] = self::array_merge_recursive2(@$arr1[$key2], $val2);
		return $arr1;
	}

	/**
	* WebApp::getMainConf()
	* 메인페이지에 표시되는 메인 모듈들에 대한 설정을 가져온다
	*/
	public static function getMainConf($sect = false, $flag = false, $THEME = THEME, $HOST = HOST) {
		static $MAIN_CONF;
		if($flag || !$MAIN_CONF) {
			$MAIN_CONF = @parse_ini_file(_APP_PATH.'_theme/'.$THEME.'/conf/main.conf.php',true);
            if (!is_array($local_conf = @parse_ini_file('html/conf/main.conf.php',true))) $local_conf = array();
			$MAIN_CONF = self::array_merge_recursive2($MAIN_CONF,$local_conf);
		}
        
		return ($sect ? $MAIN_CONF[$sect] : $MAIN_CONF);
	}


    public static function push_head($str) {
        $this->html_head.= $str."\n";
    }

    public static function push_body($str) {
        $this->html_body.= $str."\n";
    }

	public function printAll() {
		if (isset($this->tpl_['CONTENT']) && $this->tpl_['CONTENT']) {

            if ($this->html_head || $this->html_body) {

                $output = $this->fetch('LAYOUT');
                echo preg_replace(
                    array('@</head@','@</body@'),
                    array($this->html_head.'</head', $this->html_body.'</body'),
                    $output
                );

            } else {

					$this->print_('LAYOUT');
					ob_flush();
					flush();
            }
		
		
		}else{

			$this->print_('LAYOUT');
		}
	}

	public static function text_cut($str, $len, $suffix="...") { 
	  if ($len >= strlen($str)) return $str;
	  $klen = $len - 1;
	  while (ord($str{$klen}) & 0x80) $klen--;
	  return substr($str, 0, $len - ((($len + $klen) & 1) ^ 1)) . $suffix;
	}

	public static function cb_apply_theme(&$arr) 
	{
		if (isset($arr{0}) && $arr{0} == '@') {
			//if( !$arr = 'html/layout/'.substr($arr,1)) )
			//$arr = self::getTemplate('html/'.substr($arr,1));
			$arr = self::getTemplate('theme/'.THEME.'/'.substr($arr,1));
		}else{
			$arr = self::getTemplate($arr);
		}
		
		return $arr;
	}

}