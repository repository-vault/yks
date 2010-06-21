<?


class css_block {

  private $statements = array();
  private $embraced   = true;
  private $file_path  = false;

  function __construct($embraced = true){
    $this->embraced = $embraced;
  }
  function set_path($file_path){
    $this->file_path = $file_path;
  }

  function get_path(){
    return $this->file_path;
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
    $path    = $this->file_path ? "path=\"{$this->file_path}\"":"";
    $str = "<style $exposed $path>";
    foreach($this->statements as $statement)
        $str.=$statement->outputXML();

    $str .= "</style>";
    return $str;
  }
}