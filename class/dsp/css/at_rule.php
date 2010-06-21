<?



class at_rule {
    //rule is @[keyword] [expression];|{

  private $at_keyword;
  private $at_expression = null;
  private $at_statements = null;

  public function __construct($keyword){
    $this->at_keyword = $keyword;
    $this->at_expression = array();
  }

  public function stack_expression($expression){
    $this->at_expression []= $expression;
  }

  public function set_block($block){
    $this->at_statements = $block;
  }

  public function output(){
    $str  = "@{$this->at_keyword} ";
    if($this->at_expression)
        $str .= join(" ", $this->at_expression);

    if(!is_null($this->at_statements)) {
        $str .= $this->at_statements->output();
    } else $str .= ";";

    return $str;
  }

  static function fromXML($xml){
    $tmp = new self((string)$xml['keyword']);
    foreach(css_parser::split_values((string)$xml->expression) as $value)
        $tmp->stack_expression($value);
    if($xml->style)
        $tmp->set_block(css_block::fromXML($xml->style));
    return $tmp;
  }

  function outputXML(){
                   
    $str = "<atblock keyword=\"{$this->at_keyword}\">";
    if($this->at_expression)
        $str .= "<expression>".join(" ", $this->at_expression)."</expression>";

    if(!is_null($this->at_statements))
        $str .= $this->at_statements->outputXML();
    $str.="</atblock>";
    return $str;
  }

}
