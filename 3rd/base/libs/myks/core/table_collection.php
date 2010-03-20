<?php


abstract class table_collection extends myks_collection {
  protected $collection_type;
  protected $table;

  function __construct($table, $triggers_xml){
    if(!$this->collection_type)
        throw new Exception("Invalid collection definition type ".get_class($this));

    $this->table = $table;
    $tmp = $this->collection_type;
    foreach($triggers_xml->trigger as $trigger_xml) {
        $trigger = new $tmp($this->table, $trigger_xml);
        $this->stack($trigger);
    }
  }


}

