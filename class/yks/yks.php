<?php

class yks
{
  static public $get;
  const FATALITY_XSL_404    = "xsl_404";
  const FATALITY_XML_SYNTAX = "xml_syntax";
  const FATALITY_404        = "404";
  const FATALITY_CONFIG     = "config";
  const FATALITY_SITE_CLOSED     = "site_closed";

  static function init(){
    $host = strtolower($_SERVER['SERVER_NAME']);
    if(preg_match("#[^a-z0-9_.-]#", $host)) die("Invalid hostname");

    define('RSRCS_PATH', YKS_PATH.'/rsrcs');
    $config_file = CONFIG_PATH."/$host.xml";
    if(!is_file($config_file)) yks::fatality(yks::FATALITY_CONFIG, "$config_file not found");
    $GLOBALS['config'] = $config =  config::load($config_file);


    self::$get = new yks(); $paths = array();
    $paths = array(YKS_PATH."/libs", CLASS_PATH);
    if($config->include_path)
        foreach(explode(PATH_SEPARATOR, $config->include_path['paths']) as $path)
            $paths[] = paths_merge(ROOT_PATH, $path);
    classes::extend_include_path($paths);
    classes::activate($config->include_path['exts']);
  }

  static function fatality($fatality, $details=false, $render_mode="html"){
    if($details) error_log("[FATALITY] $details");
    header($render_mode=="jsx"?TYPE_XML:TYPE_HTML);
    $contents  = file_get_contents(RSRCS_PATH."/fatality/-top.html");
    if(DEBUG) $contents .= "\r\n<!-- ".strtr($details,array("-->"=>"--"))."-->\r\n";
    $contents .= file_get_contents(RSRCS_PATH."/fatality/$fatality.html");
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
        $this->$flag = config::$config;

    if($key == "entities")
        $this->$flag = data::load($key, $args);

    return $this->$flag;
  }

  public function __get($key){ return $this->get($key);  }
}

if(PHP_SAPI != 'cli')
    yks::init();
else {
    classes::activate();
}







