<?
include_once CLASS_PATH."/mails/mail_base.php";
include_once CLASS_PATH."/apis/mails/mime_part.php";
include_once CLASS_PATH."/apis/mails/mime_functions.php";

abstract class mime extends mail_base {


  function output_headers( $headers=array() ){

    $headers = array_filter(array_merge(array(
        "Subject"     => self::header_encode(jsx::translate(specialchars_decode($this->subject))),
        "From"        => $this->from,
        "To"          => join(', ', $this->to),
        "CC"          => join(', ', $this->cc),
    ),$headers)); $headers = mask_join(CRLF,$headers, '%2$s: %1$s').CRLF;

    return $headers;
  }

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



