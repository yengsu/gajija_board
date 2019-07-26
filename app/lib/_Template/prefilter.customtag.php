<?php
/**
* $Id$
* Description : custom tag engine
*****************************************************************
*
*/
function customtag($source,$tpl) {
	
	$GLOBALS['__HTML__'] = $tpl->tpl_path;
	
	if( strpos($tpl->tpl_path, "\\") !== 0 ) // os -> win
	{
		$tmp = str_replace('\\', '/', $tpl->tpl_path);
		$GLOBALS['__HTML__'] = str_replace($_SERVER['DOCUMENT_ROOT'].'/', '',$tmp);
	}

	$GLOBALS['__html__'] = str_replace($_SERVER['DOCUMENT_ROOT'].'/','',$GLOBALS['__HTML__']);
    //$GLOBALS['__html__'] = ereg_replace('^'.realpath(getenv('DOCUMENT_ROOT')).'/','',$GLOBALS['__HTML__']);

	$dir = 'cache/dynamic/'.$GLOBALS['__html__'];
    $d = @dir($dir);

    if ($d) {
        while ($file = $d->read()) {
            if ($file == '.' || $file == '..') continue;
            if (is_dir($file)) continue;
            @unlink($dir.'/'.$file);
        }
    }

    return preg_replace_callback(
   		'%<([a-zA-Z0-9-_]+):([a-zA-Z0-9-_]+)\s*((?: [a-zA-Z0-9_]+="[^"]*")*)(?:(?: ?/>)|>(.*?)</\\1:\\2>)%is',
        'cb_customtag',
        $source
    );
}

function _parse_attr(&$str) {
    $ret = array();

    //preg_match_all('@([a-z][a-z0-9:_]*)[ =]+((?:"(?:\\\\\\\\|\\\\"|[^"])*")|(?:\'(?:\\\\\\\\|\\\\\'|[^\'])*\')|(\s+))@i',$str,$reg,PREG_SET_ORDER);
      preg_match_all('@([a-z][a-z0-9:_]*)[ =]+((?:"(?:\\\\\\\\|\\\\"|[^"])*")|(?:\'(?:\\\\\\\\|\\\\\'|[^\'])*\')|(\s+))@i',$str,$reg,PREG_SET_ORDER);
    foreach ($reg as $item) {
        list(,$key,$val) = $item;
        //$ret[strtolower($key)] = preg_replace('@(^[\'"]|[\'"]$)@','',$val);
        $ret[$key] = preg_replace('@(^[\'"]|[\'"]$)@','',$val);
    }
    return $ret;
}
/**
 * 
 * [0] => 
		{@list}
			{.key_}=>{.value_}

		{/}
		
    [1] => provider
    [2] => applet
    [3] =>  module="sub_content.main"
    [4] => 
		{@list}
			{.key_}=>{.value_}

		{/}
 * @param array $match
 * @return string
 */
function cb_customtag($match) {
	
    $xmlns = $match[1];
    $tagName = $match[2];
    $_attr = $match[3];
    $innerHTML = $match[4];
    $attr = _parse_attr($match[3]);
    
    if (is_positive($attr['ignore'])) return '';
    $taglib = _APP_LIB."WebApp/namespace/{$xmlns}/{$tagName}.php";
    if (is_file($taglib))
        return include($taglib);
    else
        return "<{$xmlns}:{$tagName} {$_attr}" . (($innerHTML) ? ">{$innerHTML}</{$xmlns}:{$tagName}>" : " />");
}

function is_positive($str) {
    $positives = array('true','yes','on','1');
    return in_array(strtolower($str),$positives);
}

function array2php($arr) {
	if (is_array($arr)) {
		$ret = array();
		foreach ($arr as $key=>$value) {
			//if(ereg("^\{(.*)\}$",$value)) {
			if(preg_match("(^\{(.*)\}$)",$value)) {
				$ret[] = $key."=>".$value;
			} else {
				$ret[] = "'".$key."'=>\"".addslashes($value)."\"";
			}
		}
		return "array(".implode(',',$ret).")";
	}
}

function array2js($arr) {	// object literal
	if (is_array($arr)) {
		$ret = array();
		foreach ($arr as $key=>$value) {
			$ret[] = $key.":\"".addslashes($value)."\"";
		}
		return "{".implode(',',$ret)."}";
	}
}

function array2param($arr) {
	if (is_array($arr)) {
		$ret = array();
		foreach ($arr as $key=>$value) {
			$ret[] = $key."=\"".addslashes($value)."\"";
		}
		return implode(' ',$ret);
	}
}

function savetofile($path,$content) {
	$parts = explode("/", $path);
	$filename = array_pop($parts);
	for ($i=0;$i<count($parts);$i++) {
		$_path.= $parts[$i]."/";
		if (!is_dir($path)) @mkdir($_path,0777);
	}

	$fp = fopen($path,'w');
	fwrite($fp,$content);
	fclose($fp);
	return is_file($path);
}
?>