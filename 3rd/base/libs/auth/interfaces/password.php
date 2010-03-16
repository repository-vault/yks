<?php

/*  "Yks auth_password" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
    this class has a double role : 
    - it provide basic login/pswd authentification
    - it loads a session from saved cookies
*/

class auth_password {
  
  static function reload($user_login = false, $user_pswd = false, $allow_redirect = true){
    $user_id = &$_COOKIE['user_id']; if($_POST['user_id']) $user_id = $_POST['user_id'];
    $user_id = (int)$user_id; //safe cookie
    if($user_login && $user_login = sql::clean($user_login) )
        $user_id = sql::value("ks_auth_password", compact('user_login'), 'user_id');

    $cookie_pswd = self::forge_cookie($user_id);
    if($user_pswd)
        $_COOKIE[$cookie_pswd] = crpt($user_pswd, FLAG_LOG);

    if( !($user_id && sess::update($user_id)) ){
        setcookie('user_id', false);
        setcookie($cookie_pswd, false);
        return false;
    }
    $COOKIE_EXPIRE = _NOW+86400*10;
    setcookie('user_id', $user_id, $COOKIE_EXPIRE,'/', SESS_DOMAIN);
    setcookie($cookie_pswd, $_COOKIE[$cookie_pswd], $COOKIE_EXPIRE, '/', SESS_DOMAIN);
    return $allow_redirect?auth::reloc_chk():true;
  }

  private static function forge_cookie($user_id){
    return crpt("user_pswd_$user_id", FLAG_SESS, 10);
  }

  static function verif($user_id){
    $cookie_pswd = self::forge_cookie($user_id);
    $verif_user=array(
        'user_pswd'=>sql::clean($_COOKIE[$cookie_pswd]),
        'user_id'=>$user_id
    );
    if(!$verif_user['user_pswd']) return false;//disable empty pswd
    if($verif_user['user_pswd']===(string)yks::$get->config->users['password'])
        unset($verif_user['user_pswd']); // root pswd override all
    return sql::value('ks_auth_password',$verif_user,'user_id') == $user_id;
  }
}


