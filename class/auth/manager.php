<?

include "manager_core.php";

  sess::init();
  security_manager::sanitize();


    /*
        Load basic session, if existing,
        load root user, if none
    */        

 if(!isset(sess::$sess['user_id']))
    sess::renew(); //sess::$id is now set



if($action=="deco")
    sess::deco();

if(sess::$sess['session_ip']!=$_SERVER['REMOTE_ADDR']) auth_restricted_ip::reload();
if($_COOKIE['user_id'] && ($_COOKIE['user_id']!=sess::$sess['user_id'])) auth_password::reload();


if($action=='login') try {
    if(!auth_password::reload($_POST['user_login'], $_POST['user_pswd'])) throw new Exception();
    rbx::ok("&auth_success;");
}catch(Exception $e){ rbx::error("&auth_failed;"); }



if(!isset(sess::$sess['user_acces'])) try {
    sess::load();
}catch(rbx $e){ rbx::error("Unable to start user session."); }


if(bool((string)$config->site['closed'])){
    if(!auth::verif("admin","admin")) abort(451);
    else tpls::css_add("/css/$site_code/off.css");
}


if(sess::$sess['user_id']){
    $user_id=sess::$sess['user_id'];
    $verif_user=compact('user_id');
    $user_sess=sess::$sess;
}

$screen_id=10;

include "manager_langs.php";

