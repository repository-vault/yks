<?php

    //extending ArrayObject should have worked, but it break session storage
abstract class _sql_base  implements ArrayAccess {

   protected $sql_table=false;
   protected $sql_key=false;
   protected $data; //do NOT access this, (need php 5.3 setAccessible)
   protected $manager = false;

  protected function __construct($from){
    if(!($this->sql_table && $this->sql_key))
        throw new Exception("Invalid definition for _sql_base");

    if(is_array($from))
        $this->feed($from);
    elseif(is_string($from))
        $this->from_id($from);
    else $this->from_id((int)$from);
  }

    //since reflection is not complete, it will not store private members
  protected function sleep($excluded = array()){
    $ref   = new ReflectionObject($this);
    $props = $ref->getProperties(); $all=array();
    foreach($props as $k) $all[] = $k->name;
    return array_diff($all, $excluded);
  }

  function feed($data){
    $this->data = $data;
  }

  function __get($key){
    if(isset($this->data[$key]))
        return $this->data[$key];
    if(method_exists($this, $getter = "get_$key")
        || $this->manager && method_exists($this->manager, $getter))
        return $this->$getter();
    if(method_exists($this, $getter = "load_$key")
        || $this->manager && method_exists($this->manager, $getter)) {
        call_user_func(array(get_class($this), $getter), $this->batch());
        return $this->$key;
    }
  }

  function get_hash_key(){
    $key = $this->sql_key;
    return $this->$key;
  }

  function __sql_where(){
    return array($this->sql_key => $this->hash_key );
  }

  public function batch(){
    $key = $this->sql_key;
    return array($this->$key => $this);
  }

  function from_id($key_id){
    $verif_base = array($this->sql_key => $key_id);
    $data = sql::row($this->sql_table, $verif_base);
    if(!$data) throw new Exception("Invalid construction " . $key_id);
    $this->feed($data);
  }

  // Proxies to manager's static functions
  function __call($method, $args){
    if(!($this->manager && method_exists($this->manager, $method))) return;
    array_unshift($args, $this);
    return call_user_func_array(array($this->manager, $method), $args);
  }

  static function from_where($class, $sql_table, $sql_key, $where) {//, optionals
    sql::select($sql_table, $where);

    return self::repack_results_as_objects($class, $sql_key, array_slice(func_get_args(), 4));
  }

  static function from_where_locked($class, $sql_table, $sql_key, $where) {//, optionals
    $token = sql::begin();

    try {
      sql::select($sql_table, $where, '*', 'for update');

      $args = func_get_args();
      $result = _sql_base::repack_results_as_objects($class, $sql_key, array_slice($args, 4));
    } catch(Exception $e) {
      sql::rollback($token);
      throw $e;
    }

    sql::commit($token);

    return $result;
  }

  static function repack_results_as_objects($class, $sql_key, $args) {
    $tmp = array();
    if(!$args) {
        foreach(sql::brute_fetch($sql_key) as $key_id => $key_infos) {
          $tmp[$key_id] = new $class($key_infos);
        }
    } else {
        $class = new ReflectionClass($class);
        foreach(sql::brute_fetch($sql_key) as $key_id => $key_infos) {
            $args_tmp = array($key_infos);
            foreach($args as $arg) {
              $args_tmp[] = $arg==PH?false:$arg[$key_id];
            }

            $tmp[$key_id] = $class->newInstanceArgs($args_tmp);
        }
    }

    return $tmp;
  }

  static function extract_where($array){
    if(!$array) return array();
    return array(($key = current($array)->sql_key) => array_values(array_extract($array, $key)));
  }


  static function from_ids($class, $sql_table, $sql_key, $ids) {
    $results = self::from_where($class,  $sql_table, $sql_key, array($sql_key=>$ids));
    return array_sort($results, $ids);
  }

  function sql_update($data, $table = false){
    $res = sql::update($table?$table:$this->sql_table, $data, $this);
    if(!$res) return false;
    if($res) foreach($data as $k=>$v) $this->_set($k, $v); //array_walk wrong arg order :/
    return true;
  }


  function sql_delete(){ return sql::delete($this->sql_table, $this); }

  function _set($key, $value){
        //fonction temporaire, à refactoriser par update une fois _user correctement integré
    $this->data[$key] = $value;
    return $this;
  }

  function offsetExists ($key){ return isset($this->data[$key])||isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset,$value){}
  function offsetUnset($key){unset($this->data[$key]); }

}
