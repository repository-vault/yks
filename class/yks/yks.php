<?php

class yks
{
  static public $get;
  const FATALITY_XSL_404    = "xsl_404";
  const FATALITY_XML_SYNTAX = "xml_syntax";
  const FATALITY_404        = "404";
  const FATALITY_CONFIG     = "config";
  const FATALITY_SITE_CLOSED     = "site_closed";

  private static $config_file;
  static function init($load_config = true){

    classes::register_class_paths(array(
        "ksimplexmlelement"   => CLASS_PATH."/exts/ksimplexml/ksimplexmlelement.php", //fast
        "config"              => CLASS_PATH."/yks/config.php",
        "exyks"               => CLASS_PATH."/exyks/exyks.php"
    ));

    classes::call_init(true);
    classes::extend_include_path(CLASS_PATH."/exts/ksimplexml", LIBS_PATH, CLASS_PATH);
    classes::activate();
    if($load_config) self::load_config(SERVER_NAME);
  }

  public static function load_config($host = SERVER_NAME){

    define('YKS_CONFIG_CACHE', isset($_SERVER['YKS_CONFIG_CACHE']));
    define('YKS_CONFIG_FORCE', isset($_SERVER['YKS_CONFIG_FORCE']));


    self::$get = new yks();

    if(preg_match("#[^a-z0-9_.-]#", $host))
        yks::fatality(yks::FATALITY_CONFIG, "Invalid host name");

    self::$config_file = CONFIG_PATH."/$host.xml";

    if(!is_file(self::$config_file))
        yks::fatality(yks::FATALITY_CONFIG, self::$config_file." not found");
    $GLOBALS['config'] = $config =  yks::$get->config;
    if(!is_a($config, "config"))
        yks::fatality(yks::FATALITY_CONFIG, "\$config is no config");

        //******************** Usefull constants **************

    $domain = parse_url($config->site['url']);

    if(!$domain['host']){
        $domain['host'] = SERVER_NAME;
        $config->site['url']="http://{$domain['host']}";

        if(!$config->site['code'])
            $config->site['code']=join('.',array_slice(explode(".",$domain['host']),0,-2));
        if(!((string)$config->site['code'])) 
            yks::fatality(yks::FATALITY_CONFIG, "site code cannot be resolved");
    }


    define('DEBUG',          strpos($config->site['debug'],$_SERVER['REMOTE_ADDR'])!==false);
    define('SQL_DRIVER',     $config->sql['driver']); //very early, but need to be BEFORE exyks
    define('SITE_CODE',      strtr($config->site['code'],'.','_'));
    define('SITE_URL',       $config->site['url']);
    define('SITE_BASE',      ucfirst(SITE_CODE));
    define('SITE_DOMAIN',    $domain['host']);
    define('SESS_DOMAIN',    pick($config->site->sess['domain'], SITE_DOMAIN) );

    define('FLAG_DOMAIN',    substr(md5(SITE_DOMAIN.SITE_CODE),0,5));
    define('FLAG_APC',       FLAG_DOMAIN);
    define('FLAG_LOG',       $config->flags['log']);
    define('FLAG_FILE',      $config->flags['file'].FLAG_DOMAIN);
    define('FLAG_SESS',      $config->flags['sess'].SESS_DOMAIN);


    define('CACHE_REL',      'cache/'.FLAG_DOMAIN);
    define('CACHE_URL',      SITE_URL.'/'.CACHE_REL);
    define('CACHE_PATH',     PUBLIC_PATH.'/'.CACHE_REL);

    define('ROOT_PATH',      paths_merge(PUBLIC_PATH, $config->site['root_path'],".."));
    define('TMP_PATH',       ROOT_PATH."/tmp");
    define('LIBRARIES_PATH', paths_merge(YKS_PATH, $config->site['libraries_path'], ".."));


        //********************************************************

    $paths = array();
     foreach($config->paths->iterate("include") as $path) {
        $path = $path['path']; if(substr($path,0,7)=="path://") continue;
        $paths[] = paths_merge(ROOT_PATH, $path);//?
     }

    $exts = (string) $config->classes['exts'];
    $call_init = ((string)$config->classes['call_init']) != 'false';

    classes::extend_include_path($paths);
    classes::activate($exts); //it's okay to activate again, autoload seems to be smart enough
    classes::call_init($call_init);
  }


  static function fatality($fatality_key, $details=false, $render_mode="html"){
    if($details) error_log("[FATALITY] $details");
    if(PHP_SAPI == "cli") die ("==== Fatality $fatality_key ====\r\n$details");
    header($render_mode=="jsx"?TYPE_XML:TYPE_HTML);
    $contents  = file_get_contents(RSRCS_PATH."/fatality/-top.html");
    if(DEBUG) $contents .= "\r\n<!-- ".strtr($details,array("-->"=>"--"))."-->\r\n";
    $contents .= file_get_contents(RSRCS_PATH."/fatality/$fatality_key.html");
    $contents .= file_get_contents(RSRCS_PATH."/fatality/-bottom.html");
    die($contents);//finish him
  }


  public function get($key, $args = false){ //dont use it as a static, use yks::$get->get(
    $flag = $args?"$key_$args":$key;
    if(isset($this->$flag)) return $this->$flag;
    if($key == "tables_xml")
        $this->$flag = data::load($key);

    if($key == "types_xml")
        $this->$flag = data::load($key);

    if($key == "config")
        $this->$flag = config::load(self::$config_file);

    if($key == "entities")
        $this->$flag = data::load($key, $args);

    return $this->$flag;
  }

  public function __get($key){ return $this->get($key);  }
}






