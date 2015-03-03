<?php

namespace yks;

use sql;

/**
 * Generic CRUD base methods for sql models.
 *
 * To use this trait you need to do three things in your class:
 *   1. Define the const ::sql_table with the myks table name.
 *   2. Define the const ::sql_key  with the primary key.
 */
trait Model {

  private $data  = array();

  protected function __construct($from) {

    if(!is_array($from))
      throw new Exception("Invalid construction");
    $this->feed($from);
  }


  function feed($data) {
    $this->data = $data;
  }


  function __get($key) {
        //const proxy
    if($key == "sql_key")
        return self::sql_key;
    if($key == "sql_table")
        return self::sql_table;

    if(isset($this->data[$key]))
        return $this->data[$key];
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();
    if(method_exists($this, $getter = "load_$key")) {
        call_user_func(array(get_class($this), $getter), $this->batch());
        return $this->$key;
    }
  }

  function get_hash_key(){
    $key = self::sql_key;
    return $this->$key;
  }

  function __sql_where(){
    return array($this->sql_key => $this->hash_key );
  }

  public function batch(){
    return array($this->hash_key => $this);
  }


  public static function from_where($where, $extras = null) {
    sql::select(self::sql_table, $where, '*', $extras);

    return array_map(
        function($v) {
            return new self($v);
        },
        sql::brute_fetch(self::sql_key)
    );
  }

  public static function from_ids($ids) {
    $results = self::from_where(array(self::sql_key =>$ids));
    return array_sort($results, $ids);
  }

  public static function instanciate($index) {
    $ret = self::from_ids([self::sql_key => $index]);
    if (count($ret) !== 1)
        throw new \RuntimeException("Could not find index `$index`.");
    return first($ret);
  }


  function sql_update($data, $table = false){
    $res = sql::update($table ? $table : $this->sql_table, $data, $this);
    if(!$res) return false;
    if($res)
        foreach($data as $k=>$v) $this->data[$k] = $v; //array_walk wrong arg order :/
    return true;
  }

  function sql_delete(){ return sql::delete($this->sql_table, $this); }

  function offsetExists ($key){
     $exists = isset($this->data[$key])
        || isset($this->$key)
        || method_exists($this, $getter = "get_$key")
        || method_exists($this, $getter = "load_$key");
     return $exists;
  }

  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset, $value){$this->$offset = $value;}
  function offsetUnset($key){unset($this->data[$key]); }


}

