<?php
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
	throw new Exception('requires PHP version 5.3 or higher.');
}
/*
 **************************************
 * 퍼미션설정 
 **************************************
 * 1. html 캐쉬처리 (707)
 * 			cache/
 * 				dynamic
 * 				template
 * 
 * 	2. XSS방어관련 캐쉬처리 (707)  ; 캐쉬디렉토리 사용안하면 성능저하가 생김(디렉토리 변경시 참조: http://htmlpurifier.org/download#toclink1)
 * 			app/lib/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer
 * 
 *  3. temp디렉토리 (707)
 *  		tmp/
 *  
 *  4. 데이타 저장디렉토리 (707)
 *  		datas/
 *  
 **************************************
 * composer 설치 (해당 폴더의 파일참조:  Readme) 
 **************************************
 * 1. 폴더:	app/lib/PhpOffice
 * 		composer require phpoffice/phpspreadsheet
 * 
 * 2. 폴더: app/lib/Api/facebook
 * 		composer require facebook/graph-sdk
 * 
 * 3. 폴더: app/lib/Api/google
 * 		composer require google/apiclient:^2.0
 * 
 **************************************
 * 웹서버
 **************************************
 * [Nginx] 
 * 		conf => /etc/nginx/nginx.conf
 * 		execute => sudo service nginx restart
 * 
 * nginx.conf 수정
 * 		client_max_body_size 100M;
 * 
 **************************************
 * php.ini 설정
 **************************************
 * [centos]
 * 		ini => /etc/php.ini
 * 		excute => sudo service php-fpm restart
 * [ubuntu]
 * 		ini : /etc/php/7.0/fpm/php.ini
 * 		excute : sudo service php7.0-fpm restart
 * 
 * php.ini 수정
 * 		max_input_vars = 5120
 * 		memory_limit = 256M
 * 
 * 		post_max_size 100M
 * 		upload_max_filesize 100M
 * 
 ***************************************
 * my.cnf 설정
 ************************************** 
 * group_concat_max_len=10240
 * 
 ***************************************
 * 
 * Warnning[ERR_BLOCKED_BY_XSS_AUDITOR] : 
 * back-end상에서 var_dump()나 print_r()과 같은 함수 사용시
 * 크롬브라우져에서 "ERR_BLOCKED_BY_XSS_AUDITOR" 오류발생하는 경우
 * 아래 header함수를 선언하면 됨 (웹서버 설정에서도 가능)
 * ==> 
 * 		header('X-XSS-Protection:0');
 *		echo '<pre>';print_r($_POST) ;
 * 
 */
header("Expires:0");
header("Pragma:no-cache");
header("Cache-Control: no-cache, must-revalidate");

header("Content-type:text/html; charset=utf-8");
//header ("Content-type: image/png; charset=euc-kr");
header('P3P: CP="NOI CURa ADMa DEVa TAIa OUR DELa BUS IND PHY ONL UNI COM NAV INT DEM PRE"');

//ini_set('memory_limit','-1');
ini_set('error_reporting', E_ALL ^ E_NOTICE);
//ini_set('error_reporting',E_ERROR|E_WARNING);
ini_set('display_errors', 'on');
@ini_set('register_globals', 'off');
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR."app/lib".PATH_SEPARATOR."app/_model" );
//ini_set("include_path", ini_get("include_path").":app/lib:./app/_model" );


ini_set('memory_limit','256M');

date_default_timezone_set('Asia/Seoul');
//ini_set("date.timezone", "UTC");

define('REQUEST_URI', strip_tags(urldecode($_SERVER['REQUEST_URI'])));

define('HOST',					$_SERVER['HTTP_HOST']);
define('REQUEST_METHOD',	$_SERVER['REQUEST_METHOD']);
define('_DOC_ROOT',			$_SERVER['DOCUMENT_ROOT']);
define('_APP_PATH',	'app/');
/**
 * path app/lib/
 */
define('_APP_LIB',	_APP_PATH.'lib/');

include_once 'class.WebApp.php';

//if( HOST && is_dir("hosts/".HOST."/tmp") ) session_save_path("hosts/".HOST."/tmp");
if( is_dir("tmp") ) session_save_path("tmp");
if (!session_id())
	session_start();

/**
 * 문자셋
 * @var string 
 */
define('UPLOAD_MAX_FILESIZE', ini_get('upload_max_filesize')) ;
/**
 * 암호화 키 (암호화/복호화)
 * @var string
 */
define('ENCRYPT_SECRET', WebApp::getConf_real('global.company.secret')); // 암호화 키(암호화/복호화) 
define('DB_KIND', WebApp::getConf_real('global.db.kind')); // DB분류(database.conf.php참고)
define('DOC_CHARSET', WebApp::getConf_real('global.doc.charset')); // 테마
define('THEME', WebApp::getConf_real('global.design.theme')); // 테마
define('TITLE', WebApp::getConf_real('global.company.title')); // 타이틀명
define('OID', (int) WebApp::getConf_real('global.company.oid')); // 업체명 WebApp::getConf('company.oid')
define('CNAME', WebApp::getConf_real('global.company.cname')); // 업체명
define('CKEYWORDS', WebApp::getConf_real('global.company.ckeywords')); // 키워드명
define('CTEL', WebApp::getConf_real('global.company.ctel')); // 업체 전화번호
define('CFAX', WebApp::getConf_real('global.company.cfax')); // 업체 FAX번호
define('CZIPCODE', WebApp::getConf_real('global.company.czipcode')); // 업체 우편번호(ZipCode)
define('CADDRESS', WebApp::getConf_real('global.company.caddress')); // 업체 주소
define('ENCRYPT_PWD', WebApp::getConf_real('global.string_encrypt.pwd')); // 문자열 암호화 엔코딩,디코딩시 암호

define('MASTER_EMAIL', WebApp::getConf_real('global.email')); // 업체 담당자 Email 정보
define('BOARD_SKIN_HOME', WebApp::getConf_real('global.board.skin_home')); // 게시판 & 갤러리 스킨 홈디렉토리
define('MNU_DEPTH', WebApp::getConf_real('global.menu.depth')); // 서브메뉴 노출시킬 깊이(depth)
define('IS_MOBILE', WebApp::mobileCheck()); // 모바일체크
//header('X-Frame-Options: DENY');
//header('X-Frame-Options: SAMEORIGIN');
//header('X-Frame-Options: ALLOW-FROM http://nid.naver.com');
//header('X-Frame-Options: ALLOW-FROM https://instagram.com');

/**
 * 상수::비동기식(Ajax)인지 체크
 * @var string (값 : "AJAX" 또는 "")
 */
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	//header('Access-Control-Allow-Origin: *');	// 크로스도메인(허용 도메인: 모두허용)
	header('Access-Control-Allow-Methods: GET, POST');	// 요청방식
	define('REQUEST_WITH', 'AJAX');
	
	//브라우져 캐쉬 사용안함
			/*
			header("Expires: Fry, 12 Jan 1990 00:00:00 GMT");
			header("Last-Modified: " . gmdate('D, d M Y H:i:s'). ' GMT');
			header("Cache-Control: no-cache, must-revalidate");
			header("Pragma: no-cache");
			*/
}else {
	define('REQUEST_WITH', '');
 	header("Content-type:text/html; charset=".DOC_CHARSET);
}

//$num = filter_input(INPUT_GET, 'mcode', FILTER_SANITIZE_STRING);
//$num = filter_input(INPUT_GET, 'x', FILTER_SANITIZE_ENCODED);
//$num = filter_input(INPUT_GET, 'x', FILTER_SANITIZE_SPECIAL_CHARS);

//$num = trim(stripslashes($_GET['x']));

// 참조 : http://www.w3bai.com/ko/php/php_ref_filter.html
function vars_filter(&$var)
{
    if( is_array($var) )
    {
        foreach($var as &$d){
            if(is_array($d)) vars_filter($d) ;
            //else $var = trim(stripslashes($d));
            else {
            	//$d = filter_var($d, FILTER_SANITIZE_MAGIC_QUOTES); // 적용 addslashes()
                $d = str_replace("\xc2\xa0", ' ', $d);
                //$d = addslashes($d);
                //$d = trim(preg_replace('/\s+/', '', $d));
                $d = trim($d);
            }
        }
    }
    else{
        //$var = trim( addslashes($var) );
    	//$var = filter_var($var, FILTER_SANITIZE_MAGIC_QUOTES); // 적용 addslashes()
        $var = str_replace("\xc2\xa0", ' ', $var);
        //$var = addslashes($var);
        //$var = trim(preg_replace('/\s+/', '', $var));
        $var = trim($var);
    }
}
if(isset($_GET)) vars_filter($_GET);
if(isset($_POST)) vars_filter($_POST);
//echo '<pre>';print_r($_POST) ;
/* array_filter($_GET, 
    function(&$value){
        //$value = stripslashes($value);
        //$value = trim (preg_replace ( '/ \ s + /', '', $value));
        $value = trim($value);
    }
); */
//echo $num;
//echo '<pre>';print_r($_GET) ;

//set_error_handler('showError');
//에러 로고파일 저장(xml)
function showError($errno, $errstr, $errfile, $errline) {
	if ($errno == E_ERROR || $errno == E_WARNING || $errno == E_PARSE)
	{
		ob_start();
		debug_print_backtrace();
		$backtrace = ob_get_contents();
		ob_end_clean();
		
		$folder_log = 'app/log/'.date('Y',time()).'/'.date('m',time()).'/'.date('d',time()).'/' ;
		$file_log = $folder_log.HOST.'-'.date('Ymd',time()).'.xml' ;
		@mkdir( $folder_log ,0707, true) ;
		
		if (file_exists($file_log)) $data = file_get_contents($file_log);
		
		$ajax_req = defined('REQUEST_WITH') ? '(AJAX)' : '' ;
		
		$data = str_replace("<?xml version=\"1.0\" encoding=\"utf-8\"?>",'', $data);
		$data = str_replace("<log>",'', $data);
		$data = str_replace("</log>",'', $data);
		
		$data.="<pageview time=\"".date('Y.m.d H:i:s', time())."\" url=\"".$_SERVER['REQUEST_URI']."\">\n";
		//$data.="<error><![CDATA[" . $errline." Line[errno:".$errno."] :: ".$errfile." => ".$errstr."]]></error>\n";
		$data.="<error><![CDATA[".$backtrace."]]></error>\n";
		 
		$data.="<method><![CDATA[" . $_SERVER['REQUEST_METHOD'].$ajax_req.  "]]></method>\n";
		$data.="<ip><![CDATA[" . $_SERVER['REMOTE_ADDR'] . "]]></ip>\n";
		$data.="<browser><![CDATA[" . $_SERVER['HTTP_USER_AGENT'] . "]]></browser>\n";
		$data.="<ref><![CDATA[" . @$_SERVER['HTTP_REFERER'] . "]]></ref>\n";
		$data.="<memory><![CDATA[" . memory_get_peak_usage() . "]]></memory>\n";
		$data.="</pageview>\n";
		$data = "<?xml version=\"1.0\" encoding=\"utf-8\"?><log>".$data."</log>\n";
		
		$handle=fopen($file_log, 'w');
		fwrite($handle, $data);
		fclose($handle);
		@chmod($file_log, 0707);
		//echo '<pre>';print_r("<b>($errno) 에러</b> $errstr $errfile 파일 <b>$errline</b> 번째 라인에서 <br>");
	}
			
}