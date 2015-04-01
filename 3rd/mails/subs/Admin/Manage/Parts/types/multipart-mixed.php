<?php

if($action == "part_add")try {


    $data= array(
        'parent_part'=>$part_id,
        'content-type'=>$_POST['content-type']
    );

    if(!$data['content-type'])
        throw rbx::error("Type de contenu invalide");


    sql::insert("ks_mails_parts", $data);

    jsx::js_eval(JSX_RELOAD);


}catch(rbx $e){}