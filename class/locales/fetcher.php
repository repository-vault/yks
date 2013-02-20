<?php

/*
  Public : fetch_all() :
    store in memory (data::load) for every language
    all cachable entities 
*/

class locales_fetcher {

  static public  $locale_search_paths;
  private static $locale_tables = array();
  private static $locale_files  = array();
  private static $fallbacks = array();
  
  const files_mask = '#\.xml$#';

    //prepare paths list
  public static function __construct_static(){

    $paths = array();

    foreach(yks::$get->config->locales->locales_paths->iterate("path") as $path){
        $paths[] = exyks_paths::resolve($path['path']);
    }

    foreach(exyks::get_modules_list() as $module)
        $paths = array_merge($paths, $module->locale_paths);

    self::$locale_search_paths =  $paths;
  }


    //this function only purpose is to be called as data::reload("entities");
  public static function retrieve(){
    self::fetch_all();
    return false; // data::reload will fetch it, finally
  }

  public static function fetch_all(){
    $locales        = yks::$get->config->locales;
    
    $languages_list = locales_manager::get_languages_list();
    
    self::$locale_tables = self::$locale_files = array();
    foreach($locales->cache->iterate("sql") as $cache_def)
      self::$locale_tables[] = $cache_def['table'];

    foreach(self::$locale_search_paths as $path)
      self::$locale_files = array_merge(self::$locale_files, files::find($path, self::files_mask, files::FIND_FOLLOWLINK));

    $babelmode = bool(yks::$get->config->locales['babelmode']);

    $done = array();
    data::store("entities_en-us", $done); //fallback no lang (en-us)
    if(!$languages_list) return $done;

    $full = array();
    $values_list = array();

    $errors = array();


    foreach(yks::$get->config->errors->iterate("error") as $error)
      $errors["&err_{$error['code']};"] = $error['message'];


    foreach(array_reverse(array_keys($languages_list)) as $lang_key){
      $full[$lang_key] = array_merge($errors, self::load_entities($lang_key));
      self::$fallbacks[$lang_key] = pick($languages_list[$lang_key][1], $languages_list[$lang_key][0]);
    }

    //Complete dictionnaries to have exhaustive item lists
    //- Build said exhaustive item list
    $dico_vide = array();
    foreach($full as $lang_key=>$values_no_fallback ) {
      $dico_vide = array_merge($dico_vide, array_keys($full[$lang_key]));
    }
    $dico_vide = array_fill_keys(array_unique($dico_vide),"");
    //- Fill every "lang" dictionnary with empty rows from item list
    foreach($full as $lang_key=>$values_no_fallback) {
      $full[$lang_key] = array_merge($dico_vide,$full[$lang_key]);
    }
    //Traduire
    foreach($full as $lang_key=>$values_no_fallback ) {
      foreach($values_no_fallback as $key => $value){
        if(!$value)
          $full[$lang_key][$key] = self::get_item_value($full, $key, $lang_key);
      }
    }
    $values_list = $full;

    $babel = array();
    foreach($values_list as $lang_key => $entities){
      if($babelmode) foreach($entities as $k=>$v)
        $babel["&$lang_key.".substr($k,1)] = $v;
      data::store("entities_{$lang_key}", $entities); //yes
      $done[] = array($lang_key, count($entities));
    }

    if($babelmode) {
      data::store("entities_babel", $babel);
      $done[] = array("babel", count($babel));
    }
    return $done;
  }

  private static function load_entities($lang_key){

    $entities = array();


    foreach(self::$locale_files as $file)
      $entities = array_merge($entities, self::ent_get($file, $lang_key));

    foreach(self::$locale_tables as $table_name)
      $entities = array_merge($entities, self::sql_get($table_name, $lang_key));

    if(locales_manager::$module_locale_exists)
      $entities = array_merge($entities, array_mask(locale::fetch_locales_values_raw($lang_key), "%s", "&%s;"));

    return $entities;
  }

  /**
  * Returns a string containing the translation of an item in a chosen language (or one of its fallbacks if needed)
  * @param  $full all item, for all languages
  * @param  $item_key The name of the item
  * @param string $lang_key The language you want the item translated to
  * @returns string The translation
  */
  private function get_item_value($full, $item_key, $lang_key, $hist = array()) {
     if(in_array($lang_key, $hist) || !$lang_key)
	return "";
    array_push($hist, $lang_key);
    
    if ($full[$lang_key][$item_key]) 
      return $full[$lang_key][$item_key];

    return self::get_item_value($full, $item_key, self::$fallbacks[$lang_key] , $hist);
  }


  private static function localization_get($lang_key) {
    $res = array();
    $locales_values = locale::fetch_locales_values($lang_key);
    foreach($locales_values as $item_key=>$value)
      $res["&$item_key;"] = $value;
    return $res;
  }

  private static function sql_get($table_name, $lang_key){
    sql::select($table_name, compact('lang_key')); $entities = array();
    while($l = sql::fetch()) $entities["&{$l['item_key']};"] = $l['value'];
    return $entities;
  }
  
  private static function ent_get($xml_file, $lang_key){
    $items = array();
    $xml = simplexml_load_file($xml_file);
    if(!$xml) return $items;
    $attributes = attributes_to_assoc($xml, "xml", true);
    if($lang_key != $attributes['lang'])
        return $items;

    foreach($xml->item as $item) {
        $key = (string)$item['key'];
        $items["&$key;"] = (string)$item;
    }
    return $items;
  }

}