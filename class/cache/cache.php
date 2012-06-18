<?php

interface iCache{
  public function __construct($conf);
  public function set($key, $value, $ttl=0);
  public function get($key);
  public function delete($key);
}

class cache {
  
  private static $conf = null;
  private static $worker = null;
  
  public static function init(){
    
    //Already configured
    if(self::$conf != null && self::$worker != null)
      return;
      
    //Load conf from xml
    if(isset(yks::$get->config->cache))
      throw new Exception("No configuration for cache");
    self::$conf = yks::$get->config->cache;
    
    //Build worker
    switch(self::$conf['type']){
      case 'file':
        self::$worker = new cache_file(self::$conf);
        break;
      case 'memory':
        self::$worker = new cache_memory(self::$conf);
        break;
      default:
        throw new Exception("No cache worker as: ".self::$conf['type']);
    }
  }
  
  //Aliases
  public static function set($key, $value, $ttl=0){
    return self::$worker->set($key, $value, $ttl=0);
  }
  public static function get($key){
    return self::$worker->get($key);
  }
  public static function delete($key){
    return self::$worker->delete($key);
  }
  
  
}
  
