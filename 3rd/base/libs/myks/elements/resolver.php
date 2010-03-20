<?php

abstract class base_type_resolver {
  protected $trans;

  function convert($type, $way, $ns = "base"){
    $trans = $this->trans[$ns][$way];
    return isset($trans[$type])?$trans[$type]:$type;
  }

  function feed($trans, $ns = "base"){
    $this->trans[$ns] = $trans;
  }

  function register($way, $types, $ns = "base"){
    if(!$this->trans[$ns][$way]) $this->trans[$ns][$way] = array();
    $this->trans[$ns][$way] = array_merge($this->trans[$ns][$way], $types);
    $this->feed($this->trans[$ns], $ns);
  }

  function __construct(){
    $this->feed(array());
  }
}