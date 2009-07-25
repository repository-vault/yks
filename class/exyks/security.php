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
          if(sess::$renewed){
              // jsx::export("ks_flag", self::$flag_ks); this will work, but i think it's a wrong path
              // instead, let them do this by their own, manually
              rbx::error("Your session has expired, reload the page you are on, and try again ");
          } else rbx::error("Invalid security flag");
      } if(JSX) jsx::$rbx=true;
      unset($_POST['ks_flag']);
    }

  }
}


