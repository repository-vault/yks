<?
class _ArrayObject implements ArrayAccess {
  function offsetExists ($key){ return isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($key, $value){$this->$key = $value;}
  function offsetUnset($key){unset($this->$key); }
}