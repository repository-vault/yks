<?

class sess {
  static $sess = array();
  static $id = false;
  static $_storage = array(); //private
  static $connected = false;
  static $renewed = false;

  static function init(){
    if(sess::$id) return false;
    session_name(SESSION_NAME);
    session_set_cookie_params (0, "/", SITE_DOMAIN, false, true);
    session_start();
    self::$sess = &$_SESSION['user'];
    self::$id = session_id();
    self::$_storage = &$_SESSION['storage'];
    self::status_check();
    define('FLAG_KS', crpt(self::$id, FLAG_SESS, 10));
  }
  static function deco(){
    $_COOKIE['user_id']=false;
    setcookie("user_pswd_".sess::$sess['user_id'],false);
    self::renew(); 
    rbx::ok("&auth_deco;");
  }

  static function renew(){
    setcookie('user_id',false);
    sess::$sess=array('users_tree'=>array(), 'session_id'=>self::$id);
    sess::$_storage=array();
    self::$renewed=true;
    auth::update(USERS_ROOT, false, true);
  }
  static function close(){ return session_write_close(); }

  static function status_check(){
    self::$connected=(self::$sess['user_id'] && self::$sess['user_id']!=USERS_ROOT);
  }
  static function store($key, $value){
    self::$_storage[$key]=$value;
  }
  static function retrieve($key){
    return self::$_storage[$key];
  }

  static function get_computed($cols='*'){
    $computed = array();
    $lines = users::get_infos(self::$sess['users_tree'],$cols);
    foreach($lines as $line) $computed=array_merge($computed,array_filter($line,'is_not_null'));
    return $computed;
  }

  static function load(){
    $user_id=(int)sess::$sess['user_id'];
    sess::$sess=array_merge(
        sess::$sess,
        array(
          'user_addr'=>users::get_addr($user_id),
          'user_acces'=>auth::renew(sess::$sess['users_tree']),
        ),
        sql::row(array('ks_users_list','user_id'=>'ks_users_tree'),compact('user_id')),
        users::get_infos_unique($user_id),
        array('computed'=>self::get_computed())
    );
    self::status_check();
  }
} sess::init();
