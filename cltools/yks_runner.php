<?php

class yks_runner {

  function __construct(){

  }

  function sql($query){
    sql::query($query);
    print_r(sql::brute_fetch());
  }


  function clear_caches(){
    $titles_cache_path = CACHE_PATH."/imgs/titles";
    files::delete_dir($titles_cache_path);
    rbx::ok("Delete dir $titles_cache_path");
  }

  function myks(){
    rbx::ok("Loading myks_runner");
    interactive_runner::start(new myks_runner());    

  }

/**
* @alias wsdls
* @alias ws
*/
  function wsdl_gen(){

    $wsdl_config = yks::$get->config->wsdls;

    classes::extend_include_path(
      LIBRARIES_PATH."/wshelper/lib/",
      LIBRARIES_PATH."/wshelper/lib/soap"
    );

    classes::activate(".class.php,.php");

    $encoding_values = array('encoded', 'literal');
    $binding_types   = array(SOAP_RPC, SOAP_LITERAL);
    $wsdls_path = ROOT_PATH."/wsdls/".FLAG_DOMAIN;
    files::empty_dir($wsdls_path, false);

    $wsdl_uri_mask  = SITE_URL;
    $wsdl_uri_mask .= pick($wsdl_config['rel_mask'], "/services/?class=%s"); 

    $wsdl_file_mask = "$wsdls_path/%s.wsdl";

    rbx::title("Running wsdl generation");
    
    foreach($wsdl_config->iterate("class") as $class){
        $class_name    = $class['name'];
        $encoding      = pick_in($wsdl_config['encoding'], $class['encoding'], $encoding_values)
                             == 'encoded' ? SOAP_ENCODED : SOAP_LITERAL;
        $binding       = pick_in(@constant($class['binding']), SOAP_RPC, $binding_types);

        $wsdl_url      = sprintf($wsdl_uri_mask, $class_name);
        $wsdl_filepath = sprintf($wsdl_file_mask, $class_name);

        $class = new IPReflectionClass($class_name);
        
        $wsdl = new WSDLStruct(SITE_CODE, $wsdl_url, $binding, $encoding);
        $wsdl->setService($class);
        $wsdl_contents = $gendoc = $wsdl->generateDocument();
        file_put_contents($wsdl_filepath, $wsdl_contents);
        rbx::ok("Generating #$class_name into $wsdl_filepath ".strlen($wsdl_contents));
    }
  }

  function clear_config(){

    if(PHP_SAPI == "cli")
        return yks_runner::httpd_tunnel(__CLASS__, "clear_config");


    rbx::line("Cleaning configuration caches");

    $hash_key   = config::hash_key();
    $hash_table = storage::fetch($hash_key);
    if(!$hash_table)
        throw rbx::error("Invalid hash key, unable to clear configuration caches");
    foreach($hash_table as $key=>$file){
        storage::delete($key);
        rbx::ok("Cleaning hash $key : $file"); 
    }
    storage::delete($hash_key);
    rbx::line();

  }
  

  function users(){
    $data = array();
    $users_root = (int)yks::$get->config->users['root'];

    if(!$users_root) {
        rbx::error("Cannot find users root in config->users['root']");
        $root_name = cli::text_prompt("Root user name");
        $users_root = sql::insert("ks_users_list", array('user_type' => 'ks_users'));
        $data = array('user_id' => $users_root, 'user_name' => $root_name);
        sql::insert("ks_users_profile", $data);
        sql::insert("ks_users_tree", array('user_id'=>$users_root, 'parent_id'=>$users_root));
        rbx::ok("Please write user root in your config : $users_root");
        die;
    }

    $create_admin = cli::bool_prompt("Create admin");

    if($create_admin) {
        sql::begin();
        $access_zone = 'yks';
        $access = sql::row("ks_access_zones", compact('access_zone'));
        if(!$access)
            sql::insert("ks_access_zones", 
                array('access_zone' => $access_zone, 'access_zone_parent' => $access_zone));

        $data=array(
            'user_name' => cli::text_prompt("Admin name"),
            'parent_id' => $users_root,
            'user_type' => 'ks_users',

            'auth_type'   => 'auth_password',
            'user_login'  => cli::text_prompt("Admin login"),
            'user_pswd'   => crpt(cli::password_prompt("Admin password"), FLAG_LOG),
            'user_access' => array($access_zone => 'admin'),
        ); $user_id = user_gest::create($data);

        if(!$user_id)
            throw rbx::error("Impossible de creer l'utilisateur");

        rbx::ok("Admin user created : $user_id");
        sql::commit();
    } else rbx::ok("Skipping admin creation");

   
  }


/**
* Check caches directory
*/
  private static function cache_dir_check(){
    try {
        files::create_dir(CACHE_PATH);
        if(!is_writable(CACHE_PATH))
            throw rbx::error(CACHE_PATH." is not writable");


        return ; //!
        $me = trim(`id -un`).':'.trim(`id -gn`); $me_id = trim(`id -u`);

        $cache_owner = fileowner(CACHE_PATH);
        if($cache_owner!=$me_id)
            rbx::error("Please make sure cache directory :'".CACHE_PATH."' is owned by $me");

    } catch(Exception $e){ rbx::error("Unable to access cache directory '".CACHE_PATH."'"); die; }
  }





/**
* cli tunneling for APC related features
*/
  public static function httpd_tunnel($runner, $command){
    self::http_auto_check(); //check self http lookup

    $myks_http_url = SITE_URL."/?/Yks/Scripts//$runner;$command|cli";
    $http_contents = self::wget_dnsless($myks_http_url);
    rbx::ok("Running $myks_http_url");
    echo $http_contents.CRLF;
  }


/**
*  Open a http lnk on itself, and search for a ping file
*  Check whenever cli is able to talk to httpd
*/
  private static function http_auto_check() {
    self::cache_dir_check();

    $ping_file = CACHE_PATH."/ping";
    $ping_url  = CACHE_URL."/ping";
    $rnd       = md5(rand());
    $res = file_put_contents($ping_file, $rnd);
    if(!$res)
        throw new Exception("Make sure cache directory '".CACHE_PATH."' is world writable");
 
    $http_contents = self::wget_dnsless($ping_url);

    if($http_contents != $rnd) {
        rbx::box("http_contents ($ping_url)", $http_contents, "rnd ($ping_file)", $rnd);
        throw new Exception("Http self check failed, please make sure <site><local @ip/> is configured");
    }
    unlink($ping_file);
    return true;
  }


  private static function wget_dnsless($url){
    $local_ip      = (string) yks::$get->config->site->local['ip'];

    $url_infos = parse_url($url);
    if($local_ip)
        $url = "{$url_infos['scheme']}://{$local_ip}{$url_infos['path']}";
    if($url_infos['query'])
        $url.="?{$url_infos['query']}";

    $options = array(
      'timeout' => 3,
      'header' => "Host:{$url_infos['host']}".CRLF,
    );
    return http::ping_url($url, $options);
  }


}
