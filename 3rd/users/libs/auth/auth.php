<?php


class auth {

  public static function login($user_login, $user_pswd, $redirect = true){

    if(auth_password::reload($user_login, $user_pswd, $redirect))
        return; //win

     //on peut essayer par ldap
     if( ! yks::$get->config->users->search('auth_ldap_soap') )
        throw new Exception("Invalid password"); //no more authentification pattern available

    if(!auth_ldap_soap::reload($user_login, $user_pswd, $redirect))
        throw new Exception("Invalid password, ldap");

  }

  
  public static function get_access_zones(){

    static $access_zones = false; if($access_zones) return $access_zones;

    sql::select("ks_access_zones", sql::true, "
            *,
            IF(access_zone_parent IN(access_zone, 'yks'),
                access_zone,
                CONCAT(access_zone_parent,':',access_zone)
            ) AS access_zone_path",
            "ORDER BY access_zone=access_zone_parent DESC,
        access_zone_parent, access_zone ASC");

    return $access_zones = sql::brute_fetch("access_zone");
  }


  static function limit($module_xml){
    // to refactor
    die;
    global $config,$href,$action;
    $module=(string)$module_xml['name'];
    $module_root=$config->modules->$module;

    $limit=$module_xml->limit?$module_xml->limit:$module_root->limit;

    $access_zone=(string) ($limit['access_zone']?$limit['access_zone']:$module_root['code']);

    if($href==ERROR_PAGE || !$limit)return true;

    $access_lvls=explode(",",(string)$limit['access_lvl']);
    foreach($access_lvls as $access_lvl){
        $grant=auth::verif($access_zone, $access_lvl);
        if($grant) continue;
        if($access_lvl=="action"){
            if(!$action)continue;$action='';
            rbx::error("&error.action_canceled;");
        } else abort(403);
    }return true;
  }

    // return a couple (user_id, users_tree)
  static function valid_tree($user_id, $skip_auth = false){
    if(!$user_id) return false;	//root_id=0 graou ?
    //get the tree above user_id, then check if current tree is compatible
    $asked_tree = users::get_parents($user_id);
    $diff = array_intersect((array) sess::$sess['users_tree'], $asked_tree);
    $users_tree = $skip_auth ? $asked_tree : self::get_tree($diff, $asked_tree);
    if(!$users_tree) return false;
    return compact('user_id', 'users_tree');
  }

  static function reloc_chk(){
    if(($reloc=$_POST['redirect_url']) || ($reloc=$_SESSION[SESS_TRACK_ERR] )){
            unset($_SESSION[SESS_TRACK_ERR]);
            reloc($reloc);
    } return true; 
  }

  static function is_valid($user_id, $auth_types){
    $grant=true;$auth_types=array_filter(explode(',',$auth_types));
    foreach($auth_types as $auth_type){
      $grant &= call_user_func(array($auth_type, 'verif'),$user_id);
    }
    return $grant;
  }

  public static function get_access($users_tree = false){
    $access_zones = self::get_access_zones();

    if($users_tree===false) $users_tree = (array)sess::$sess['users_tree'];
    sql::select('ks_users_access',array('user_id'=>$users_tree),'access_zone, access_lvl');
    $access = array();while(extract(sql::fetch())) {
        $zone_path = $access_zones[$access_zone]['access_zone_path'];
        $access[$zone_path]=array_merge_numeric(
            $access[$zone_path]?$access[$zone_path]:array(),
            array_flip(array_filter(explode(',',"$access_lvl")))
        );
    }

    return $access;
  }

  static function verif($access_zones='', $lvl='access', $die=false, $user=false){     global $action;

    if(!is_array($access_zones)) $access_zones=array($access_zones);
    $base = $user ? $user['user_access'] : sess::$sess['user_access'];
    foreach($access_zones as $access_zone) $base=$base[$access_zone];
    $valid = isset($base[$lvl]);
    if(!$die || $valid) return $valid;

    if($lvl == "action"){
        if($action) rbx::error("&error.action_canceled;");
        $action = '';
    } else abort($die);

  }

  static function get_tree($final_tree, $asked_tree){    
    //merge current tree with (indirectly) requested tree, stop at join point - meng point
    sql::select("ks_users_list", array('user_id'=>$asked_tree), 'user_id, auth_type');
    $users_auths = sql::brute_fetch('user_id', 'auth_type');
    $auths_types = array_sort($users_auths, array_reverse($asked_tree));
        
    foreach($auths_types as $user_id=>$auth_types) {
        if(!auth::is_valid($user_id, $auth_types)) return false;
        if(in_array($user_id, $final_tree) || $user_id == USERS_ROOT) break;
    } return $asked_tree;
  }
}

