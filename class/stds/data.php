<?php

/*  "Exyks data (APC)" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2008
*/

//Find documentation in the manual
class data {
  static $callbacks=array();

  static function store($flag, $v, $ttl=0){return apc_store(FLAG_APC."_$flag", $v, $ttl)?$v:false; }
  static function delete($flag) { return apc_delete(FLAG_APC."_$flag"); }
  static function fetch($flag){ return apc_fetch(FLAG_APC."_$flag"); }
  static function register($flag, $callback, $file=false){
    self::$callbacks[$flag] = $callback;
    if($file) classes::register_class_path(reset($callback), $file);
  }
  static function load($flag,$zone=''){
    $tmp = self::fetch(trim("{$flag}_{$zone}",'_'));
    if($tmp===false) return self::reload($flag, $zone);
    return substr($flag,-4)=="_xml"?simplexml_load_string($tmp):$tmp;
  }

  static function reload($flag, $zone=''){
    $tmp=false; $flag_full = trim("{$flag}_{$zone}",'_');
    if($callback = self::$callbacks[$flag]) $tmp = call_user_func($callback, $flag, $zone);
    else throw rbx::error("Invalid cache key '$flag'");

    if($tmp) self::store($flag_full,$tmp);
    else return self::fetch($flag_full); //last chance
    return substr($flag,-4)=="_xml"?simplexml_load_string($tmp):$tmp;
  }
}

