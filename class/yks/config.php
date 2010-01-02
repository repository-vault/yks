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

    if(!$use_cache)
        return KsimpleXML::load_file($file_path, __CLASS__);

    $key    = self::cache_pfx.crpt($file_path, "yks/config", 10);
    $config = YKS_CONFIG_FORCE ? false 
        : ( self::$_cache[$key] ? self::$_cache[$key] : self::$_cache[$key] = storage::fetch($key) );
    if(!$config) {
        $config = storage::store($key, self::load($file_path, false) );

        //keep hash up to date
        $hash_key = self::hash_key();
        $hash = array_filter((array)storage::fetch($hash_key)); $hash[$key] = $file_path; //infos ?
        storage::store($hash_key, $hash);
    }
    return $config;
  }

  public function to_simplexml(){
    $tmp = simplexml_load_string("<null>".$this->asXML()."</null>");
    return $tmp->{$this->getName()};  //document != documentElement
  }

  function __get($key){
    $tmp = parent::__get($key);
    

    if(!isset($tmp[self::xattr])) return $tmp;
    list($file_path, $search) = explode(" ", $tmp[self::xattr]); //!

    $file_path = paths_merge(ROOT_PATH, $file_path);
    if(! file_exists($file_path)) return $tmp;
    $ret = self::load($file_path)->search($search);
    foreach($tmp->attributes() as $k=>$v)
        if($k != self::xattr) $ret[$k] = $v; //merge args

    return $ret;
  }

}
