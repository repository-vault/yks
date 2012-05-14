<?php

auth::verif("yks", "admin", 403);

tpls::css_add("path://skin.users/users.css");

$tables_xml = data::load("tables_xml");

$user_filter = &sess::$sess->retrieve('user_filter');

$user_id=(int)$sub0;
if(!$user_id) $user_id=USERS_ROOT;
$parent_id=$user_id;
$verif_user=compact('user_id');

//degage les filtres de recherche si on a changé d'utilisateur
if($action!="user_filter" && $user_id!=$user_filter['user_id'])
  $user_filter=compact('user_id');


$parent_tree=users::get_parents($user_id);
$parent_infos=users::get_infos($parent_tree,array('user_name','user_type'));
$user_infos=  $parent_infos[$user_id];


//that _naming issue will be cool with php6 & namespaces
$parent_path=array();
foreach($parent_infos as $_user_id=>$user_infos)
  $parent_path[]="<a href='/?/Admin/Users//$_user_id/list' target='users_list'>{$user_infos['user_name']}</a>";
$parent_path=join(' &gt; ',$parent_path);

exyks::$head->title = "Gestion des utilisateurs"; //$user_infos["user_name"] ... JSX :/

if($action == "users_move") try {
  $parent_id=(int)$_POST['where_id'];
  if(!$parent_id)	throw rbx::error("Impossible de deplacer les elements");

  $users_id = array_keys((array)$_POST['users_id']);
  if(!$users_id && ($user_id=$_POST['user_id'])) $users_id = array($user_id);

  $data=compact("parent_id");
  foreach($users_id as $user_id) {
    sql::update("ks_users_tree",$data,compact('user_id'));
  }

  return jsx::js_eval("Jsx.open('/?$href_fold//$parent_id/list','users_list',this)");
} catch(rbx $e) {}

if($action=="users_delete") try{
  $users_id = array_keys((array)$_POST['users_id']);
  if(!$users_id && ($user_id=$_POST['user_id'])) $users_id = array($user_id);

  foreach($users_id as $user_id) {
    sql::delete("ks_users_tree",compact("user_id"));
  }

  return jsx::js_eval("Jsx.open('/?$href_fold//$parent_id/list','users_list',this)");
}catch(rbx $e) {}
