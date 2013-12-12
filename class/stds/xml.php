<?php


class xml {
  private static $dtds_paths = array();


  static function register_fpi($FPI, $dtd_path, $root_element=false) {
    self::$dtds_paths[$FPI] = array(
        'dtd_path'=>$dtd_path,
        'root_mask'=>$root_element?$root_element:"[a-z]+", //anonymous root preg
        'fpi_mask'=> preg_quote($FPI, '#')
    );
  }

  static function clean_html($str){
    $doc = new DOMDocument('1.0','UTF-8');

    @$doc->loadHTML("<html><body>$str</body></html>"); $str=$doc->saveXML();
    $str = utf8_decode(html_entity_decode($str, ENT_NOQUOTES, "UTF-8"));

    $str = mb_ereg_replace("&", "&amp;", mb_ereg_replace("&amp;","&",$str));
    if(strpos($str,"<body/>")) return "";

    $start = strpos($str, "<body>")+6;
    $end   = strpos($str, "</body>");
    $str   = substr($str, $start, $end-$start);

    return $str;
  }

  static function load_html($str, $first_element=false){
    libxml_use_internal_errors(true);
    $doc = new DomDocument("1.0", "UTF-8");
    $doc->loadHTML($str);
    libxml_clear_errors(); libxml_use_internal_errors();
    $res = simplexml_import_dom($doc);
    if($first_element)
        return first($res->xpath("//$first_element"));
    else return $res;
  }



  static function load_file($file_path, $FLAGS = LIBXML_YKS, $FPI = false){

    if(!$FPI) {
        $doc = new DOMDocument('1.0','UTF-8');
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace= false;
        $tmp = $doc->load($file_path, $FLAGS);
        return $tmp?$doc:false;
    }

    $contents = file_get_contents($file_path);
    return self::parse_string($contents, $FLAGS, $FPI);
  }

  static function load_string($str, $FLAGS = LIBXML_YKS, $FPI = false){
    if(!$FPI) {
        $doc = new DOMDocument('1.0','UTF-8');
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace= false;
        $tmp = $doc->loadXML($str, $FLAGS);
        return $tmp?$doc:false;
    }
    return self::parse_string($str, $FLAGS, $FPI);

  }


  private static function parse_string($contents, $FLAGS, $FPI){

    $fpi = self::$dtds_paths[$FPI];
    if(!$fpi) throw new Exception("Unknow fpi");

    $fpi['dtd_path'] = strtr(realpath($fpi['dtd_path']), '\\', '/');

    $search_mask = '#<\!DOCTYPE\s+(%s)\s+PUBLIC\s+"%s"[^>]*>#';
    $search_mask = sprintf($search_mask, $fpi['root_mask'], $fpi['fpi_mask']);
    $replace = '<!DOCTYPE $1 SYSTEM "'.$fpi['dtd_path'].'">';

    $contents = preg_replace( $search_mask, $replace, $contents);

    if(!$contents)
        throw new Exception("Invalid syntax");

    libxml_use_internal_errors(true);

        $doc      = new DomDocument("1.0", "UTF-8");
        $doc->loadXML($contents, LIBXML_MYKS);
        $success = $doc->validate();

    libxml_clear_errors();
    libxml_use_internal_errors();

    if(!$success)
        throw new Exception("Invalid syntax");
    return $doc;


  }

}