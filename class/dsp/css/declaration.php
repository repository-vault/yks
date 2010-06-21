<?

class css_declaration {
  private $property_name;
  private $values;
  private $alternative;

  function __construct($property_name){
    $this->property_name = $property_name;
    $this->values        = array();
    $this->alternative   = false;
  }

  function stack_value($value){
    $this->values []= $value;
  }
  function set_alternative(){
    $this->alternative = true;
  }
  function output(){
    $str = "";
    $join = $this->alternative?',':' ';

    $str .= $this->property_name;
    $str .= ':';
    $str .= join($join, $this->values);
    $str .= ';';
    return $str;
  }


  static function fromXML($xml){
    $tmp = new self((string)$xml['name']);
    if($xml['alternative']=='alternative') $tmp->set_alternative();
    foreach(css_parser::split_values((string)$xml) as $value)
        $tmp->stack_value($value);
    return $tmp;
  }

  function outputXML(){
    $alternative = $this->alternative ? "alternative='alternative'":"";
    $str = "<rule name=\"{$this->property_name}\" $alternative>";
    $str .= join(' ', $this->values);
    $str .= "</rule>";
    return $str;
  }
}