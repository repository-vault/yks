<?php

class rfc_2047 {


  static function encoding_encode($str, $encoding = '7bit'){
        if($encoding == "quoted-printable") return quoted_printable_encode($str);
        elseif($encoding == "base64") return rtrim(chunk_split(base64_encode($str), 72),CRLF);
        else return $str; //8bit LOL
    }

  static function header_encode($str){
    if(preg_match("#[^\x20-\x7E]#", $str))
        $str = "=?UTF-8?Q?" . quoted_printable_encode($str)."?=";

    return $str;
  }

}

