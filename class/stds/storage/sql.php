<?php

/**
*  @alias storage
**/
class _storage_sql {
  private static $config;
  private static $secure_key = false;
  
  static function init() {
    $xml = yks::$get->config->storage;
    self::$config = array(
      'table_name'  => pick($xml['table_name'],  'storage'),
      'key_field'   => pick($xml['key_field'],   'key'),
      'value_field' => pick($xml['value_field'], 'value'),
    );

  }

  private static function input($obj){
    if(!self::$secure_key)
      return serialize($obj);
    return crypt::encrypt(serialize($obj), self::$secure_key, true);
  }
  
  private static function output($str){
    if(!self::$secure_key)
      return unserialize($obj);
    return unserialize(crypt::decrypt($str), self::$secure_key, true);
  }

  public static function set_key($key) {
    self::$secure_key = $key;
  }
  
  static function store($k, $v, $ttl=0) {

    $verif_key = array(self::$config['key_field']=>$k);
    $data = array(
        self::$config['value_field'] => self::input($v)
    ); sql::replace(self::$config['table_name'], $data, $verif_key);
    return $v;
  }

  static function fetch($k) {
    $verif_key = array(self::$config['key_field']=>$k);
    $str = sql::value(self::$config['table_name'], $verif_key, self::$config['value_field']);
    return self::output($str);
  }


  static function delete($k) {
    $verif_key = array(self::$config['key_field']=>$k);
    sql::delete(self::$config['table_name'], $verif_key);
  }

}