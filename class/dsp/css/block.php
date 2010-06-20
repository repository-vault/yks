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
}