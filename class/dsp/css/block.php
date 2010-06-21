<?


class css_block {

  private $statements = array();
  private $embraced = true;

  function __construct($embraced = true){
    $this->embraced = $embraced;
  }

  function stack_statement($statement){
    $this->statements[] = $statement;
  }

  function output(){
    $str = "";
    foreach($this->statements as $statement)
        $str.=$statement->output();
    if($this->embraced)
        $str = "{{$str}}";
    return $str;
  }


  static function fromXML($xml){
    $embraced = $xml['exposed']=='exposed';
    $tmp = new self($embraced);
    foreach($xml->children() as $child) {
        if($child->getName() == "style")
            $tmp->stack_statement(css_block::fromXML($child));
        elseif($child->getName() == "ruleset")
            $tmp->stack_statement(css_ruleset::fromXML($child));
        elseif($child->getName() == "atblock")
            $tmp->stack_statement(at_rule::fromXML($child));

    }
    return $tmp;
  }

  function outputXML(){
    $exposed = $this->embraced ? "exposed='exposed'":"";
    $str = "<style $exposed>";
    foreach($this->statements as $statement)
        $str.=$statement->outputXML();

    $str .= "</style>";
    return $str;
  }
}