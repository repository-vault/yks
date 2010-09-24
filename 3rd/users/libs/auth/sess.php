<?php

class sess  {
  static $sess = array();
  static $id = false;

  static $connected = false;
  static $renewed = false;

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    if(sess::$id) return false;
    session_name(SESSION_NAME);
    session_set_cookie_params (0, "/", SESS_DOMAIN, false, true);
    self::$id = $_COOKIE[SESSION_NAME];
  }

    //need 5.3 late static binding self::_class
  static protected $_storage = array();
  static function store($key, $value){
    $key = SITE_CODE.$key;
    return self::$_storage[$key] = $value;
  }

  static function fetch($key){
    $key = SITE_CODE.$key;
    return self::$_storage[$key];
  }

    //deprecated
  static function retrieve($key){ return self::fetch($key); }

  static function flag_ks($connected = true){
    $id = $connected?self::$id:$_COOKIE[SESSION_NAME];
    return crpt($id, FLAG_SESS, 10);
  }

  static function connect(){
    session_start();
    self::$sess = &$_SESSION['user'];
    self::$id = session_id();
    self::$_storage = &$_SESSION['storage'];
    self::status_check();
  }

  static function deco(){
    $_COOKIE['user_id'] = false;
    setcookie("user_pswd_".sess::$sess['user_id'],false);
    self::renew(); 
    rbx::ok("&auth_deco;");
  }

  static function renew(){
    setcookie('user_id', false, 0, "/", SESS_DOMAIN);
    sess::$sess     = array();
    sess::$_storage = array();
    self::$renewed  = true;
    $sess_infos     = auth::valid_tree(exyks::retrieve('USERS_ROOT'));
    if($sess_infos) sess::$sess = $sess_infos;
  }

  static function close(){ return session_write_close(); }

  static function status_check(){
    self::$connected=( self::$sess['user_id']
                      && self::$sess['user_id'] != exyks::retrieve('USERS_ROOT') );
  }

  static function update($user_id, $skip_auth = false){
    $sess_infos = auth::valid_tree($user_id, $skip_auth);
    if(!$sess_infos) return false;
    
    self::load($sess_infos['user_id'], $sess_infos['users_tree']);
    return true;
  }

  static function load($user_id, $users_tree){ //private ? - no, force tree
    sess::$sess     = new user($user_id, $users_tree);
    sess::$sess->sql_update(array('user_connect'=>_NOW), "ks_users_profile");
    sess::$_storage = array();
    self::status_check();
  }

  static function reload(){
    self::load(sess::$sess['user_id'], sess::$sess['users_tree']);
  }

}
