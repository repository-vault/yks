<?php


class yks
{
  static public $get;

  static function init(){
    self::$get = new yks();
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

  private function __get($key){ return $this->get($key);  }


} yks::init();




