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
      "ksimplexmlelement"   => CLASS_PATH . "/exts/ksimplexml/ksimplexmlelement.php", //fast
      "config"              => CLASS_PATH . "/yks/config.php",
      "exyks"               => CLASS_PATH . "/exyks/exyks.php",
    ));

    classes::call_init(true);
    classes::extend_include_path(CLASS_PATH."/exts/ksimplexml", LIBS_PATH, CLASS_PATH);

    classes::activate();

    if($load_config)
      self::load_config(SERVER_NAME);
    else
      exyks_paths::init();
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

    if($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR'])
      $_SERVER['SERVER_ADDR'] = $_SERVER['REMOTE_ADDR'] = "127.0.0.1"; //obviously

    // Yks configuration directive can use constants in the form {CONSTANT_NAME}
    $envs_vars = array_merge(
        array_key_map('strtoupper', array_mask($_ENV, "%s", "{%s}")),
        array_mask($_SERVER, "%s", "{%s}"),
        retrieve_constants("#_PATH$#")
    );

    //******************** Usefull constants **************

    if($config->site['url'])
      $SITE_URL = $config->site['url'];

    if($config->site['cli_url'] && PHP_SAPI == "cli")
      $SITE_URL = $config->site['cli_url'];

    if(!isset($SITE_URL)) {
      $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? "https" : "http";
      $SITE_URL = "{$scheme}://{SERVER_NAME}:{SERVER_PORT}";
    }

    $SITE_URL =  strip_end(str_set($SITE_URL, $envs_vars), ":80"); //bandwith enhancement
    $domain = parse_url($SITE_URL);

    define('DEBUG', false); //use yks::$get->config->is_debug()

    $site_code = strtr($config->site['code'], '.', '_');

    define('SQL_DRIVER',      pick($config->sql['driver'], 'pgsql'));
    define('STORAGE_DRIVER',  pick($config->storage['driver'], PHP_SAPI=='cli'?'var':'apc'));

    define('SITE_CODE',      strtolower($site_code));

    define('SITE_URL',       $SITE_URL);
    define('SITE_BASE',      ucfirst($site_code));
    define('SITE_DOMAIN',    pick($config->site['domain'], $domain['host']));
    define('SESS_DOMAIN',    pick($config->site->sess['domain'], SITE_DOMAIN) );

    //enable default module & define yks rsrcs
    define('SITE_STANDALONE', bool($config->site['standalone']));

    define('FLAG_DOMAIN',    substr(md5(SITE_DOMAIN.SITE_CODE),0,5));
    define('FLAG_APC',       FLAG_DOMAIN);
    define('FLAG_LOG',       $config->flags['log']);
    define('FLAG_FILE',      $config->flags['file'].FLAG_DOMAIN);
    define('FLAG_SESS',      $config->flags['sess'].SESS_DOMAIN);
    define('CACHE_REL',      'cache/'.FLAG_DOMAIN);
    define('CACHE_URL',      SITE_URL.'/'.CACHE_REL);

    $attrs = $config->paths->attributes();
    $attrs = array_merge(array_diff_key(array(
        'libraries_path' => realpath(YKS_PATH.DIRECTORY_SEPARATOR.".."),
        'cache_path'     => PUBLIC_PATH.DIRECTORY_SEPARATOR.CACHE_REL,
        'tmp_path'       => ROOT_PATH.DIRECTORY_SEPARATOR."tmp",
    ), $attrs), $attrs); //order is relevant ! (CACHE_PATH might = {TMP_PATH})

    foreach($attrs as $k=>$v){
      $k = strtoupper($k); $v = str_set($v, $envs_vars);
      $v = paths_merge(ROOT_PATH, $v);
      define($k, $v); $envs_vars["{{$k}}"] = $v;
    }

    define ('PCLZIP_TEMPORARY_DIR', TMP_PATH.'/'); //!!!

    //********************************************************

    $paths = array();
    foreach($config->paths->iterate("include") as $path) {
      $path = $path['path']; if(substr($path,0,7)=="path://") continue;
      $paths[] = paths_merge(ROOT_PATH, $path);//?
    }

    foreach($config->classes->iterate("class") as $class) {
      $class_name = $class['name'];

      $class_path = exyks_paths::resolve($class['path']);
      classes::register_class_path($class_name, $class_path);
    }

    $exts = pick($config->classes['exts'], $config->paths['exts']);

    $call_init = ((string)$config->classes['call_init']) != 'false';

    classes::extend_include_path($paths);
    classes::activate($exts); //it's okay to activate again, autoload seems to be smart enough
    classes::call_init($call_init);
  }


  static function fatality($fatality_key, $details=false, $render_mode="html"){
    if($details) error_log("[FATALITY] $details");
    if(PHP_SAPI == "cli" || SITE_STANDALONE) die ("==== Fatality $fatality_key ====\r\n$details\r\n");
    header($render_mode=="jsx"?TYPE_XML:TYPE_HTML);
    $contents  = file_get_contents(RSRCS_PATH."/fatality/-top.html");
    if(yks::$get->config->is_debug())
        $contents .= "\r\n<!-- ".strtr($details,array("-->"=>"--"))."-->\r\n";
        
    $contents .= file_get_contents(RSRCS_PATH."/fatality/$fatality_key.html");
    $contents .= file_get_contents(RSRCS_PATH."/fatality/-bottom.html");
    die($contents);//finish him
  }


  private function get($key){
    if(isset($this->$key)) return $this->$key;
    if($key == "tables_xml")
      $this->$key = data::load($key);

    if($key == "types_xml")
      $this->$key = data::load($key);

    if($key == "config")
      $this->$key = config::load(self::$config_file);

    return $this->$key;
  }

  public function __get($key){ return $this->get($key);  }

}

