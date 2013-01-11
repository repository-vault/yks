<?php
 

/*
    Client Side
*/

class Auth_Domyks extends AuthPlugin {
  private $wsdl_url;
  private $host_key;
  private $ext_sess; //distant session
  private $ext_user; //distant user
  private $access_zone;
  private $session_id;

  public function __construct($wsdl_url, $access_zone){
    $this->wsdl_url     = $wsdl_url;
    $this->access_zone  = $access_zone; 
    $tmp = parse_url($this->wsdl_url);
    $this->host_key = substr(md5($tmp['host']),0,5);
  }

  public function autoCreate(){ return true; }  // require pour que domyks prenne la main
  public function userExists( $user_name ) { return true;}  // stfu
  public function strict() { return true;}

  public function updateUser(&$user){
    $user->mRealName = $this->ext_user['user_name'];
    $user->mEmail    = $this->ext_user['user_mail'];
    $user->saveSettings();
    return true;
  }

  public function authenticate($user_login, $user_pswd){
    try {
	$user_login = strtolower($user_login);
        $this->ext_sess = new  SoapClient($this->wsdl_url, array('cache_wsdl' => WSDL_CACHE_NONE));
        $this->session_id = $this->ext_sess->login($user_login, $user_pswd);
	$this->ext_user = unserialize($this->ext_sess->getUser($this->session_id));
        $auth  = $this->ext_sess->verifAuth($this->session_id, $this->access_zone, "access");
        return $auth;
    } catch(Exception $e){ return false; }

  }

  public function initUser( &$user, $autocreate=false ) {
    //$user->mName     = sprintf("%s:%s", $this->host_key, strtolower($user->mName));
    $user->mRealName = $this->ext_user['user_name'];
    $user->mEmail    = $this->ext_user['user_mail'];
  }

}


