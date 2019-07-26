<?php
/**
* 파일명: 
* 작성일: 
* 작성자: 이영수
* 설  명: 모듈을 embed 하는 태그
*****************************************************************
* 
*/
if ($innerHTML) {
    $hash = md5($innerHTML) ;
    $dynTemplate = 'cache/dynamic/'.$GLOBALS['__html__'].'/'.$hash;
    if (!is_file($dynTemplae)) {
        savetofile($dynTemplate,$innerHTML);
    }
    if (!$attr['template']) $attr['template'] = $dynTemplate;
}

/*
 * class method 호출시
 * 
 * @형식 : <provider:applet method="클래스 메서드명" ..> ... </provider:applet>
 * @example
 * 		html에 선언시 : <provider:applet method="test" bid="abc" serial="32" ignore="0">
 * 		parsing 결과: { applet->test('abc', 32) }
 */

if(array_key_exists('method', $attr))
{
	if(empty($attr['method'])) return ;

	$tmp_params = array();
	$method = $attr['method'] ;
	
	unset($attr['method'], $attr['ignore']);
	
	$attr = json_encode($attr) ;
	$attr = base64_encode($attr);
	
	return  "{ applet->".$method."('".$attr."') }";
}
	/*
	 * class module 호출시
	 *
	 * @형식 : <provider:applet module="모듈파일" ..> ... </provider:applet>
	 * @example
	 * 		html에 선언시 : <provider:applet module="test/1.php" ignore="0" bid="aa">
	 * 		parsing 결과: WebApp::call('applet',array('module'=>"test/1.php",'ignore'=>"0",'bid'=>"aa",'template'=>"cache/dynamic/html/main.htm/5286164...
	 */
else{
	if(empty($attr['module']) || empty($tagName)) return ;

	$ret = "<?\n";
	$ret.= "WebApp::call($tagName, ".array2php($attr).");";
	$ret.= "\n?>"; 
	return $ret;

}