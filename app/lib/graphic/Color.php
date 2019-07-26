<?
//namespace lib\graphic ;
/**
 * @desc Color 관련
 * 
 * @author yengsu lee
 * @email yengsu@hanmail.net
 *
 */
class Color
{
	function __construct(){
	}
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}
	public static function _checkHex( $hex ) {
		// Strip # sign is present
		$color = str_replace("#", "", $hex);
		// Make sure it's 6 digits
		if( strlen($color) == 3 ) {
			$color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
		} else if( strlen($color) != 6 ) {
			throw new Exception("HEX color needs to be 6 or 3 digits long");
		}
		return $color;
	}
	/**
	 * @desc hexa(16진수)를 RGB 코드를 변환
	 *
	 * @param string $hex ( '#ffffff' or 'ffffff')
	 *
	 * @return array ( r, g, b)
	 */
	public static function hex2rgb($hex) {
		$hex = str_replace("#", "", $hex);
		/* if(strlen($hex) == 3) {
		 $r = hexdec($hex[0].$hex[0]);
		 $g = hexdec($hex[1].$hex[1]);
		 $b = hexdec($hex[2].$hex[2]);
		 } else {
		 $r = hexdec($hex[0].$hex[1]);
		 $g = hexdec($hex[2].$hex[3]);
		 $b = hexdec($hex[4].$hex[5]);
		 } */
		if(strlen($hex) == 3){
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		}else{
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
		
		return array($r, $g, $b);
	}
	/**
	 * 
	 * @param string $color Hexa code ('#ffffff' or 'ffffff')
	 * @return array array('H'=>?, 'S'=>?, 'L'=>?)
	 */
	public static function hex2hsl($color){
		// Sanity check
		$color = self::_checkHex($color);
		// Convert HEX to DEC
		$R = hexdec($color[0].$color[1]);
		$G = hexdec($color[2].$color[3]);
		$B = hexdec($color[4].$color[5]);
		$HSL = array();
		$var_R = ($R / 255);
		$var_G = ($G / 255);
		$var_B = ($B / 255);
		$var_Min = min($var_R, $var_G, $var_B);
		$var_Max = max($var_R, $var_G, $var_B);
		$del_Max = $var_Max - $var_Min;
		$L = ($var_Max + $var_Min)/2;
		if ($del_Max == 0)
		{
			$H = 0;
			$S = 0;
		}
		else
		{
			if ( $L < 0.5 ) $S = $del_Max / ( $var_Max + $var_Min );
			else $S = $del_Max / ( 2 - $var_Max - $var_Min );
			$del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
			$del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
			$del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
			if($var_R == $var_Max) $H = $del_B - $del_G;
			else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
			else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;
			if ($H<0) $H++;
			if ($H>1) $H--;
		}
		$HSL['H'] = ($H*360);
		$HSL['S'] = $S;
		$HSL['L'] = $L;
		return $HSL;
	}
	/**
	 * RGB Color Delta
	 *
	 * @param array $rgb1 array( r, g, b)
	 * @param array $rgb2 array( r, g, b)
	 * @return number
	 */
	public static function rgbColorDelta($rgb1, $rgb2) {
		$r = 255 - abs($rgb1[0] - $rgb2[0]);
		$g = 255 - abs($rgb1[1]- $rgb1[2]);
		$b = 255 - abs($rgb1[2]- $rgb1[3]);
		
		$r /= 255;
		$g /= 255;
		$b /= 255;
		
		return ($r + $g + $b) / 3;
	}
	/**
	 * RGB를 hexa(16진수)로 변환
	 * @param array $rgb array(R, G, B)
	 * @return string 16진수 코드
	 */
	public static function rgb2hex($rgb)
	{
		return '#' . sprintf('%02x', (int)$rgb['0']) . sprintf('%02x', (int)$rgb['1']) . sprintf('%02x', (int)$rgb['2']);
	}
	/**
	 * 
	 * @param array $rgb array(R, G, B)
	 * @return array array(x, y, z)
	 */
	public static function rgb2xyz ($rgb) 
	{
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
	/**
	 * 
	 * @param array $xyz array(x, y, z)
	 * @return array array(l, a, b)
	 */
	public static function xyz2lab ($xyz) 
	{
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
	/**
	 * 
	 * @param array $rgb array(r, g, b)
	 * @return array array(h, s, v)
	 */
	public static function rgb2hsv($rgb)  // RGB Values:Number 0-255
	{
		list($R, $G, $B) = $rgb;
		// Convert the RGB byte-values to percentages
		$R = ($R / 255);
		$G = ($G / 255);
		$B = ($B / 255);
		
		// Calculate a few basic values, the maximum value of R,G,B, the
		//   minimum value, and the difference of the two (chroma).
		$maxRGB = max($R, $G, $B);
		$minRGB = min($R, $G, $B);
		$chroma = $maxRGB - $minRGB;
		
		// Value (also called Brightness) is the easiest component to calculate,
		//   and is simply the highest value among the R,G,B components.
		// We multiply by 100 to turn the decimal into a readable percent value.
		$computedV = 100 * $maxRGB;
		
		// Special case if hueless (equal parts RGB make black, white, or grays)
		// Note that Hue is technically undefined when chroma is zero, as
		//   attempting to calculate it would cause division by zero (see
		//   below), so most applications simply substitute a Hue of zero.
		// Saturation will always be zero in this case, see below for details.
		if ($chroma == 0)
			return array(0, 0, $computedV);
			
		// Saturation is also simple to compute, and is simply the chroma
		//   over the Value (or Brightness)
		// Again, multiplied by 100 to get a percentage.
		$computedS = 100 * ($chroma / $maxRGB);
		
		// Calculate Hue component
		// Hue is calculated on the "chromacity plane", which is represented
		//   as a 2D hexagon, divided into six 60-degree sectors. We calculate
		//   the bisecting angle as a value 0 <= x < 6, that represents which
		//   portion of which sector the line falls on.
		if ($R == $minRGB)
			$h = 3 - (($G - $B) / $chroma);
		elseif ($B == $minRGB)
			$h = 1 - (($R - $G) / $chroma);
		else // $G == $minRGB
			$h = 5 - (($B - $R) / $chroma);
				
		// After we have the sector position, we multiply it by the size of
		//   each sector's arc (60 degrees) to obtain the angle in degrees.
		$computedH = 60 * $h;
		
		return array($computedH, $computedS, $computedV);
	}
	/**
	 * 랜덤으로 Hexa Color code 생성
	 * 
	 * @return string 'ffffff'
	 */
	public static function random_hexaColor()
	{
		$color = sprintf('%06s',dechex(rand(0x000000, 0xFFFFFF)));
		
		return $color ;
	}
}