<?
/** http://doc.exyks.org/wiki/Source:ext/css **/

class at_rule extends ibase {
    //rule is @[keyword] [expression];|{

  private $at_keyword;
  private $at_expression = array();
  private $at_statements = null; //statement is block|declarations (font-face style)

  public function __construct($keyword, $expression = false){
    $this->at_keyword = $keyword;
    if($expression)
        $this->stack_expression($expression);
  }

  public function stack_expression($expression){
    $this->at_expression []= $expression;
  }

  public function set_expression($expression) {
    $this->at_expression = array($expression);
  }

  public function get_keyword(){
    return $this->at_keyword;
  }

  public function set_block($block){
    $block->set_parent($this);
    $this->at_statements = $block;
  }

  public function set_declarations($declarations){
    $declarations->set_parent($this);
    $this->at_statements = $declarations;
  }

  public function get_expressions(){
    if($this->at_expression)
        return join(" ", $this->at_expression);
    return '';
  }

  public function output(){
    $str  = "@{$this->at_keyword} ";
    $str .= $this->expressions;

    if(!is_null($this->at_statements))
        $str .= $this->at_statements->output();
    else $str .= ";";

    return $str;
  }



  function outputXML(){
    $str = "<atblock {$this->uuid} keyword=\"{$this->at_keyword}\">";
    if($this->at_expression)
        $str .= "<expression>".join(" ", $this->at_expression)."</expression>";

    if(!is_null($this->at_statements))
        $str .= $this->at_statements->outputXML();
    $str.="</atblock>";
    return $str;
  }

}
