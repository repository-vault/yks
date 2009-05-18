<?


$user_access=array();$access=array();
sql::select("ks_users_access",$verif_user,'access_zone,access_lvl');
while(extract(sql::fetch()))
    $user_access[$access_zone]=array_flip(array_filter(explode(',',$access_lvl)));

$parents=array('user_id'=>array_diff($parent_tree,array($user_id)));
sql::select("ks_users_access", $parents, 'access_zone,access_lvl');
while(extract(sql::fetch()))
    $access[$access_zone]=array_flip(array_filter(explode(',',$access_lvl)));


if($action=="access_save")try {
    foreach($access_zones as $access_zone=>$zone_infos){
        $set=array();
        foreach(vals($types_xml->access_lvl) as $access_lvl){
            if($_POST['access'][$access_zone][$access_lvl])$set[]=$access_lvl;
        }

        $data=array( 'access_lvl'=>join(',',$set) );
                $verif_access=compact('user_id','access_zone');

        if(!$set && $user_access[$access_zone])
            sql::delete("ks_users_access",$verif_access);
        elseif($set)
            sql::replace("ks_users_access",$data,$verif_access);
    }
    rbx::ok("Modification enregistrées");
}catch(rbx $e){}
