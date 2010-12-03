<?php

class _user extends _sql_base {
  const sql_table = 'ks_users_list';
  const sql_key = 'user_id';

  protected $sql_table = self::sql_table;
  protected $sql_key = self::sql_key;
  private $storage = array();


  static function from_where($class, $where){
    return parent::from_where($class, self::sql_table, self::sql_key, $where);
  }

  //attention, a n'utiliser que si on est certain que la liste contient un arbre complet
  //todo : check
  static function from_ids($ids, $class ){
    throw new Exception("Not yet");
  }
  
  
  static function from_tree($tree, $class ){
    
    print_r($tree);
    //$users_id = array_keys(users::linearize_tree($tree)));
print_r($users_id);die('o');
    parent::from_ids($class, self::sql_table, self::sql_key, $users_id);
    
    $this->computed = array(); $this->users_types = array();
    //$ids
    foreach(users::get_infos($this->users_tree,"*") as $line){
        $this->users_types[$line['user_type']] = $line['user_id'];
        $this->computed = array_merge($this->computed, array_filter($line,'is_not_null'));
    } 
    
    foreach($users as $user){
          $user->users_tree  = $users_infos[$user_id]['users_tree'];
          $user->computed    = $users_infos[$user_id]['computed'];
          $user->users_types = $users_infos[$user_id]['users_types'];

          $data = array_sort($user, array('auth_type', 'user_type', 'user_name'));
          $data["parent_id"] = $user->users_tree[max(count($user->users_tree)-2,0)];
          $user->data = $data; 
    }
    
    return $users;
  }

  function __toString(){ return $this->user_name; }

  function get_users_tree(){
    return $this->users_tree = users::get_parents($this->user_id);
  }


  function get_children_list(){
    return $this->children_list = users::get_children($this->user_id);
  }
  
  static function instanciate($user_id, $class = __CLASS__){
    $tree= users::get_parents($user_id);
    $users = self::from_tree($tree, $class);
    $user = $users[$user_id];
    if(!$user->user_id || !$user->users_tree)
        throw new Exception("Unable to load user #{$user->user_id}");

    return $user; 
  }

  protected function __construct($from){
    parent::__construct($from);
    $user_id = (int)$from[self::sql_key];
    $type_id = preg_reduce('#^[a-z]{2,3}_(.*?)s?$#', $this->user_type).'_id';
    if($this->user_type!="ks_users") $this->$type_id = $user_id;
    $this->sql_key = $type_id;
  }

  function _set($key, $value){
    if(isset($this->computed[$key])){
        $this->computed[$key] = $value;
        return $this;
    } return parent::_set($key, $value);
  }

  function store($key, $value){ $this->storage[$key] = $value; return $this; }
  function delete($key){ unset($this->storage[$key]); return $this; }
  function &retrieve($key){ return $this->storage[$key]; }

  function __sql_where($sql_table = false){
    $key = $this->sql_key;
    if($sql_table && $table_xml  = yks::$get->tables_xml->$sql_table)
        $key = reset(array_keys(fields($table_xml), _user::sql_key));
    return array($key => $this->user_id);
  }

  function update($data_full) {
    $tables_list = array('ks_users_profile'); //todo : see users::$users_linear_tables
    foreach($tables_list as $table_name){
        $data = mykses::validate_update($data_full, yks::$get->tables_xml->$table_name);
        if(!$data) continue;
        sql::update($table_name, $data, $this);

            //contains inherited values
        $this->computed = array_merge($this->computed,
            array_intersect_key($data, $this->computed));
            //contains non-inheritABLE values
        $this->data = array_merge($this->data,
            array_intersect_key($data, $this->data));

    }
  }

  function __get($key){
    $get = parent::__get($key);
    if(!is_null($get)) return $get;
    if(isset($this->computed[$key]))
        return $this->computed[$key];
    if(isset($this->storage[$key]))
        return $this->storage[$key];
  }
}
