<?


class XHTMLElement extends simpleXmlElement {
  function getElementById($id){
    return current($this->xpath("//*[@id='$id']"));
  }

  function getElementsByTagName($tag){
    $list = $this->xpath("descendant-or-self::$tag");
    return $list;
  }

  function getParent(){
    return current($this->xpath(".."));
  }

}


