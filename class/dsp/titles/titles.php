<?php

//http://doc.exyks.org/wiki/Source:dsp/titles

class dsp_titles {

  private static $themes_config;
  private static $cache_path;
  private static $fonts_paths;

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    self::$themes_config = yks::$get->config->themes;
    if(!self::$themes_config) die("Unable to load theme config");

    self::$fonts_paths = array();
    self::$fonts_paths[] =  exyks_paths::resolve("path://yks/rsrcs/fonts");

    if(self::$themes_config->fonts_paths->path)
      foreach(self::$themes_config->fonts_paths->path as $path)
        self::$fonts_paths[] = exyks_paths::resolve($path['path']);

    include_once CLASS_PATH."/imgs/effects.php";

  }

  private static function parse_theme_config($themes_list, $theme_name){
    if(!is_array($themes_list)) $themes_list = array($themes_list);


    list($theme_name, $theme_pseudo) = explode(':', $theme_name, 2);
    foreach($themes_list as $theme_parent){

        $theme_xml = $theme_parent->search($theme_name);

        if(!$theme_xml)
            continue;

        array_unshift($themes_list, $theme_xml);
        $theme_config =  $theme_xml->attributes();

        if($theme_pseudo)
            return array_merge($theme_config,
                self::parse_theme_config($themes_list, $theme_pseudo));

        return $theme_config;
    }

    return array();
  }


  public static function draw($theme_name, $title_text) {

    $themes_list = self::$themes_config->titles;
    $theme_name  = $theme_name?$theme_name:$themes_list[$is_title?'base_title':'base'];

    $theme_config = self::parse_theme_config($themes_list, $theme_name);
    if(!$theme_config)
        throw rbx::error("Unaccessible theme : '$theme_name'");

    $font_name    = "{$theme_config['font']}.ttf";

    if(!($font_file = files::locate($font_name, self::$fonts_paths)))
        throw rbx::error("Requested font '$font_name' cannot be found");

    if(!is_file($box_src = exyks_paths::resolve($theme_config['src']) )) $box_src = false;
       // break rbx::error("Le thème demandée est incomplet, image $box_src introuvable");

    if($theme_config->options['caps']=="true") $text = mb_strtoupper($text);
    if($theme_config['mask']) $text=sprintf($theme_config['mask'], $text);

    $icon = is_file($theme_config['icon'])?"{$theme_config['icon']}":false;
    $colors = array_map('hexdec', explode("-",$theme_config['color']));

    $data=array(
        'text'=>$title_text,
        'font'=>$font_file,
        'box_src'=>$box_src,
        'box_grid'=>"{$theme_config['grid']}",
        'font_size'=>(int)$theme_config['size'],
        'color'=>count($colors)==1?$colors[0]:$colors,
        'border'=>isset($theme_config['border'])?hexdec("{$theme_config['border']}"):false,
        'shadow'=>isset($theme_config['shadow'])?hexdec("{$theme_config['shadow']}"):false,
        'angle'=>(int)$theme_config['angle'],
        'drop_bottom'=>true,
        'width'=>(int)$theme_config['width'],
        'text_align'=>$theme_config['align'],
        'icon'=>$icon,
        'icon_grid'=>$theme_config['icon_grid'],
    ); //print_r($data);die;

    return self::create_img($data);
  }


  private static function create_img($data){ extract($data);

    $text_tmp = imagettfbbox($font_size,0,$font,$text);
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
    if($width) $img_w = $width;
    $img_back = imgs::imagecreatetruealpha($img_w,$img_h);

    if($box_src){
        $box_img = imgs::imagecreatefromfile($box_src);
        if(!$box_x) $box_x = array(0,imagesx($box_img));
        if(!$box_y) $box_y = array(0,imagesy($box_img));
        imgs::image_bg_scale($img_back, $box_img, $box_x, $box_y);  

        if($icon){
            list($tmp_x, $tmp_y) = explode(";",$icon_grid);

            $icon   = imgs::imagecreatefromfile($icon);
            $icon_h = imagesy($icon);
            $icon_y = floor(($img_h-$icon_h)/2) - $tmp_y;
            $icon_x = $box_xl + $tmp_x;
            imgs::imagefusion($img_back, $icon, $icon_x, $icon_y);
        }
    }

    $img_text      = imgs::imagecreatetruealpha($img_w,$img_h);
    $img_text_mask = imgs::imagecreatetruealpha($img_w,$img_h);

    $text_align = $text_align?$text_align:"center";

    $text_y= floor(($box_mu+$text_h+$img_h-$box_md)/2);if(!$db)$text_y-=$text_tmp[1];
    $text_x= 0;
    if($text_align=="left") $text_x = $box_ml; //left
    elseif($text_align=="right") $text_x = $img_w - ($text_w+$box_mr); //right
    else  $text_x = $box_ml + floor(($img_w-($box_ml+$box_mr+$text_w))/2); //center

    imagettftext($img_text_mask, $font_size, 0, $text_x, $text_y, imgs::COLOR_GRAY, $font, $text);

    if(is_array($color))
        $img_text = imagefilldegrad($img_back, $img_text_mask,127, $color );
    else
        imagettftext($img_text, $font_size, 0, $text_x, $text_y, $color, $font, $text);

    if($border!==false){
        $res = imageboldedge($img_text_mask, imgs::colordec($border)); 
        imgs::imagefusion($img_text,$res);
    }
    if($shadow!==false)
        $img_back = imageglow($img_back, $img_text_mask, imgs::colordec($shadow));

    imgs::imagefusion($img_back, $img_text);
    return $img_back;
  }

}



