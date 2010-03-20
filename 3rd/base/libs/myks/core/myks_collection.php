<?php


abstract class myks_collection extends myks_parsed {
  private $elements = array();

  protected function stack($element){
    $key = $element->hash_key();
    $this->elements[$key] = $element;
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
    if(is_string($search))
        return isset($this->elements[$search]);
    return in_array($search, $this->elements);
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