<?php

/**
*  @alias storage
**/
class _storage_apc {
  static function store($k, $v, $ttl=0) { return apc_store($k, $v, $ttl)?$v:false; }
  static function fetch($k)           { return apc_fetch($k); }
  static function delete($k)          { return apc_delete($k); }
}
