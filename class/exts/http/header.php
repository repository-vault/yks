<?php


class header {
  public $name;  //modify
  public $value; //modify
  public $extras;
  private $raw;
  private $value_raw;

  function __construct($name, $value, $extras, $raw, $value_raw = false){

    $this->name      = $name;
    $this->value     = $value;
    $this->extras    = self::parse_extras($extras);
    $this->raw       = $raw;
    $this->value_raw = $value_raw ? $value_raw : $value;
  }

  static function parse_string($str){
    if(!preg_match("#(.*?):\s*(([^;]*)(;.*)?)#", $str, $out)) return null;
    list(, $name, $value_raw, $value_first, $extras) = $out;
    $name = ucfirst(preg_replace("#(-[a-z])#e", 'strtoupper("$1")' , $name));
    $value_first = trim(self::unescape($value_first));
    return new header($name, $value_first, $extras, $str, $value_raw);
  }

  static function unescape($str){
   if(preg_match("#^\s*(?:\"([^\"]*)\"|'([^']*)')\s*\$#", $str, $out)) 
     return pick($out[1], $out[2]);
   return $str;
  }

  static function parse_extras($str, $normalize = true){
    $params=array();
    preg_match_all('#;\s*([a-z0-9-]+)(?:=((["\'])[^\\3]*?\\3|[^;]+))?#i',$str,$out,PREG_SET_ORDER);
    foreach($out as $data) $params[$data[1]]=trim($data[2],$data[3]);
    if($normalize) $params = array_change_key_case($params, CASE_LOWER);
    return $params;
  }
  function __toString(){
    return $this->value;
  }

  function __get($key){

    if($key=="value_raw")
        return $this->value_raw;

    if($key=='raw')
        return $this->raw;

    if($key=='full')
        return "$this->name: $this->value"; //extras ?
  }
}

