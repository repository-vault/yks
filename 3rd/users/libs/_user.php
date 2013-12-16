<?php

abstract class _user extends _sql_base {
  const sql_table = 'ks_users_list';
  const sql_key = 'user_id';

  protected $sql_table = self::sql_table;
  protected $sql_key = self::sql_key;
  private $storage = array();
  public $computed = array();

  static function from_where($class, $where){
    return parent::from_where($class, self::sql_table, self::sql_key, $where);
  }

  //attention, a n'utiliser que si on est certain que la liste contient un arbre complet
  //todo : check
  static function from_ids($ids, $class = 'user'){
    $users = array();
    foreach($ids as $user_id) {
        $user = self::instanciate($user_id, $class);
        $users[$user_id] = $user;
    }
    return $users;
  }
  
  function get_addr($verif_addr = array("addr_type != ''")){
    $verif_addr[] = $this;
    return sql::row('ks_users_addrs', $verif_addr);
  }
  
  private static function feed_tree($tree, &$users_infos, $parent_infos = array()){
      $user_id    = $tree['user_id'];
      $user_infos = $users_infos[$user_id];
      //$users_infos[$user_id] = $parent_infos - 

    // Ensure that indices are defined.
    $parent_infos = array_merge(array(
    'users_types'   => null,
    'users_tree'    => null,
    'user_id'       => null,
    'computed'      => null,
    ), $parent_infos);

      $user_infos['users_types'] = pick($parent_infos['users_types'], array());
      $user_infos['users_types'][$user_infos['user_type']] = $user_id;
      
      $user_infos['users_tree'] = pick($parent_infos['users_tree'], array()); 
      $user_infos['users_tree'][] = $user_id;
      $user_infos['parent_id'] = pick($parent_infos['user_id'], $user_id);

        //pas indispensable
      unset($parent_infos['computed']['users_tree']);
      unset($parent_infos['computed']['users_type']);
      
      $user_infos['computed'] = array_merge_deep(
              pick($parent_infos['computed'], array()),
              array_filter($user_infos, 'is_not_null'));

      $users_infos[$user_id] = $user_infos;
      if($tree['children']) 
      foreach($tree['children'] as $child)
        self::feed_tree($child, $users_infos,  $user_infos);
  }

  protected static function from_flat_tree($flat_tree, $class){
    $tree = array(); $here = &$tree;
    foreach($flat_tree as $node_id){
      $here[$node_id]['user_id'] = $node_id;
      $here[$node_id]['children'] = array();
      $here = &$here[$node_id]['children'];
    }; unset($here);

   return self::from_tree($tree, $class);
  }
  
  protected static function from_tree($tree, $class ){
    $users_id = array_keys(users::linearize_tree($tree));
    $users_infos = users::get_infos($users_id, '*');
      //feed $users_infos with computed, users_tree & users_types
    if(isset($tree))
      foreach($tree as $root_id=>$tree)
        self::feed_tree($tree, $users_infos);

    $users = array();
    foreach($users_infos as $user_id=>$user_infos){
          $data = array_sort($user_infos, array('user_id', 'auth_type', 'user_type', 'user_name', 'parent_id'));
          $user = new $class($data);
                //this is no good
          $user->users_tree  = $user_infos['users_tree'];
          $user->computed    = $user_infos['computed'];
          $user->users_types = $user_infos['users_types'];
          $users[$user_id] = $user;
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
  
  protected static function instanciate($user_id, $class ){
    $tree  = users::get_parents($user_id);
    $users = self::from_flat_tree($tree, $class);
    $user  = $users[$user_id];
    if(!$user->user_id || !$user->users_tree)
        throw new Exception("Unable to load user #{$user->user_id}");
    return $user; 
  }


  protected function get_user_access(){
    return $this->user_access = auth::get_access($this->users_tree);    
  }

  function verif($access_zone, $lvl = "access"){
    return auth::verif($access_zone, $lvl, false, $this);
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
        $key = first(array_keys(fields($table_xml), _user::sql_key));
    return array($key => $this->user_id);
  }

  function update($data_full) {
    $tables_list = array('ks_users_profile', sprintf('%s_profile', $this->user_type));
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


    //override sql_base
  function offsetExists ($key){ return isset($this->computed[$key]) || parent::offsetExists($key);}
}
