<?php

define('__GRAY_LVL',127); //http://doc.exyks.org/wiki/Php:Classes_constants_are_crap
define('__COLOR_GRAY',  imgs::coloralpha(__GRAY_LVL, __GRAY_LVL, __GRAY_LVL));
define('__COLOR_WHITE', imgs::coloralpha(255, 255, 255));


class imgs {
  const GRAY_LVL    = __GRAY_LVL;
  const COLOR_GRAY  = __COLOR_GRAY;
  const COLOR_WHITE = __COLOR_WHITE;

  static function imageoutstring($img, $func ) { //= "imagepng", doit
    ob_start();
    $func($img);
    $str = ob_get_contents();
    ob_end_clean();
    return $str;
  }

  static function dechex24b($str){
    return substr("000000".dechex($str),-6);
  }

  static function imagetruecolortopalette($img, $palette = array(), $bg = 0){
    list($dx, $dy) = array(imagesx($img), imagesy($img));
    list($img_out, $img_palette) = self::imagecreatewithpalette($dx, $dy, $palette, $bg);

    for($x=0;$x<$dx;$x++)
      for($y=0;$y<$dy;$y++) {
        $color        = imagecolorat($img, $x, $y);
        $color_index  = array_search($color, $img_palette);
        if(!$color_index) continue;
        imagesetpixel($img_out, $x, $y, $color_index);
    }

    return $img_out;
  }

  static function colorswap($img, $colors){
    $palette = self::retrievepalette($img);
    if(!$palette)
      return self::colorswaptruecolor($img, $colors);

    foreach($palette as $i=>$color) {
      if(!isset($colors[$color])) continue;
      $color_new = self::colordec($colors[$color]);
      imagecolorset($img, $i, $color_new['red'], $color_new['green'], $color_new['blue']);
    }
  }

  static function colorswaptruecolor($img, $colors) {
    list($dx, $dy) = array(imagesx($img), imagesy($img));
    for($x=0;$x<$dx;$x++)
      for($y=0;$y<$dy;$y++) {
        $color        = imagecolorat($img, $x, $y);
        if(!($color_new = $colors[$color])) continue;
        imagesetpixel($img, $x, $y, $color_new);    
    }
  }

  static function imagecreatewithpalette($dx, $dy, $palette, $bg = 0){
    $img     = imagecreate($dx, $dy);
    $img_pal = array();
    $img_pal[self::imagecoloradd($img, $bg)] = $bg;
    foreach($palette as $color)
      $img_pal[self::imagecoloradd($img, $color)] = $color;
    return array($img, $img_pal);
  }

  static function imagecoloradd($img, $color){
    $color  = self::colordec($color);
    $index = imagecolorallocate($img, $color['red'], $color['green'], $color['blue']);
    return $index;
  }


  static function retrievepalette($img){
    $colors  = imagecolorstotal($img);
    $palette = array();
    for($i=0;$i<$colors;$i++)
      $palette[$i] = self::colorget(imagecolorsforindex($img, $i));
    return $palette;
  }

  static function coloralpha($r=255,$g=255,$b=255,$alpha=0){
    return ($alpha<<24)+($r<<16)+($g<<8)+$b;
  }

  static function colorget($c){
    return self::coloralpha((int)$c['red'],(int)$c['green'],(int)$c['blue'],(int)$c['alpha']);
  }

  static function colordec($c){
    return array(
      'alpha'=>($c>>24)&0x7F,
      'red'=>($c>>16)&0xFF,
      'green'=>($c>>8)&0xFF,
      'blue'=>$c&0xFF
    );
  }

  static function gray($c){
    return self::alphablend(self::colordec($c));
    return  floor(((($c>>16)&0xFF) + (($c>>8)&0xFF) + ($c&0xFF))/3);
  }

  static function alphablend($color, $bg=255){
    return min(floor((($color['alpha'])/127)*$bg+((127-$color['alpha'])/127)*$color['red']),255);
  }

/*
    Calcule la couleur resultante de la superposition de deux autres, en supportant leur canal alpha
    Ca rouske.
*/
  static function colorfusion($dest,$mask){
    //en.wikipedia.org/wiki/Alpha_transparency
    $aa=(127-$mask['alpha'])/127;$ab=(127-$dest['alpha'])/127;
    $na=($aa+$ab*(1-$aa));

    return array(
        'alpha'=> 127-$na*127,
        'red'=> (int)($na?($aa*$mask['red']+(1-$aa)*$ab*$dest['red'])/$na:0),
        'green'=> (int)($na?($aa*$mask['green']+(1-$aa)*$ab*$dest['green'])/$na:0),
        'blue'=> (int)($na?($aa*$mask['blue']+(1-$aa)*$ab*$dest['blue'])/$na:0),
    );
  }

  static function colorsblend($colors_set){
    $shares = 0;
    $red = $green = $blue = 0;

    foreach($colors_set as $color_set) {
        list($color, $share) = array(self::colordec($color_set[0]), $color_set[1]);
        if($share <= 0) continue;

        $red   += $share * $color['red'];
        $green += $share * $color['green'];
        $blue  += $share * $color['blue'];
        $shares += $share;
    }
    
    $red   = round($red / $shares);
    $green = round($green / $shares);
    $blue  = round($blue / $shares);
    return self::colorget(compact('red', 'green', 'blue'));
  }

 /* return the color 'value' (from 0 to 1), based on gray level & considering alpha
    use this for setting alpha level :   (1-self::colorvalue($color))*127;
    or for setting a gray level :        self::colorvalue($color) * 255
 */
  static function colorvalue($color){
    $color = self::colordec($color);
    $color_level = (255-self::colorgray($color))/255;
    return ((127-$color['alpha'])/127)*$color_level;
  }


/*
    There is no one "correct" conversion from RGB to grayscale, since it depends on the sensitivity response curve of your detector to light as a function of wavelength. A common one in use is:
    Y = 0.3*R + 0.59*G + 0.11*B
*/

  static function colorgray($color){
    return (int)$color['red']*0.3+$color['green']*0.59+$color['blue']*0.11;
  }


  static function imagecross($img, $cx,$cy, $ray = 10, $color = 0) {
    imageline($img, $cx-$ray, $cy, $cx+$ray, $cy, $color);
    imageline($img, $cx, $cy-$ray, $cx, $cy+$ray, $color);
    imageellipse ($img, $cx , $cy , $ray, $ray, $color );
  }


/** Create a new image, respecting alpha channels */
  static function imagecreatetruealpha($w,$h){
    $img = imagecreatetruecolor($w,$h);
    imagealphablending($img,false);
    imagesavealpha($img,true);
    imagefill($img, 0, 0, self::coloralpha(255,255,255,127));
    return $img;
  }

/** Create a new image from a file, regarless its extension */
  static function imagecreatefromfile($file, $force_ext = false){
    $img_supported_ext=array('png'=>'png','jpg'=>'jpeg','gif'=>'gif','jpeg'=>'jpeg');
    $ext = pick($force_ext, trim(strtolower(strrchr($file,'.')),'.'));
    if(!$ext=$img_supported_ext[$ext]) return false;
    $func="imagecreatefrom$ext";
    if(!($img=@$func($file))) return false;
    imagealphablending($img,false);
    imagesavealpha($img,true);
    return $img;
  }

  static function imagecolorize($img, $color){
    $w = imagesx($img); $h = imagesy($img);
    for($x=0;$x<$w;$x++)
      for($y=0;$y<$h;$y++) {
        $value = self::colorvalue(imagecolorat($img, $x, $y));
        $alpha = (1-$value)*127;
        $tmp = self::coloralpha($color['red'], $color['green'], $color['blue'], $alpha);
        imagesetpixel($img, $x, $y, $tmp);
    }
  }


  static function imagetrunk($from, $x, $y, $w, $h){
    $img = self::imagecreatetruealpha($w,$h);
    imagecopy($img, $from, 0, 0, $x, $y, $w, $h);
    return $img;
  }

/** Resize an image based on width and optional height */
  static function imageresize($img,$w,$h=false,$bigger=false){
    $old_w=imagesx($img);$old_h=imagesy($img);
    $ratio=min($w?$w/$old_w:$h/$old_h,$h?$h/$old_h:$w/$old_w);
    if($ratio>1 && !$bigger) $ratio=1;
    $new_w=$ratio*$old_w;$new_h=$ratio*$old_h;
    $out = self::imagecreatetruealpha($new_w,$new_h);
    imagecopyresampled($out,$img,0,0,0,0,$new_w,$new_h,$old_w,$old_h);
    return $out;
  }

    //useless
  static function imagecolorhide($img,$color){
    $pic_w = imagesx($img); $pic_h = imagesy($img);
    $trans = self::coloralpha(255,255,255,127);
    for($x=0;$x<$pic_w;$x++){
        for($y=0;$y<$pic_h;$y++){
            $to = imagecolorat($img,$x,$y);
            if($to==$color || $to==-1)
                imagesetpixel($img,$x,$y,$trans);
        }
    }
  }


/** Merge two images, respecting alpha channels */
  static function imagefusion($dest,$mask,$decx=0,$decy=0){
    $pic_w = imagesx($mask); $pic_h = imagesy($mask);
    try {
    if(!is_numeric($decx))
        list($decx, $decy) = self::imageposition($dest, $mask, $decx);
    } catch(Exception $e){ list($decx, $decy) = array(0, 0); }

    for($x=0; $x<$pic_w; $x++){
        for($y=0; $y<$pic_h; $y++){
            $to = self::colordec(imagecolorat($mask,$x,$y));
            if($to['alpha']=='127') continue;
            $from = self::colordec(imagecolorat($dest,$x+$decx,$y+$decy));
            $to   = self::colorget(self::colorfusion($from,$to));
            imagesetpixel($dest,$x+$decx,$y+$decy,$to);
        }
    }
  }
/*
    return mask's coordinate so it can be positionned over img
    position_str is [left|center|right]-[top|middle|down]
*/

  function imageposition($src, $mask, $position_str){
    $ranges = array(
        'x'=> array('left', 'right', 'center'),
        'y'=> array('top', 'bottom', 'middle'),
    );
    $values = self::valid_ranges($position_str, $ranges);
    $src_w  = imagesx($src);  $src_h  = imagesy($src);
    $mask_w = imagesx($mask); $mask_h = imagesy($mask);
    $values_x = array(
        'left'   => 0,
        'right'  => $src_w - $mask_w,
        'center' => floor(($src_w - $mask_w)/2),
    );

    $values_y = array(
        'top'    => 0,
        'bottom' => $src_h - $mask_h,
        'middle' => floor(($src_h - $mask_h)/2),
    );


    $decx = $values_x[$values['x']];
    $decy = $values_y[$values['y']];

    return array($decx, $decy);

  }

/*
    valid a data source according to a data definition
*/
  private static function valid_ranges($data_src, $ranges) {
    if(!is_array($data_src))
        $data = preg_split("#[,-\s]+#", $data_src, 2);
    else $data = $data_src;

    $results = array_fill_keys(array_keys($ranges), null);

    foreach($data as $value) {
        foreach($ranges as $range=>$values)
            if(in_array($value, $values)) $results[$range] = $value;
    }

    foreach($ranges as $range=>$values)
        if(!in_array($results[$range], $values))
            throw new Exception("Invalid range $position_str");

    return $results;
  }

/** duplicate an image, return an empty one of the same size */
  static function imageempty($img){
    list($pic_w, $pic_h) = array( imagesx($img), imagesy($img));
    $tmp = self::imagecreatetruealpha($pic_w, $pic_h);
    return $tmp;
  }

/** duplicate an image/ have to check if alpha is well supported */
  static function imageduplicate($img){
    list($pic_w, $pic_h) = array( imagesx($img), imagesy($img));
    $tmp = self::imagecreatetruealpha($pic_w,$pic_h);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $pic_w, $pic_h, $pic_w, $pic_h);
    return $tmp;
  }


  static function imagecrop($img, $x, $y, $w, $h = false){
    if(!$h) $h = imagesy($img);
    $new = self::imagecreatetruealpha($w, $h);

    imagecopyresampled($new, $img, 0, 0, $x, $y, $w, $h, $w, $h);
    return $new;
  }

/** correct use of imagettftext supporting alpha chan.*/
  static function imagetext($img, $font_size, $angle, $x, $y, $color, $font, $str){
    $img_text = self::imageempty($img);
    imagettftext($img_text, $font_size, $angle, $x, $y, $color, $font, $str);
    self::imagefusion($img, $img_text);
    imagedestroy($img_text);
  }

    //useless
  static function imagerotation(&$img, $angle){
    $img= imagerotate($img, $angle, imagecolortransparent($img));
    imagealphablending($img,false);
    imagesavealpha($img,true);
  }

  static function imagescale($img, $width = false, $height = false){
    list($img_w, $img_h) = array( imagesx($img), imagesy($img));
    $new_w = $width?$width:$img_w; 
    $new_h = $height?$height:$img_h;
    $new = self::imagecreatetruealpha($new_w, $new_h);
    for($x=0;$x<=$new_w;$x+=$img_w) 
        imagecopyresampled($new, $img, $x, 0, 0, 0, $img_w, $img_h, $img_w, $img_h);
    return $new;
  }


/** function for w000ting purposes **/
  static function image_bg_scale($img_back,$bx_img,$bx_x,$bx_y){
    list($img_w, $img_h) = array( imagesx($img_back), imagesy($img_back));
    list($bx_w, $bx_h)   = array( imagesx($bx_img), imagesy($bx_img));

    list($bx_xl, $bx_xm, $bx_xr) = $bx_x; list($bx_yu, $bx_ym, $bx_yd) = $bx_y;

    //filling BG
    $map=array();
    if($bx_xl&&$bx_yu) $map[]=array(0,0,0,0,$bx_xl,$bx_yu,$bx_xl,$bx_yu);
    if($bx_xl&&$bx_yd) $map[]=array(0,$img_h-$bx_yd,0,$bx_h-$bx_yd,$bx_xl,$bx_yd,$bx_xl,$bx_yd);

    if($bx_xm && $bx_ym) for($x=$bx_xl;$x<$img_w-$bx_xr;$x+=$bx_xm)
        for($y=$bx_yu;$y<$img_h-$bx_yd;$y+=$bx_ym)
          $map[]=array($x,$y,$bx_xl,$bx_yu,$bx_xm,$bx_ym,$bx_xm,$bx_ym);

    if($bx_xm) for($x=$bx_xl;$x<$img_w-$bx_xr;$x+=$bx_xm){
      if($bx_yu) $map[]=array($x,0,$bx_xl,0,$bx_xm,$bx_yu,$bx_xm,$bx_yu);
      if($bx_yd) $map[]=array($x,$img_h-$bx_yd,$bx_xl,$bx_h-$bx_yd,$bx_xm,$bx_yd,$bx_xm,$bx_yd);
    }

    if($bx_ym)for($y=$bx_yu;$y<$img_h-$bx_yd;$y+=$bx_ym){
      if($bx_xl) $map[]=array(0,$y,0,$bx_yu,$bx_xl,$bx_ym,$bx_xl,$bx_ym);
      if($bx_xr) $map[]=array($img_w-$bx_xr,$y,$bx_w-$bx_xr,$bx_yu,$bx_xr,$bx_ym,$bx_xr,$bx_ym);
    }

    //__FATALITY
    if($bx_xr&&$bx_yu) $map[]=array($img_w-$bx_xr,0,$bx_w-$bx_xr,0,$bx_xr,$bx_yu,$bx_xr,$bx_yu);
    if($bx_xr&&$bx_yd) $map[]=array($img_w-$bx_xr,$img_h-$bx_yd,$bx_w-$bx_xr,
        $bx_h-$bx_yd,$bx_xr,$bx_yd,$bx_xr,$bx_yd); // __FINISH HIM \o/

    foreach($map as $i)
        imagecopyresampled($img_back,$bx_img,$i[0],$i[1],$i[2],$i[3],$i[4],$i[5],$i[6],$i[7]);
  }

  
}
