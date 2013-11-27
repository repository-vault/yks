<?php

/*  "Yks config" by Leurent F. (131)
    distributed under the terms of GNU General Public License
*/

class config extends KsimpleXMLElement {
  const cache_pfx = 'config_';      // cache key prefix
  const xattr = 'file';             // derivation attribute

  public static function load($file_path, $use_cache = YKS_CONFIG_CACHE) {
    if(!$use_cache)
      return KsimpleXML::load_file($file_path, __CLASS__);

    $cache_key    = ROOT_PATH.'_'.crpt($file_path, "yks/config", 10);
    $config = YKS_CONFIG_FORCE ? false : config_storage::fetch($cache_key) ;
    if(!$config) {
      $config = self::load($file_path, false);
      config_storage::store($cache_key, $config);
    }

    return $config;
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
    $path  =$tmp[self::xattr];
    $parts = explode(" ", $path);
    while(count($parts) < 2) {
      $parts[] = null;
    }

    list($file_path, $search) = $parts;
    $file_path = paths_merge(ROOT_PATH, $file_path);
    if(! file_exists($file_path)) {
      error_log("Configuration file do not exists $file_path ($path)");
      return $tmp;
    }

    $ret = self::load($file_path);
    if($search) $ret = $ret->search($search, true); //autocreate

    foreach($tmp->attributes() as $k=>$v)
      if($k != self::xattr) $ret[$k] = $v; //merge args
      return $ret;
  }

  public function is_debug(){
    static $result = null;
    if(!is_null($result)) return $result;

    $base = yks::$get->config->site['debug'];
    $base = preg_split(VAL_SPLITTER, $base);
    $result = http::ip_allow($base, exyks::$CLIENT_ADDR);
    return $result;
  }

  public function asSimpleXML(){
    $tmp = simplexml_load_string("<null>".$this->asXML(true)."</null>");
    return $tmp->{$this->getName()};  //document != documentElement
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

