<?php

class sql_parsed_table {
  private $table_name;
  private $fields;
  private $keys;

  protected $keys_name = array(
    'PRIMARY'=>"%s_pkey", 
    'UNIQUE'=>"%s_%s_%s",
    'FOREIGN'=>"%s_%s_%s",
  );

  function __construct($name){
    $this->table_name = $name;
    $this->fields = array();
    $this->keys   = array();
  }

  function field_add($field_infos){
    $this->fields[$field_infos['Field']] = $field_infos;
  }

  function key_add($type, $field, $refs=array()){
    $TYPE=strtoupper($type);
    $key_name = sprintf($this->keys_name[$TYPE], $this->table_name['name'], $field, $type);
    if($TYPE=="PRIMARY"){
        $this->keys[$key_name]['type'] = $TYPE;
        $this->keys[$key_name]['members'][$field] = $field;
    } elseif($TYPE=="UNIQUE"){
        $this->keys[$key_name]['type'] = $TYPE;
        $this->keys[$key_name]['members'] = &$this->tmp_key[$field];
        $this->tmp_key[$field][$field] = $field;
    }
  }
}


