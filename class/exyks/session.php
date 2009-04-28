<?

/*  "Yks session manager" by Leurent F. - 131 (131.php@cloudyks.org)
    distributed under the terms of GNU General Public License - © 2008
*/

class exyks_session {

  static function init_core() {
        //register classic sess managers
    classes::register_class_paths(array(
        "sess"                 => CLASS_PATH."/auth/sess.php",
        "auth"                 => CLASS_PATH."/auth/auth.php",
        "security_manager"     => CLASS_PATH."/exyks/security_manager.php",
        "auth_password"        => CLASS_PATH."/auth/interfaces/password.php",
        "auth_restricted_ip"   => CLASS_PATH."/auth/interfaces/restricted_ip.php",
    ));
    sess::init();
  }


    //  Load basic session, if existing, load root user else
  static function load_classic(){
    global $action;

    if(!isset(sess::$sess['user_id']))
        sess::renew(); //sess::$id is now set

    if($action=="deco")
        sess::deco();

    if(sess::$sess['session_ip']!=$_SERVER['REMOTE_ADDR']) auth_restricted_ip::reload();
    if($_COOKIE['user_id'] && ($_COOKIE['user_id']!=sess::$sess['user_id'])) auth_password::reload();

    if($action=='login') try {
        if(!auth_password::reload($_POST['user_login'], $_POST['user_pswd'])) throw new Exception();
        rbx::ok("&auth_success;");
    } catch(Exception $e){ rbx::error("&auth_failed;"); }

    if(!isset(sess::$sess['user_acces'])) try {
        sess::load();
    } catch(Exception $e){ rbx::error("Unable to start user session."); }


    if(bool((string)yks::$get->config->site['closed'])){
        if(!auth::verif("admin","admin")) yks::fatality(yks::FATALITY_SITE_CLOSED);
        else tpls::css_add("/css/".SITE_BASE."/off.css");
    }

        //last piece of trash code ?
    global $user_id, $verif_user, $user_sess, $screen_id;
    $user_id=sess::$sess['user_id'];
    $verif_user=compact('user_id');
    $user_sess=sess::$sess;
    $screen_id=10;
  }

}