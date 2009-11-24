<?php


class xml {
  private static $dtds_paths = array();


  static function load_file($file_path, $FLAGS = LIBXML_YKS, $FPI = false){
    if(!$FPI) {
        $doc = new DOMDocument('1.0','UTF-8');
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace= false;
        $tmp = $doc->load($file_path, $FLAGS);
        return $tmp?$doc:false;
    }


    $fpi = self::$dtds_paths[$FPI];
    if(!$fpi) throw new Exception("Unknow fpi");

    $search_mask = '#<\!DOCTYPE\s+(%s)\s+PUBLIC\s+"%s"[^>]*>#';
    $search_mask = sprintf($search_mask, $fpi['root_mask'], $fpi['fpi_mask']);
    $replace = '<!DOCTYPE $1 SYSTEM "'.$fpi['dtd_path'].'">';

    $contents = file_get_contents($file_path);
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


  static function load_string($str, $FLAGS = LIBXML_YKS){
    $doc = new DOMDocument('1.0','UTF-8');
    $doc->formatOutput = false;
    $doc->preserveWhiteSpace= false;
    $tmp = $doc->loadXML($str, $FLAGS);
    return $tmp?$doc:false;
  }

  static function register_fpi($FPI, $dtd_path, $root_element=false) {
    self::$dtds_paths[$FPI] = array(
        'dtd_path'=>$dtd_path,
        'root_mask'=>$root_element?$root_element:"[a-z]+", //anonymous root preg
        'fpi_mask'=> preg_quote($FPI, '#')
    );
  }

}