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
 * class module 호출시
 *
 * @형식 : <provider:plugin module="모듈파일" ..> ... </provider:applet>
 * @example
 * 		html에 선언시 : <provider:plugin module="test/1.php" ignore="0" bid="aa">
 * 		parsing 결과: WebApp::call('applet',array('module'=>"test/1.php",'ignore'=>"0",'bid'=>"aa",'template'=>"cache/dynamic/html/main.htm/5286164...
 */
if(empty($attr['module']) || empty($tagName)) return ;
$ret = "<?\n";
$ret.= "WebApp::call($tagName, ".array2php($attr).");";
$ret.= "\n?>";
return $ret;