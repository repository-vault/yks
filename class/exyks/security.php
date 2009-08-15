<?


class exyks_security {
  private static $flag_ks;

  static function init(){
    self::$flag_ks = sess::flag_ks();
    jsx::set('ks_flag', self::$flag_ks);
  }

  static function sanitize(){
    global $action;

      /* Basic input escape & security check */
    if($_POST) $_POST = specialchars_deep($_POST);
    if($action){
      if($_POST['ks_flag'] != self::$flag_ks){
          $action="";
          $msg = sess::$renewed ? "Your session has expired, reload the page you are on, and try again"
                : "Invalid security flag";
          rbx::error($msg);
      } if(JSX) jsx::$rbx=true;
      unset($_POST['ks_flag']);
    }

  }
}


