<?

class sess  {
  static $sess = array();
  static $id = false;

  static $connected = false;
  static $renewed = false;

    //need 5.3 late static binding self::_class
  static protected $_storage = array();
  static function store($key, $value){ return self::$_storage[$key]=$value; }
  static function retrieve($key){ return self::$_storage[$key]; }

  static function flag_ks($connected = true){
    $id = $connected?self::$id:$_COOKIE[SESSION_NAME];
    define('FLAG_KS', crpt($id, FLAG_SESS, 10));
    return FLAG_KS;
  }
  static function init(){
    if(sess::$id) return false;
    session_name(SESSION_NAME);
    session_set_cookie_params (0, "/", SITE_DOMAIN, false, true);
    session_start();
    self::$sess = &$_SESSION['user'];
    self::$id = session_id();
    self::$_storage = &$_SESSION['storage'];
    self::status_check();
    self::flag_ks();
  }
  static function deco(){
    $_COOKIE['user_id'] = false;
    setcookie("user_pswd_".sess::$sess['user_id'],false);
    self::renew(); 
    rbx::ok("&auth_deco;");
  }

  static function renew(){
    setcookie('user_id', false);
    sess::$sess=array('users_tree'=>array(), 'session_id'=>self::$id);
    sess::$_storage=array();
    self::$renewed=true;
    auth::update(USERS_ROOT);
  }
  static function close(){ return session_write_close(); }

  static function status_check(){
    self::$connected=(self::$sess['user_id'] && self::$sess['user_id']!=USERS_ROOT);
  }

  static function update($user_id, $force=false){
    if(!auth::update($user_id, $force)) return false;
    sess::load();
    return true;
  }

  static function load(){
    sess::$sess = new user(sess::$sess['user_id'], sess::$sess['users_tree']);
    self::status_check();
  }
} 
