<?


/* Applies a 10px glow from $img_mask */


function imageglow($img_back, $img_mask, $color){
    if(!function_exists('imagefilter')) return;
    $img_back = imageduplicate($img_back);

    $radius = 10;
    $img_w=imagesx($img_mask);$img_h=imagesy($img_mask);
    $glow_w=($img_w+$radius);$glow_h=($img_h+$radius);
    $img_glow=imagecreatetruealpha($glow_w,$glow_h);
    imagecopyresampled ($img_glow, $img_mask,
            ($img_w-$glow_w)/2,($img_h-$glow_h)/2,
            0,0,
            $glow_w,$glow_h,
            $img_w,$img_h
    );
    imagefilter ($img_glow,IMG_FILTER_SMOOTH,0);
    for($x=0;$x<$img_w;$x++){
        for($y=0;$y<$img_h;$y++){
            $value=colorgray(colordec(imagecolorat($img_glow,$x,$y)));
            $color['alpha']=127-floor($value/2);
            $from=colordec(imagecolorat($img_back, $x,$y));
            $to=colorget(colorfusion($from, $color));
            imagesetpixel($img_back,$x,$y,$to);
        }
    }return $img_back;
}



//check doc in the manual
function imageboldedge($img_mask,$border){
    $img_border = imageduplicate($img_mask); $color_base = colorgray($border);
    imagefilter ($img_border,IMG_FILTER_EDGEDETECT);
    $pic_w=imagesx($img_mask);$pic_h=imagesy($img_mask);
    $dest = imagecreatetruealpha($pic_w, $pic_h);
    for($x=0;$x<$pic_w;$x++){
        for($y=0;$y<$pic_h;$y++){
            $current = colorgray(colordec(imagecolorat($img_border,$x,$y)));
            $base = colordec(imagecolorat($img_mask,$x,$y));
            if($current==GRAY_LVL && $base['alpha']==TRANSPARENT_LVL) continue;
            $weight = abs($current-GRAY_LVL)/GRAY_LVL;
            if($base['alpha']!=0) $weight = ($weight+1)/2;
            $border['alpha'] = (1-$weight)*127; $to = colorget($border);
            imagesetpixel($dest, $x, $y, $to);
        }
    } return $dest;
}



function imagefilldegrad($dest, $mask,$color_base, $colors_interval){
    include_once "edge_detect.php";
    list($from, $to) = $colors_interval; $from = colordec($from); $to = colordec($to);

    $dest = imageduplicate($dest);
    $pic_w=imagesx($dest);$pic_h=imagesy($dest);
    list($zones_dims, $zones) = detect_zones($mask, $color_base);
    for($y =$path= 0; $y<$pic_h;$y++) for($x=0;$x<$pic_w;$x++,$path++){
        if(!$zone_id=$zones[$path]) continue;
        $zone_infos = $zones_dims[$zone_id];
        $step = ($x-$zone_infos['xl'])/($zone_infos['xr'] - $zone_infos['xl']);
        $color = degrad($from, $to, $step);
        imagesetpixel( $dest, $x, $y, $color);
    }

    return $dest;
}




