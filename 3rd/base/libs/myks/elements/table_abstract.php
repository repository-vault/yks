<?php

class table_abstract extends myks_installer {

  protected $table;
  protected $table_keys;
  protected $table_fields;
  protected $abstact_xml;

  protected $view;
  protected $triggers;
  protected $procedures;


  function modified(){
    $modified = false;
    if($this->view)       $modified |= $this->view->modified();
    if($this->procedures) $modified |= $this->procedures->modified();

    foreach($this->triggers as $triggers)
        $modified |= $triggers->modified();
    return $modified;
  }

  function get_name(){
    return $this->table->get_name();
  }

  function alter_def(){
    $ret = array();
    if($this->procedures) $ret = array_merge($ret, $this->procedures->alter_def());
    if($this->view) $ret = array_merge($ret, $this->view->alter_def());

    foreach($this->triggers as $triggers)
      $ret = array_merge($ret, $triggers->alter_def());
    return $ret;
  }

  function xml_infos(){
    if($this->view) $this->view->xml_infos();
    if($this->procedures) $this->procedures->xml_infos();

    foreach($this->triggers as $triggers)
      $triggers->xml_infos();
  }

  function sql_infos(){
    if($this->view) $this->view->sql_infos();
    if($this->procedures) $this->procedures->sql_infos();
    foreach($this->triggers as $triggers)
      $triggers->sql_infos();
  }

  function delete_def(){
    $ret = array();
    if($this->procedures) $ret = array_merge($ret, $this->procedures->delete_def());
    if($this->view) $ret = array_merge($ret, $this->view->delete_def());

    foreach($this->triggers as $triggers)
      $ret = array_merge($ret, $triggers->delete_def());
    return $ret;
  }

}
