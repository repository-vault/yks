<?php

  /**	Myks_gen by 131 for Exyks distributed under GPL V2
    this class export the basic field SQL definition from a myks_xml structure
  */

class mykse extends mykse_base {

  function int_node(){

    $sizes=array(
        'mini'    => 'smallint',
        'small'   => 'smallint',
        'int'     => 'integer',
        'big'     => 'integer',
        'giga'    => 'bigint',
        'float'   => 'double precision',
        'decimal' => 'float(10,5)',
    );$type = $sizes[(string)$this->mykse_xml['size']];
    if($this->birth) {
        $table_name = $this->table->get_name();
        $field_name = $this->field_def['Field'];
        $this->field_def["Default"] = "auto_increment('$field_name','{$table_name['name']}')";
    }

    $this->field_def["Type"] = $type;
  }


  function default_value($type){

    if( $this->field_def['Default']==null && isset($this->mykse_xml['default'])){
        $this->field_def['Default']=(string)$this->mykse_xml['default'];
        if($this->field_def['Default']=="unix_timestamp()"){
           //$this->field_def['Default']=0;
           //die("HW");
        }
    }
  }

  protected function get_def(){
    if($this->mykse_xml == "sql_timestamp") {
        $this->field_def["Type"]="timestamptz";
    }else return parent::get_def();
  }

  protected function resolve($type){
    if($type=="sql_timestamp") {
        $this->mykse_xml = $type;
        return $this;
    }else return parent::resolve($type);
  }


  function enum_node(){
    $type = (string)$this->type.'_enum';

    $set=((string)$this->mykse_xml['set'])=='set';
    if($set)$type .='[]';

    $this->field_def["Type"] = $type;
  }


  function linearize(){
    $str="`{$this->field_def['Field']}` {$this->field_def['Type']}";
    return $str;
  }

}
