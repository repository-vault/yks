<?php

class resolver extends base_type_resolver {

  function feed($trans, $ns = "base"){
    $trans['search'] = array_flip($trans['in']);
    return parent::feed($trans, $ns);
  }

  function __construct(){
    $trans = array();

    $trans['in'] = array(
        'timestamp without time zone'=>'timestamp(0)', //information_schema.routines.data_type
    ); $this->feed($trans);
  }
}