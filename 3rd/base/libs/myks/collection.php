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


abstract class myks_collection extends myks_parsed {
  private $elements = array();

  protected function stack($element){
    $key = $element->hash_key();
    $this->elements[$key] = $element;
  }


  function xml_infos(){
    foreach($this->elements as $element)
        $element->xml_infos();
  }

  function sql_infos(){
    foreach($this->elements as $element)
        $element->sql_infos();
  }

    //contains($element);/contains($hash_key);
  function contains($search){
    if(is_string($search))
        return isset($this->elements[$search]);
    return in_array($search, $this->elements);
  }

  function modified(){
    $ret = false;
    foreach($this->elements as $element)
        $ret |= $element->modified();
    return $ret;
  }


  function alter_def(){
    $ret = array();
    foreach($this->elements as $element)
        $ret = array_merge($ret, $element->alter_def());
    return $ret;
  }

}