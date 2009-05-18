<?

if($action=="user_edit")try {

        $_POST['user_update']=date('d/m/Y',_NOW);

        $res = true;

            //force dans $_POST les champs hérité à NULL
        $fields_inherit=is_array($tmp=$_POST['fields_inherit'])?array_keys($tmp):array();
        foreach($fields_inherit as $field_type)
            if(mykse_validate(array($field_type=>$_POST[$field_type]),$field_type))
                throw rbx::warn("$field_type ne peut être hérité s'il a une valeur",$field_type);
            else $_POST[$field_type]=null;


        $data=mykse_validate($_POST,$profile_def);
        if($data) $res = sql::replace($profile_table,$data,$verif_user);
        if(!$res) throw rbx::error("Impossible de proceder à l'enregistrement");


        $data=mykse_validate($_POST,$std_profile_def);
        if($data) $res = sql::replace($std_profile_table, $data, $verif_user);
        if(!$res) throw rbx::error("Impossible de proceder à l'enregistrement");
        $data=mykse_validate($_POST,array('auth_type'));


        sql::update("ks_users_list",$data,$verif_user);


        if($data['auth_type']=='auth_password'){
            $data=mykse_validate($_POST,array('user_login','user_pswd'));
            if($data['user_login']) {
                $tmp = sql::row("ks_auth_password",array('user_login'=>$data['user_login']));
                if($data['user_pswd'] && ( !$tmp || $tmp['user_id']==$user_id) ){
                    $data['user_pswd']=crpt($data['user_pswd'],FLAG_LOG);
                    sql::replace("ks_auth_password",$data,$verif_user);
                }
            } else throw rbx::warn("Le login spécifié est invalide","user_login");
        }elseif($data['auth_type']=='') {
                sql::delete("ks_auth_password",$verif_user);
        }

        rbx::ok("Vos modification ont bien été sauvegardées");

}catch(rbx $e){  }

