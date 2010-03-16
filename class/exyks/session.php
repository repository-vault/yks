<?php

/*  "Yks session manager" by Leurent F. - 131 (131.php@cloudyks.org)
    distributed under the terms of GNU General Public License - © 2008
*/

class exyks_session {

  public static $renewed = false;

  private static $sess_loaded = false;

  public static function init(){
    self::$sess_loaded = class_exists('sess');
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


  public static function load(){
    if(self::$sess_loaded)
        return self::load_classic();

    session_start();
  }

    //  Load basic session, if existing, load root user else
  private static function load_classic(){
    global $action;

    if(!isset(sess::$sess['user_id']))
        sess::renew(); //sess::$id is now set

    if($action=="deco")
        sess::deco();

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


    if(bool((string)yks::$get->config->site['closed'])){
        if(!auth::verif("admin","admin")) yks::fatality(yks::FATALITY_SITE_CLOSED);
        else tpls::css_add("/css/".SITE_BASE."/off.css"); //not mandatory.., but could help
    }

  }

}