<?


class header {
  public $name;
  public $value;
  public $extras;

  function __construct($name, $value, $extras=''){
    $this->name = $name;
    $this->value = $value;
    $this->extras = self::parse_extras($extras);
  }

  static function parse_string($str){
    if(!preg_match("#(.*?):\s*([^;]*)(;.*)?#", $str, $out)) return null;
    list(, $name, $value, $extras) = $out;
    $name = ucfirst(preg_replace("#(-[a-z])#e", 'strtoupper("$1")' , $name));
    return new header($name, $value, $extras);
  }

  static function parse_extras($str){
    $params=array();
    preg_match_all('#;\s*([a-z0-9-]+)=((["\'])[^\\3]*?\\3|[^\s]+)#i',$str,$out,PREG_SET_ORDER);
    foreach($out as $data) $params[$data[1]]=trim($data[2],$data[3]);
    return $params;
  }
  function __toString(){
    return $this->value;
  }
}

class cookie {
  public $name;
  public $value;
  public $path;
  public $expire;
  public $domain;

  function __construct($name, $value, $extras) {
    $this->name = $name;
    $this->value = $value;
    $this->path = $extras['Path'];
    $this->domain = $extras['Domain'];
  }

  static function parse_header($header){
    list($name, $value) = explode("=", $header->value, 2);
    return new cookie($name, $value, $header->extras);
  }


}