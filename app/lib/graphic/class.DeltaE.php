<?
//참고 : https://zetawiki.com/wiki/DeltaE_CIE76_%EA%B5%AC%ED%98%84
//참고 : https://zetawiki.com/wiki/DeltaE_CIE2000_%EA%B5%AC%ED%98%84
//참고 : https://www.compuphase.com/cmetric.htm
//참고 : https://stackoverflow.com/questions/1633828/distance-between-colours-in-php/1634206#1634206

//https://stackoverflow.com/questions/935992/find-images-of-similar-color/19495291#19495291
//https://stackoverflow.com/questions/1633828/distance-between-colours-in-php/1634206#1634206
//https://en.wikipedia.org/wiki/HSL_and_HSV
//http://jsfiddle.net/96sME/
//https://stackoverflow.com/questions/22692134/detect-similar-colours-from-hex-values
//https://en.wikipedia.org/wiki/Color_difference
//https://github.com/dtao/nearest-color
//https://stackoverflow.com/questions/31798883/performance-of-delta-e-cie-lab-calculating-and-sorting-in-sql
//http://colorsearchtest.herokuapp.com/?color=4b50ba
//밝기조절 : https://gist.github.com/stephenharris/5532899
//델타E 출처 : https://github.com/renasboy/php-color-difference/blob/master/lib/color_difference.class.php
class DeltaE
{
	function __construct(){
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	public function deltaECIE2000 ($rgb1, $rgb2)
	{
		$tmp = $this->_rgb2lab($rgb1) ;
		$l1 = $tmp[0];
		$a1= $tmp[1];
		$b1= $tmp[2];
		$tmp = $this->_rgb2lab($rgb2) ;
		$l2= $tmp[0];	
		$a2= $tmp[1];
		$b2= $tmp[2];
		
		$avg_lp = ($l1 + $l2) / 2;
		$c1 = sqrt(pow($a1, 2) + pow($b1, 2));
		$c2 = sqrt(pow($a2, 2) + pow($b2, 2));
		$avg_c  = ($c1 + $c2) / 2;
		$g  = (1 - sqrt(pow($avg_c , 7) / (pow($avg_c, 7) + pow(25, 7)))) / 2;
		$a1p = $a1 * (1 + $g);
		$a2p = $a2 * (1 + $g);
		$c1p = sqrt(pow($a1p, 2) + pow($b1, 2));
		$c2p = sqrt(pow($a2p, 2) + pow($b2, 2));
		$avg_cp = ($c1p + $c2p) / 2;
		$h1p = rad2deg(atan2($b1, $a1p));
		if ($h1p < 0) {
			$h1p += 360;
		}
		$h2p = rad2deg(atan2($b2, $a2p));
		if ($h2p < 0) {
			$h2p += 360;
		}
		$avg_hp = abs($h1p - $h2p) > 180 ? ($h1p + $h2p + 360) / 2 : ($h1p + $h2p) / 2;
		$t  = 1 - 0.17 * cos(deg2rad($avg_hp - 30)) + 0.24 * cos(deg2rad(2 * $avg_hp)) + 0.32 * cos(deg2rad(3 * $avg_hp + 6)) - 0.2 * cos(deg2rad(4 * $avg_hp - 63));
		$delta_hp   = $h2p - $h1p;
		if (abs($delta_hp) > 180) {
			if ($h2p <= $h1p) {
				$delta_hp += 360;
			}
			else {
				$delta_hp -= 360;
			}
		}
		$delta_lp   = $l2 - $l1;
		$delta_cp   = $c2p - $c1p;
		$delta_hp   = 2 * sqrt($c1p * $c2p) * sin(deg2rad($delta_hp) / 2);
		$s_l = 1 + ((0.015 * pow($avg_lp - 50, 2)) / sqrt(20 + pow($avg_lp - 50, 2)));
		$s_c = 1 + 0.045 * $avg_cp;
		$s_h = 1 + 0.015 * $avg_cp * $t;
		$delta_ro   = 30 * exp(-(pow(($avg_hp - 275) / 25, 2)));
		$r_c = 2 * sqrt(pow($avg_cp, 7) / (pow($avg_cp, 7) + pow(25, 7)));
		$r_t = -$r_c * sin(2 * deg2rad($delta_ro));
		$kl = $kc = $kh = 1;
		$delta_e = sqrt(pow($delta_lp / ($s_l * $kl), 2) + pow($delta_cp / ($s_c * $kc), 2) + pow($delta_hp / ($s_h * $kh), 2) + $r_t * ($delta_cp / ($s_c * $kc)) * ($delta_hp / ($s_h * $kh)));
		
		return $delta_e;
	}
	private function _rgb2lab ($rgb) {
		return $this->_xyz2lab($this->_rgb2xyz($rgb));
	}
	private function _rgb2xyz ($rgb) {
		list($r, $g, $b) = $rgb;
		$r = $r <= 0.04045 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
		$g = $g <= 0.04045 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
		$b = $b <= 0.04045 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
		$r *= 100;
		$g *= 100;
		$b *= 100;
		$x = $r * 0.412453 + $g * 0.357580 + $b * 0.180423;
		$y = $r * 0.212671 + $g * 0.715160 + $b * 0.072169;
		$z = $r * 0.019334 + $g * 0.119193 + $b * 0.950227;
		return [ $x, $y, $z];
	}
	private function _xyz2lab ($xyz) {
		list ($x, $y, $z) = $xyz;
		$x /= 95.047;
		$y /= 100;
		$z /= 108.883;
		$x = $x > 0.008856 ? pow($x, 1 / 3) : $x * 7.787 + 16 / 116;
		$y = $y > 0.008856 ? pow($y, 1 / 3) : $y * 7.787 + 16 / 116;
		$z = $z > 0.008856 ? pow($z, 1 / 3) : $z * 7.787 + 16 / 116;
		$l = $y * 116 - 16;
		$a = ($x - $y) * 500;
		$b = ($y - $z) * 200;
		return [ $l, $a, $b ];
	}
}
/* $a = '#5c009c';
$b = '#3f006b';
$rgb1= hex2rgb($a);
$rgb2= hex2rgb($b);
$color_difference = (new color_difference())->deltaECIE2000($rgb1, $rgb2);
 */
//-------------
/* <?php
$color = [];
for($i=1;$i<=100;$i++){
	$color[] = dechex(rand(0x000000, 0xFFFFFF));
}
sort($color);
foreach($color as $v){
	echo '
<div style="height:20px;background-color:  #'.$v.';">'.$v.'</div>';
} */
//----------------------



/* <?php

$yellow1 = "5c009c";
$yellow2 = "3f006b";
$blue = "0000FF";

function hexColorDelta($hex1, $hex2) {
	// get red/green/blue int values of hex1
	
	$r1 = hexdec( substr($hex1, 0, 2));
	$g1 = hexdec( substr($hex1, 2, 2));
	$b1 = hexdec( substr($hex1, 4, 2) );
	echo $r1." / " . $g1." / " . $b1."<br>" ;
	// get red/green/blue int values of hex2
	$r2 = hexdec( substr($hex2, 0, 2));
	$g2 = hexdec( substr($hex2, 2, 2));
	$b2 = hexdec( substr($hex2, 4, 2));
	echo $r2." / " . $g2." / " . $b2."<br>" ;
	// calculate differences between reds, greens and blues
	$r = 255 - abs($r1 - $r2);
	$g = 255 - abs($g1 - $g2);
	$b = 255 - abs($b1 - $b2);
	
	// limit differences between 0 and 1
	$r /= 255;
	$g /= 255;
	$b /= 255;
	// 0 means opposit colors, 1 means same colors
	
	return ($r + $g + $b) / 3;
}
function hex2rgb($hex) {
	$hex = str_replace("#", "", $hex);
	
	if(strlen($hex) == 3) {
		$r = hexdec(substr($hex,0,1).substr($hex,0,1));
		$g = hexdec(substr($hex,1,1).substr($hex,1,1));
		$b = hexdec(substr($hex,2,1).substr($hex,2,1));
	} else {
		$r = hexdec(substr($hex,0,2));
		$g = hexdec(substr($hex,2,2));
		$b = hexdec(substr($hex,4,2));
	}
	$rgb = array($r, $g, $b);
	//return implode(",", $rgb); // returns the rgb values separated by commas
	return $rgb; // returns an array with the rgb values
}
echo hexColorDelta($yellow1, $yellow2) ;
echo '<br><br>';
echo hexColorDelta($yellow1, $blue) ; */
//칼라 랜덤추출 : $color = dechex(rand(0x000000, 0xFFFFFF));