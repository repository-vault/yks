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
  private static $full = array();
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

    //register site-specifics errors messages
    foreach(yks::$get->config->site->errors->iterate("err") as $err)
      $entities["&err_{$err['code']};"] = $err['sys'];

    foreach(array_reverse(array_keys(
    ages_list)) as $lang_key){
      self::$full[$lang_key] = self::load_entities($lang_key);
      $fback = locale::fetch_first_fallback($lang_key);;
      self::$fallbacks[$lang_key] = $fback['lang_fallback'];
    }

    foreach(self::$full as $lang_key=>$values_no_fallback ) {
      foreach($languages_list[$lang_key] as $fb_lang)
        //Merge $lang_key items list with $fb_lang items list
        $values_no_fallback = array_merge(self::$full[$fb_lang],$values_no_fallback);
      foreach($values_no_fallback as $item_key=>$item_value)
        $values_list[$lang_key][$item_key] = self::get_item_value($item_key, $lang_key);
    }

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

    $entities=array();

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
  * @param  $item_key The name of the item
  * @param string $lang_key The language you want the item translated to
  * @returns string The translation
  */
  private function get_item_value($item_key, $lang_key) {
    if (self::$full[$lang_key][$item_key])
      return self::$full[$lang_key][$item_key];

    $fback = self::$fallbacks[$lang_key];
    if($fback == $lang_key || !$fback)
      return "";

    $item_value = self::$full[$fback][$item_key];
    if(!$item_value)
      return self::get_item_value($item_key, $fback);
    return $item_value;
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