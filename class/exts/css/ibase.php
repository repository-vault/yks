<?php

abstract class ibase  implements ArrayAccess  {

  private static $hashes;
  private $parent;

  function set_parent($parent){
    $this->parent = $parent;
  }

  //abstract function remove_child($child){ }

  function dispose(){
    $this->parent->remove_child($this);
  }

  function __get($key){
    if(method_exists($this, $getter = "get_{$key}"))
        return $this->$getter();
    return null;
  }

  function offsetExists ($key){ return isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset,$value){}
  function offsetUnset($key){ }


  function xpath($query_path){

    $xml = $this->outputXML();

    $doc = new DOMDocument("1.0", "UTF-8");
    $doc->loadXML($xml);

    $xpath = new DOMXPath($doc);
    $entries = $xpath->query($query_path);

    $rest = array();
    for($a=0; $a<$entries->length; $a++) {
        $uid = $entries->item($a)->getAttribute('uid');
        $rest[] = self::$hashes[$uid];
    }
    return $rest;
  }

  
  function get_uid(){
    return $this->uid = spl_object_hash($this);
  }

  function get_uuid(){
    self::$hashes[$this->uid] = $this;
    return "uid=\"{$this->uid}\"";
  }
}