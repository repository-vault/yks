<?php

//cache should be done at main.php level

$paths = retrieve_constants('#_PATH$#');

$fonts_paths = array( strtr("{RSRCS_PATH}/fonts", $paths) );
if($themes_config->fonts_paths->path)
  foreach($themes_config->fonts_paths->path as $path)
    $fonts_paths[] = strtr((string) $path['path'], $paths);

$titles_cache_path = CACHE_PATH."/imgs/titles";
$theme_name = $sub0;
$is_title = (bool)$sub1;
$title_text  = $argv0;

include "$class_path/imgs/titles.php";
$font_path = RSRCS_PATH."/fonts";

if(!$title_text) $title_text="Default title";


$hash=md5("$theme_name,$title_text,$site_code,$is_title");
$img_path = "$titles_cache_path/$hash.png";

if(true || !is_file($img_path)) try {
    include_once "$class_path/stds/files.php";

    $themes_list = $themes_config->titles;
    $theme_name  = $theme_name?$theme_name:($is_title?$themes_list['base_title']:$themes_list['base']);
    if(!$theme_config = $themes_list->$theme_name)
        throw rbx::error("Le thème '$theme_name' demandée n'existe pas");

    $font_name    = "{$theme_config['font']}.ttf";

    if(!($font_file = files::locate($font_name, $fonts_paths)))
        throw rbx::error("La police demandée : $font_name est introuvable");
    if(!is_file($box_src=ROOT_PATH."/{$theme_config['src']}")) $box_src=false;
       // break rbx::error("Le thème demandée est incomplet, image $box_src introuvable");

    if($theme_config->options['caps']=="true")$text=mb_strtoupper($text);
    if($theme_config['mask'])$text=sprintf($theme_config['mask'],$text);

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
    ); //print_r($data);die;
    $img=draw_title($data);

    //header(TYPE_PNG);

    files::create_dir($titles_cache_path);
    if(!imagepng($img,$img_path))
        throw rbx::error("Image generation failed");

}catch(rbx $e){ exit("Unable to load theme"); }


header(TYPE_PNG);
header(HTTP_CACHED_FILE);
readfile($img_path);
die;

