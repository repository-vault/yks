<?php

class yks
{
  static public $get;

  static function init(){
    $host = strtolower($_SERVER['SERVER_NAME']);
    if(preg_match("#[^a-z0-9_.-]#", $host)) die("Invalid hostname");

    $config_file = CONFIG_PATH."/$host.xml";
    if(!is_file($config_file))
        die("Unable to load config file <b>".basename($config_file)."</b>");
    $GLOBALS['config'] = $config =  config::load($config_file);


    self::$get = new yks(); $paths = array();
    $paths = array(YKS_PATH."/libs");
    if($config->include_path)
        foreach(explode(PATH_SEPARATOR, $config->include_path['paths']) as $path)
            $paths[] = paths_merge(ROOT_PATH, $path);
    classes::extend_include_path($paths);
    classes::activate($config->include_path['exts']);
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
        $this->$flag = data::load($key,$args);

    return $this->$flag;
  }

  public function __get($key){ return $this->get($key);  }


} yks::init();





