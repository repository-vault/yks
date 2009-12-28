<?

class KsimpleXMLElement implements ArrayAccess, IteratorAggregate, Countable {

  private $name;
  private $parent;
  private $children; //splat
  private $attrs;
  private $contents; //no mixed contents

  public function __construct($name, $attrs = array(), $contents = null) {
    $this->name     = $name;
    $this->contents = $contents;
    $this->attrs    = $attrs;
    $this->children = array();
  }

  
  public function adopt($element){
    $element->parent = $this;
    $name = $element->getName();
    $this->children[] = $element;
    return $element;
  }

  public function asXML(){
    $str = "<{$this->name}";
    if(count($this->attrs)) $str .= ' '.mask_join(" ", $this->attrs, ATTR_MASK);

    if($this->is_empty())
        return "$str/>";
    $str .= ">";

    if(is_null($this->contents)) foreach($this->children() as $children)
        $str .= $children->asXML();
    else
        $str .= $this->contents;
    $str.= "</{$this->name}>";

    return $str;
  }

  
  private function is_empty(){
    return is_null($this->contents) && count($this->children) == 0;
  }


  public function children(){
    return $this->children; //simple copy as children is a splat
  }

  public function __toString(){
    return (string)$this->contents;
  }

  public function getName(){
    return $this->name;
  }
  
  public function attributes(){
    return $this->attrs;
  }

    //*****  internal tree browsing functions
  private function retrieve($key){
    foreach($this->children as $node)
        if($node->name == $key) return $node;
  }

  private function retrieve_siblings($index = false){
    $siblings = array();
    if(is_null($this->parent)) return $siblings;

    foreach($this->parent->children as $node) 
        if($node->name  == $this->name) $siblings[] = $node;
    
    return $index ===false ? $siblings : $siblings[$index];
  }
    //****************************************


  public function __get($key){
    $tmp = $this->retrieve($key);
    if(!$tmp)
        return $this->adopt(new self($key));
    return $tmp;
  }
  
  function __set($key, $value){ //from my parent
    $this->__get($key)->set($value);
  }

  public function set($value){ //to myself
    $this->contents = $value;
  }

  
    //******** Interfaces *************************
  public function offsetExists($key){ throw new Exception("Not implemented");}
  public function offsetUnset($key){ throw new Exception("Not implemented"); }

  public function offsetGet($key){
    if(is_numeric($key))
        return $this->retrieve_siblings($key);
    return $this->attrs[$key];
  }
  public function offsetSet($key, $value){
    if(is_numeric($key))
        throw new Exception("Not implemented");
    $this->attrs[$key] = $value;
  }

  public function getIterator() {
    $siblings = $this->retrieve_siblings();
    return new ArrayIterator($siblings);
  }

  public function count(){
    return count($this->retrieve_siblings());
  }
    //*********************************************

}


