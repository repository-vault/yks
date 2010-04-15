<?php

class view extends view_base  {

  private $rules; //rules only exists in this driver
  private $privileges;

  function __construct($view_xml){
    parent::__construct($view_xml);

    $this->privileges  = new privileges($this, $view_xml->grants, 'view');
    $this->rules       = new rules($this, $view_xml->rules->xpath('rules/rule'), 'view');
  }

  function sql_infos(){
    parent::sql_infos();
    $this->privileges->sql_infos();
    $this->rules->sql_infos();
  }

  function xml_infos(){
    parent::xml_infos();
    $this->privileges->xml_infos();
    $this->rules->xml_infos();
  }


  function modified(){
    $res  = parent::modified();
    $res |= $this->privileges->modified();
    $res |= $this->rules->modified();
    return $res;
  }

  function alter_def(){
    return array_merge(
        parent::alter_def(),
        $this->privileges->alter_def(),
        $this->rules->alter_def()
    );
  }
}
