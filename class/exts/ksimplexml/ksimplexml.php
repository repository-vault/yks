<?php


class KsimpleXML {

  public static function load_string($str, $class = null){
    $parser = new KsimpleXMLParser($class);
    return $parser->process($str);
  }

  public static function load_file($file_path, $class = null){
    $contents = file_get_contents($file_path);
    return self::load_string($contents, $class);
  }
}
