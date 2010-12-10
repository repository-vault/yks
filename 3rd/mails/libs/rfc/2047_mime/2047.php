<?php

class rfc_2047 {


  static function encoding_encode($str, $encoding = '7bit'){
        if($encoding == "quoted-printable") return self::quoted_printable_encode($str);
        elseif($encoding == "base64") return chunk_split(base64_encode($str));
        else return $str; //8bit LOL
    }

  static function header_encode($str){
    if(preg_match("#[^\x20-\x7E]#", $str))
        $str = "=?UTF-8?Q?".self::quoted_printable_encode($str,1024,true)."?=";

    return $str;
  }

  static function  quoted_printable_encode($str) {
    $filter_name     = 'convert.quoted-printable-encode';
    $filter_fallback = array('php_legacy', 'quoted_printable_encode');

    $str =  stdflow_filter::transform($str, $filter_name, $filter_fallback);
    return $str;
  }

}

