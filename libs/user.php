<?php

class user extends _user {
  const sql_table = 'ks_users_list';
  const sql_key = 'user_id';

  protected $sql_table = "ks_users_list";
  protected $sql_key = "user_id";
  static private $tables_registration = array();


  static function init(){
    user::register("auth_password", "ks_auth_password");
  }

  function __construct($user_id, $auth_tree){
    $this->users_tree = $auth_tree;
    parent::__construct($user_id);
    $this->sql_update(array('user_connect'=>_NOW), "ks_users_profile");

    $this->user_access = auth::get_access($this->users_tree);
    $this->user_flags  = explode(',',$this->user_flags);
  }

  function register($key, $table_name){
    if(!($table_xml = yks::$get->tables_xml->$table_name)) return false;
    $table_keys = fields($table_xml,'primary'); 
    $row_unique = (count($table_keys)==1 && current($table_keys)=='user_id');
    unset($table_keys['user_id']);
    $table_key = count($table_keys)==1?reset($table_keys):false;
         //on indexe les resultat sur la deuxieme clée(si unique, hors user_id de join)
    self::$tables_registration[$key] = compact('table_name', 'table_key', 'row_unique');
  }

  function has_flag($flag){
    return in_array($flag, $this->user_flags);
  }

  function upgrade_rights($rights) {
    foreach($rights as $zone_name=>$zone)
      foreach($zone as $access=>$val)
        $this->user_access[$zone_name][$access] = true;
  }


  private function get_extended_infos($key){
    if((!$tmp = self::$tables_registration[$key]) || !extract($tmp)) return false;
    sql::select($table_name, $this);
    return $row_unique?sql::fetch():sql::brute_fetch($table_key, $table_key);
  }

  function __get($key){
    if(isset(self::$tables_registration[$key])){
        return $this->get_extended_infos($key);
    }

    return parent::__get($key);
  }

}
