<?


class XHTMLElement extends SmartXML {

  function xpathElement($query, $where = "current") { //where could be [current]|end
    return $where($this->xpath($query));
  }

  function getElementById($id){
    return $this->xpathElement("//*[@id='$id']");
  }

  function getElementsByTagName($tag){
    $list = $this->xpath("descendant-or-self::$tag");
    return $list;
  }

  function getParent($tag=false){
    return $this->xpathElement($tag?"ancestor::$tag":"..", "end");
  }
  function nextSibling(){
    return $this->xpathElement("following-sibling::*");
  }
  function previousSibling(){
    return $this->xpathElement("preceding-sibling::*", "end");
  }
}


