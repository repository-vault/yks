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


  function data_dump(){
    return $this->data;
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
    return array($this->hash_key => $this);
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

    $args = func_get_args();
    return self::repack_results_as_objects($class, $sql_key, array_slice($args, 4));
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

  function sql_dump($rmap = array()){
    return mykses::dump_key($this->sql_key, $this->hash_key, $rmap);
  }

    //please extend and dont use directly (only for generic /Admin/Users/Restore interface)
  static function raw_restore($zks_deletion_id) {

    $verif_deletion = compact('zks_deletion_id');
    $deletion = sql::row("zks_deletion_history", $verif_deletion);
    $restore_blob = json_decode($deletion['deletion_blob'], true);
    $context_blob = json_decode($deletion['context_blob'], true);

    $token = sql::begin();

    foreach($restore_blob as $table_name => $restore_data)
        foreach($restore_data as $line)
            sql::insert($table_name, $line);

    foreach($context_blob as $table_name => $restore_data)
        foreach($restore_data as $line)
            sql::update($table_name, $line['data'], $line['where']);


    sql::delete("zks_deletion_history", $verif_deletion);

    sql::commit($token);
  }
  
  static protected function restore($sql_key, $object_id){
      //look up deletion id
    $verif_deletion = array(
        'mykse_type'  => $sql_key,
        'mykse_value' => (string) $object_id,
    );
    $deletion_id = sql::value("zks_deletion_history", $verif_deletion, "zks_deletion_id");
    if(!$deletion_id )
        throw new Exception("Could not resolve deletion for object $object_id of type {$this->sql_key}");

    self::raw_restore($deletion_id);
  }

  function delete($reason, $rmap = array()){
    if(!$reason)
      throw rbx::error("Please specify motivation");

    $drop_all = mykses::find_key($this->sql_key, $this->hash_key, $rmap);

    $token = sql::begin();
    $dump  = $this->sql_dump($rmap);

    $data = array(
      "deletion_reason" => $reason,
      "mykse_type"      => $this->sql_key,
      "deletion_blob"   => json_encode($dump['delete']),
      "context_blob"    => json_encode($dump['update']),
      "mykse_value"     => $this->hash_key,
      "user_id"         => sess::$sess->user_id,
    ); 
    $deletion_id = sql::insert("zks_deletion_history", $data, true);


    //order dont matter, deferred powa

    if(false) {    //check for trigger (they are source of trouble)
        $all_tables = array();
        foreach($drop_all as $drop_infos) {
          $table_infos = sql::resolve($drop_infos[0]);
          $all_tables[] = $table_infos['schema'].$table_infos['name'];
        }
        $verif_triggers = array(
          'trigger_enabled'    => true,
          'event_manipulation' => array('DELETE', 'INSERT'),
          'concat(event_object_schema,event_object_table)' => $all_tables,
        );
        sql::select("zks_information_schema_ttriggers", $verif_triggers);
        $all_triggers = sql::brute_fetch();
        if($all_triggers)
          throw new Exception("All trigger must be turned off");
    }

    foreach($drop_all as $drop_info) {
      if($drop_info[3] != 'set_null')
          sql::delete($drop_info[0], array($drop_info[1] => $drop_info[2]));
    }

    sql::commit($token);

  }

  /**
  * Return the diff between current and new. Data from current.
  * 
  * @param mixed $data
  * @param mixed $revert
  */
  public function data_diff($data, $revert=false) {
    if(!$revert) // Data qui vont être changées dans le current
      return array_diff_assoc($this->data, $data);
    else        // Data qu'on veut inserer et qui sont différentes
      return array_diff_assoc($data, $this->data);
  }

  function sql_delete(){ return sql::delete($this->sql_table, $this); }

  function _set($key, $value){
        //fonction temporaire, à refactoriser par update une fois _user correctement integré
    $this->data[$key] = $value;
    return $this;
  }

  function offsetExists ($key){ return isset($this->data[$key])||isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset, $value){$this->$offset = $value;}
  function offsetUnset($key){unset($this->data[$key]); }

}
 