<?php

/**
*  @alias storage
**/
class _storage_var {
  private static $data = array();
  static function store($k, $v, $ttl=0) { return self::$data[$k] = $v; }
  static function fetch($k)           {   return self::$data[$k]; }
  static function delete($k)          {   unset(self::$data[$k]); }
  public static function clean()      { self::$data = array(); }

}