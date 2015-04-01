<?php
set_time_limit(90);
ini_set("memory_limit","256M");

$access_lvls = mykses::vals("access_lvl");

if($action == "set_zones") {
    sess::store("search_results",(array)$_POST['access_zones']);
    jsx::js_eval(JSX_RELOAD);
    jsx::end();
}


//$cols = "user_id, user_name";
$select_cols = array(
      "user_id",
      "user_name",
      "user_type",
      "user_create",
      "user_update",
      "coalesce(user_connect , 0) as user_connect",
      "depth",
      "parent_id",
      );
//$cols .= ", ".join(',', $affichage);

$users_list = users::get_children_infos(USERS_ROOT, true, $select_cols  , "user_connect DESC" );

// On crée un arbre des parents (pour un noeud on remonte son arbre jusqu'à racine)
$users_tree_splat = array_extract($users_list, 'parent_id');
$users_tree = make_tree($users_tree_splat, false, true);

// Droits

$requested_zone = (array) sess::retrieve("search_results");


$access_zone_list = auth::get_access_zones();
array_sort_deep($access_zone_list, 'access_zone_path');

$access_zone_list_dsp =  $requested_zone
    ?array_intersect_key( $access_zone_list, array_flip($requested_zone) )
    :array();

$verif_users = array('user_id'=>array_keys($users_list));
sql::select("ks_users_access",  $verif_users , 'user_id, access_zone, access_lvl');
$access_list = sql::brute_fetch();
foreach($access_list as $access) {
  $user_id = $access['user_id'];
  $access_infos = $access_zone_list[$access['access_zone']];
  $access_lvl   = array_filter(explode(',', $access['access_lvl']));
  $access_lvl   = array_combine($access_lvl, $access_lvl);
  $users_list[$user_id]['local_rights'][$access_infos['access_zone_path']] = $access_lvl;
}


foreach($users_list as $user_id => &$user_infos){
    $user_infos['parent_tree']      = array_reverse(array_keys(linearize_tree($users_tree[$user_id])));
    $user_infos['inherited_rights'] = array();

    foreach($user_infos['parent_tree'] as  $parent_id) {
        $parent_rights = $users_list[$parent_id]['local_rights'];
        if(!$parent_rights) continue;
        $user_infos['inherited_rights'] =  array_merge_numeric ($user_infos['inherited_rights'] , $parent_rights);
    }
} unset($user_infos);

