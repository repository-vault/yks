<?
include_once "effects.php";

define('GRAY_LVL',127);
define('COLOR_WHITE',coloralpha());
define('COLOR_GRAY', coloralpha(GRAY_LVL, GRAY_LVL, GRAY_LVL));
define('TRANSPARENT_LVL',127);

function draw_title($data){ extract($data);

    $text_tmp=imagettfbbox($font_size,0,$font,$text);
    $text_w=max(abs($text_tmp[4]-$text_tmp[0]),abs($text_tmp[2]-$text_tmp[6]));


    $db=(bool)$drop_bottom;$db=false;
    $text_h=max(abs($text_tmp[7]-($db?0:$text_tmp[1])),abs($text_tmp[5]-($db?0:$text_tmp[3])));

    list($tmp_x,$tmp_y) = explode(";",$box_grid);
    list($box_xl,$box_xm,$box_xr,$box_ml,$box_mr) = $box_x = $tmp_x?explode(',',$tmp_x):array();
    list($box_yu,$box_ym,$box_yd,$box_mu,$box_md) = $box_y =  $tmp_y?explode(',',$tmp_y):array();
    if(!isset($box_ml))$box_ml=$box_xl;
    if(!isset($box_mr))$box_mr=$box_xr;
    $img_h=max( $text_h+$box_mu+$box_md, $box_yu+$box_ym+$box_yd);
    $img_w=max( $text_w+$box_ml+$box_mr, $box_xl+$box_xm+$box_xr, $width);
    $img_back=imagecreatetruealpha($img_w,$img_h);

    if($box_src){
        $box_img=imagecreatefromfile($box_src);
        if(!$box_x) $box_x = array(0,imagesx($box_img));
        if(!$box_y) $box_y = array(0,imagesy($box_img));
        image_bg_scale($img_back, $box_img, $box_x, $box_y);  

        if($icon){
            $icon = imagecreatefromfile($icon);
            $icon_h = imagesy($icon);
            $icon_y = floor(($img_h-$icon_h)/2);
            imagefusion($img_back, $icon, $box_xl, $icon_y);
        }
    }

    $img_text=imagecreatetruealpha($img_w,$img_h);
    $img_text_mask=imagecreatetruealpha($img_w,$img_h);

    $text_align = $text_align?$text_align:"center";

    $text_y= floor(($box_mu+$text_h+$img_h-$box_md)/2);if(!$db)$text_y-=$text_tmp[1];
    $text_x= 0;
    if($text_align=="left") $text_x = $box_ml; //left
    elseif($text_align=="right") $text_x = $img_w - ($text_w+$box_mr); //right
    else  $text_x = $box_ml + floor(($img_w-($box_ml+$box_mr+$text_w))/2); //center

    imagettftext($img_text_mask,$font_size,0,$text_x,$text_y,COLOR_GRAY,$font,$text);

    if(is_array($color))
        $img_text = imagefilldegrad($img_back, $img_text_mask,127, $color );
    else
        imagettftext($img_text,$font_size,0,$text_x,$text_y,$color,$font,$text);

    if($border!==false){
        $res = imageboldedge($img_text_mask, colordec($border)); 
        imagefusion($img_text,$res);
    }
    if($shadow!==false)
        $img_back = imageglow($img_back, $img_text_mask, colordec($shadow));

    imagefusion($img_back, $img_text);
    return $img_back;
}

