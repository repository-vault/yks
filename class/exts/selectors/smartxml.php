<?php


abstract class SmartXML extends SimpleXMLElement {

  private static $__registred_methods = array();

  function __call($method_name, $args){
    $callback = self::$__registred_methods[$method_name];
    if(!$callback) throw new Exception("Call to un implemented function $method_name");
    return call_user_func_array($callback, array_merge(array($this), $args));
  }

    // register static external method usable via __call as dynamic ones 
  static function __register($class){
    $flx = new ReflectionClass($class);
    $methods = $flx->getMethods();
    foreach($methods as $method){
        $method_name = $method->name;
        $args = $method->getParameters();
        $is_childable = $args[0]->getClass()->name == "Element";
        if(!$is_childable) continue;
        self::$__registred_methods[$method_name] = array($class, $method_name);
    }
  }







}