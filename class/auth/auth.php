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

  static function update($user_id, $force=false, $skip_full_load=false){
    if(!$user_id) return false;	//root_id=0 graou ?

    //get the tree above user_id, then check if current tree is compatible

    if($force) $tree = users::get_parents($user_id);
    else $tree = auth::get_tree($user_id);
    if(!$tree) return false;

    sess::$sess['user_id']=$user_id;
    sess::$sess['users_tree']=$tree;

        sql::update("ks_users_profile",array('user_connect'=>_NOW),compact('user_id'));

        $types=array();
        sql::select("ks_users_list",array('user_id'=>$tree));
        $types_raw= sql::brute_fetch('user_id','user_type');
        foreach($tree as $user_id)$types[$types_raw[$user_id]]=$user_id;
        sess::$sess['users_types']=$types;

    if(!$skip_full_load) sess::load();
    return true;
  }

  static function reloc_chk(){
    if(($reloc=$_POST['redirect_url']) || ($reloc=$_SESSION[SESS_TRACK_ERR] )){
            unset($_SESSION[SESS_TRACK_ERR]);
            reloc($reloc);
    } return true; 
  }

  static function is_valid($user_id){
    $auth_types=sql::row("ks_users_list",compact('user_id'),'auth_type');
    $grant=true;$auth_types=array_filter(explode(',',$auth_types['auth_type']));
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

  static function verif($acces_zone='',$lvl='acces',$die=false){
    $valid=isset(sess::$sess['user_acces'][$acces_zone][$lvl]);
    if($die && !$valid)abort($die);
    return $valid;
  }

  static function get_tree($user_id){
        //merge current tree with (indirectly) requested tree, stop at join point - meng point
    $tree=sess::$sess['users_tree'];$path=array();
    do {
        if(!auth::is_valid($user_id))return false;
        $path[]=$user_id;
        if(!extract(sql::row('ks_users_tree',compact('user_id'),'parent_id as user_id'))) return false;
    }while(!in_array($user_id,$tree) && $user_id!=USERS_ROOT);
    return array_unique(array_merge($tree,array_reverse($path)));
  }
}

