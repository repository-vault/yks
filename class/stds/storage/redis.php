<?php

/**
*  @alias storage
**/
class _storage_redis {
  
  private static $redis = null;
  
  public static function init(){
    if(self::$redis != null)
      return;
    
    //Connect to redis
    $conf = yks::$get->config->storage;
    $host = pick(trim($conf['host']), '127.0.0.1');
    $port = pick((int)$conf['port'], 6379);
    self::$redis = new Redis();
    if(!self::$redis->connect($host, $port))
      throw new Exception("Failed to connect on redis host $host:$port");
  }
  
  static function store($k, $v, $ttl=0){
    return self::$redis->set($k, serialize($v), $ttl);
  }

  static function fetch($k){
    $out = self::$redis->get($k);
    return $out ? unserialize($out) : false;
  }

  static function delete($k){
    return self::$redis->del($k);
  }
  
  public static function clean(){
    return false;
  }
}
