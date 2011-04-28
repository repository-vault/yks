<?php

abstract class __wrapper implements ArrayAccess,Countable {
  private $__base;
  protected $base_type;

  protected abstract function __extend($base);


  function __construct($base){
    $this->__base = $base;
  }

  protected function  ¤($base){
        //level zero extension
    if(is_object($base) && $base instanceof $this->base_type)
        return $this->__extend($base);

        //level one (array) extension
    if(is_array($base))
        foreach($base as &$tmp)
            if(is_object($tmp) && $tmp instanceof $this->base_type)
                $tmp = $this->__extend($tmp);

    return $base;
  }

  function __toString(){
    return (string) $this->__base;
  }

  function __call($method, $args){
    $cb = array($this->__base, $method);
    return $this->¤(call_user_func_array($cb, $args));
  }

  function __get($key){
    $res = $this->__base->$key;
    $base = $res = $this->¤($res);
    return $res;
  }

  function offsetExists ($key){  }
  function offsetGet($key){ return $this->__base[$key];}
  function offsetSet($offset,$value){}
  function offsetUnset($key){ }

  function count(){
    return count($this->__base);
  }


}