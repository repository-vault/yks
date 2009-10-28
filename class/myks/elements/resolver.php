<?php

abstract class base_type_resolver {
  protected $trans;
  function convert($type, $way){
    $trans = $this->trans[$way];
    return isset($trans[$type])?$trans[$type]:$type;
  }
  function feed($trans){
    $this->trans = $trans;
  }

  function register($way, $types){
    if(!$this->trans[$way])$this->trans[$way] = array();
    $this->trans[$way] = array_merge($this->trans[$way], $types);
    $this->feed($this->trans);
  }

  function __construct(){
    $this->feed(array());
  }
}