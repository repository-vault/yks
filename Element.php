<?

class Element extends XHTMLElement {

  function getElements($expression){
    $expression  = explode(',', $expression);
    $local = array(); $items=array();
    foreach($expression as $i=>$selector) {
        $elements = Selectors_Utils::search($this, $selector, $local);
        $items = array_merge($items, $elements);
    } return $items;
  }
  function getElement($expression){
    return current($this->getElements($expression));
  }
  function match($selector) {
    if(!$selector || $selector == $this) return true;
    list($tag, $id) = Selectors_Utils::parseTagAndID($selector);
    if(!Selectors_Filters::ById($this, $id) || !Selectors_Filters::byTag($this, $tag)) return false;
    $parsed = Selectors_Utils::parseSelector($selector);
    return $parsed ? Selectors_Utils::filter($this, $parsed, array() ):true;
  }

  function clean(){
    $c=0; $name = $this->getName();
    foreach ($this->getParent()->$name as $node) {
        if($node == $this) {
            unset($this->getParent()->{$name}[$c]);
            return false;
        } $c++;
    }
  }

  function get($key){
    if($key=="text") {
        $str = "";
        return dom_import_simplexml($this)->textContent;
    }else {

        return (string)$this[$key];

    }

  }
}


