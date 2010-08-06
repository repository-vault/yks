<?php

/*  "Yks config" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2010
    Keep an internal reference table on ROOT_PATH hash so we can use it to clear APC cache
*/

class config extends KsimpleXMLElement {
    const cache_pfx = 'config_';      // cache key prefix
    const xattr = 'file';             // derivation attribute
    private static $_cache = array(); // prevent level 0 re-instanciation

  public static function hash_key(){ return self::cache_pfx.crpt(ROOT_PATH, "yks/confighash", 10); }
  public static function load($file_path, $use_cache = YKS_CONFIG_CACHE) {

    $key    = self::cache_pfx.crpt($file_path, "yks/config", 10);
    if(!$use_cache){
        syslog(LOG_INFO, "Skipping config cache for $key,you might want to SetEnv YKS_CONFIG_CACHE true");
        return KsimpleXML::load_file($file_path, __CLASS__);
    }


    $config = YKS_CONFIG_FORCE ? false 
        : ( self::$_cache[$key] ? self::$_cache[$key] : self::$_cache[$key] = config_storage::fetch($key) );
    if(!$config) {
        $config = self::load($file_path, false);
        config_storage::store($key, $config);
        syslog(LOG_INFO, "Reloading config for $key");

        //keep hash up to date
        $hash_key = self::hash_key();
        $hash = array_filter((array)config_storage::fetch($hash_key)); $hash[$key] = $file_path; //infos ?
        config_storage::store($hash_key, $hash);
    }
    return $config;
  }

  public function to_simplexml(){
    $tmp = simplexml_load_string("<null>".$this->asXML()."</null>");
    return $tmp->{$this->getName()};  //document != documentElement
  }

  function search($key, $autocreate = false){
    $tmp = parent::search($key, $autocreate);
    
    if(!isset($tmp[self::xattr]))
        return $tmp;

    $ret = self::resolve($tmp);
    $this->replace($ret, $tmp);
    return $ret;
  }

  private static function resolve($tmp){
    list($file_path, $search) = explode(" ", $tmp[self::xattr]); //!
    $file_path = paths_merge(ROOT_PATH, $file_path);
    if(! file_exists($file_path))
        return $tmp;

    $ret = self::load($file_path);
    if($search) $ret = $ret->search($search, true); //autocreate

    foreach($tmp->attributes() as $k=>$v)
        if($k != self::xattr) $ret[$k] = $v; //merge args
    return $ret;
  }

  
  public function asSimpleXML(){
    return simplexml_load_string($this->asXML(true));
  }

  public function asXML($resolve = false){
    if(!$resolve || !isset($this[self::xattr]))
        return parent::asXML($resolve);
    $ret = self::resolve($this);
    return $ret->asXML($resolve);
  }

}


if(PHP_SAPI == 'cli') { class config_storage { //speed
  private static $data = array();
  static function store($k, $v, $ttl=0) { return self::$data[$k] = $v; }
  static function fetch($k)           {   return self::$data[$k]; }
  static function delete($k)          {   unset(self::$data[$k]); }
}} else { class config_storage { //on apc
  static function store($k, $v, $ttl=0) { return apc_store($k, $v, $ttl)?$v:false; }
  static function fetch($k)           { return apc_fetch($k); }
  static function delete($k)          { return apc_delete($k); }
}}


