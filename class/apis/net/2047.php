<?

class rfc_2047 {


  static function encoding_encode($str, $encoding = '7bit'){
        if($encoding == "quoted-printable") return self::quoted_printable_encode($str);
        elseif($encoding == "base64") return chunk_split(base64_encode($str));
        else return $str; //8bit LOL
    }

  static function header_encode($str){
    if(preg_match("#[^\x20-\x7E]#", $str))
        $str = "=?UTF-8?Q?".self::quoted_printable_encode($str)."?=";

    return $str;
  }

  static function  quoted_printable_encode($str) {
    $fp = fopen("php://memory", 'r+');
    stream_filter_append($fp, 'convert.quoted-printable-encode',STREAM_FILTER_READ);
    fwrite($fp, $str); rewind($fp);$str = stream_get_contents($fp);
    return $str;
  }

}