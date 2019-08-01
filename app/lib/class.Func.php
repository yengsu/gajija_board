<?php
/****************
* 
* 작성자: 이영수(youngsu lee)
* email: yengsu@hanmail.net
* 설   명: 
****************/
class Func
{
	protected static $bindObj;
	
	public function __construct() {

	}
	public function __destruct() {
		//unset($this);
	}
	
	/**
	 * @desc 파일 확장자 체크(실행 스크립트 있는지 체크)
	 * 
	 * @param string $filename
	 * @return boolean
	 */
	public static function fileType_Check($filename)
	{
		$ext = end((explode(".",$filename)));
		
		if ( preg_match("/(php|php3|phtml|py|cgi|pl|sh|inc|js)$/", $ext) ) {
			return false ;
		}
		return true ;
	}
	/**
	 * @desc 용량크기 변환
	 * 
	 * @param int $size
	 * @param string $from 원본크기 ['B', 'KB', 'MB', 'GB', 'TB']
	 * @param string $to 바꿀크기 ['B', 'KB', 'MB', 'GB', 'TB']
	 * @return number
	 */
	public static function changeType($size, $from, $to){
		$arr = ['B', 'KB', 'MB', 'GB', 'TB'];
		$tSayi = array_search($to, $arr);
		$eSayi = array_search($from, $arr);
		$pow = $eSayi - $tSayi;
		return $size * pow(1024, $pow);
	}
	
	/**
	 * @desc 이미지 유무 체크
	 * 
	 * @param string $mime_Type
	 * @return boolean
	 */
	public static function imageType_Check($mime_Type)
	{
		//if(!preg_match("/\.png$/", $file['name']))
		//if ( preg_match("/(\.gif|\.jpg|\.png)$/", $img_file) ) {
		// 이미지인지 체크
		if($mime_Type != 'image/gif' && $mime_Type != 'image/jpeg'  && 
			$mime_Type != 'image/png' && $mime_Type != 'image/bmp'
		){
			return false ;
		}
		return true ;
	}
	/**
	 * @desc 이미지 리사이징 계산
	 * 
	 * @param integer $img_width (이미지 가로 pixel)
	 * @param integer $img_height (이미지 세로 pixel)
	 * @param integer $Limit_width  (이미지 가로 제한 pixel)
	 * @return array ("width"=>??, "height"=>??)
	 */
	public static function imageResizing($image_width, $image_height, $Limit_width)
	{	
		if($image_width < $image_height)
		{
			if($image_width > $Limit_width)
			{
				$rst = $image_width / $Limit_width;
			}else{
				$rst = 1;
			}
			
			$rstWidth = round($image_width / $rst);
			$rstHeight = round($image_height / $rst);
		}
		else
		{
			if($image_height > $Limit_width){
				$rst = $image_height / $Limit_width;
			}else{
				$rst = 1;
			}
			$rstWidth = round($image_width / $rst);
			$rstHeight = round($image_height / $rst);
		}
		
		return array(
				"width" => $rstWidth,
				"height" => $rstHeight
		);
	}
	
	/**
	* @desc 이미지 파일명 변경
	*
	* @uses $fileName = $_FILES['??']['name']
	* @uses $rename_prefix = 파일명 앞에 붙일 식별문자
	*
	* @return string|false 이미지가 아니면 false 반환
	**/
	public static function fileRename_image($fileName, $rename_prefix="")
	{
		$scale = getimagesize($fileName['tmp_name']);
		
		if( ! $this->imageType_Check($scale['mime']) ) return false ;
		
		preg_match('/\.([^\.]*$)/', $fileName['name'], $extension);
		/*확장자*/$file_ext = strtolower($extension[1]);
		/*파일명*/$file_name = substr($fileName['name'], 0, ((strlen($fileName['name']) - strlen($file_ext)))-1);
		
		// 파일명 재정의
		//$rename_prefix = 'tpl'; // 파일 식별자
		$fileName =  $rename_prefix.'_'.mt_rand(1,100).time().'_'.$scale[0].'x'.$scale[1].'.'.$file_ext;
	
		return $fileName ;
	}
	/**
	 * @desc 파일명 변경
	 *
	 * @uses $fileName = $_FILES['??']['name']
	 * @uses $rename_prefix = 파일명 앞에 붙일 식별문자
	 *
	 * @return string
	 **/
	public static function fileRename($fileName, $rename_prefix="")
	{
		//if( ! $this->fileType_Check($fileName) ) return false ;
		
		preg_match('/\.([^\.]*$)/', $fileName, $extension);  // ex) array( 0 => ".gif", 1 => "gif" )
		/*확장자*/$file_ext = strtolower($extension[1]);
		/*파일명*/$file_name = substr($fileName, 0, ((strlen($fileName) - strlen($file_ext)))-1);
		
		// 파일명 재정의
		//$rename_prefix = 'tpl'; // 파일 식별자
		$fileName =  $rename_prefix.'_'.mt_rand(1,100).time(). '.' .$file_ext;
		
		return $fileName ;
	}
	/**
	 * @desc 배열 같은위치에 있는 키(key)를 기준으로 정렬
	 *  
	 * @param mixed (정렬시킬 배열정보, 배열키, sort 플래그, [배열키, sort 플래그......])
	 * @return mixed
	 * 
	 * @tutorial 
	 * 		$sorted = Func::array_orderby($data, 'volume', SORT_DESC);
	 * 		$sorted = Func::array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
	 * 
	 * @example
	 *  	
	 *  	$sorted = Func::array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
	 * 
	 *		$data['f'] = array('volume' => 67, 'edition' => 2);
	 *		$data['a'] = array('volume' => 86, 'edition' => 1);
	 *		$data['b'] = array('volume' => 85, 'edition' => 6);
	 *		$data['c'] = array('volume' => 98, 'edition' => 2);
	 *		$data['d'] = array('volume' => 86, 'edition' => 6);
	 *		$data['e'] = array('volume' => 67, 'edition' => 7);
	 *			
	 *		결과 ===>
	 *					$data['f'] = array('volume' => 67, 'edition' => 2);
	 *					$data['e'] = array('volume' => 67, 'edition' => 7);
	 *					$data['b'] = array('volume' => 85, 'edition' => 6);
	 *					$data['a'] = array('volume' => 86, 'edition' => 1);
	 *					$data['d'] = array('volume' => 86, 'edition' => 6);
	 *					$data['c'] = array('volume' => 98, 'edition' => 2);
	 */
	public static function array_orderby()
	{
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
					$args[$n] = $tmp;
			}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}
	
	/**
	 * @desc 다중배열에서 값을 찾아 [ 값이 들어간 키만 삭제 ]
	 * @param array $array
	 * @param integer or string $val (찾을 값)
	 * @return $array
	 */
	public static function array_searchValue_remove(&$array, $val)
	{
		if(is_array($array))
		{
			foreach($array as $key=>&$arrayElement)
			{
				if(is_array($arrayElement))
				{
					self::array_searchValue_remove($arrayElement, $val);
				}
				else
				{
					if($arrayElement == $val)
					{
						unset($array[$key]);
					}
				}
			}
		}
	}
	/**
	 * @desc 다중배열에서 키(key)를 찾아 [ 키(key)만 삭제 ]
	 * @param array $array
	 * @param integer|string|array $key (찾을 키[key])
	 * @return $array
	 */
	public static function array_searchKey_remove(&$array, $key)
	{
		if(is_array($array))
		{
			foreach($array as $k=>&$arrayElement)
			{
				if(is_array($arrayElement))
				{
					self::array_searchKey_remove($arrayElement, $key);
				}
				else
				{
					if(is_array($key)){
						foreach($key as $c){
							if($k == $c){
								unset($array[$k]);
							}
						}
					}else if($k == $key){
						unset($array[$k]);
					}
				}
			}
		}
	}
	/**
	 * @desc 단일 or 다중배열에서 키와 값을 찾아 [ 배열 값 변경 ]
	 * @param array $a
	 * @param String $key (찾을 키)
	 * @param mixed $val (찾을 값)
	 * @param mixed $update_val (변경할 값)
	 * @return $a
	 */
	public static function array_searchKeyValue_replace(&$a, $key, $val, $update_val){
		foreach($a as $k => &$v){
			if(is_array($v)){
				$r = self::array_searchKeyValue_replace($v, $key, $val, $update_val);
				
				if($r){
					if($key == $k && $val == $v){
						$a[$k] = $update_val ;
						//return true;
						//break ;
					}
				}
			}elseif($key == $k && $val == $v){
				$a[$k] = $update_val ;
				//return true;
				//break ;
			}
		}
		//return false;
	}
	/**
	 * @desc 단일 or 다중배열에서 키와 값을 찾아 [ 배열(자산의 배열과 하위배열 포함) 삭제 ]
	 * 
	 * @param array &$a 
	 * @param String $key (찾을 키)
	 * @param mixed $val (찾을 값)
	 * @return $a
	 */
	public static function array_searchKeyValue_remove(&$a, $key, $val){
		if($a)
		{
			foreach($a as $k => &$v){
				if(is_array($v)){
					$r = self::array_searchKeyValue_remove($v, $key, $val);
					if($r){
						unset($a[$k]);
					}
				}elseif($key == $k && $val == $v){
					return true;
				}
			}
		}
		return false;
	}
	/**
	 * @desc 단일 or 다중배열에서 키와 값을 찾아 [ 배열(하위배열 포함) 리턴 ]
	 * @param array $a
	 * @param String $key (찾을 키)
	 * @param mixed $val (찾을 값)
	 * @return $a
	 */
	public static function array_searchKeyValue($a, $key, $val)
	{
		if(!empty($a))
		{
			foreach($a as $k => &$v){
				
				if(is_array($v)){
					
					$r = self::array_searchKeyValue($v, $key, $val);
					if($r){
						//unset($a[$k]);
						return $a[$k];
					}
				}elseif($key == $k && $val == $v){
					return true;
				}
			}
		}
		return false;
	}
	/**
	* @desc 다중배열에서 value값 있는지 체크
	*
	* @param int|string $search_for : 찾을값(value)
	* @param array $search_in : 배열
	* @return bool
	*/
	public static function array_exist_multi_search($search_for, $search_in) {
		
		if( empty($search_in) || !is_array($search_in))
			return ;
		
		foreach ($search_in as $element) {
			if ( ($element === $search_for) ){
				return true;
			}elseif(is_array($element)){
				$result = self::array_exist_multi_search($search_for, $element);
				if($result == true)
					return true;
			}
    	}
    	return false;
	}
	/**
	* @desc 다중배열에서 value값 있으면 검색된 배열값 리턴
	*
	* @param array $array : 배열
	* @param int|string $input : 찾을값(value)
	* @return array $outputArray  : 찾은값의  배열정보
	*/
	public static function array_multi_search($array, $input){  
	    $iterator = new RecursiveIteratorIterator( new RecursiveArrayIterator($array) );  
	    foreach($iterator as $id => $sub){ 
	        $subArray = $iterator->getSubIterator(); 
	            if(@strstr(strtolower($sub), strtolower($input))){ 
	                $subArray = iterator_to_array($subArray); 
	                $outputArray[] = array_merge($subArray, array('Matched' => $id)); 
	            } 
		} 
    	return $outputArray; 
	}
	
	/**
	 * @desc 현재위치에서 상위(부모)배열 정보 가져오기
	 * 
	 * @param array $array ( 배열 데이타 )
	 * @param string|number $key (찾을 키)
	 * @param string $input (찾을값)
	 * @param number $parent_dec ( 찾은배열위치로부터 부모(depth) 위치 )
	 * @return array <multitype:, RecursiveIterator>
	 */
	public static function array_searchParentKeyValue($array, $key, $input, $parent_dec=1 ){
		$iterator = new RecursiveIteratorIterator( new RecursiveArrayIterator($array) );
		foreach($iterator as $id => $sub){
			$depth = $iterator->getDepth();
			$subArray = $iterator->getSubIterator();
			if( @strstr(strtolower($id), strtolower($key)) && @strstr(strtolower($sub), strtolower($input)) ){
				$subArray = iterator_to_array($subArray);
				
				/* $keys = array();
				for ($i = $iterator->getDepth()-1; $i>-1; $i--) {
					$keys[] = $iterator->getSubIterator($i)->key();
				} */
				$parentKey = $iterator->getSubIterator($depth-$parent_dec)->key();
				
				$subArray = array_merge($subArray, array('Matched_parentkey' => $parentKey));
				
				$outputArray = $subArray ;
				break ;
			}
		}
		//echo '<pre>';print_r($outputArray);
		return $outputArray;
	}
	
	public static function array_search($array, $key, $value)
	{
	    $results = array();
	    if (is_array($array))
	    {
	        if (isset($array[$key]) && $array[$key] == $value)
	            $results[] = $array;
	            //$results = $array;
	        foreach ($array as $subarray)
	            $results = array_merge($results, self::array_search($subarray, $key, $value));
	    }
	    //$results = array_pop($results);
	    return $results;
	}

	public static function array_Build_index($items, $parent_key) {
		if(!$parent_key) return $items ;
		$childs = array();
		foreach($items as &$item) $childs[$item[$parent_key]] = &$item;
		unset($item);
		
		return $childs;
	}
	/**
	 * @desc 연관배열정보 리턴
	 * 
	 * @param array $items
	 * @param int|string $parent_key
	 * @param int|string $child_key
	 * @return array
	 */
	public static function array_Build_RelateIndex($items, $parent_key, $child_key) {
		if(!$parent_key || !$child_key) return $items ;
		$childs = array();
		foreach($items as &$item) $childs[(int)$item[$parent_key]][] = &$item;
		unset($item);
		foreach($items as &$item) if (isset($childs[$item[$child_key]]))
		$item['children'] = $childs[$item[$child_key]];
		
		return $childs;
	}
	
	/**
	 * 
	 * @param array $file_post
	 * @param string|integer $baseKey
	 * @return void|array
	 * @tutorial Array(
						    [name] => Array(
						            [0] => foo.txt
						            [1] => bar.txt
						        )
						    [type] => Array(
						            [0] => text/plain
						            [1] => text/plain
						        )
						    [tmp_name] => Array(
						            [0] => /tmp/phpYzdqkD
						            [1] => /tmp/phpeEwEWG
						        )
						)
		를 
		
		[0] => Array(
            [name] => foo.txt
            [type] => text/plain
            [tmp_name] => /tmp/phpYzdqkD
            [error] => 0
            [size] => 2132
        )
        [1] => Array(
            [name] => foo.txt
            [type] => text/plain
            [tmp_name] => /tmp/phpYzdqkD
            [error] => 0
            [size] => 2132
        )
        로 변환
	 */
	public static function array_ReArray(&$file_post, $baseKey) { //$baseKey = 'name'
		
		if( empty($baseKey) ) return ;
		
		/* $file_ary = array();
		$file_count = count($file_post[$baseKey]);
		$file_keys = array_keys($file_post);
		
		if( $file_count > 0){
			for ($i=0; $i<$file_count; $i++) {
				foreach ($file_keys as $key) {
					$file_ary[$i][$key] = $file_post[$key][$i];
				}
			}
		} */
		$file_ary = array();
		$file_count = count($file_post[$baseKey]);
		$file_keys = array_keys($file_post);
		
		if( $file_count > 0){
			//for ($i=0; $i<$file_count; $i++) {
			foreach($file_post[$baseKey] as $i => $v){
				foreach ($file_keys as $key) {
					$file_ary[$i][$key] = $file_post[$key][$i];
				}
			}
		}
		
		return $file_ary;
	}
	
	/**
	 * ★★사용보류★★
	 * @deprecated
	 * 
	 * Groups an array by a given key.
	 *
	 * Groups an array into arrays by a given key, or set of keys, shared between all array members.
	 *
	 * Based on {@author Jake Zatecky}'s {@link https://github.com/jakezatecky/array_group_by array_group_by()} function.
	 * This variant allows $key to be closures.
	 *
	 * @param array $array   The array to have grouping performed on.
	 * @param mixed $key,... The key to group or split by. Can be a _string_,
	 *                       an _integer_, a _float_, or a _callable_.
	 *
	 *                       If the key is a callback, it must return
	 *                       a valid key from the array.
	 *
	 *                       If the key is _NULL_, the iterated element is skipped.
	 *
	 *                       ```
	 *                       string|int callback ( mixed $item )
	 *                       ```
	 * @desc array|null array_group_by( array $array, mixed $key1 [, mixed $... ] )
	 * 
	 * @return array|null Returns a multidimensional array or `null` if `$key` is invalid.
	 * 
	 * @tutorial https://gist.github.com/mcaskill/baaee44487653e1afc0d
	 */
	public static function array_group_by_oldVersion(array $array, $key)
	{
		if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key) ) {
			trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
			return null;
		}
		$func = (!is_string($key) && is_callable($key) ? $key : null);
		$_key = $key;
		// Load the new array, splitting by the target key
		$grouped = [];
		foreach ($array as $value) {
			$key = null;
			if (is_callable($func)) {
				$key = call_user_func($func, $value);
			} elseif (is_object($value) && isset($value->{$_key})) {
				$key = $value->{$_key};
			} elseif (isset($value[$_key])) {
				$key = $value[$_key];
			}
			if ($key === null) {
				continue;
			}
			$grouped[$key][] = $value;
		}
		// Recursively build a nested grouping if more parameters are supplied
		// Each grouped array value is grouped according to the next sequential key
		if (func_num_args() > 2) {
			$args = func_get_args();
			foreach ($grouped as $key => $value) {
				$params = array_merge([ $value ], array_slice($args, 2, func_num_args()));
				$grouped[$key] = forward_static_call_array(array('Func', 'array_group_by'), $params);
			}
		}
		return $grouped;
	}
	/**
	 * Groups an array by a given key. Any additional keys will be used for grouping
	 * the next set of sub-arrays.
	 *
	 * @author Jake Zatecky
	 *
	 * @param array $arr     The array to be grouped.
	 * @param mixed $key,... A set of keys to group by.
	 *
	 * @return array
	 */
	public static function array_group_by(array $arr, $key) //: array
	{
	    if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key)) {
	        trigger_error('array_group_by(): The key should be a string, an integer, a float, or a function', E_USER_ERROR);
	    }
	    $isFunction = !is_string($key) && is_callable($key);
	    // Load the new array, splitting by the target key
	    $grouped = [];
	    foreach ($arr as $value) {
	        $groupKey = null;
	        if ($isFunction) {
	            $groupKey = $key($value);
	        } else if (is_object($value)) {
	            $groupKey = $value->{$key};
	        } else {
	            $groupKey = $value[$key];
	        }
	        $grouped[$groupKey][] = $value;
	    }
	    // Recursively build a nested grouping if more parameters are supplied
	    // Each grouped array value is grouped according to the next sequential key
	    if (func_num_args() > 2) {
	        $args = func_get_args();
	        foreach ($grouped as $groupKey => $value) {
	            $params = array_merge([$value], array_slice($args, 2, func_num_args()));
	            //$grouped[$groupKey] = call_user_func_array('array_group_by', $params);
	            $grouped[$groupKey] = call_user_func_array(array('Func', 'array_group_by'), $params);
	        }
	    }
	    return $grouped;
	}
	/**
	 *
	 * @param string $file
	 * @return string
	 */
	public static function uri_data($file) {
		//$mime = mime_content_type($filename);
		$mime = self::mime($file);
		$base64 = base64_encode(file_get_contents($file));
		return "data:$mime;base64,$base64";
	}
	
	/**
	* @desc 도메인 파싱
	*
	* @parameter : www.devgx.com
	*
	* @return : devgx.com
	*/
	public static function domain_get($domain)
	{
	  //$pieces = parse_url($url);
	  //$domain = isset($pieces['host']) ? $pieces['host'] : '';
	  if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
	  	return $regs['domain'];
	  }
	  return false;
	}
	
	
	/**
	 * @desc html문서에서 이미지에 Domain 일괄변경
	 * 
	 * @param string $html
	 * @param string $url
	 * @return mixed $html
	 * 
	 * @example 
	 * 		$html = '<p><img alt="" src="/images/ex01_intro.jpg" style="url(background:url(/image/sos.gif);" src="sss"></p>';
	 * 		$html = html_image_replace( $html, 'http://abc.com') ;
	 * 		Result => '<p><img alt="" src="http://abc.com/images/ex01_intro.jpg" style="url(background:url(http://abc.com/image/sos.gif);" src="sss"></p>';
	 */
	public static function html_image_replace($html, $url){
		//$string = preg_replace('/<img([^>]*)src=["\']["\'\\/]([^"\']*)["\']/', '<img\1src="'.$u.'\2"', $x);
		/*image tag*/$html = preg_replace('/img.*?src=[\'\"](.*?)[\'\"]/', "img src='$url$1'", $html);
		/*css background*/$html = preg_replace('~url\(("|\')?/?(.*?)(?:\1)?\)~', 'url($1'.$url.'/$2$1)', $html) ;
		return $html ;
	}
	/**
	 * @desc html문서에서 이미지url 추출 ( img태그 또는 src에서 추출)
	 * 
	 * @param string $html
	 * @return array
	 */
	public static function html_image_get($html){
		$images = array();
		preg_match_all('/(img|src)\=(\"|\')[^\"\'\>]+/i', $data, $media);
		unset($data);
		$data=preg_replace('/(img|src)(\"|\'|\=\"|\=\')(.*)/i',"$3",$media[0]);
		foreach($data as $url)
		{
			$info = pathinfo($url);
			if (isset($info['extension']))
			{
				if (($info['extension'] == 'jpg') ||
						($info['extension'] == 'jpeg') ||
						($info['extension'] == 'gif') ||
						($info['extension'] == 'png'))
							array_push($images, $url);
			}
		}
		return (array)$images ;
	}
	
	/**
	 * 
	 * [usage] Func::QueryString_filter( string | array<key, value>,  TRUE or FALSE )
	 * 
	 * @param mixed( string or array ) $add_qs <key, value>
	 * @param boolean (redirect url 일경우)
	 * 
	 * 1. Redirect URL인 경우($redirect=TRUE)
	 * 
	 *      $add_qs = ?redir=/board/BoardComm/edit/46?bid=aa&mcode=598783
	 *    
	 *      @return  /board/BoardComm/edit/46?bid=aa&mcode=598783
	 *    
	 *    
	 * 2. Query String인 경우
	 * 
	 *     $add_qs = array('foo'=>'bar',
	 *				'baz'=>'boom',
	 *				'cow'=>'milk',
	 *				'php'=>'hypertext processor');
	 *
	 *     @return string ( ?a1=10&b2=20.... )
	 */
	public static function QueryString_filter($add_qs='', $redirect=FALSE)
	{
		if( $redirect )
		{
			$queryString = preg_match("/\=/", $add_qs) ? substr( $add_qs, strpos($add_qs,"=")+1 ) : $add_qs ;
		}
		else{
			if($_SERVER["QUERY_STRING"])
			{
				$queryStr = preg_replace("/(^[?]+)/", '', $_SERVER["QUERY_STRING"]) ;
				parse_str( $queryStr, $queryString) ;
			
				if( is_array($add_qs) )
					$queryString = array_merge($queryString, $add_qs);
	
				$queryString = http_build_query($queryString);
				if($queryString) $queryString = '?'. $queryString;
			}
			else{
				if( !empty($add_qs) )
				{
					$queryString = http_build_query($add_qs);
	
					if($queryString) $queryString = '?'. $queryString;
				}
			}
		}
		return $queryString ;
	}
	/**
	 * @desc URL Query String(Parameters) : 해당 쿼리변수 제거
	 * 
	 * @param string|array $Item (parameter variable)
	 * @param string $queryString
	 * @return string
	 * 
	 * @example $queryString = '?redir=/board/BoardComm/edit/46?bid=aa&mcode=598783'
	 * @example $queryString = 'redir=/board/BoardComm/edit/46?bid=aa&mcode=598783'
	 * @example $Item = 'bid' or array('bid', 'mcode')
	 */
	public static function QueryString_removeItem($Item, $queryString){
		if( !empty($Item) ){
			if( is_array($Item) ){
				foreach($Item as $var){
					$queryString= preg_replace_callback('/([?&])'.$var.'=[^&]+(&|$)/', function($matches) {
						return $matches[2] ? $matches[1] : '';
					}, $queryString);
				}
			}else{
				$queryString= preg_replace_callback('/([?&])'.$Item.'=[^&]+(&|$)/', function($matches) {
					return $matches[2] ? $matches[1] : '';
				}, $queryString);
			}
		}
		return $queryString;
	}
	/**
	* @desc 외부서버 파일 다운로드
	*/
	public static function download_external($URL)
	{
		$parseURL =  parse_url($URL);

		$tmp = explode('/', $URL);
		$new_file_name = $tmp[count($tmp)-1];
		
		$fp = fsockopen($parseURL['host'],80,$errno,$errstr,5); 
		if ( $fp ) { 
		    $out = 'GET '.$parseURL['path'].' HTTP/1.0'."\r\n"; 
		    $out.= "Host: ".$parseURL['host']."\r\n"; 
		    $out.= 'Connection: Close'."\r\n\r\n"; 
		    fwrite($fp,$out); 
		    $content = ''; 
		    while ( !feof($fp) ) $content.= fread($fp,128); 
		    fclose($fp); 
		    $content = substr($content, strpos($content,"\r\n\r\n")+4); 
		    //$ls_type = mime_content_type($ars_file);
		    $ls_type = self::mime($ars_file);
		    header('Content-Type: '.$ls_type); 
		    header('Content-Disposition: attachment; filename="'.$new_file_name.'"'); 
		    header('Content-Transfer-Encoding: binary'); 
		    header("Pragma: no-cache");
			 header("Expires: 0");
		    header('Content-Length: '.strlen($content)); 
		    echo $content; 
		} 
	}
	/**
	 * 
	 * @param string $ars_file
	 * @param string $ars_name
	 * @return boolean
	 * @deprecated
	 */
	public function downloads($ars_file, $ars_name) {
		//echo $ars_file."....".$ars_name;exit;
		if(is_file($ars_file) && !empty($ars_name)){
			//$ls_downName = urlencode($this->decode_euckr($ars_name));
			$ls_downName = $ars_name;
			//Header("Content-type: file/unknown");
			//$ls_type = mime_content_type($ars_file);
			$ls_type = self::mime($ars_file);
			header('Content-Description: File Transfer');
			header("Content-type: $ls_type");
			header('Content-Disposition: attachment; filename="'.$ls_downName.'"');
			header('Content-Transfer-Encoding: binary');
			header("Content-Description: PHP5 Generated Data");
			header("Cache-Control: cache, must-revalidate");
			header("Pragma: no-cache");
			header("Expires: 0");
			header("Content-Length: ".(string)(filesize("$ars_file")));
			ob_clean();
			flush();
			readfile($ars_file);
			return true;
		}
		else {
			return false;
		}
	}
	public static function mime($file) {
    	if (function_exists('finfo_open')) {
    	    $finfo = finfo_open(FILEINFO_MIME);
    	    $finfo = finfo_file($finfo, $file);
    	    finfo_close($finfo);
    	    if (!$finfo) {
    	        return false;
    	    }
    	    list($type, $charset) = explode(';', $finfo);
    	    return $type;
    	}
    	if (function_exists('mime_content_type')) {
    	    return mime_content_type($file);
    	}
	}
	/**
	 * 파일 다운로드
	 * @param string $filePath
	 * @param string $output_filename 다운로드시 파일명( "abc.jpg" or "abc.xlsx" or "abc.pdf" ... )
	 * //@deprecated
	 */
	public static function download($filePath, $output_filename='') 
	{     
	    /* echo $filePath."<br>" ;
	    if(is_file($filePath)) echo "yes";
	    else echo 'no'; */
    if(!empty($filePath)) 
    { 
        $fileInfo = pathinfo($filePath); 
        $fileName = !empty($output_filename) ? $output_filename : $fileInfo['basename']; 
        $fileExtnesion   = $fileInfo['extension']; 
        $default_contentType = "application/octet-stream"; 
        //$content_types_list = mime_content_type($filePath); 
        $content_types_list = self::mime($filePath);
        
        if (array_key_exists($fileExtnesion, $content_types_list))  
        { 
            $contentType = $content_types_list[$fileExtnesion]; 
        } 
        else 
        { 
            $contentType =  $default_contentType; 
        } 
        if(file_exists($filePath)) 
        { 
            $size = filesize($filePath); 
            $offset = 0; 
            $length = $size; 

            if(isset($_SERVER['HTTP_RANGE'])) 
            { 
                preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches); 
                $offset = intval($matches[1]); 
                $length = intval($matches[2]) - $offset; 
                $fhandle = fopen($filePath, 'r'); 
                fseek($fhandle, $offset); // seek to the requested offset, this is 0 if it's not a partial content request 
                $data = fread($fhandle, $length); 
                fclose($fhandle); 
                header('HTTP/1.1 206 Partial Content'); 
                header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $size); 
            }
            
            header("Content-Disposition: attachment;filename=".$fileName); 
            header('Content-Type: '.$contentType); 
            header("Accept-Ranges: bytes"); 
            header("Pragma: public"); 
            header("Expires: -1"); 
            header("Cache-Control: no-cache"); 
            header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0"); 
            header("Content-Length: ".filesize($filePath)); 
            $chunksize = 8 * (1024 * 1024); //8MB (highest possible fread length) 
            if ($size > $chunksize) 
            { 
              $handle = fopen($_FILES["file"]["tmp_name"], 'rb'); 
              $buffer = ''; 
              while (!feof($handle) && (connection_status() === CONNECTION_NORMAL))  
              { 
                $buffer = fread($handle, $chunksize); 
                print $buffer; 
                ob_flush(); 
                flush(); 
              } 
              if(connection_status() !== CONNECTION_NORMAL) 
              { 
                echo "Connection aborted"; 
              } 
              fclose($handle); 
            } 
            else  
            { 
              ob_clean(); 
              flush(); 
              readfile($filePath); 
            } 
         } 
         else 
         { 
           echo 'File does not exist!'; 
         } 
    } 
    else 
    { 
        echo 'There is no file to download!'; 
    } 
}  
	/**
	 * @desc stdClass -> Array 로 변경
	 * 
	 * @param object $Objects
	 * @return array|object $Objects
	 * @example
	 * 		$Objects = new stdClass;
	 *	 	$Objects->foo = "Test data";
	 *		$Objects->bar = new stdClass;
	 *		$Objects->bar->baaz = "Testing";
	 *		$Objects->bar->fooz = new stdClass;
	 *		$Objects->bar->fooz->baz = "Testing again";
	 *		$Objects->foox = "Just test";
	 */
	public function objectToArray($Objects) {
		if (is_object($d)) {
			$d = get_object_vars($Objects);
		}
	
		if (is_array($Objects)) {
			/*
			 * Return array converted to object
			 * Using __FUNCTION__ (Magic constant)
			 * for recursive call
			 */
			return array_map(__FUNCTION__, $Objects);
		} else {
			// Return array
			return $Objects;
		}
	}
	
	/**
	 * Array -> stdClass 로 변경
	 *
	 * @param array $Arrays
	 * @return object|array $Objects
	 */
	public function arrayToObject($Arrays) {
		if (is_array($Arrays)) {
			/*
			 * Return array converted to object
			 * Using __FUNCTION__ (Magic constant)
			 * for recursive call
			 */
			return (object) array_map(__FUNCTION__, $Arrays);
		} else {
			// Return object
			return $Arrays;
		}
	}
	
	/**
	 * Email 보내기  : html
	 * 
	 * :::: 일반 email : 첨부파일이 없음
	 * :::: 첨부 email : 첨부파일 있음
	 * 
	 *  내용입력은 html형
	 *  
	 * @param string|array $To (받는사람 email)
	 * @param string $From (보내는 사람 email)
	 * @param string $Subject (제목)
	 * @param string $Messages (내용)
	 * @param array $Attach (첨부파일)
	 * 		$Attach = array(
	 *							"files" => (array) 첨부파일[이미 서버에 저장된 파일],
	 *							"upload => array(
	 *												"dir" => "업로드 경로 (..../)",
	 *												"files" => $_FILES
	 *											)
	 *						);
	 * @param boolean $flag (default : false)
	 * 
	 * 
	 * @example parameter $To
	 * //		"sample@gmail.com"
	 * //		array("sample@gmail.com", "aaa@hanmail.net", "cccc@naver.com".....)
	 * 
	 * @example parameter
	 * 		$Attach = array(
	 *							"files" => array("aaaa/1.txt", "bbbbb/cc/2.png", "data/year/live.pdf".....),
	 *							"upload => array(
	 *												"dir" => "upload_tmp/",
	 *												"files" => $_FILES
	 *											)
	 *						);
	 *							
	 * @tutorial $To에 담아서 많은사람에게 보낼경우 php.ini의 memory_limit 을 설정바람
	 * 
	 *   -----------------------
	 *   대량메일발송 또다른 방법
	 *   -----------------------
	 *   ■ 파라미터 $Flag를 true로 설정
	 *  
	 *   ■ $result = mailSend('', $From, $Subject, $Messages, $Attach, $flag=true) ;
	 *   ■ Loop시작
	 *   ■ 	mail("받는 Email", $result["subject"], $result["message"], $result["headers"]);
	 *   ■ Loop종료
	 */
	public static function mailSend($To, $From, $Subject, $Messages, $Attach=array(), $Flag=false)
	{
		
		$Subject= "=?UTF-8?B?".base64_encode($Subject)."?=";
		
		if( !empty($Attach) )
		{
			//-------------------------
			// 첨부 메일 보내기
			//-------------------------
			$AllowedExtensions = ["pdf","doc","docx","gif","jpeg","jpg","png","rtf","txt"];
			$upload_file= []; // 첨부할 파일
			
			if(isset($Attach["upload"]["files"]) && (bool) $Attach["upload"]["files"]) {
				foreach($Attach["upload"]["files"] as $name => $file) {
					$file_name = $file["name"];
					$file_temp = $file["tmp_name"];
					foreach($file_name as $key) {
						$path_parts = pathinfo($key);
						$extension = strtolower($path_parts["extension"]);
						if(!in_array($extension, $AllowedExtensions)) {
							return false ;
							break ;
						}
						$upload_file[] = $Attach["upload"]["dir"] . $path_parts["basename"];
					}
					for($i = 0; $i<count($file_temp); $i++) { move_uploaded_file($file_temp[$i], $upload_file[$i]); }
				}
			}
			// 병합(업로드파일과 이미저장된 파일)
			$Attach_file = array_unique(array_merge($upload_file, $Attach["files"])) ;

			$headers = "From: $From";
			
			$semi_rand = md5(time());
			$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
			$headers .= "\nX-Priority: 1";
			$headers .= "\nMIME-Version: 1.0\n"
					. "Content-Type: multipart/mixed;\n"
					. " boundary=\"{$mime_boundary}\"";
					
			$message = "This is a multi-part message in MIME format.\n\n"
							. "--{$mime_boundary}\n"
							. "Content-Type: text/html; charset=utf-8\"\n"
							//. "Content-Type: text/plain; charset=\"iso-8859-1\"\n"
							. "Content-Transfer-Encoding: 7bit\n\n"
							. $Messages. "\n\n";
			$message .= "--{$mime_boundary}\n";

			$FfilenameCount = 0;
			for($i = 0; $i<count($Attach_file); $i++) 
			{
				if( is_file($Attach_file[$i]) )
				{
					preg_match("/([^\/]+$)/", $Attach_file[$i], $match);
					$file_name = $match[0] ;
					
					$afile = fopen($Attach_file[$i],"rb");
					$data = fread($afile,filesize($Attach_file[$i]));
					fclose($afile);
					$data = chunk_split(base64_encode($data));
					$name = $file_name ;
					$message .= "Content-Type: {\"application/octet-stream\"};\n"
							. " name=\"$name\"\n"
							. "Content-Disposition: attachment;\n"
							. " filename=\"$name\"\n"
							. "Content-Transfer-Encoding: base64\n\n"
							. $data . "\n\n";
					$message .= "--{$mime_boundary}\n";
				}
			}
			if( $Flag )
			{
					return array(
										"subject" => $Subject,
										"message" => $message,
										"headers" => $headers
									);
			}
			else{
				
				if( is_array($To) ) // 보낼메일이 N개 인경우
				{
					$notAccapt = array();
					for($i=0; $i < count($To); $i++){
						if( !mail($To[$i], $Subject, $message, $headers) )
							array_push($notAccapt, $To[$i]) ;
					}
					//unlink($upload_file);
					if( empty($notAccapt) ){ // 모두 성공이면 true
						return true ;
					}else{ // 하나라도 실패하면 전송안된 메일을 리턴함
						return $notAccapt ;
					}
							
				}
				else{ // 보낼메일이 1개 인경우
					
					$res = mail($To, $Subject, $message, $headers) ;
					if($res)
						return true ;
						else
							return false ;
				}
				
			}
		}
		else{
		//-------------------------
		// 일반메일 보내기
		//-------------------------
			$headers = "From: $From\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			//$header .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			$headers .= "Content-Type: text/html; charset=utf-8\r\n";
			$headers .= "X-Priority: 1\r\n";
			
			if( $Flag )
			{
				return array(
						"subject" => $Subject,
						"message" => $message,
						"headers" => $headers
				);
			}
			else{
				if( is_array($To) ) // 보낼메일이 N개 인경우
				{
					$notAccapt = array();
					for($i=0; $i < count($To); $i++){
						if( !mail($To[$i], $Subject, $Messages, $headers) )
							array_push($notAccapt, $To[$i]) ;
					}
					if( empty($notAccapt) ) // 모두 성공이면 true
						return true ;
					else // 하나라도 실패하면 전송안된 메일을 리턴함
						return $notAccapt ;
					
				}
				else{ // 보낼메일이 1개 인경우
					
					$res = mail($To, $Subject, $Messages, $headers) ;
					if($res)
						return true ;
					else
						return false ;
				}
				
			}
		}
	}
	
	public static function mobileCheck()
	{
		//Check Mobile
		$mAgent = array("iPhone","iPad","Android","Blackberry",
				"Opera Mini", "Windows ce", "Nokia", "sony" );
		$chkMobile = false;
		for($i=0; $i<sizeof($mAgent); $i++){
			if(stripos( $_SERVER['HTTP_USER_AGENT'], $mAgent[$i] )){
				return 1;
				break;
			}
		}
		return false ;
		
	}
	/**
	 * 날짜 범위 추출
	 *
	 * @param int $timestamp
	 * @return array (int, int) 하루(0시,23시)
	 */
	public static function get_allDayTime($timestamp)
	{
		if( (int)$timestamp )
		{
			$Year = date('Y', $timestamp);
			$Month = date('m', $timestamp);
			$Day = date('d', $timestamp);
			$s_date = mktime(0, 0, 0, $Month, $Day, $Year) ;
			$e_date = mktime(23, 59, 59, $Month, $Day, $Year) ;
			
			return array( (int)$s_date,(int)$e_date ) ;
		}
	}
	/**
	 * Curl 서버에 요청
	 *
	 * @param array $DATA array("url"=>URL + Query String, "post"=>??, "header"=>?? )
	 * @return string|array
	 */
	public static function curl_Request($DATA)
	{
		$session = curl_init();
		//curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($session, CURLOPT_URL, $DATA["url"]) ; // URL + Query String
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $DATA['post']);
		
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt ($ch, CURLOPT_TIMEOUT, 30); // TimeOut 값
		if(!empty($DATA['header'])) curl_setopt($session, CURLOPT_HTTPHEADER, array($DATA['header']));
		
		$response = curl_exec($session);
		
		$results = json_decode($response, true);
		
		$info = curl_getinfo($session);
		//http://php.net/manual/en/function.curl-getinfo.php
		//$info = curl_getinfo($session, CURLINFO_HTTP_CODE );
		
		//echo 'result<pre>';print_r($results);
		curl_close($session);
		
		return array(
				"info" => $info,
				"response" => $results
		) ;
	}

}