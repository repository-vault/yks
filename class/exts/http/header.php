<?


class header {
  public $name;
  public $value;
  public $extras;
  private $raw;

  function __construct($name, $value, $extras, $raw){
    $this->name = $name;
    $this->value = $value;
    $this->extras = self::parse_extras($extras);
    $this->raw = $raw;
  }

  static function parse_string($str){
    if(!preg_match("#(.*?):\s*([^;]*)(;.*)?#", $str, $out)) return null;
    list(, $name, $value, $extras) = $out;
    $name = ucfirst(preg_replace("#(-[a-z])#e", 'strtoupper("$1")' , $name));
    return new header($name, $value, $extras, $str);
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
    if($key=='raw')
        return "$this->name: {$this}";

  }
}

