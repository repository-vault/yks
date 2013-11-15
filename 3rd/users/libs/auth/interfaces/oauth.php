<?php

/*  "Yks auth_oauth" by Leurent F.
    distributed under the terms of GNU General Public License - © 2013
    this class has a double role : 
    - it provide oauth login/pswd authentification
    - it loads a session from saved cookies
*/

class auth_oauth {
  const sql_table = "ks_auth_oauth";

  public static $endpoints_list = array();
  private static $pubkey;

  static function __construct_static(){
    $endpoints_list = array();
    $users = yks::$get->config->users;
    foreach($users->iterate("auth_oauth") as $endpoint) {
      $driver = $endpoint['driver'];
      if($driver != "facebook") continue;
      $fbauth = new oAuth_Facebook($endpoint['client_id'], $endpoint['client_secret']);
      $endpoints_list[(string) $endpoint['endpoint_name']] = $fbauth;
    }
    self::$endpoints_list = $endpoints_list;
    
  }
  
  static function reload($user_id_forced = false, $user_pswd = false,  $continue = true, $skip_auth = false){
    
    $user_id = &$_COOKIE['user_id'];
    $user_id = (int)$user_id; //safe cookie
    if($user_id_forced)
        $user_id = $user_id_forced;

    $cookie_pswd = self::forge_cookie($user_id);
    $_COOKIE[$cookie_pswd] = $user_pswd;
    
    if( !($user_id && sess::update($user_id, $skip_auth)) ){
        setcookie('user_id', false);
        setcookie($cookie_pswd, false);
        die("Auth failed");
    }
    $COOKIE_EXPIRE = _NOW+86400*10;
    setcookie('user_id', $user_id, $COOKIE_EXPIRE,'/', SESS_DOMAIN);
    setcookie($cookie_pswd, $_COOKIE[$cookie_pswd], $COOKIE_EXPIRE, '/', SESS_DOMAIN);

    $js_continue = $continue ? "window.opener.window['{$continue}']();" : "";
    die("<script>$js_continue;window.close();</script>Now, we close");
  }

  private static function forge_cookie($user_id){
    return crpt("user_oauth_$user_id", FLAG_SESS, 10);
  }

  static function verif($user_id){

    $auth_oauth = sql::row("ks_auth_oauth", compact('user_id'));

    $endpoint   = self::$endpoints_list[$auth_oauth['auth_oauth_endpoint_name']];
    if(!$endpoint) 
      return false;

    $access_token = $auth_oauth['oauth_token'];    
    $cookie_pswd = self::forge_cookie($user_id);
    $user_pswd   = $endpoint->sign($auth_oauth['auth_oauth_user_id']);

    if($_COOKIE[$cookie_pswd] != $user_pswd )
      return false;
    
    try {
        $check = $endpoint->token_check($access_token);
        $me = $endpoint->call("/me", array(
          'access_token' => $access_token,
          'fields'       => 'id,name,picture,email,first_name,last_name', //,permissions,friends
        ));

        $success = $me['id'] == $auth_oauth['auth_oauth_user_id'];
        return $success;
    } catch(Exception $e){ }

    return false;
  }
}

