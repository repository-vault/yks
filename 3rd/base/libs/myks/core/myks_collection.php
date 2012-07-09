<?php


abstract class myks_collection extends myks_parsed {
  public $elements = array(); //trace

  function stack($element, $hash = null){
    $key = pick($hash, $element->hash_key());
    $this->elements[$key] = $element;
  }

  function retrieve($key){
    return $this->elements[$key];
  }

  function xml_infos(){
    foreach($this->elements as $element)
        $element->xml_infos();
  }

  function sql_infos(){
    foreach($this->elements as $element)
        $element->sql_infos();
  }

    //contains($element);/contains($hash_key);
  function contains($search){
    if($search instanceof myks_installer)
        $search = $search->hash_key();
    $search = (string) $search;
    return isset($this->elements[$search]);
  }

  function modified(){
    $ret = false;
    foreach($this->elements as $element)
        $ret |= $element->modified();
    return $ret;
  }


  function alter_def(){
    $ret = array();
    foreach($this->elements as $element)
        $ret = array_merge($ret, $element->alter_def());
    return $ret;
  }

}