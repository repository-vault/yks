<?php

class WSAuthApi {

  const TRUE_CLASS = 'exyks_auth_api';

  /**
   * Retourne la session id
   * @param string $user_login
   * @param string $user_pswd
   * @return string
   **/
  public static function login($user_login, $user_pswd){
    $args = func_get_args();
    call_user_func_array(array(self::TRUE_CLASS, __FUNCTION__), $args);
    return session_id();
  }

  /**
   * Retourne un object serialise definissant l'user
   * @param string $session_id
   * @return string
   **/
  public static function getUser($session_id){
    $args = func_get_args(); array_shift($args);
    return call_user_func_array(array(self::TRUE_CLASS, __FUNCTION__), $args);
  }

  /**
   * Valide un niveau d'access
   * @param string $session_id
   * @param string $access_zone
   * @param string $access_lvl
   * @return boolean
   **/
  public function verifAuth($session_id, $access_zone, $access_lvl){
    $args = func_get_args(); array_shift($args);
    return call_user_func_array(array(self::TRUE_CLASS, __FUNCTION__), $args);
  }

  /**
   * Valide un niveau d'access
   * @param string $session_id
   * @return string
   **/
  public function getAccesses($session_id){
    $args = func_get_args();
    return call_user_func_array(array(self::TRUE_CLASS, __FUNCTION__), $args);
  }


}