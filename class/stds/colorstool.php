<?php
  class colorstool {

  const GOLDEN_RATIO_CONJUGATE = 0.618033988749895;

  /**
  * Generate an array of well distributed color using Golden Ratio scattering algorithm
  *
  * @param int $color_number
  * @param int $hue
  * @param int $saturation
  * @param int $value
  *
  * @return array
  */
  static function distributed_colors($color_number, $hue = false, $saturation = 0.6, $value = 0.95){
    if(!$hue)
      $hue = mt_rand(0, 100)/100;

    $result = array();
    for($i = 0; $i < $color_number; $i++) {
      $hue += self::GOLDEN_RATIO_CONJUGATE;
      $hue = fmod($hue, 1);
      $result[] = self::hsv_to_rgb($hue, $saturation, $value);
    }

    return $result;
  }

  /**
  * convert decimal rgb to html hexa
  *
  * @param float $r red
  * @param float $g green
  * @param float $b blue
  */
  static function rgb_to_html($r, $g, $b) {
    $r = dechex($r<0?0:($r>255?255:$r));
    $g = dechex($g<0?0:($g>255?255:$g));
    $b = dechex($b<0?0:($b>255?255:$b));

    return sprintf("%02s%02s%02s",$r,$g,$b);
  }

  /**
  *
  *
  * @param int $h hue (0-1)
  * @param int $s saturation (0-1)
  * @param int $v value (0-1)
  *
  * @return array rgb results:number 0-255
  */
  static function hsv_to_rgb ($h, $s, $v) {
    $rgb = array();

    if($s == 0) {
        $r = $g = $b = $v * 255;
    }
    else {
        $var_h = $h * 6;
        $var_i = floor( $var_h );
        $var_1 = $v * ( 1 - $s );
        $var_2 = $v * ( 1 - $s * ( $var_h - $var_i ) );
        $var_3 = $v * ( 1 - $s * (1 - ( $var_h - $var_i ) ) );

        if       ($var_i == 0) { $var_r = $v     ; $var_g = $var_3  ; $var_b = $var_1 ; }
        else if  ($var_i == 1) { $var_r = $var_2 ; $var_g = $v      ; $var_b = $var_1 ; }
        else if  ($var_i == 2) { $var_r = $var_1 ; $var_g = $v      ; $var_b = $var_3 ; }
        else if  ($var_i == 3) { $var_r = $var_1 ; $var_g = $var_2  ; $var_b = $v     ; }
        else if  ($var_i == 4) { $var_r = $var_3 ; $var_g = $var_1  ; $var_b = $v     ; }
        else                   { $var_r = $v     ; $var_g = $var_1  ; $var_b = $var_2 ; }

        $r = $var_r * 255;
        $g = $var_g * 255;
        $b = $var_b * 255;
    }

    $rgb['r'] = $r;
    $rgb['g'] = $g;
    $rgb['b'] = $b;

    return $rgb;
  }
}
