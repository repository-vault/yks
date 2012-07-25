<?

class cms_rewrite {
  private static $map_file;
  private static $map;

  private static $mask_from;
  private static $mask_to;

  const DATA_KEY = __CLASS__;

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    $cms_config = yks::$get->config->cms;

    self::$mask_from = $cms_config->rewrite['from'];
    self::$mask_to   = $cms_config->rewrite['to'];

    self::$map_file  = "path://cache/map.txt";
    self::$map      = data::fetch(self::DATA_KEY);
    if(!self::$map) {

        self::$map = cms_rewrite::generate_map();
        self::write();
    }
    
  }

  private static function write(){
    data::store(self::DATA_KEY, self::$map);
    $str = mask_join(CRLF, self::$map, "%s %s");
    file_put_contents(self::$map_file, $str);
  }

  static function format(&$str){
    $str = specialchars_decode($str);
    $str = strtr($str, array('&'=>'and', "'"=>'’', '"'=>'-', '!'=>''));
    $str = preg_replace("#\s+#"," ", $str); 
    $str = str_replace(" ", "_", $str);
    $str = txt::strip_accents($str);
    return $str;
  }
  static function resolve_links($doc, $node){
    $link = $node->getAttribute("href"); 

    if(!self::$map[$link]) return;
    $link = sprintf("/%s.htm", self::$map[$link]);
    $node->setAttribute("href", $link);
  }

    //beta
  static function generate_map($verif_root = array('true')){

    $nodes = cms_node::from_where($verif_root);
    $node_types = array_extract($nodes, 'node_type', true);

    $map = array();
    foreach($nodes as $node){
        $leaf = $node->leaf;
        $data = array(
            'node_title' => $leaf->node_title,
            'lang_key'   => $leaf->lang_key,
            'node_id'    => $node->node_id,
        );
        $from = str_evaluate(self::$mask_from, $data);
        $to   = str_evaluate(self::$mask_to, $data);
        $map[$from] = self::format($to);
    }

    return $map;
  }


}