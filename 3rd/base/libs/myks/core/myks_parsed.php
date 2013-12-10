<?php

    //right click => refactor => rename
abstract class myks_parsed {
  abstract function sql_infos();
  abstract function xml_infos();
//return bool
  abstract function modified();
//return array($queries);
  abstract function alter_def();

  protected $sql_def = array();
  protected $xml_def = array();


  function __get($key){
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();

    throw new Exception("Unauthorized access to $key");

  }


  function check($force = false){
    $this->xml_infos();

    if($force)
        $this->sql_def = array();
    else $this->sql_infos();

    if($this->modified())
        return $this->alter_def();
    return array();

  }

}
