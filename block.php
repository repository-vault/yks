<?
/** http://doc.exyks.org/wiki/Source:ext/css **/

class css_block extends ibase {

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

  function replaces_statement($from, $to){
    $key = array_search($from, $this->statements, true);
    if($key === false) return;
    $to->set_parent($this);
    $this->statements[$key] = $to;
  }

  function stack_statement($statement){
    $statement->set_parent($this);
    $this->statements[] = $statement;
  }

  function stack_at($statement){
      //put at rules before other statements..
    foreach(array_values($this->statements) as $i=>$tmp)
        if(!is_a($tmp, 'at_rule') || $tmp->keyword != 'import')
            break;
    $statement->set_parent($this);
    array_splice($this->statements, $i, 0, array($statement));
  }

  function output(){
    $str = "";
    foreach($this->statements as $statement)
        $str.=$statement->output();
    if($this->embraced)
        $str = "{{$str}}";
    return $str;
  }

  function remove_child($child){
    $i = array_search($child, $this->statements, true);
    if($i !== false) unset($this->statements[$i]);
  }


  function outputXML(){
    $exposed = $this->embraced ? "exposed='exposed'":"";
    $path    = $this->file_path ? "path=\"{$this->file_path}\"":"";
    $str = "<style {$this->uuid} $exposed $path>";
    foreach($this->statements as $statement)
        $str.=$statement->outputXML();

    $str .= "</style>";
    return $str;
  }
}
