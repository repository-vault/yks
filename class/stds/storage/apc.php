<?php

/**
*  @alias storage
**/
class _storage_apc {
  const HTTP_GET = 'GET';
  const HTTP_PUT = 'PUT';
  const HTTP_DELETE = 'DELETE';

  private static $gw = null;
  public static function __construct_static(){

    if(PHP_SAPI == "cli") {
      self::$gw = (string) yks::$get->config->storage->apc['gw'];
      if(self::gw_call(self::HTTP_GET, "") != "pong") {
        error_log("Invalid apc GW configuration");
        self::$gw = null;
      }
    }
  }

  private static function gw_call($method, $key, $data = null){
    $opts = array('http' => array( 'method'  => $method, 'content' => $data ));
    $context  = stream_context_create($opts);

    $url = sprintf("%s/%s", self::$gw, $key);
    $result = @file_get_contents($url, false, $context);
    return unserialize($result);
  }

  static function store($k, $v, $ttl=0) {
    if(self::$gw)
      return self::gw_call(self::HTTP_PUT, $k, $v);
    return apc_store($k, $v, $ttl)?$v:false;
  }

  static function fetch($k) {
    if(self::$gw)
      return self::gw_call(self::HTTP_GET, $k);
    return apc_fetch($k);
  }

  static function delete($k) {
    if(self::$gw)
      return self::gw_call(self::HTTP_DELETE, $k);
    return apc_delete($k);
  }

  public static function clean(){
    return false;
  }
}
