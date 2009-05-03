<?

class locales_fetcher {

    //this function only purpose is to be called as data::reload("entities");
  public static function retrieve(){
    self::fetch_all();
    return false; // data::reload will fetch it, finally
  }

  public static function fetch_all($trace=false){
    $done = array();
    $languages       = exyks::retrieve('LANGUAGES');
    $languages_order = preg_split("#[, ]+#", yks::$get->config->languages['order']);
    $languages_order = array_intersect($languages_order, $languages);

    if(!$languages_order) return $done;

    $entities=array();
    foreach($languages_order as $lang_key){
        $flag_full = "entities_$lang_key";
        $entities = self::load_entities($lang_key, $entities);
        data::store($flag_full, $entities);
        $done[] = array($lang_key, count($entities));
    } return $done;
  }

  private static function load_entities($lang_key, $entities=array()){ 
    include_once CLASS_PATH."/stds/files.php";
 
    $config = yks::$get->config;

        //softly remove paths and flags from localization constants
    $constants = retrieve_constants(CONST_LOCALES, "&%s;");

    $dyn_entities = array();
    if($config->dyn_entities)
      foreach($config->dyn_entities->children() as $entity_def){
        if(strpos($entity_def['options'],"cachable")===false)continue;
        $dyn_entities = array_merge($dyn_entities,
            locales_renderer::render($entity_def->getName(), false, $lang_key));
    }

    $entities = array_merge($constants, $dyn_entities, $entities);
    $path = "lang/$lang_key";

    foreach(files::find($path,'\.ent$',files::FIND_FOLLOWLINK) as $dtd_file)
        $entities = array_merge($entities, dtd::ent_get($dtd_file));

    return $entities;
  }

}