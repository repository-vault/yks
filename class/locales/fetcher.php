<?php

/*
  Public : fetch_all() :
    store in memory (data::load) for every language
    all cachable entities 
*/

class locales_fetcher {

  static public $locale_paths;
  static private $locale_tables = array();
  

    //prepare paths list
  public static function init(){

    $paths = array();

    foreach(yks::$get->config->locales->locales_paths->iterate("path") as $path){
        $paths[] = exyks_paths::resolve($path['path']);
    }

    foreach(exyks::get_modules_list() as $module)
        $paths = array_merge($paths, $module->locale_paths);

    self::$locale_paths =  $paths;
  }


    //this function only purpose is to be called as data::reload("entities");
  public static function retrieve(){
    self::fetch_all();
    return false; // data::reload will fetch it, finally
  }

  public static function fetch_all(){

    $locales          = yks::$get->config->locales;
    $languages        = exyks::retrieve('LANGUAGES');


    $languages_order  = array_filter(preg_split(VAL_SPLITTER, $locales['order']));
    $languages_order  = array_intersect($languages_order, $languages);


    if($locales->cache->sql) foreach($locales->cache->sql as $cache_def)
        self::$locale_tables[] = $cache_def['table'];

    $done = array();
    data::store("entities_en-us", $done); //fallback no lang (en-us)
    if(!$languages_order) return $done;

    $entities = array();
    foreach($languages_order as $lang_key){
        $flag_full = "entities_$lang_key";
        $entities = self::load_entities((string)$lang_key, $entities);
        data::store($flag_full, $entities);
        $done[] = array($lang_key, count($entities));
    } return $done;
  }

  private static function load_entities($lang_key, $entities=array()){ 

    $files = array();
    foreach(self::$locale_paths as $path)
        $files = array_merge($files, files::find($path,'\.xml$',files::FIND_FOLLOWLINK));

    foreach($files as $file)
        $entities = array_merge($entities, self::ent_get($file, $lang_key));

    foreach(self::$locale_tables as $table_name)
        $entities = array_merge($entities, locales_sql_scanner::scan_all($table_name, $lang_key));

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