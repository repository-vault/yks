<?

abstract class _sql_base_unserialize_wrapper   implements ArrayAccess {
  protected $data;
  function __get($key){
   return $this->data[$key]; 
  }
  

  function offsetExists ($key){ return isset($this->data[$key])||isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset,$value){}
  function offsetUnset($key){unset($this->data[$key]); }
}