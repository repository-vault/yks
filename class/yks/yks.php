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


  private static function find_config_file($host){
    static $hashkey = false;
    if(preg_match("#[^a-z0-9_.-]#", $host))
        yks::fatality(yks::FATALITY_CONFIG, "Invalid host name");

    $host  = explode(".", $host);
    for($a = count($host); $a>0; $a--) {
        $host_part = join('.', array_slice($host, -$a));
        self::$config_file = CONFIG_PATH."/$host_part.xml";
        if(is_file(self::$config_file))
            return;
    }
    yks::fatality(yks::FATALITY_CONFIG, self::$config_file." not found");

  }

  public static function load_config($host = SERVER_NAME){

    define('YKS_CONFIG_CACHE', isset($_SERVER['YKS_CONFIG_CACHE']));
    define('YKS_CONFIG_FORCE', isset($_SERVER['YKS_CONFIG_FORCE']));

    self::find_config_file($host);

    self::$get = new yks();

    $GLOBALS['config'] = $config =  yks::$get->config;
    if(!is_a($config, "config"))
        yks::fatality(yks::FATALITY_CONFIG, "\$config is no config");


        //******************** Usefull constants **************

    $domain = parse_url($config->site['url']);

    if(!$domain['host']){
        $domain['host'] = SERVER_NAME;
        $config->site['url']="http://{$domain['host']}";
        if(!$config->site['code'])
            $config->site['code'] = pick(join('.',array_slice(explode(".",$domain['host']),0,-2)), SERVER_NAME);
    }


    define('DEBUG',          strpos($config->site['debug'],$_SERVER['REMOTE_ADDR'])!==false);

    $site_code = strtr($config->site['code'],'.','_');

    define('SQL_DRIVER',      pick($config->sql['driver'], 'pgsql'));
    define('STORAGE_DRIVER',  pick($config->storage['driver'], PHP_SAPI=='cli'?'var':'apc'));

    define('SITE_CODE',      strtolower($site_code));
    define('SITE_URL',       $config->site['url']);
    define('SITE_BASE',      ucfirst($site_code));
    define('SITE_DOMAIN',    $domain['host']);
    define('SESS_DOMAIN',    pick($config->site->sess['domain'], SITE_DOMAIN) );

    define('FLAG_DOMAIN',    substr(md5(SITE_DOMAIN.SITE_CODE),0,5));
    define('FLAG_APC',       FLAG_DOMAIN);
    define('FLAG_LOG',       $config->flags['log']);
    define('FLAG_FILE',      $config->flags['file'].FLAG_DOMAIN);
    define('FLAG_SESS',      $config->flags['sess'].SESS_DOMAIN);

    define('CACHE_REL',      'cache/'.FLAG_DOMAIN);
    define('CACHE_URL',      SITE_URL.'/'.CACHE_REL);

    $consts = array_merge(array_mask($_ENV, "%s", "{%s}"), retrieve_constants("#_PATH$#"));
    define('ROOT_PATH',      paths_merge(PUBLIC_PATH, ".."));

    $defs  = array(
        'libraries_path' => realpath(YKS_PATH.DIRECTORY_SEPARATOR.".."),
        'cache_path'     => PUBLIC_PATH.DIRECTORY_SEPARATOR.CACHE_REL,
        'tmp_path'       => ROOT_PATH.DIRECTORY_SEPARATOR."tmp",
    ); $attrs = $config->paths->attributes();
    $attrs = array_merge(array_diff_key($defs, $attrs), $attrs);

    foreach($attrs as $k=>$v){
        $k = strtoupper($k); $v = str_set($v, $consts);
        $v = paths_merge(ROOT_PATH, $v);
        define($k, $v); $consts["{{$k}}"] = $v;
    }

    define ('PCLZIP_TEMPORARY_DIR', TMP_PATH.'/'); //!!!

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
    $flag = $args?"{$key}_{$args}":$key;
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


  public static function wsserver() {

    if(!defined('YKS_FUNCTIONS'))
      require CLASS_PATH."/functions.php";

    header(TYPE_XML);
    set_time_limit(90);

    rbx::$output_mode = 0;

    $WSClasses = array();
    foreach(yks::$get->config->wsdls->iterate("class") as $class)
        $WSClasses[] = $class['name'];

    $wsdls_path = ROOT_PATH."/wsdls/".FLAG_DOMAIN;

    $class_name = $_GET['class'];
    if(!in_array($class_name, $WSClasses)) {
        header(TYPE_TEXT);
        die("No valid class selected");
    }

    $wsdl_file = "$wsdls_path/$class_name.wsdl";


    if($_SERVER['REQUEST_METHOD']=='GET') {
        readfile($wsdl_file);
        die;
    }

    $options = array('actor' => SITE_CODE, 'classmap' =>array());
    $server = new SoapServer($wsdl_file, $options);
    $server->setClass($class_name);
    $server->setPersistence(SOAP_PERSISTENCE_REQUEST);
//      use_soap_error_handler(true);
    $server->handle();
  }

}







