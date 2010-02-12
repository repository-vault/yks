<?php

$theme_name  = $sub0;
$is_title    = (bool)$sub1;
$title_text  = $argv0;

if(!$title_text) $title_text = "Default title";

$titles_cache_path = CACHE_PATH."/imgs/titles";

$hash     = md5("$theme_name, $title_text, ".SITE_CODE.", $is_title");
$img_path = "$titles_cache_path/$hash.png";


if(!is_file($img_path)) try {

    $img = dsp_titles::draw($theme_name, $title_text, $is_title);
    files::create_dir($titles_cache_path);
    if(!imagepng($img, $img_path))
        throw rbx::error("Image generation failed");

}catch(Exception $e){
    syslog(LOG_ERR, "Unable to load theme : $e");
    die("Unable to load theme");
}


header(TYPE_PNG);
header(HTTP_CACHED_FILE);
readfile($img_path);
die;


