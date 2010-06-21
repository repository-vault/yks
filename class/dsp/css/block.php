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


  function outputXML(){
    $exposed = $this->embraced ? "exposed='exposed'":"";
    $str = "<style $exposed>";
    foreach($this->statements as $statement)
        $str.=$statement->outputXML();

    $str .= "</style>";
    return $str;
  }
}