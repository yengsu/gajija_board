<?
/**
 * 문자(열) 처리
 *
 * @author 이영수
 * @email yengsu@hanmail.net
 */
class Strings
{
	public static $HTMLPurifier_config ;
	public static $HTMLPurifier ;
	
	public function __construct($module='')
	{
	}

	public function __destruct()
	{
		//unset($this);
	}
	
	/**
	* @desc 태그 및 스크립트 제거 - 필터링 ( conf/tagFilter.conf.php 파일 참조 )
	*
	* @param : $strings (내용)
	* @param string $remove_tag ( 필터그룹명 : conf/tagFilter.conf.php 파일 참조 )
	*
	* @return boolean|string 필터링후 결과값
	*/
	public static function tag_remove($strings, $remove_tag = "remove_tag")
	{
		if(!$strings) return false;
		
		$tags = WebApp::getConf("tagFilter.".$remove_tag);
		if (array_key_exists('tags', $tags)) $strings = strip_tags($strings);
		$strings = preg_replace($tags,"",$strings);

		return trim($strings) ;
	}
	public static function html_filter( string $str )
	{
		// &nbsp; ==> ' '
		$str = str_replace( "&nbsp;&nbsp;", '  ', $str );
		
		
		//$str = preg_replace("/\r\n|\r|\n/",'&#10;',$str); // enter replace
		
		$str = preg_replace( "/<(?:\s+|)style/si", '&#'.ord("<").';style ', $str ) ;
		$str = preg_replace( "/<(?:\s+|)link/si", '&#'.ord("<").';link ', $str ) ;
		/* $str = preg_replace_callback( "/<style/si", function($m){
		 return str_replace('<', '&#'.ord("<").'; ', $m[0] ) ;
		 }, $str) ; */
		
		//</style> ==> &#60;/style>
		$str = preg_replace( "/<(?:\s+|)\/(?:\s+|)style/si", '&#'.ord("<").';/style', $str ) ;
		//$str = preg_replace( "/<(?:\s+|)\/(?:\s+|)style/i", '&#'.ord("<").';/style', $str ) ;
		$str = preg_replace( "/<(?:\s|)\/style/i", '&#'.ord("<").';/style', $str ) ;
		
		// <script...> ==> &#60;script...>
		$str = preg_replace( "/<(?:\s|)script/i", '<&#'.ord("s").';cript', $str ) ;
		$str = preg_replace( "/<(?:\s|)\/script/i", '</&#'.ord("<").';cript', $str ) ;
		
		// javascript: ==> javascript&#58;
		//$str = preg_replace( "/javascript\:/i", '#not-javascript:', $str ) ;
		$str = preg_replace( "/script\:/i", 'script&#'.ord(':').';', $str ) ;
		/*
		$str = str_replace( "<?", '&#'.ord("<").';&#'.ord("?").';' , $str );
		$str = str_replace( "?>", '&#'.ord("?").';&#'.ord(">").';' , $str );
		*/
		$str = preg_replace( "/<(?:\s|)iframe/i", '&#'.ord("<").';iframe', $str ) ;
		$str = preg_replace( "/<(?:\s|)\/iframe/i", '&#'.ord("<").';/iframe', $str ) ;
		
		//$str = preg_replace("/<\//", '&#'.ord("<").';/', $str);
		
		$str = str_replace( "<!--", '&#'.ord("<").';!--' , $str );
		$str = str_replace( "-->", '--&#'.ord(">").';' , $str );
		$str = str_replace( "<!--?", '&#'.ord("<").';--？' , $str );
		$str = preg_replace( "/\?php/i", '？php' , $str );
		$str = str_replace( "<?", '&#'.ord("<").';？' , $str );
		$str = str_replace( "?>", '？&#'.ord(">").';' , $str );
		
		//preg_match_all('/on([\w\-]+)(?:\s?)+=(?:\s?)+([^"\']+|([\'"]?)(?:[^\3]|\3+)+?\3)/si', $input_lines, $output_array);
		
		$str = preg_replace_callback('/on([\w\-]+)(?:\s?)+=(?:\s?)+([^"\']+|([\'"]?)(?:[^\3]|\3+)+?\3)/si', function($match){
			return '';// 'not-'.$match[1].'='.$match[2];
		}, $str);
	    //$str = str_replace("=", '&#'.ord("=").';', $str);
		
		return $str ;
	}
	public static function set_xss_config()
	{
		require_once 'htmlpurifier/library/HTMLPurifier.auto.php';
		
		self::$HTMLPurifier_config = HTMLPurifier_Config::createDefault();
		self::$HTMLPurifier_config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
		
		//self::$HTMLPurifier_config->set('AutoFormat.RemoveEmpty', true); // 비어있는 태그 제거
		self::$HTMLPurifier_config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true); // &nbsp;빈공간 으로 처리
		
		// embeded, object 사용불가
		self::$HTMLPurifier_config->set( 'Filter.YouTube', false);
		
		// attribute id 관련
		self::$HTMLPurifier_config->set('Attr.EnableID', false); // attribte의 id 허용유무
		//self::$HTMLPurifier_config->set('Attr.IDPrefix', 'user_'); // attribute 의 id(사용 허용하면)명 앞에 정의된 문자를 삽입
		/* self::$HTMLPurifier_config->set('Attr.IDBlacklist', array( // attributedml id명으로 사용불가능하도록 
				'SYS', 'of', 'attribute', 'values', 'that', 'are', 'forbidden'
		)); */
		// 해당태그 사용불가
		self::$HTMLPurifier_config->set('HTML.ForbiddenElements', array('script','style','link', 'applet', 'iframe', 'svg', 'canvas')); 
		self::$HTMLPurifier_config->set('URI.DisableExternalResources', false) ; // 외부 리소스 삽입불가 img....
		
		
	}
	/**
	 * XSS 공격방지
	 * 
	 * @param string $str
	 * @return string 
	 */
	public static function set_xss_detect( string $str )
	{
		if( empty($str) || ctype_space($str)) return '' ;
		
		if( ! self::$HTMLPurifier_config instanceof HTMLPurifier_Config ) self::set_xss_config() ;
		if( ! self::$HTMLPurifier instanceof HTMLPurifier ) self::$HTMLPurifier = new HTMLPurifier();

		$str = self::$HTMLPurifier->purify($str);
		
		return $str ;
	}
	/**
	 * XSS 방어 필터링 : $_GET, $_POST, $_REQUEST
	 *
	 * @param array $vars 값이 없으면 해당하는 $_GET, $_POST, $_REQUEST 모두 적용
	 * @return void
	 * 
	 * @example $vars = array(
								 'post' => array('writer', 'pwd', 'memo'),
								 'get' => array('bid', 'keyword'..)
	 						)
	 */
	public static function set_xss_variable( array $variables = array() )
	{
		if( empty($variables) )
		{
			foreach($_REQUEST as $name => &$val)
			{
				if( $val && is_string($val) )
				{
					$val = ( empty($val) || ctype_space($val) ) ? '' : Strings::set_xss_detect( $val ) ;
					if( !empty($_POST[$name]) ) $_POST[$name] = $val ;
					if( !empty($_GET[$name]) ) $_GET[$name] = $val ;
				}
			}
		}
		else{

			foreach($variables as $method => $vars)
			{
				$method = strtoupper($method) ;
				
				for( $i=0, $l=count($vars); $i < $l; $i++)
				{
					if($method == 'POST' && !empty($_POST[$vars[$i]]) ) $var = &$_POST[$vars[$i]] ;
					else if($method == 'GET' && !empty($_GET[$vars[$i]]) ) $var = &$_GET[$vars[$i]] ;
					
					if( $var && is_string($var) )
					{
						$_REQUEST[$vars[$i]] = $var = ( empty($var) || ctype_space($var) ) ? '' : Strings::set_xss_detect( $var ) ;
					}
					
					unset($var);
				}
			}
		}
	}
	/**
	 * [문자를 -> 엔티티로 변환] 문자열중 html태그 , 큰 따옴표("), 작은 따옴표(') 모두 
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function html_encode($str, $has_filter=true, $Charset="UTF-8")
	{
		// &nbsp; ==> ' '
		//$str = str_replace( "&nbsp;&nbsp;", '  ', $str );
		
		//$str = preg_replace("/\r\n|\r|\n/",'',$str); // enter replace
		//$str = preg_replace("/<(?:\s|)br(?:\s|)(?:\/|)(?:\s|)>/", "\n", $str) ;
		$str = htmlentities($str, ENT_QUOTES, $Charset) ;
		
		return $str;
		
		// <br>, <br />, <br/> 태그 모두 ==> new line처리
		//$str = preg_replace('/(<(?:\s+|)br(?:[^>]*\/>|.*>))/is', "\n", $str);
		
		//if($has_filter === true) $str = self::remove_fillter($str) ;
		if($has_filter === true) $str = self::html_filter($str) ;
		
		//on(\w+)=[\'"]([^\'"]*)[\'"]
		/* $str = str_replace( "script", '&#'.ord("s").';cript' , $str );
		$str = preg_replace('/on(\w+)=[\\'"]([^\\'"]*)[\\'"]/si', '$0 --> $2 $1', $str); */
		
		//$str = str_replace( "onClick", '&#'.ord("s").';cript' , $str );
		
		//

		/* $str = str_replace( '$', '&#36;', $str) ;//'&#'.ord('$').';', $str ); //&#36;
		$str = str_replace( "'", '&#'.ord("'").';', $str ); //&#39;
		$str = str_replace( '"', '&#'.ord('"').';', $str ); //&#34;
		$str = str_replace( "&nbsp;", ' ', $str );
		//$str = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $str );
		$str = str_replace( "<", "&lt;" , $str );
		$str = str_replace( ">", "&gt;" , $str ); */
		
		//echo $str;exit;
		//$str = str_replace( '$', '&#36;', $str) ;//'&#'.ord('$').';', $str ); //&#36;
		//$str = str_ireplace( chr(36), '&#36;', $str) ;//'&#'.ord('$').';', $str ); //&#36;
		
		/* $str = str_replace( "<?", '&#'.ord("<") , $str );
		$str = str_replace( ">", '&#'.ord(">") , $str );
		$str = str_replace( "<", '&#'.ord("<") , $str );
		$str = str_replace( ">", '&#'.ord(">") , $str );
		//$str = str_replace( "<?", '&#'.ord("<").';&#'.ord("?").';' , $str );
		$strs = str_replace( "?>", '&#'.ord("?").';&#'.ord(">").';' , $str );
		 */
		//$str = html_entity_decode($str, ENT_NOQUOTES|ENT_HTML5, $Charset) ;
		//echo $str;exit;
		$str = htmlentities($str, ENT_QUOTES, $Charset) ;
		
		return $str;
	}
	/**
	 * [엔티티를 -> 문자로 변환] 문자열중 html태그 , 큰 따옴표("), 작은 따옴표(') 모두
	 *
	 * @param string $str
	 * @return string
	 */
	public static function html_decode($str, $has_filter=true, $Charset="UTF-8")
	{
		if( empty($str) ) return $str ;
		/*
		$str =  $str = str_replace( "<?", '<pre><code>&lt;?' , $str );
		$str =  $str = str_replace( "?>", '?&gt;</code></pre>' , $str );
		*/
		//$str = str_replace( "&nbsp;", ' ', $str );
		//$str = str_replace("  "," &nbsp;", $str);
		//$str = str_replace("\n"," <br/>", $str);
		/* $str = str_replace("#60;", "<", $str);
		$str = str_replace("#62;", ">", $str); */
		//$str = html_entity_decode($str, ENT_QUOTES|ENT_HTML5, $Charset) ;//ENT_NOQUOTES
		$str = html_entity_decode($str, ENT_NOQUOTES, $Charset) ;//ENT_NOQUOTES
		
		//if($has_filter === true) $str = self::html_filter($str) ;
		//if($has_filter === true) $str = self::html_filter($str) ;
		
		//---------------
		// 보기시
		//---------------
		/* $str = str_replace( '$', '&#36;', $str) ;//'&#'.ord('$').';', $str ); //&#36;
		$str = str_replace( "'", '&#'.ord("'").';', $str ); //&#39;
		$str = str_replace( '"', '&#'.ord('"').';', $str ); //&#34;
		$str = str_replace( "&nbsp;", ' ', $str );
		//$str = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $str );
		$str = str_replace( "<", "&lt;" , $str );
		$str = str_replace( ">", "&gt;" , $str ); */
		
		//---------------
		// 편집시
		//---------------
		/* 
		$str = str_replace( '&#36;', '$', $str) ;//'&#'.ord('$').';', $str ); //&#36;
		$str = str_replace('&#'.ord("'").';',  "'", $str ); //&#39;
		$str = str_replace('&#'.ord('"').';', '"', $str ); //&#34;
		$str = str_replace( "&nbsp;", ' ', $str );
		//$str = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $str );
		$str = str_replace( "&lt;", "<", $str );
		$str = str_replace( "&gt;", ">", $str );
		 */
		
		//$str = str_replace( "&amp;", "&" , $str );
		
		return $str;
	}
	/* public static function specialchars($ats_str="") {
		$ats_str = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $ats_str );
		$ats_str = str_replace( "<", "&lt;" , $ats_str );
		$ats_str = str_replace( ">", "&gt;" , $ats_str );
		$ats_str = str_replace( '"', "&quot;", $ats_str );
		$ats_str = str_replace( "'", '&#039;', $ats_str );
		
		$ats_str = preg_replace( "/javascript/i" , "j&#097;v&#097;script", $ats_str );
		$ats_str = preg_replace( "/alert/i" , "&#097;lert" , $ats_str );
		$ats_str = preg_replace( "/about:/i" , "&#097;bout:" , $ats_str );
		$ats_str = preg_replace( "/onmouseover/i", "&#111;nmouseover" , $ats_str );
		$ats_str = preg_replace( "/onclick/i" , "&#111;nclick" , $ats_str );
		$ats_str = preg_replace( "/onload/i" , "&#111;nload" , $ats_str );
		$ats_str = preg_replace( "/onsubmit/i" , "&#111;nsubmit" , $ats_str );
		$ats_str = preg_replace( "/document\./i" , "&#100;ocument." , $ats_str );
		
		return trim($ats_str);
	} */
	/**
	* @desc 자동증가값
	*
	* @param int|string $Data  
	* @uses 기본값이 없을경우 시작값. 
	*	값의 형식은 0-9A-Za-z만 가능.
	*	특수문자 경고(특수문자가 뒷쪽에 있는경우 동일한 값이 나옴)
	* @return mixed
	*/
	public static function _auto_increment($Data)
	{
		if( preg_match("/([0-9A-Za-z]+)ui", $Data) )
			return $Data++;
		else return false;
	}
	
	/**
	 * @desc 반복되는 단어를 제거 (대소 문자 구분)
	 * @param string $text
	 * @return string
	*/
	public static function dup_remove($text)
	{
		$text = preg_replace("/\s(\w+\s)\1/i", "$1", $text);
		$text = preg_replace("/\.+/i", ".", $text); 
		
		return $text ;
	}
	
	public function utf8_strlen($str) { return mb_strlen($str, 'UTF-8'); }
	public function utf8_charAt($str, $num) { return mb_substr($str, $num, 1, 'UTF-8'); }
	public function utf8_ord($ch) {
		$len = strlen($ch);
		if($len <= 0) return false;
		$h = ord($ch{0});
		if ($h <= 0x7F) return $h;
		if ($h < 0xC2) return false;
		if ($h <= 0xDF && $len>1) return ($h & 0x1F) <<  6 | (ord($ch{1}) & 0x3F);
		if ($h <= 0xEF && $len>2) return ($h & 0x0F) << 12 | (ord($ch{1}) & 0x3F) << 6 | (ord($ch{2}) & 0x3F);          
		if ($h <= 0xF4 && $len>3) return ($h & 0x0F) << 18 | (ord($ch{1}) & 0x3F) << 12 | (ord($ch{2}) & 0x3F) << 6 | (ord($ch{3}) & 0x3F);
		return false;
	}
	
	/**
	 * @desc 태그 필터링
	 *
	 * @param string $data (html tag 리소스)
	 * @return string
	 */
	public static function remove_fillter($data){
		//$data = strip_tags($data) ;
		//$data = html_entity_decode($data) ;
		$data= preg_replace('/&#(\d+);/m',"chr(\\1)",$data); #decimal notation
		$data= preg_replace('/&#x([a-f0-9]+);/mi',"chr(0x\\1)",$data);  #hex notation
		$data= preg_replace("/(&#[0-9]+;)/","",$data); #decimal notation
		$data= preg_replace("/(&[a-z]+;)/i","",$data); # &nbsp;  &lt;  &gt; ....
		$data= preg_replace("/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</","",$data); # all script convert
		$data= preg_replace(
		array('/(?:^\\s*<!--|-->\\s*$)/','/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/'),
		'', $data); // 주석제거
		$data = str_replace(array('<![CDATA[', ']]>','<!--','-->','<?','?>','<!','/*','*/'), '', $data);
		$data = preg_replace("/\r\n|\r|\n/",'&#10;',$data); // enter replace
		$data = trim($data);
		return $data ;
	}
	
	/**
	 * 범용 글자 줄이기
	 *
	 * @param string $str (텍스트)
	 * @param integer $len (길이)
	 * @param string $suffix ( 줄임 식별기호 : ... )
	 * @return string
	 */
	public static function text_cut($str, $len, $suffix="...") {
		if ( !$len || $len >= mb_strlen($str)) return $str;
		$klen = $len - 1;
		while (ord($str{$klen}) & 0x80) $klen--;
		return mb_substr($str, 0, $len - ((($len + $klen) & 1) ^ 1)) . $suffix;
	}
	
	public static function utf8_length($str) {
		$len = strlen($str);
		for ($i = $length = 0; $i < $len; $length++) {
			$high = ord($str{$i});
			if ($high < 0x80)//0<= code <128 범위의 문자(ASCII 문자)는 인덱스 1칸이동
				$i += 1;
			else if ($high < 0xE0)//128 <= code < 224 범위의 문자(확장 ASCII 문자)는 인덱스 2칸이동
				$i += 2;
			else if ($high < 0xF0)//224 <= code < 240 범위의 문자(유니코드 확장문자)는 인덱스 3칸이동
				$i += 3;
			else//그외 4칸이동 (미래에 나올문자)
				$i += 4;
		}
		return $length;
	}
	
	/**
	 * UTF-8용 문자 자르기
	 *
	 * 출처 : http://blog.lael.be/post/77
	 * @param string $str (텍스트)
	 * @param int $chars (길이)
	 * @param string $tail ( 줄임 식별기호 : ... )
	 * @return string
	 */
	public static function utf8_strcut($str, $chars, $tail = '...') {
		if (self::utf8_length($str) <= $chars)//전체 길이를 불러올 수 있으면 tail을 제거한다.
			$tail = '';
		else
			$chars -= self::utf8_length($tail);//글자가 잘리게 생겼다면 tail 문자열의 길이만큼 본문을 빼준다.
		$len = strlen($str);
		for ($i = $adapted = 0; $i < $len; $adapted = $i) {
			$high = ord($str{$i});
			if ($high < 0x80)
				$i += 1;
			else if ($high < 0xE0)
				$i += 2;
			else if ($high < 0xF0)
				$i += 3;
			else
				$i += 4;
			if (--$chars < 0)
				break;
		}
		return trim(substr($str, 0, $adapted)) . $tail;
	}
	/**
	 * 글자수만큼 나눠서 저장
	 * 
	 * @param string $str 문자열
	 * @param integer $chars 나눌 문자갯수
	 * @param array &$data  저장되는 변수 
	 * @return array 결과값
	 * 
	 * @example 
	 * 		$a = String::utf8_strcut('우리나라 대한민국 입니다..', 10);
	 * 		$a값은 $a = array(
	 * 				0 => '우리나라 대한민국 ',
	 * 				1 => '입니다.'
	 * 			);
	 */
	public static function strDivide(string $str, int $chars, array &$data=array()) {
		
		$len = strlen($str);
		$lens = $chars ;
		
		for ($i = $adapted = 0; $i < $len; $adapted = $i) 
		{
			$high = ord($str{$i});
			$s = $i;
			if ($high < 0x80)
				$i += 1;
			else if ($high < 0xE0)
				$i += 2;
			else if ($high < 0xF0)
				$i += 3;
			else
				$i += 4;
				
			if (--$chars < 0){
				break;
			}
		}
		
		$datas = substr($str, 0, $adapted);
		$data[] = $datas;
		$str = str_replace($datas,'', $str) ;
		if(! empty($str)) self::utf8_strcut($str, $lens, $data);
		
		return $data;
	}
	/**
	 * @desc 랜덤문자열 생성 : 영숫자 조합
	 *
	 * @param number $length (자릿수)
	 * @return string
	 */
	/* public static function shuffle_alphaNum($length=10)
	{
		$array = array_merge(range(0,9),range('A','Z'),range('a','z'));
		shuffle($array);
		$shuffleCode = implode('',array_slice($array, 0, $length));
		return $shuffleCode ;
	} */
	/**
	 * @desc 랜덤 영숫자 문자열 생성
	 *
	 * @param number $length ( 자릿수 )
	 * @param array $addChars (추가로 섞을 문자들 : 1차원 배열정보)
	 * @return string
	 */
	public static function shuffle_alphaNum($length=6, $addChars=array())
	{
		$array = array_merge(range(0,9),range('A','Z'),range('a','z'));
		if(!empty($addChars)) $array = array_merge($array, $addChars) ;
		shuffle($array);
		return implode('',array_slice($array,0,$length));
	}
	/**
	 * @desc Generate Uniq ID
	 * @param number $length
	 * @return string
	 */
	public static function GUID($length=10) {
		return strtoupper(bin2hex(openssl_random_pseudo_bytes( (int)$length )));
	}
	
	/**
	 * @desc 암호화(sha256알고리즘) 하기
	 *
	 * @param string $password
	 * @return string
	 */
	public static function encrypt_sha256( string $password )
	{
	    return hash("sha256", (string) $password) ;
	}
	/**
	 * 지난(경과)일 가져오기
	 *
	 * @param int $timestamp
	 * @return string
	 */
	public static function get_elapsed_days($timestamp)
	{
	    //경과일
	    $total_time = time() - $timestamp;
	    $days = floor($total_time/86400);
	    $time = $total_time - ($days*86400);
	    $hours = floor($time/3600);
	    $time = $time - ($hours*3600);
	    $min = floor($time/60);
	    $sec = $time - ($min*60);
	    if($days==0 && $hours==0 && $min==0) $data = $sec."초";
	    elseif($days==0&&$hours==0) $data = $min."분";
	    elseif($days==0) $data = $hours."시간";
	    else $data = $days."일";
	    
	    return $data ;
	}
	/**
	 * 지난(경과)날짜 뽑아오기
	 * 
	 * @param int $from_timestamp [timestamp] 날짜범위(시작 날짜)
	 * @param int|string $to_timestamp [timestamp] 날짜범위(마지막 날짜)
	 * 
	 * @example Strings::get_elapsed_date( 1545035904 ); // 지정된 날짜로부터 현재날짜까지 계산
	 * @example Strings::get_elapsed_date( 1545035904, 1545640771 ) ;
	 * 
	 * @return object
	 * @example return DateInterval Object
									(
									    [y] => 0 (년)
									    [m] => 10 (월)
									    [d] => 15 (일)
									    [h] => 22 (시)
									    [i] => 17 (분)
									    [s] => 20 (초)
									    [weekday] => 0 (몇주)
									    [weekday_behavior] => 0
									    [first_last_day_of] => 0
									    [invert] => 0
									    [days] => 318 (일수)
									    [special_type] => 0
									    [special_amount] => 0
									    [have_weekday_relative] => 0
									    [have_special_relative] => 0
									)
	 */
	public static function get_elapsed_date( int $from_timestamp, $to_timestamp="NOW" )
	{
		if( ! (int) $from_timestamp ) return false ;
		
		//경과일
		$From = date('Y-m-d H:i:s', $from_timestamp) ;
		
		if( (int)$to_timestamp ) $To = date('Y-m-d H:i:s', $to_timestamp) ;
		//else $To = "NOW" ; // 현재 날짜
		
		$date_from = new DateTime( $From );
		$date_to = new DateTime( $To );
		
		$interval = date_diff($date_from, $date_to);
		//echo $interval->m + ($interval->y * 12) . ' months';
		
		return $interval ;
	}
	/**
	 *  엔코딩
	 * @param string $data
	 * @return string
	 */
	public static function encrypt( $data )
	{
		//$aa = serialize($data) ;
		$data = json_encode($data) ;
		$data = str_replace('"', '', $data) ;
		return $data;
	}
	/**
	 * 디코딩
	 * @param string $data
	 * @return array
	 */
	public static function decrypt( $data )
	{
		//$aa = unserialize($data) ;
		$data = preg_replace('/(\w+)/', '"$1"', $data);
		$data = json_decode($data, true) ;
		return $data;
	}
	public static function encrypt_Pcrypt($data)
	{
		if (!class_exists('Pcrypt')) include_once _APP_LIB."class.Pcrypt.php" ;
		$encrypt_data = SMpcrypt_encode( $data ) ;
		
		return $encrypt_data;
	}
	public static function decrypt_Pcrypt($data)
	{
		if (!class_exists('Pcrypt')) include_once _APP_LIB."class.Pcrypt.php" ;
		$decrypt_data = SMpcrypt_decode( $data ) ;
		
		return $decrypt_data;
	}
	
}
?>