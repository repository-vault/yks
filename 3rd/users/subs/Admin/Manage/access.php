<?

$user_access = auth::get_access(array($user_id));
$access      = auth::get_access( array_diff($parent_tree,array($user_id)) );

if($action=="access_save")try {
    foreach($access_zones as $access_zone=>$zone_infos){
        $set = array();
        foreach(vals(yks::$get->types_xml->access_lvl) as $access_lvl){
            if($_POST['access'][$access_zone][$access_lvl])$set[]=$access_lvl;
        }

        $data=array( 'access_lvl'=>join(',',$set) );
                $verif_access=compact('user_id','access_zone');

        if(!$set && $user_access[$zone_infos['access_zone_path']])
            sql::delete("ks_users_access",$verif_access);
        elseif($set)
            sql::replace("ks_users_access",$data,$verif_access);
    }

    rbx::ok("Modification enregistrées");
}catch(rbx $e){}

if($action=="access_duplicate")try {

  $token = sql::begin();
  $user_ids = explode(',', $_POST['duplicate_user']);
  $user_list = user::from_ids($user_ids);

  $parent_access = array();
  foreach($user_list as $user){
    $parent_access[$user->user_id] = auth::get_access(array_diff($user->users_tree,array($user->user_id)) );
  }

  $source_access = array_merge_recursive($user_access, $access);

  foreach($access_zones as $access_zone=>$zone_infos){
    if($source_access[$zone_infos['access_zone_path']]){
      foreach($user_list as $user){

        $new_rights = $source_access[$zone_infos['access_zone_path']];
        if($parent_access[$user->user_id][$zone_infos['access_zone_path']])
          $new_rights = array_diff(
            $source_access[$zone_infos['access_zone_path']],
            $parent_access[$user->user_id][$zone_infos['access_zone_path']]
          );

        if(empty($new_rights) || $new_rights == $user->user_access[$zone_infos['access_zone_path']]) continue;

        $data = array(
          'access_lvl' => join(',', $new_rights)
        );

        $verif_access = array(
          'user_id'     => $user->user_id,
          'access_zone' => $zone_infos['access_zone'],
        );
        sql::replace("ks_users_access",$data,$verif_access);
      }
    }
  }

  sql::commit($token);

  rbx::ok("Nouveaux droits attribués.");
}catch(rbx $e){}