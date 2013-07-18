<?php

class WSAuthExternal {

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
      $domain_error = yks::$get->config->apis->authapi->googleauth->domains['error_msg'];

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

}
