<?php

class storage {
  private static $data = array();
  static function store($k, $v, $ttl=0) { return self::$data[$k] = $v; }
  static function fetch($k)           {   return self::$data[$k]; }
  static function delete($k)          {   unset(self::$data[$k]); }
}