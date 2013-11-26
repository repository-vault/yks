<?php


Element::__register("Forms");



if(!function_exists('preg_reduce')) {
  function preg_reduce($mask, $str){
    preg_match($mask,$str,$out);
    return $out[1];
  }
}

if(!function_exists('pick')) {
    //return the first non empty value
    function pick(){ $args = func_get_args(); return reset(array_filter($args)); }
}

if(!function_exists('innerHTML')) {
    function innerHTML($str){ return preg_reduce("#^[^>]+>(.*?)<[^<^]+$#s", $str); }
}

function simplexml_load_url($url){
    $str = file_get_contents($url);
    return simplexml_load_html($str);
}

function simplexml_load_html($str, $charset = "utf-8", $class="Element"){
    return dom::simplexml_load_html($str, $charset, $class);
}

