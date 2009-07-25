<?

abstract class __wrapper implements ArrayAccess {
  private $__base;
  protected $base_type;

  protected abstract function __extend($base);


  function __construct($base){
    $this->__base = $base;
  }

  protected function  ¤($base){
    if(is_object($base) && $base instanceof $this->base_type)
        return $this->__extend($base);
    return $base;
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
 


}