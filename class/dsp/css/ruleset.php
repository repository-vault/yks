<?

class css_ruleset {
  private $selector; //string for now
  private $declarations;

  function __construct($selector) {
    $this->selector = $selector;
    $this->declarations = array();
  }

  function stack_declaration($declaration){
    $this->declarations[] = $declaration;
  }
  
  function output(){
    $str = "";
    $str .= $this->selector;

    //declarations
    $str .= '{';
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

  static function fromXML($xml){
    $tmp = new self((string)$xml['selector']);
    foreach($xml->rule as $rule)
        $tmp->stack_declaration(css_declaration::fromXML($rule));
    return $tmp;
  }


  function outputXML(){
    $selector = specialchars_encode($this->selector);
    $str = "<ruleset selector=\"$selector\">";
    foreach($this->declarations as $declaration)
        $str .= $declaration->outputXML();
    $str .= "</ruleset>";
    return $str;    
  }
}