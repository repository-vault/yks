<?php

//abstract table_collection

abstract class table_collection extends myks_collection {
  protected $collection_type;
  protected $table;

    //can only be used with a non specific constructor
  function __construct($table, $elements_collection = array()){
    if(!$this->collection_type)
        throw new Exception("Invalid collection definition type ".get_class($this));

    $this->table = $table;
    $tmp = $this->collection_type;

    foreach($elements_collection as $element_xml) {
        $element = new $tmp($this->table, $element_xml);
        $this->stack($element);
    }
  }


}

