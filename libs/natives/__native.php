<?


abstract class __native implements ArrayAccess {
  protected $data;

  function __get($key){
    if(isset($this->data[$key]))
        return $this->data[$key];
    if(  (substr($key,0,3)=="is_" && method_exists($this, $getter = $key))
        || method_exists($this, $getter = "get_$key")
        || $this->manager && method_exists($this->manager, $getter))
        return $this->$getter();
  }

    //proxies to manager's static functions
  function __call($method, $args){
    if(!($this->manager && method_exists($this->manager, $method))) return;
    array_unshift($args, $this);
    return call_user_func_array(array($this->manager, $method), $args);
  }


  function offsetExists ($key){ return isset($this->data[$key])||isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset,$value){}
  function offsetUnset($key){ }
 
}


