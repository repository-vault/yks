<?


sql::select("ks_access_zones",true,"*","
    ORDER BY access_zone=access_zone_parent
    DESC,access_zone_parent,  access_zone ASC
"); $access_zones = sql::brute_fetch("access_zone");

$user_infos=users::get_infos_unique($user_id,array('user_name','user_type','auth_type'));
$user_infos=array_merge($user_infos,sql::row('ks_auth_password',$verif_user));

if(!$user_infos) return rbx::error("L'utilisateur demandé n'existe pas");

tpls::page_def("ks_users");
$user_type=$user_infos['user_type'];
if(is_file("$user_type.php")) tpls::page_def($user_type);

$user_verif=compact('user_id');


//Recherche des siblings
    //le parent est l'element precedent le user courant dans le parent_tree, normal
$parent_id = $parent_tree[max(0,array_search($user_id,$parent_tree)-1)];
$siblings_list=users::get_children($parent_id,1);

$next_user_id = array_step($siblings_list,$user_id);
$prev_user_id = array_step($siblings_list,$user_id,-1);



$dsp_profile=array();

$std_profile_table = "ks_users_profile";
$std_profile_def = fields($tables_xml->$std_profile_table);
$std_profile_def = array_diff($std_profile_def,array('user_id')); //potentiellement dangereux

$profile_def=array();


        //on complete les infos de l'utilisateur en fonction de son profil
        //puisque l'on est avant le traitement des actions, ca n'est judicieux ici qu'en JSX


$profile_table="{$user_type}_profile";
$profile_table_xml  = $tables_xml->$profile_table;


if($user_type!="ks_users"){
    $profile_def = fields($profile_table_xml);
    $profile_def = array_diff($profile_def,array('user_id'));  //potentiellement dangereux
    $user_infos=array_merge($user_infos,sql::row($profile_table, $verif_user));
}
$tables_children_list = array();
if($profile_table_xml['children']) {
    $tables_children = explode(",", $profile_table_xml['children']);
    foreach($tables_children as $table_children_name){
        $data = array();
        if($user_id) { sql::select($table_children_name, $verif_user); $data = sql::brute_fetch(); }
        $fields = fields($tables_xml->$table_children_name);
        $tables_children_list[$table_children_name] = array(
            'fields'=> array_diff($fields,array('user_id')),
            'data'=>$data,
        );
    }

}


$user_infos=array_merge($user_infos,sql::row("ks_users_profile", $verif_user));

if(FLAG_LOG) $user_infos['user_pswd']='';


$exclude = array('user_id', 'user_connect','user_create' ); //thoses fields cannot be modified
$std_profile_def = array_diff_key($std_profile_def, array_flip($exclude) );

$exclude = array('user_update');    //thoses fields should not be displayed
$dsp_profile = array_merge($std_profile_def, $profile_def);
$dsp_profile = array_diff_key($dsp_profile, array_flip($exclude) ); //only display



$user_infos = mykses::out($user_infos);

if($action=="su")try {
    sess::renew();
    unset($_SESSION[SESS_TRACK_ERR]);
    //$_COOKIE["user_pswd_$user_id"]=crpt($user_infos['user_pswd'],FLAG_LOG);
    sess::update($user_id, true);
    rbx::ok("Changement d'utilisateur : ".sess::$sess['user_name']);
}catch(Exception $e){rbx::error("Erreur lors du changement d'utilisateur");}
