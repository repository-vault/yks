<?php

  class WSAuthBasic {

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
    * Login with an ID. Restricted by IP for internals exchanges
    *
    * @param int $user_id
    * @return string
    */
    public static function loginFromId($user_id){
      $args = func_get_args();
      return call_user_func_array(array(self::TRUE_CLASS, __FUNCTION__), $args);
    }

    /**
    * Connect a user using a google token
    *
    * @param string $token google token for email https://www.googleapis.com/auth/userinfo.email
    *
    * @return string session_id
    */
    public static function loginGoogleToken($token){
        $infos = file_get_contents("https://www.googleapis.com/oauth2/v3/userinfo?access_token=".$token);
        $infos = json_decode($infos, true);
        if($infos['error']){
          Throw new SoapFault('TokenError', 'Invalid google token');
        }

        $domain_list = yks::$get->config->apis->authapi->googleauth->domains->children();
        $domain_error = yks::$get->config->apis->authapi->googleauth->domains['error'];

        $avalaible_domain = array();
        foreach($domain_list as $domain){
          $avalaible_domain[] = (String)$domain;
        }

        list($user , $domain) = explode('@', $infos['email'], 2);

        if(!in_array($domain, $avalaible_domain))
          Throw new SoapFault('MailError', $infos['email'].' '.$domain_error);

        //IVS mail OK && active
        $user_id = sql::value('ks_users_profile', array('user_mail' => $infos['email']), 'user_id');

        sess::connect();
        if(!isset(sess::$sess['user_id']))
          sess::renew(); //sess::$id is now set

        $auth_success = sess::update($user_id, true); //skip authentification

        if(!$auth_success)
         throw new SoapFault("BadUser", $infos['email']." is not valid user");


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
