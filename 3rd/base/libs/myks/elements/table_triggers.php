<?php

/**
*  A myks_trigger collection group against a table ref
*/

class myks_table_triggers extends table_collection {
  protected $collection_type = "myks_trigger";
  protected $drops = array();
  protected $fn_drops = array();


  function alter_def(){
    $ret  = parent::alter_def();
    if($this->drops) {
      foreach($this->drops as $trigger_name)
        $ret[] = "DROP TRIGGER \"$trigger_name\" ON {$this->table->name['safe']}";
    }
    foreach($this->fn_drops as $procedure){
        $ret = array_merge($ret, $procedure->delete_def());
    }
    return $ret;
  }

  function modified(){
    return parent::modified()
        || count($this->drops)
        || count($this->fn_drops);
  }

  function sql_infos(){
    parent::sql_infos();

    //look for droppable triggers
    $verif = array(
        'event_object_table'  => $this->table->name['name'],
        'event_object_schema' => $this->table->name['schema'],
        "trigger_name NOT LIKE 'rtg_%'",
        "trigger_name NOT LIKE 'bucardo_%'", // never delete any bucardo related trigger
    );


    sql::select("zks_information_schema_ttriggers", $verif);
    $elements = array();
    foreach(sql::brute_fetch() as $line)
        $elements[] = sql::resolve("{$line['trigger_schema']}.{$line['trigger_name']}");

    $this->drops = array();
    foreach($elements as $element) {
        if($this->contains($element['hash'])) continue;
        $this->drops[] = $element['name'];
    }


    //look for dropptable trigger' functions (unattached)

    $procedures = procedure::sql_search(
        "tg_{$this->table->name['name']}%",
        $this->table->name['schema'],
        'trigger');

    foreach($procedures as $proc_name=>$procedure){
        $attached = false;
        foreach($elements as $element){
            $mask = "tg_{$element['name']}";
            $attached |= strpos($proc_name, $mask)!==false;
        }
        if($attached) continue;
        $this->fn_drops[$proc_name] = $procedure;
    }

  }

}
