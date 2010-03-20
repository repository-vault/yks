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
