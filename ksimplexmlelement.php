<?

class KsimpleXMLElement implements ArrayAccess, IteratorAggregate, Countable {

  private $name;
  private $parent;
  private $children; //splat
  private $attrs;
  private $contents; //no mixed contents (string)

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

  protected function replace($element, $old){
    $element->name = $old->name;
    foreach($this->children as &$n) {
        if($n===$old) $n=$element; 
    }
    return $element;
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

    //retrieve the node matching a path (or create it)
  public function search($path, $autocreate = false){
    if(!$path)
        return null;

    $className = get_class($this);
    $tmp = $this->retrieve($path);
    if($autocreate && !$tmp)
        return $this->adopt(new $className($path));
    return $tmp;
  }
    //return an iterator in all case - usefull with foreach(
  public function iterate($path){
    $ret = $this->search($path);
    return $ret ? $ret : array();
  }

  public function set($value){ //to myself
    $this->contents = $value;
  }

  public function __get($key) {
	  return $this->search($key, true);
  }

  public function __set($key, $value) {
	  return $this->search($key, true)->set($value);
  }

    //******** Interfaces *************************
  public function offsetExists($key){ 
    if(is_numeric($key))
        throw new Exception("Not implemented");
    return isset($this->attrs[$key]);
  }

  public function offsetUnset($key){ throw new Exception("Not implemented"); }

  public function offsetGet($key){
    if(is_numeric($key))
        return $this->retrieve_siblings($key);
	return isset($this->attrs[$key]) ? $this->attrs[$key] : null;
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

  public function asXML($tmp = false){
    $str = "<{$this->name}";
    if(count($this->attrs)) $str .= ' '.self::join_args($this->attrs);

    if($this->is_empty())
        return "$str/>";
    $str .= ">";

    if(is_null($this->contents)) foreach($this->children() as $children)
        $str .= $children->asXML($tmp);
    else
        $str .= htmlspecialchars($this->contents);
    $str.= "</{$this->name}>";

    return $str;
  }

    //***********************************

  private static function join_args($attrs){
    $ret = array();
    foreach($attrs as $k=>$v)$ret[] = "$k=\"".htmlspecialchars($v) ."\"";
    return join(' ', $ret);
  }


}


