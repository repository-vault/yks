<?

/*
    This renderer is an entity (&...;) processor
    given a text ($str) in a language ($lang) renderer::process_entities($str, $lang)
    will translate every registered entity pattern into their value (depending on $lang)
    to register an entity decoder, please use renderer::register($entity_prefix, $callback function)
    the registered callback will receive : $entity_type, $entities_vals, $lang at call
*/

class renderer {
  static private $entities_renderer = array();

  static private $entities_std_definition = array();

  public static function register($base, $callback){ self::$entities_renderer[$base] = $callback; }
  public static function defined($entity_type){ return isset(self::$entities_renderer[$entity_type]); }

  public static function register_std_renderer($entity_type, $entity_table, $entity_col){
    self::$entities_std_definition[$entity_type] = compact('entity_table', 'entity_col');
    self::register($entity_type, array(__CLASS__, 'std_entity_renderer'));
  }

  public static function process_entities($str, $lang){
    $entity_mask=join('|', array_keys(self::$entities_renderer) );
    $entity_dynamic_mask="#&($entity_mask)\.([.a-zA-Z0-9_-]+);#";
    preg_match_all($entity_dynamic_mask, $str, $out);

    $entity_loaded=array();
    foreach($out[1] as $k=>$entity_type)
        $entity_loaded[$entity_type][]=$out[2][$k];

    $entities = array();
    $entities = array_merge($entities, self::process($entity_loaded, $lang));
    return strtr($str, $entities);
  }


  private static function process($entities_vals, $lang){
    $entities = array();
    foreach($entities_vals as $entity_type=>$entities_vals){ //copy
        if($renderer = self::$entities_renderer[$entity_type]) 
            $entities = array_merge($entities,
                call_user_func($renderer, $entity_type, $entities_vals, $lang));
    }
    return $entities;
  }

  private static function std_entity_renderer($entity_type, $entities_vals){
    $entity_def = self::$entities_std_definition[$entity_type];
    if(!$entity_def) return false; //Undefined std entity definition
    $verif_entities = array($entity_type=>$entities_vals);
    $cols = "$entity_type, {$entity_def['entity_col']}";
    sql::select($entity_def['entity_table'], $verif_entities, $cols);
    $entities = array(); $entity_col = $entity_def['entity_col'];
    while($l= sql::fetch()) $entities["&$entity_type.{$l[$entity_type]};"] = $l[$entity_col];
    return $entities;
  }


}