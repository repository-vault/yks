<?php

class yks_runner {


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
    classes::extend_include_path(
      LIBRARIES_PATH."/wshelper/lib/",
      LIBRARIES_PATH."/wshelper/lib/soap"
    );

    $wsdls_path = ROOT_PATH."/wsdls/".FLAG_DOMAIN;
    files::create_dir($wsdls_path);

    $wsdl_uri_mask  = SITE_URL."/services/?class=%s";
    $wsdl_file_mask = "$wsdls_path/%s.wsdl";

    $WSClasses = array();
    foreach(yks::$get->config->wsdls->iterate("class") as $class)
        $WSClasses[] = $class['name'];
    if(!$WSClasses)
      throw rbx::error("Cannot find ws classes, please check your configuration");
    cli::box("Running wsdl generation", $WSClasses);

    foreach($WSClasses as $class_name){
        $wsdl_url      = sprintf($wsdl_uri_mask, $class_name);
        $wsdl_filepath = sprintf($wsdl_file_mask, $class_name);

        $class = new IPReflectionClass($class_name);
        $wsdl = new WSDLStruct(SITE_CODE, $wsdl_url, SOAP_RPC, SOAP_ENCODED);
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
  


  function install(){
    $root_path  = ROOT_PATH;
    $www_path  = $root_path.DIRECTORY_SEPARATOR.'www';
    $tpls_path  = $root_path.DIRECTORY_SEPARATOR.'tpls';
    $subs_path  = $root_path.DIRECTORY_SEPARATOR.'subs';
    $config_path  = $root_path.DIRECTORY_SEPARATOR.'config';


        //creating base dirs
    files::create_dir($www_path);
    files::create_dir($tpls_path);
    files::create_dir($subs_path);
    files::create_dir($config_path);

    $host_name = cli::text_prompt("Host name");
    $host_key  = join('.',array_slice(explode(".",$host_name),0,-2));
    do {
        $prompt = "Host key".($host_key?" [{$host_key}]":"");
        $host_key = pick(cli::text_prompt($prompt), $host_key);
    } while(!$host_key);

    $config = simplexml_load_string(XML_HEAD."<config/>");
    
    echo $config->asXML();
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
    echo $http_contents;
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
    unlink($ping_file);

    if($http_contents != $rnd) {
        rbx::box("http_contents ($ping_url)", $http_contents, "rnd ($ping_file)", $rnd);
        throw new Exception("Http self check failed, please make sure <site><local @ip/> is configured");
    }
    return true;
  }


  private static function wget_dnsless($url){
    $local_ip      = (string) yks::$get->config->site->local['ip'];
    return http::ping_url($url, 3, $local_ip);
  }


}
