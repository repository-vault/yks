<?

include "manager_core.php";


    /*
        Load basic session, if existing,
        load root user, if none
    */        

 if(!isset(sess::$sess['user_id']))
    sess::renew(); //sess::$id is now set


    /* Basic input escape & security check */
 if($_POST) $_POST = specialchars_deep($_POST);



  if($action){
    if($_POST['ks_flag']!=FLAG_KS){
        $action="";
        if(sess::$renewed){
            // jsx::export("ks_flag", FLAG_KS); this will work, but i think it's a wrong path
            // instead, let them do this by their own, manually
            rbx::error("Your session has expired, reload the page you are on, and try again ");
        } else rbx::error("Invalid security flag");
    } if(JSX) jsx::$rbx=true;
    unset($_POST['ks_flag']);
  }





if($action=="deco")
    sess::deco();

if(sess::$sess['session_ip']!=$_SERVER['REMOTE_ADDR']) auth_restricted_ip::reload();
if($_COOKIE['user_id'] && ($_COOKIE['user_id']!=sess::$sess['user_id'])) auth_password::reload();


if($action=='login'){
    if(!auth_password::reload($_POST['user_login'], $_POST['user_pswd']))
        rbx::error("&auth_failed;");
    else rbx::ok("&auth_success;");
}

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

$base = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$accept_hash = md5($base);

unset($_SESSION['langs'][$accept_hash]);
if(!$user_lang = $_SESSION['langs'][$accept_hash]) {
    include "detect_lang.php";
    $langs=vals($types_xml->lang_key);
    $user_lang =  find_best_lang($base, $langs);
    $_SESSION['langs'][$accept_hash] = $user_lang;
}


define('USER_LANG', $user_lang);

$entities=yks::$get->get("entities",USER_LANG);
