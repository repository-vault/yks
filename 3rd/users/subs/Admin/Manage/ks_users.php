<?
$module_locale = false;
foreach(exyks::get_modules_list() as $modules){
  if($modules->key == 'localization'){
    $module_locale = $modules;
    $locale_path = reset(array_keys($module_locale->virtual_paths));
    break;
  }
}

if($action=="user_edit")try {

        $_POST['user_update']=date('d/m/Y',_NOW);

        $res = true;

            //force dans $_POST les champs hérité à NULL
        $fields_inherit=is_array($tmp=$_POST['fields_inherit'])?array_keys($tmp):array();
        foreach($fields_inherit as $field_type)
            if(mykses::validate(array($field_type=>$_POST[$field_type]),$field_type))
                throw rbx::warn("$field_type ne peut être hérité s'il a une valeur",$field_type);
            else $_POST[$field_type]=null;


        $data = mykses::validate($_POST,$profile_def);
        if($data) $res = sql::replace($profile_table,$data,$verif_user);
        if(!$res) throw rbx::error("Impossible de proceder à l'enregistrement");


        $data = mykses::validate($_POST,$std_profile_def);
        if($data) $res = sql::replace($std_profile_table, $data, $verif_user);
        if(!$res) throw rbx::error("Impossible de proceder à l'enregistrement");

        $data = mykses::validate($_POST,array('auth_type'));


        sql::update("ks_users_list",$data,$verif_user);

        if($data['auth_type'] != $user_infos['auth_type'] || !$data['auth_type']) { //cleanup
          sql::delete("ks_auth_password",  $verif_user);
          sql::delete("ks_auth_ldap_soap", $verif_user);
          sql::delete("ks_auth_oauth", $verif_user);
        }

        if( $data['auth_type'] == 'auth_password' ){
            try {
                $data = mykses::validate($_POST, array('user_login','user_pswd'));
                if($data['user_pswd'])
                    users::update_password($verif_user, $data['user_login'], $data['user_pswd']);
            } catch(Exception $e){ throw rbx::warn("Unable to save password", "user_login"); }
        } else if($data['auth_type']=='auth_ldap_soap'){
            try {
                $data = mykses::validate($_POST, array('auth_ldap_soap_endpoint_name'));

                if($data['auth_ldap_soap_endpoint_name'])
                    sql::replace("ks_auth_ldap_soap", $data, $verif_user);
            } catch(Exception $e){ throw rbx::warn("Unable to save password", "user_login"); }
        } else if($data['auth_type']=='auth_oauth'){
            try {
                $data = mykses::validate($_POST, array('auth_oauth_endpoint_name'));
                if($data['auth_oauth_endpoint_name'])
                    sql::replace("ks_auth_oauth", $data, $verif_user);
            } catch(Exception $e){ throw rbx::error("Unable to save credentials"); }
        }

        rbx::ok("Vos modification ont bien été sauvegardées");

}catch(rbx $e){  }

