<?php

/**
  Users factory, need a full description of wanted user
  users_gest::create($data);
  $data=array(
    user_id // if not provided, nextval() is used
    parent_id // default ROOT_ID
    user_name ...
  );
*/

class user_gest {
  static function create($def){
    $users_type = yks::$get->types_xml->user_type;
    $tables_xml = yks::$get->tables_xml;


    $def=array_merge(array(
        'user_update'=>date('d/m/Y'),
        'parent_id'=>USERS_ROOT,
        'user_name'=>"Utilisateur anonyme",
        //'auth_type'=>'auth_password',
        'user_type'=>'ks_users',
        'user_access'=>array(), //zone=rights
    ),$def);

    if($def['user_login'] && !$def['auth_type'])
        $def['auth_type']='auth_password';

    $mode_update=(bool) ($user_id=$def['user_id']);

    $data=mykses::validate($def, $tables_xml->ks_users_list);

    if($mode_update){
        sql::update("ks_users_list", $data,compact('user_id'));
    } else {
        $def['user_create']=date('d/m/Y');
        $user_id = sql::insert("ks_users_list", $data, true);
        $def['user_id']=$user_id;
    }
    if(!$user_id) return false;

    $data = array_filter(mykses::validate($def, $tables_xml->ks_users_addrs));
    unset($data['user_id']);
    if($data)
        sql::replace("ks_users_addrs", $data, compact('user_id') );

    if(is_array($def['user_access'])){
        foreach($def['user_access'] as $access_zone=>$access_lvl){
            $access_lvl = join(',',(array)$access_lvl);
            sql::insert("ks_users_access", compact('user_id', 'access_zone', 'access_lvl'));
        }
    }
    $data = mykses::validate($def, $tables_xml->ks_users_tree);
    sql::insert("ks_users_tree",$data);

    foreach(array_unique(array("ks_users",$def['user_type'])) as $user_type){
        $profile_table="{$user_type}_profile";
        $data = mykses::validate($def, $tables_xml->$profile_table);
        sql::insert($profile_table,$data);
    }

    if(strpos($def['auth_type'],'auth_password')!==false){
        $data = mykses::validate($def, $tables_xml->ks_auth_password);
        $res = sql::insert("ks_auth_password", $data);
        if(!$res) return false;
    }


    return $def['user_id'];
  }
}