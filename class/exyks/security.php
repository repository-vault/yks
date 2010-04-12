<?php


class exyks_security {
  private static $flag_ks;

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    self::$flag_ks = exyks_session::flag_ks();
    jsx::set('ks_flag', self::$flag_ks);
  }

  static function sanitize(){
    global $action;

      /* Basic input escape & security check */
    if($_POST) $_POST = specialchars_deep($_POST);
    if($action){
      if($_POST['ks_flag'] != self::$flag_ks){
          $action="";
          $msg = exyks_session::$renewed
                   ? "Your session has expired, reload the page you are on, and try again"
                   : "Invalid security flag, try refreshing this page (F5)";
          rbx::error($msg);
      } if(JSX) jsx::$rbx=true;
      unset($_POST['ks_flag']);
    }

  }
}


