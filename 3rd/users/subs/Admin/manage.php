<?php





if($action=="user_manage")try {
    $data=array(
        'user_name'=>$_POST['user_name'],
        'auth_type'=>'',
        'parent_id'=>$parent_id,
        'user_type'=>$_POST['user_type'],
    );extract($data);
    if(!$user_name)
        throw rbx::warn("Vous devez indiquer un nom",'user_name');

    $user_id=user_gest::create($data);
    if(!$user_id) throw rbx::error("Impossible de creer l'utilisateur");


    jsx::js_eval(JSX_PARENT_RELOAD);
    rbx::ok("L'utilisateur a bien été créé : #id($user_id)");

}catch(rbx $e) {}