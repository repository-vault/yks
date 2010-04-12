<?

/*
    Report de bugs 
*/

class auth_api_webservice {
  /**
   * @param string $user_login
   * @param string $user_pswd
   * @return string
   **/
  public static function login($user_login, $user_pswd){
    $user_login = strtolower($user_login);

    sess::connect();
    if(!isset(sess::$sess['user_id']))
        sess::renew(); //sess::$id is now set


    $auth_success = auth_password::reload($user_login, $user_pswd, false); //no redirect
    if(!$auth_success)
        return "Invalid login/password ( $user_login, $user_pswd) ";

    $data = array(
        'user_name'    => sess::$sess['user_name'],
        'user_mail'    => sess::$sess['user_mail'],
        'users_tree'   => sess::$sess['users_tree'],
        'user_id'      => sess::$sess['user_id'],
    );
    return self::output($data);
  }

  /**
   * @param string $access_zone
   * @param string $access_lvl
   * @return boolean
   **/
  public function verifAuth($access_zone, $access_lvl){
    sess::connect();

    return auth::verif($access_zone, $access_lvl);
  }


    //les données ne peuvent pas être de type complexe en retour d'un Webservice, on serialize
  private function output($data){ return serialize($data); }


  private function isValidApiKey($api_key){
    error_log($api_key);
  }



}
