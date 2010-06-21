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


  function outputXML(){
    $alternative = $this->alternative ? "alternative='alternative'":"";
    $str = "<rule name=\"{$this->property_name}\" $alternative>";
    $str .= join(' ', $this->values);
    $str .= "</rule>";
    return $str;
  }
}