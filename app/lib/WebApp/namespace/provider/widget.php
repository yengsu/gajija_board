<?php
/**
 * 파일명:
 * 작성일:
 * 작성자: 이영수
 * 설  명: 위젯을 embed 하는 태그
 *****************************************************************
 *
 */
if ($innerHTML) {
	$hash = md5($innerHTML) ;
	$dynTemplate = 'cache/dynamic/'.$GLOBALS['__html__'].'/'.$hash;
	//$dynTemplate = 'cache/dynamic/'.$hash;
	if (!is_file($dynTemplae)) {
		savetofile($dynTemplate,$innerHTML);
	}
	if (!$attr['template']) $attr['template'] = $dynTemplate;
}
/*
 * class method 호출시
 * @위젯파일 경로 : app/_widget
 * @형식 : <provider:widget name="위젯명" ignore="0"  ..> ... </provider:widget>
 * @example
 * 		html에 선언시 : <provider:widget name="View" bid="abc" serial="32" ignore="0">
 */
//-------------------------------------------------
if(array_key_exists('name', $attr))
{
	if(empty($attr['name'])) return ;
	
	$ret = "<?\n";
	$ret.= "WebApp::call_class(".array2php($attr).");";
	$ret.= "\n?>";
	return $ret ;
}