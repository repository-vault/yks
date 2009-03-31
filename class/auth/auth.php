<?


class auth {
  static function limit($module_xml){
    
    global $config,$href,$action;
    $module=(string)$module_xml['name'];
    $module_root=$config->modules->$module;

    $limit=$module_xml->limit?$module_xml->limit:$module_root->limit;

    $acces_zone=(string) ($limit['acces_zone']?$limit['acces_zone']:$module_root['code']);

    if($href==ERROR_PAGE || !$limit)return true;

    $acces_lvls=explode(",",(string)$limit['acces_lvl']);
    foreach($acces_lvls as $acces_lvl){
        $grant=auth::verif($acces_zone,$acces_lvl);
        if($grant) continue;
        if($acces_lvl=="action"){
            if(!$action)continue;$action='';
            rbx::error("&error.action_canceled;");
        } else abort(403);
    }return true;
  }

  static function update($user_id, $force=false){
    if(!$user_id) return false;	//root_id=0 graou ?
    //get the tree above user_id, then check if current tree is compatible
    $asked_tree = users::get_parents($user_id);
    $diff = array_intersect(sess::$sess['users_tree'], $asked_tree);
    $users_tree = $force?$asked_tree:self::get_tree($diff, $asked_tree);
    if(!$users_tree) return false;
    sess::$sess = compact('user_id', 'users_tree');
    return true;
  }

  static function reloc_chk(){
    if(($reloc=$_POST['redirect_url']) || ($reloc=$_SESSION[SESS_TRACK_ERR] )){
            unset($_SESSION[SESS_TRACK_ERR]);
            reloc($reloc);
    } return true; 
  }

  static function is_valid($user_id, $auth_types){
    $grant=true;$auth_types=array_filter(explode(',',$auth_types));
    foreach($auth_types as $auth_type)$grant&=call_user_func(array($auth_type, 'verif'),$user_id);
    return $grant;
  }

  static function renew($users_tree=false){
    if($users_tree===false)$users_tree=sess::$sess['users_tree'];
    sql::select('ks_users_acces',array('user_id'=>$users_tree),'acces_zone, acces_lvl');
    $acces=array();while(extract(sql::fetch()))
        $acces[$acces_zone]=array_merge_numeric(
            $acces[$acces_zone]?$acces[$acces_zone]:array(),
            array_flip(array_filter(explode(',',"$acces_lvl")))
        );
    return $acces;
  }

  static function verif($acces_zones='',$lvl='acces',$die=false){
    if(!is_array($acces_zones)) $acces_zones=array($acces_zones);
    $base = sess::$sess['user_acces'];
    foreach($acces_zones as $acces_zone) $base=$base[$acces_zone];
    $valid=isset($base[$lvl]);
    if($die && !$valid)abort($die);
    return $valid;
  }

  static function get_tree($final_tree, $asked_tree){
        //merge current tree with (indirectly) requested tree, stop at join point - meng point
    sql::select("ks_users_list", array('user_id'=>$asked_tree), 'user_id, auth_type');
    $auths_types = array_sort(sql::brute_fetch('user_id','auth_type'), array_reverse($asked_tree));

    foreach($auths_types as $user_id=>$auth_types) {
        if(!auth::is_valid($user_id, $auth_types)) return false;
        if(in_array($user_id, $final_tree) || $user_id == USERS_ROOT) break;
    } return $asked_tree;
  }


}

