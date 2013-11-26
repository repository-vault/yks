<?
/** http://doc.exyks.org/wiki/Source:ext/css **/


class css_declarations extends ibase  {

  private $declarations = array();

  function __construct(){
    $this->declarations = array();
  }


  function get_rules(){
    return $this->declarations;
  }

  function stack_declaration($declaration){
    $declaration->set_parent($this);
    $this->declarations[] = $declaration;
  }

  protected function remove_child($child){
    $i = array_search($child, $this->declarations, true);
    if($i !== false) unset($this->declarations[$i]);
  }

  function output(){
    $str = '{';
    end($this->declarations);
    $last = key($this->declarations);

    foreach($this->declarations as $i=>$declaration) {
        $is_last = $last == $i;
        $tmp = $declaration->output();
        $str .= $is_last ? substr($tmp, 0, -1) : $tmp;
    }
    $str .= '}';
    return $str;
  }

  function outputXML(){
    $selector = specialchars_encode($this->selector);
    $str = "<declarations {$this->uuid}>";
    foreach($this->declarations as $declaration)
        $str .= $declaration->outputXML();
    $str .= "</declarations>";
    return $str;    
  }
}
