<?

class cms_rewrite {
  private static $map_file;
  private static $map;

  const DATA_KEY = __CLASS__;

  static function init(){
    self::$map_file = CACHE_PATH."/map.txt";
    self::$map      = data::fetch(self::DATA_KEY);
  }

  static function store_map($map){
    self::$map = $map;
    array_walk(self::$map, array(__CLASS__, "format"));

    data::store(self::DATA_KEY, self::$map);
    self::write();
  }

  static function write(){
    $str = mask_join(CRLF, self::$map, "%s %s");
    file_put_contents(self::$map_file, $str);
  }
  static function format(&$str){
    $str = str_replace(" ", "_", $str);
    $str = strip_accents($str);
    return $str;
  }
  static function resolve_links($doc, $node){
    $link = $node->getAttribute("href");
    if(!self::$map[$link]) return;
    $link = sprintf("/%s.htm", self::$map[$link]);
    $node->setAttribute("href", $link);
  }
}