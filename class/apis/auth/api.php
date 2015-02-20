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

    sess::connect();
    if(!isset(sess::$sess['user_id']))
        sess::renew(); //sess::$id is now set

    auth::login($user_login, $user_pswd);

    return self::_getUser();
  }

  public static function getUser(){
    sess::connect();
     return self::_getUser();
  }

  private static function _getUser(){
    $data = sess::$sess->computed;
    $data['users_tree'] =  sess::$sess['users_tree'];

    return self::output($data);
  }

    /**
   * @param int $user_id
   * @return string
   **/
  public static function loginFromId($user_id){
    //DebugBreak("1@172.51.1.88");
    $trusted_ips = explode(";", yks::$get->config->apis->authapi['trusted_ip']);
    $is_trusted = in_array(exyks::$CLIENT_ADDR , $trusted_ips);
    if(!$is_trusted)
      throw new Exception("Only from trusted ip");


    sess::connect();
    if(!isset(sess::$sess['user_id']))
        sess::renew(); //sess::$id is now set

    $auth_success = sess::update($user_id, true); //skip authentification
    if(!$auth_success)
        throw new Exception("Invalid user ( $user_id) ");

    $data = sess::$sess->computed;
    $data['users_tree'] =  sess::$sess['users_tree'];

    return self::output($data);
  }



    /**
   * @param string $user_mail
   * @return string
   **/
  public static function loginFromMail($user_mail){
    //DebugBreak("1@172.51.1.88");
    $trusted_ips = explode(";", yks::$get->config->apis->authapi['trusted_ip']);
    $is_trusted = in_array(exyks::$CLIENT_ADDR , $trusted_ips);
    if(!$is_trusted)
      throw new SoapFault("oo", "Only from trusted ip ".(exyks::$CLIENT_ADDR));


    sess::connect();
    $user_id = sql::value("ks_users_profile", compact('user_mail'), "user_id");

    if(!isset(sess::$sess['user_id']))
        sess::renew(); //sess::$id is now set

    $auth_success = sess::update($user_id, true); //skip authentification
    if(!$auth_success)
        throw new Exception("Invalid user ( $user_id) ");

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
