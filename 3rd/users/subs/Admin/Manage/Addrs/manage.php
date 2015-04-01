<?php
if($action=="addr_delete")try {
    $verif_delete=compact('addr_id','user_id'); //pour être sûr... 
    sql::delete("ks_users_addrs",$verif_delete);
    jsx::js_eval(JSX_PARENT_CLOSE.JSX_CLOSE);
}catch(rbx $e){}


if($action=="addr_manage" || $action=="addr_duplicate")try {

    if($action == "addr_duplicate") unset($addr_id);
    $data=array(
        'addr_lastname'=>$_POST['addr_lastname'],
        'addr_firstname'=>$_POST['addr_firstname'],
        'addr_type'=>$addr_type=$_POST['addr_type'],
        'addr_field1'=>$_POST['addr_field1'],
        'addr_field2'=>$_POST['addr_field2'],
        'addr_phone'=>$_POST['addr_phone'],
        'addr_fax'=>$_POST['addr_fax'],
        'addr_zipcode'=>$_POST['addr_zipcode'],
        'addr_city'=>$_POST['addr_city'],
        'country_code'=>$_POST['country_code'],
        'user_id'=>$user_id,
    );
    
    if(!$addr_id) $res=sql::insert("ks_users_addrs",$data);
    else $res=sql::update("ks_users_addrs",$data,$verif_addr);


    if(!$res) throw rbx::error("Impossible d'enregistrer les coordonnées");

    rbx::ok("Coordonnées enregistrées");
    jsx::js_eval(JSX_PARENT_RELOAD);

}catch(rbx $e){  }


if(!$addr_infos['addr_type'])
    $addr_infos['addr_type']=(string)yks::$get->types_xml->addr_type['default'];


