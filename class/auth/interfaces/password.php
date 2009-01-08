<?
/*    "Yks auth_password" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
*/

define("COOKIE_EXPIRE",_NOW+86400*10);

class auth_password {

  static function reload($user_login = false, $user_pswd = false, $allow_redirect = true){
    $user_id=&$_COOKIE['user_id'];$user_id=(int)$user_id;
    if($_POST['user_id'])$user_id=(int)$_POST['user_id'];

    if($user_login){
        $verif=array('user_login'=>sql::clean($user_login));
        $tmp=sql::row("ks_auth_password",$verif,'user_id');
        $user_id=$tmp['user_id'];
    }
    if($user_pswd) $_COOKIE["user_pswd_$user_id"] = crpt($user_pswd, FLAG_LOG);

    if(!auth::update($user_id)){
        setcookie('user_id',false);
        setcookie("user_pswd_$user_id",false);
        return false;
    } 
    setcookie('user_id',$user_id,COOKIE_EXPIRE,'/');
    setcookie("user_pswd_$user_id",$_COOKIE["user_pswd_$user_id"], COOKIE_EXPIRE, '/');
    return $allow_redirect?auth::reloc_chk():true;
  }

  static function verif($user_id){
    $verif_user=array(
        'user_pswd'=>sql::clean($_COOKIE["user_pswd_$user_id"]),
        'user_id'=>$user_id
    );
    if(!$verif_user['user_pswd']) return false;
    if($verif_user['user_pswd']===(string)yks::$get->config->users['password'])
        unset($verif_user['user_pswd']);
    return current(sql::row('ks_auth_password',$verif_user,'user_id'))==$user_id;
  }
}


