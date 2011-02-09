<?

class unix {

  
  public static function getuserhomedir($user_name){
    $users = file_get_contents("/etc/passwd");
    if(!preg_match("#^$user_name:.*#m", $users, $out))
      throw new Exception("Invalid user");
    list($user_login, , $uid, $gid, , $user_home) = explode(':', $out[0]);
    return $user_home;
  }


}