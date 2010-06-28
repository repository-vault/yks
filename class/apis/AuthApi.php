<?

/*
    Server Side
*/

class exyks_auth_api {
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

    $data = sess::$sess->computed;
    $data['users_tree'] =  sess::$sess['users_tree'];

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




  public function getAccesses(){
    sess::connect();
    $data = sess::$sess->user_access;

    return self::output($data);
  }

  public function update($field_name, $field_value){
    sess::connect();
    $data = array($field_name => $field_value);
    return sess::$sess->update($data);
  }

    //les données ne peuvent pas être de type complexe en retour d'un Webservice, on serialize
  private function output($data){ return serialize($data); }


  private function isValidApiKey($api_key){
    error_log($api_key);
  }



}
