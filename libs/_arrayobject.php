<?
class _ArrayObject implements ArrayAccess {

  function offsetExists ($key){ return isset($this->$key); }
  function offsetGet($key){ return $this->$key;}
  function offsetSet($key, $value){$this->$key = $value;}
  function offsetUnset($key){unset($this->$key); }



  protected function __get($key){
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();
  }

  public static function expose($obj){
    if(is_scalar($obj) || is_null($obj)) 
      return $obj;

    if(is_array($obj)) {
      $out = new self();
      foreach($obj as $k=>$v)  
        $out->$k = call_user_func(__METHOD__, $v);
      return $out;
    }

    if(is_object($obj)) {
      $out = new self();
      $fx = new ReflectionObject($obj);
      foreach($fx->getProperties() as $property) {
          $property_name = $property->getName();
          $property->setAccessible(true);
          $out->$property_name = call_user_func(__METHOD__, $property->getValue($obj));
      }
      return $out ;
    }

    return null;

  }
}