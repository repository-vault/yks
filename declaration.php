<?
/** http://doc.exyks.org/wiki/Source:ext/css **/


class css_declaration extends ibase  {
  private $property_name;
  private $values; //(as values_group=>values)

  function __construct($property_name){
    $this->property_name = $property_name;
    $this->values        = array();
  }

  function stack_value($value, $gid = 0){
    $this->values[$gid] []= $value;
  }

  function set_value($value, $i = null, $gid = 0){
    if(is_null($i)) $this->values = array(array($value));
    else $this->values[$gid][$i] = $value;
  }


  function get_values($gid = 0){
    return $this->values[$gid];
  }

  function get_values_groups(){
    return $this->values;
  }

  function __toString(){
    $tmp = $this->values;
    foreach($tmp as &$values) $values = join(' ', $values);
    return join(',', $tmp);
  }

  function output(){
    $str = "{$this->property_name}:{$this};";
    return $str;
  }

  function get_name(){
    return $this->property_name;
  }

  function outputXML(){
    $str = "<rule {$this->uuid} name=\"{$this->property_name}\">";
    foreach($this->values as $gid=>$values) {
        $str .= "<valuegroup>";
        foreach($values as $value)
            $str .= "<val>$value</val>";
        $str.="</valuegroup>";
    } $str .= "</rule>";
    return $str;
  }
}
