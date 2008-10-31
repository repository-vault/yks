<?


class XHTMLElement extends simpleXmlElement {
  function getElementById($id){
    return current($this->xpath("//*[@id='$id']"));
  }

  function getElementsByTagName($tag){
    $list = $this->xpath("descendant-or-self::$tag");
    return $list;
  }

  function getParent($tag=false){
    return end($this->xpath($tag?"ancestor::$tag":".."));
  }

}


