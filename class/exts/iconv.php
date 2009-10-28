<?php

//This emulate iconv functions 
// !!!! REQUIRE ext mb_string

function iconv($in_charset, $out_charset, $str){
    $out_charset=preg_replace('#//[a-z]+$#i','',$out_charset);
    if($in_charset) return mb_convert_encoding( $str, $out_charset,$in_charset);
    return mb_convert_encoding( $str, $out_charset);
}
function iconv_strlen($str, $charset){
    return mb_strlen($str,$charset);
}
