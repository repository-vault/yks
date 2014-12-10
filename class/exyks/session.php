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

  static function store_results($key, $query){
    $session_ns = SITE_CODE.$key;
    $session_id = session_id();

    sql::delete("ks_sessions_results_heap", compact('session_ns', 'session_id'));

    $query = "INSERT INTO `ks_sessions_results_heap`
      (session_id, session_ns, session_heap_value)
      SELECT
      '$session_id' AS session_id,
      '$session_ns'        AS session_ns,
      id AS session_heap_value
      FROM ($query) AS src";
    $results = sql::query($query);
    $results_nb = sql::arows($results);
    self::store("results_{$key}", $results_nb);
    return $results_nb;
  }

  static function fetch_results($key, $table, $criteria, $cols = "*", $extras = '', $by = 0 ){
    if($by) $extras = "LIMIT $by OFFSET ".($start = (int) $extras);
    $verif_join = array(
      'session_ns' => SITE_CODE.$key,
      'session_id' => session_id(),
      "session_heap_value = $criteria",
    );
    $query = " SELECT $cols FROM $table
      INNER JOIN `ks_sessions_results_heap`
      ".sql::on($verif_join)."
      ORDER BY session_heap_key ASC
      $extras";

    sql::query($query);
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
    if(self::$sess_loaded){
      if($_POST['ks_action'] == 'login' && bool(yks::$get->config->apis->cors['enabled']))
        sess::cors_connect();
      else sess::connect();
    }
    else session_start();

    if(!isset($_SESSION['load'])) {
      $data = array('session_id' => session_id());
      //sql::insert("zks_sessions_list", $data);
      $_SESSION['load'] = true;
    }

    $user_tz = $_SESSION['client']['tz'];
    self::$_storage = &$_SESSION[__CLASS__.'_storage'];

    if(is_null($user_tz)) {
      $_SESSION['client']['tz']  = IDATEZ;
    } elseif(array_get($_SERVER, 'HTTP_YKS_CLIENT_TZ')) {
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

    $parse_origin_url = parse_url($_SERVER['HTTP_ORIGIN']);

    if($parse_origin_url['host'] == SITE_DOMAIN){
      header('Access-Control-Allow-Origin: http://'.SITE_DOMAIN);
    }

    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
    {
      header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
      header('Access-Control-Allow-Headers: yks-client-tz,yks-jsx');
      header('Content-Type:text/plain');
      header('Content-Length: 0');
      die;
    }

    if(!isset(sess::$sess['user_id']))
      sess::renew(); //sess::$id is now set

    if($action == "logout" || $action=="deco") //yeah
      sess::logout();

    if(sess::$sess['session_ip'] != $_SERVER['REMOTE_ADDR'])
      auth_restricted_ip::reload();

    if($_COOKIE['user_id'] && ($_COOKIE['user_id']!=sess::$sess['user_id']))
      auth_password::reload();

    if($action=='login') try {
      auth::login($_POST['user_login'], $_POST['user_pswd']);
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
