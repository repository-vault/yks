<?php

/*  "Yks session manager" by Leurent F. - 131 (131.php@cloudyks.org)
    distributed under the terms of GNU General Public License - © 2008
*/

class exyks_session {

  public static $renewed = false;

  private static $sess_loaded = false;

  public static function init(){
        // load & start session (if available)
        // session is ready to start
    self::$sess_loaded = class_exists('sess');
  }


  static protected $_storage = array();
  static function store($key, $value){
    $key = SITE_CODE.$key;
    return self::$_storage[$key] = $value;
  }

  static function fetch($key){
    $key = SITE_CODE.$key;
    return self::$_storage[$key];
  }


  public static function close(){
    if(self::$sess_loaded)
        return sess::close();

    session_write_close();
  }

  public static function flag_ks(){
    if(self::$sess_loaded) 
        return sess::flag_ks();
    return "ks_flag";
  }


  public static function connect(){
    if(self::$sess_loaded)
        sess::connect();
    else session_start();

    $user_tz = $_SESSION['client']['tz'];
    self::$_storage = &$_SESSION[__CLASS__.'_storage'];

    if(is_null($user_tz)) {
        $_SESSION['client']['tz']  = IDATEZ;
    } elseif($_SERVER['HTTP_YKS_CLIENT_TZ']) {
        $_SESSION['client']['tz']  = (int)$_SERVER['HTTP_YKS_CLIENT_TZ'];
    }

    $user_tz  = $_SESSION['client']['tz'];
    exyks::store("USER_TZ", $user_tz);

  }

  public static function load(){
    if(self::$sess_loaded)
        return self::load_classic();
  }

    //  Load basic session, if existing, load root user else
  private static function load_classic(){
    global $action;

    sess::connect();

    if(!isset(sess::$sess['user_id']))
        sess::renew(); //sess::$id is now set

    if($action == "logout" || $action=="deco") //yeah
        sess::logout();

    if(sess::$sess['session_ip']!=$_SERVER['REMOTE_ADDR']) auth_restricted_ip::reload();
    if($_COOKIE['user_id'] && ($_COOKIE['user_id']!=sess::$sess['user_id'])) auth_password::reload();

    if($action=='login') try {
        if(!auth_password::reload($_POST['user_login'], $_POST['user_pswd'])) throw new Exception();
        rbx::ok("&auth_success;");
    } catch(Exception $e){ rbx::error("&auth_failed;"); }

    if(!isset(sess::$sess['user_access'])) try {
        sess::reload();
    } catch(Exception $e){
        error_log("Unable to start user session. ".$e->getMessage());
        rbx::error("Unable to start user session.");
    }

  }

}