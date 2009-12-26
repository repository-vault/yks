<?php


/** Create a new image, respecting alpha channels */
function imagecreatetruealpha($w,$h){
    $img=imagecreatetruecolor($w,$h);
    imagealphablending($img,false);
    imagesavealpha($img,true);
    imagefill($img,0,0,coloralpha(255,255,255,127));
    return $img;
}
/** Create a new image from a file, regarless its extension */
function imagecreatefromfile($file){
    $img_supported_ext=array('png'=>'png','jpg'=>'jpeg','gif'=>'gif','jpeg'=>'jpeg');
    if(!$ext=$img_supported_ext[trim(strtolower(strrchr($file,'.')),'.')]) return false;
    $func="imagecreatefrom$ext";
    if(!($img=@$func($file))) return false;
    imagealphablending($img,false);
    imagesavealpha($img,true);
    return $img;
}

function imagecolorize($img, $color){
    $w = imagesx($img); $h = imagesy($img);
    for($x=0;$x<$w;$x++)
      for($y=0;$y<$h;$y++) {
        $value = colorvalue(imagecolorat($img, $x, $y));
        $alpha = (1-$value)*127;
        $tmp = coloralpha($color['red'], $color['green'], $color['blue'], $alpha);
        imagesetpixel($img, $x, $y, $tmp);
    }
}


function imagetrunk($from,$x,$y,$w,$h){
    $img=imagecreatetruealpha($w,$h);
    imagecopy($img,$from,0,0,$x,$y,$w,$h);
    return $img;
}

/** Resize an image based on width and optional height */
function imageresize($img,$w,$h=false,$bigger=false){
    $old_w=imagesx($img);$old_h=imagesy($img);
    $ratio=min($w?$w/$old_w:$h/$old_h,$h?$h/$old_h:$w/$old_w);
    if($ratio>1 && !$bigger) $ratio=1;
    $new_w=$ratio*$old_w;$new_h=$ratio*$old_h;
    $out=imagecreatetruealpha($new_w,$new_h);
    imagecopyresampled($out,$img,0,0,0,0,$new_w,$new_h,$old_w,$old_h);
    return $out;
}

    //useless
function imagecolorhide($img,$color){
    $pic_w=imagesx($img);$pic_h=imagesy($img);
    $trans=coloralpha(255,255,255,127);
    for($x=0;$x<$pic_w;$x++){
        for($y=0;$y<$pic_h;$y++){
            $to=imagecolorat($img,$x,$y);
            if($to==$color || $to==-1) imagesetpixel($img,$x,$y,$trans);
        }
    }
}


/** Merge two images, respecting alpha channels */
function imagefusion($dest,$mask,$decx=0,$decy=0){
    $pic_w=imagesx($mask);$pic_h=imagesy($mask);
    try {
    if(!is_numeric($decx))
        list($decx, $decy) = imageposition($dest, $mask, $decx);
    } catch(Exception $e){ list($decx, $decy) = array(0, 0); }

    for($x=0; $x<$pic_w; $x++){
        for($y=0; $y<$pic_h; $y++){
            $to = colordec(imagecolorat($mask,$x,$y));
            if($to['alpha']=='127') continue;
            $from = colordec(imagecolorat($dest,$x+$decx,$y+$decy));
            $to   = colorget( colorfusion($from,$to));
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
    $values = valid_ranges($position_str, $ranges);
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
function valid_ranges($data_src, $ranges){
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
function imageempty($img){
    $pic_w=imagesx($img);$pic_h=imagesy($img);
    $tmp=imagecreatetruealpha($pic_w,$pic_h);
    return $tmp;
}

/** duplicate an image/ have to check if alpha is well supported */
function imageduplicate($img){
    $pic_w=imagesx($img);$pic_h=imagesy($img);
    $tmp=imagecreatetruealpha($pic_w,$pic_h);
    imagecopyresampled($tmp,$img,0,0,0,0,$pic_w,$pic_h,$pic_w,$pic_h);
    return $tmp;
}


/** correct use of imagettftext supporting alpha chan.*/
function imagetext($img,$font_size,$angle,$x,$y,$color,$font,$str){
    $img_text=imageempty($img);
    imagettftext($img_text, $font_size, $angle, $x, $y, $color, $font, $str);
    imagefusion($img,$img_text);
    imagedestroy($img_text);
}

    //useless
function imagerotation(&$img,$angle){
    $img= imagerotate($img, $angle, imagecolortransparent($img));
    imagealphablending($img,false);
    imagesavealpha($img,true);
}

function imagescale($img, $width = false, $height = false){
    $img_w = imagesx($img); $img_h = imagesy($img);
    $new_w = $width?$width:$img_w; 
    $new_h = $height?$height:$img_h;
    $new = imagecreatetruealpha($new_w, $new_h);
    for($x=0;$x<=$new_w;$x+=$img_w) 
        imagecopyresampled($new, $img, $x, 0, 0, 0, $img_w, $img_h, $img_w, $img_h);
    return $new;


}


/** function for w000ting purposes **/
function image_bg_scale($img_back,$bx_img,$bx_x,$bx_y){
    $img_w=imagesx($img_back); $img_h=imagesy($img_back);
    $bx_w=imagesx($bx_img);$bx_h=imagesy($bx_img);

    list($bx_xl,$bx_xm,$bx_xr)=$bx_x; list($bx_yu,$bx_ym,$bx_yd)=$bx_y;

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