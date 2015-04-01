<?php

class data_headers {
  var $cols;
  var $pos=0;
  function __construct($array){
    $keys     = array_keys($array);
    $numerics = array_filter($keys, 'is_numeric');
    foreach($array as $key=>$value)
        $this->adopt($key, $value);
  }

  function adopt($key, $value){
    $key = is_numeric($key)?$value['key']:$key;
    $value = is_string($value)?compact('value'):$value;
    $value['key'] = $key;
    $this->inject($key, $value);
  }

  function inject($key, $value, $position='queue'){
    $pos = $this->next_pos();
    $this->cols_order[$pos] = $key;
    $this->cols[$key] = $value;
  }

  function next_pos(){
    return $this->pos++;
  }

  function __toString(){
    $data = array();
    foreach($this->cols_order as $key)
        $data[] = $this->cols[$key]['value'];

    return join("\n", $data);
  }

  function replace($key0, $key1){
    $old_pos0 = array_search($key0, $this->cols_order);
    $old_pos1 = array_search($key1, $this->cols_order);
    if($old_pos0 === false || $old_pos1 === false) return false;
    $this->cols_order[$old_pos0] = $key1;
    unset($this->cols_order[$old_pos1]);
    return true;
  }


  function feed_line($data){
    $str = '';
    foreach($this->cols_order as $col_key){
        $str.= isset($data[$col_key]) ? $data[$col_key] : "<td>&XML_EMPTY;</td>";
    }
    return $str;
  }

  function feed_tr($data, $mask="<tr class='line_pair'>%s</tr>"){
    return sprintf($mask, $this->feed_line($data));
  }
}
