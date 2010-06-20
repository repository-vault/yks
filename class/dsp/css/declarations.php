<?

class css_declarations_block {
  private $declarations;

  function __construct(){
    $this->declarations = array();
  }

  function stack_declaration($declaration){
    $this->declarations[] = $declaration;
  }

  function output(){
    $str = '{'; end($this->declarations);
    $last = key($this->declarations);

    foreach($this->declarations as $i=>$declaration) {
        $is_last = $last == $i;
        $tmp = $declaration->output();
        $str .= $is_last ? substr($tmp, 0, -1) : $tmp;
    }
    $str .= '}';
    return $str;
  }
}