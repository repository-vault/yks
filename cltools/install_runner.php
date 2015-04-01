<?php

class install_runner {

/**
*  Create initial folder structure
*  @alias struct
*/
  public static function init(){
    $dirs = array(
        ROOT_PATH.DIRECTORY_SEPARATOR."tpls",
        ROOT_PATH.DIRECTORY_SEPARATOR."subs",
        ROOT_PATH.DIRECTORY_SEPARATOR."config",
        ROOT_PATH.DIRECTORY_SEPARATOR."libs",
        ROOT_PATH.DIRECTORY_SEPARATOR."www",
    );

    $restart = false;
    foreach($dirs as $dir) {
        if(is_dir($dir)) continue;
        $restart = true;
        rbx::ok("Create $dir");
        files::create_dir($dir);
    }

    if($restart) {
        rbx::ok("Main structure is ready, exiting (restart)");
        die;
    }
    rbx::ok("Main structure is ready");
  }

  function install(){

    $host_name = cli::text_prompt("Host name", $_SERVER['SERVER_NAME']);
    $host_key  = join('.',array_slice(explode(".",$host_name),0,-2));
    $host_key = cli::text_prompt("Host key", $host_key);


    $config = simplexml_load_string(XML_HEAD."<config/>");
    $config->site['code'] = $host_key;
    $config_file_name = "path://config/$host_name.xml";

    rbx::ok("Wrote config in $config_file_name");
    file_put_contents($config_file_name, $config->asXML());

    //minimal configuration is ready - yeah
    yks::init(true); //load config
    exyks::init();



    $dirs = array(
        "subs".DIRECTORY_SEPARATOR.SITE_BASE,
        "tpls".DIRECTORY_SEPARATOR.SITE_BASE,
        CACHE_PATH,
    );

    foreach($dirs as $dir) {
        if(is_dir($dir)) continue;
        rbx::ok("Create $dir");
        files::create_dir($dir);
    }

    touch("subs/main.php");
    touch("subs/".SITE_BASE."/main.php");
    touch("subs/".SITE_BASE."/error.php");
    touch("subs/".SITE_BASE."/home.php");

    $home = "tpls/".SITE_BASE."/home.tpl";
    if(!is_file($home))
        file_put_contents($home, "Hello world");

    touch("tpls/".SITE_BASE."/error.tpl");

    $index = "www/index.php";
    if(!is_file($index))
        file_put_contents($index, '<?'.CRLF.'include "yks/class/yks/loader.php";'.CRLF.'include EXYKS;');

    $runner = new myks_runner();
    $runner->manage_types();

    $runner->manage_xsl();


  }


}