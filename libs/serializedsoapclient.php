<?php

class SerializedSoapClient extends SoapClient {
  function __call($func, $args){
    $result = call_user_func_array( array('parent', '__call') , array($func, $args));
    return unserialize($result);
  }
}

