<?php

/*  "Yks auth_ldap_soap" by Viande S.
    distributed under the terms of GNU General Public License - © 2012
    this class has a double role : 
    - it provide ldap login/pswd authentification
    - it loads a session from saved cookies
*/

class auth_ldap_soap {
  const sql_table = "ks_auth_ldap_soap";

  public static $endpoints_list = array();
  private static $pubkey;

  static function init(){
    $endpoints_list = array();
    $users = yks::$get->config->users;
    foreach($users->iterate("auth_ldap_soap") as $endpoint)
      $endpoints_list[(string) $endpoint['endpoint_name']] = (string) $endpoint['endpoint_url']; //value osef
    self::$endpoints_list = $endpoints_list;
    

    $pubkey_content = crypt::BuildPemKey($users->public['key'], crypt::PEM_PUBLIC);      
    self::$pubkey   = openssl_get_publickey($pubkey_content);
  }
  
  static function reload($user_login = false, $user_pswd = false, $allow_redirect = true, $skip_auth = false){
    
    $user_id = &$_COOKIE['user_id'];
    if($_POST['user_id'])
      $user_id = $_POST['user_id'];
    $user_id = (int)$user_id; //safe cookie
        
    if($user_login && $user_login = sql::clean($user_login) ) {
        $from = array("ks_users_profile", "user_id" => self::sql_table);
        $user_id = sql::value($from, "user_mail LIKE '$user_login@%'", 'user_id');
    }
    
    $cookie_pswd = self::forge_cookie($user_id);
    if($user_pswd) {
        openssl_public_encrypt($user_pswd, $out, self::$pubkey);
        $_COOKIE[$cookie_pswd] = base64_encode($out);
    }
    
    
    if( !($user_id && sess::update($user_id, $skip_auth)) ){
        setcookie('user_id', false);
        setcookie($cookie_pswd, false);
        return false;
    }
    $COOKIE_EXPIRE = bool(yks::$get->config->users['nopersistence']) ? 0 : _NOW+86400*10;
    setcookie('user_id', $user_id, $COOKIE_EXPIRE,'/', SESS_DOMAIN);
    setcookie($cookie_pswd, $_COOKIE[$cookie_pswd], $COOKIE_EXPIRE, '/', SESS_DOMAIN);
    return $allow_redirect?auth::reloc_chk():true;
  }

  private static function forge_cookie($user_id){
    return crpt("user_ldap_pswd_$user_id", FLAG_SESS, 10);
  }

  static function verif($user_id){
    
    $success       = false;    
    $user_mail     = sql::value("ks_users_profile", compact('user_id'), 'user_mail');
    $exploded_mail = explode('@', $user_mail); //only variables can be passed by reference
    $user_login    = reset($exploded_mail); //!
    $endpoint_name = sql::value(self::sql_table, compact('user_id'), 'auth_ldap_soap_endpoint_name');
    $endpoint_url  = self::$endpoints_list[$endpoint_name];
    
    if(!$endpoint_url) 
      return false;
    
    $cookie_pswd = self::forge_cookie($user_id);
    $user_pswd   = $_COOKIE[$cookie_pswd];
    
    if(!$user_login || !$user_pswd)
      return false;
    
    try {
      $wsdl_url = $endpoint_url."/services/?class=WSAuthLdap&wsdl";

      $client = new SoapClient($wsdl_url, array(
        'cache_wsdl' => WSDL_CACHE_NONE,
	'location'   => $wsdl_url,
      ));

            
      //Body of the Soap Header. 
      $headers = array();
      $headers['IP'] = $_SERVER['REMOTE_ADDR'];
      
      if(defined("SOAP_SESSION_ID")){
        $soap_request = simplexml_load_string(file_get_contents("php://input", "r"), NULL, LIBXML_ERR_NONE);
        if(isset($soap_request) && $soap_request !== false){
          $soap_request->registerXPathNamespace("envelope", "http://schemas.xmlsoap.org/soap/envelope/");

          $request_headers = array();
          foreach($soap_request->xpath("/envelope:Envelope/envelope:Header/*/item") as $header) {
            $request_headers[(string)$header->key] = (string)$header->value;
          }
          
          if(isset($request_headers['IP']))
            $headers['IP'] = $request_headers['IP'];
        }
      }
        
      $soap_headers = new SOAPHeader($endpoint_url, 'Headers', $headers);

      $client->__setSoapHeaders($soap_headers);
  
      $success = (bool)$client->login($user_login, $user_pswd);
    }
    catch(Exception $e){
      $success = false;
    }
        
    return $success;
  }
}
