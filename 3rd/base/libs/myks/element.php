<?php

    //right click => refactor => rename
abstract class myks_parsed {
  abstract function sql_infos();
  abstract function xml_infos();
//return bool
  abstract function modified();
//return array($queries);
  abstract function alter_def();



  function check(){
    $this->xml_infos();
    $this->sql_infos();

    if($this->modified())
        return $this->alter_def();
    return array();
    
  }

}

abstract class myks_installer extends myks_parsed {
  abstract function delete_def();
  abstract function get_name();
  function hash_key(){
    $name = $this->get_name();
    return $name['hash'];
  }
}