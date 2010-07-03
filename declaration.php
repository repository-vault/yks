<?

class css_declaration extends ibase  {
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

  function set_value($value, $i = null){
    if(is_null($i)) $this->values = array($value);
    else $this->values[$i] = $value;
  }

  function get_values(){
    return $this->values;
  }

  function set_alternative(){
    $this->alternative = true;
  }

  function __toString(){
    $join = $this->alternative?',':' ';
    return join($join, $this->values);
  }

  function output(){
    $str = "{$this->property_name}:{$this};";
    return $str;
  }

  function get_name(){
    return $this->property_name;
  }

  function outputXML(){
    $alternative = $this->alternative ? "alternative='alternative'":"";
    $str = "<rule {$this->uuid} name=\"{$this->property_name}\" $alternative>";
    foreach($this->values as $value)
        $str .= "<val>$value</val>";
    $str .= "</rule>";
    return $str;
  }
}
