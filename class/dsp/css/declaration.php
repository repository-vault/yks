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

}