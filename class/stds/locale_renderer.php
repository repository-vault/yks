<?


class locale_renderer {
  static private $locale_definition = array();
  static function init(){
    foreach(yks::$get->config->dyn_entities->children() as $entity_def)
        renderer::register($entity_def->getName(), array(__CLASS__, 'render'));
  }
  static function render($entity_type, $vals, $lang){
    $entity_def = yks::$get->config->dyn_entities->$entity_type;
    $entity_key = (string)($entity_def['key']?$entity_def['key']:$entity_type);
    $entity_pfx = isset($entity_def['prefix'])?(string)$entity_def['prefix']:false;
    $prefix_len = $entity_pfx?($entity_pfx?strlen($entity_pfx)+1:0):0;
    $entity_mode = $entity_def['mode'];
    $entity_col = $entity_def['col'];
    $entity_table = $entity_def['table'];

    $entities=array();

    if($vals) {
        if($entity_pfx) $vals = array_mask($vals, "$entity_pfx.$entity_type.%s");
        $where = array($entity_key=>$vals);
        $limit="LIMIT ".count($vals);
    }else $where=array("true");


    if($entity_mode == 'col') $val = $entity_col?$entity_col:$lang;
    else {
        $where['lang_key']=$lang;
        $val = $entity_col;
    }

    sql::select($entity_table,$where, "`$entity_key` as key, `$val` as val",$limit);
    while(extract(sql::fetch()))
        $entities['&'.(
                $entity_pfx!==false?substr($key,$prefix_len):$entity_type.'.'.$key
              ).';']=$val;
    return $entities;
  }

}