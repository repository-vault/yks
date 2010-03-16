<?php


class _mykse extends _sql_base {
  function __construct($from){
    $manager = $this->manager;


    parent::__construct($from);
  }

  function get_table_xml() {
    $table_name  = $this->sql_table; 
    return $this->table_xml = yks::$get->tables_xml->$table_name;
  }

  function get_fields($keys = false){
    return fields($this->table_xml, $keys);
  }


  function sql_validate($data, $fields = array()){
    $data = mykses::validate($data, $this->table_xml);
    if($fields)
        $data = array_intersect_key($data, array_flip($fields));
    return $data;
  }

  function format_output(){
    $out=array();
    foreach($this->fields as $field_name=>$field_type)
        $out[$field_name] = mykses::value($field_type, $this->$field_name);
    return $out;

  }
}
