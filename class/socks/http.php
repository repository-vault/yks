<?
include "2616.php";

class http {
  const LWSP='[\s]';
  static $headers_multiple = array('Set-Cookie');

  static function parse_headers($headers_str){
    $headers_str = preg_replace('#'.CRLF.self::LWSP.'+#',' ',$headers_str);
    $headers = array();

    $liste = explode(CRLF, $headers_str);
    foreach($liste as $header_str) {
        $header = header::parse_string($header_str); $name = $header->name;
        if(!$header) continue;
        if(in_array($name, self::$headers_multiple)) $headers[$name][] = $header;
        else {
            $tmp=$headers[$name];
            $headers[$name] = $tmp?array_merge(is_array($tmp)?$tmp:array($tmp), array($header)):$header;
        }
    }
    return $headers;
  }

}
