<?php
class _ArrayObject implements ArrayAccess {

  function offsetGet($key){ return $this->$key;}
  function offsetSet($key, $value){$this->$key = $value;}
  function offsetUnset($key){unset($this->$key); }
  function offsetExists ($key){
    return isset($this->data[$key]) ||
           isset($this->$key);
  }


  public function __get($key){
    if(isset($this->data[$key]))
        return $this->data[$key];
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();
  }

  public static function expose($obj, $visited_obj=array()){
    if(is_scalar($obj) || is_null($obj))
      return $obj;

    if(is_array($obj)) {
      $out = new self();
      foreach($obj as $k=>$v)
        $out->$k = call_user_func(__METHOD__, $v, $visited_obj);
      return $out;
    }

    if(is_object($obj)) {

      if($visited_obj[spl_object_hash($obj)])  // reference
        return $visited_obj[spl_object_hash($obj)];

      $out = new self();
      $visited_obj[spl_object_hash($obj)] = $out;
      $fx = new ReflectionObject($obj);
      foreach($fx->getProperties() as $property) {
          $property_name = $property->getName();
          $property->setAccessible(true);
          $out->$property_name = call_user_func(__METHOD__, $property->getValue($obj), $visited_obj);
      }
      return $out ;
    }

    return null;

  }
}
