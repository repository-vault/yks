<?
/** http://doc.exyks.org/wiki/Source:ext/css **/


class css_ruleset extends ibase  {
  private $selector; //string for now
  private $declarations;

  function __construct($selector) {
    $this->selector = $selector;
    $this->declarations = null;
  }

  function get_rules(){
    return $this->declarations->get_rules();
  }

  function set_declarations($declarations){
    $declarations->set_parent($this);
    $this->declarations = $declarations;
  }

  function get_selector(){
    return $this->selector;
  }

  function output(){
    $str = "";
    $str .= $this->selector;
    $str .= $this->declarations->output();
    return $str;
  }

  function outputXML(){
    $selector = specialchars_encode($this->selector);
    $str = "<ruleset {$this->uuid} selector=\"$selector\">";
    $str .= $this->declarations->outputXML();
    $str .= "</ruleset>";
    return $str;
  }
}