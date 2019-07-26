<?php

/* POSTFILTER EXAMPLE */

function arrangeSpace($source, $tpl)
{
	$source=  preg_replace("/(<!--.+?-->)/s",'', $source);
	$source= preg_replace('!/\*.*?\*/!s', '', $source);
	$source= preg_replace('/\n\s*\n/', "\n", $source);
	return preg_replace('/^\s+|\t|\s+$/m', '', $source);
}