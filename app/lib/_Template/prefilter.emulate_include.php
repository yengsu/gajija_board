<?php
/**
* 파일명: prefilter.emulate_include.php
* 작성일: 
* 작성자: 
* 설  명: ssi 형태의 include 지원
*****************************************************************
*
*/

if (!function_exists('file_get_contents')) {
    function file_get_contents($filename) {
        return @implode('',file($filename));
    }
}

function emulate_include($source,$tpl) {
    if (!defined('TPL_COMPLIE_DIR')) define('TPL_COMPILE_DIR',$tpl->compile_dir);
    if (!defined('TPL_COMPLIE_EXT')) define('TPL_COMPILE_EXT',$tpl->compile_ext);
    global $__HTML__,$__html__,$__ref__;
   	$ref = '';
    $__HTML__ = $tpl->tpl_path;

  	$__html__ = str_replace($_SERVER['DOCUMENT_ROOT'].'/','',$GLOBALS['__HTML__']);

    $reffile = TPL_COMPILE_DIR.'/__include_information__.php';
    list(,$serialized) = @file($backlinkfile);
    $__ref__ = (array)unserialize($serialized) or array();
    $ret =preg_replace_callback(
        '%<!--[ ]*#include (file|virtual)[ =]+((?:"(?:\\\\\\\\|\\\\"|[^"])*")|(?:\'(?:\\\\\\\\|\\\\\'|[^\'])*\')|(\s+))[ ]*-->%i',
        'cb_include',
        $source
    );
    $fp = fopen($reffile,'w');
    fwrite($fp,$ref);
    fclose($fp);
    return $ret;
}

function cb_include(&$match) {
    global $__HTML__,$__html__,$__ref__;
    $filename = preg_replace('@(^[\'"]|[\'"]$)@','',$match[2]);
    if ($match[1] == 'vurtial') $filename = WebApp::mapPath($filename);
    else $filename = dirname($__HTML__).'/'.$filename;
    if (!is_file($filename)) return "<!-- Not Found: $filename -->";
    //==-- find backlinks and delete cache --==//
    while ($_delete = each($__ref__[$filename])) {
        @unlink(TPL_COMPILE_DIR.'/'.$_delete.'.'.TPL_COMPILE_EXT);
    }
    if (!is_array($__ref__[$filename])) $__ref__[$filename] = array();
    if (!in_array($__html__,$__ref__[$filename])) {
        $__ref__[$filename][] = $__html__;
    }
    return file_get_contents($filename);
}

?>

