<?php

/**
*  @alias storage
**/
class _storage_redis {

  private static $redis = null;
  private static $local_cache = array();

  public static function __construct_static(){
    if(self::$redis != null)
      return;

    $conf = yks::$get->config->storage;

    if(trim($conf['driver']) != 'redis')
      throw new Exception("Invalid redis configuration '{$conf['driver']}'");

    //Get list of redis
    $hosts_redis = array();
    foreach($conf->redis as $r){
      $hosts_redis[trim($r['name'])] = array(
        'host' => trim($r['host']),
        'port' => (int)$r['port'],
      );
    }

    $host_redis = $hosts_redis[pick( $conf['default_lnk'], "default") ];

    self::$redis = new Redis();
    if(!self::$redis->connect($host_redis['host'], $host_redis['port'])){
      self::$redis = false;
      syslog(LOG_ERR, "Failed to connect to redis host, disabling cache");
    }
  }


  static function store($k, $v, $ttl=0){
    if(!self::$redis )
      return self::$local_cache[$k] = $v;
    return self::$redis->set($k, serialize($v), $ttl) ;
  }

  static function fetch($k){
    if(!self::$redis)
     return self::$local_cache[$k];  

    $out = self::$redis->get($k);
    return $out ? unserialize($out) : false;
  }

  static function delete($k){
    if(!self::$redis) {
     unset(self::$local_cache[$k]);
     return;
    }

    return self::$redis->del($k);
  }


  public static function clean(){
    return false;
  }
}
