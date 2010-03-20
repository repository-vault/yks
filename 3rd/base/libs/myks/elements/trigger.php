<?php


class myks_triggers extends table_collection {
  protected $collection_type = "myks_trigger";
  protected $drops = array();
  protected $fn_drops = array();


  function alter_def(){
    $ret  = parent::alter_def();
    if($this->drops) {
      foreach($this->drops as $trigger_name)
        $ret[] = "DROP TRIGGER `$trigger_name` ON {$this->table['safe']}";
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
        'event_object_table'  => $this->table['name'],
        'event_object_schema' => $this->table['schema'],
    );

    sql::select("information_schema.triggers", $verif);
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
        "tg_{$this->table['name']}%",
        $this->table['schema'],
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


class myks_trigger extends myks_installer {

  private  $element_name;

/**
* event_object_schema
* event_object_table
* event_manipulation DELETE INSERT
* action_statement EXECUTE PROCEDURE fn_trigger_website()
* action_orientation ROW/STATEMENT
* condition_timing BEFORE/AFTER
*/

  private $sql_def; 
  private $xml_def;
  private $procedure;
  private $table_name;

  function __construct($table, $xml){
    $this->table_name = $table;

    $this->xml_def = array(
      'event_object_schema' => $this->table_name['schema'],
      'event_object_table'  => $this->table_name['name'],
      'event_manipulation'  => strtoupper($xml['on']),
      'condition_timing'    => strtoupper($xml['timing']),
      'action_orientation'  => strtoupper($xml['orientation']),
    );
    $query                = (string)$xml;
    $trigger_hash         = substr(md5(json_encode($this->xml_def).$query), 0, 5);

    $element_name         = "{$this->table_name['name']}_{$trigger_hash}";
    $element_sql_name     = "{$this->table_name['schema']}.$element_name";
    $this->element_name   = sql::resolve($element_sql_name);

    $proc_sql_name        = "{$this->element_name['schema']}.tg_{$this->element_name['name']}";
    $this->xml_def['proc_name'] = sql::resolve($proc_sql_name);

    $proc = array(
        'type' => 'trigger',
        'def'  => $query
    );
    $this->procedure = new procedure($this->xml_def['proc_name'], $proc);
  }

  function get_name(){
    return $this->element_name;
  }

  function delete_def(){
    $queries = array();
    $queries[] =  "DROP TRIGGER {$this->element_name['name']} ".
                   "ON {$this->table_name['safe']}";
    return $queries;
  }

  function alter_def(){
    $todo = array();
    if(!$this->modified())
        return $todo;

    $todo = array_merge(
        $todo,
        $this->procedure->alter_def()
    );

    if($this->sql_def)
        $todo = array_merge($todo, $this->delete_def());

    $query  = "CREATE TRIGGER {$this->element_name['name']}".CRLF; //dont use safe here..
    $query .= $this->xml_def['condition_timing'].' '. $this->xml_def['event_manipulation'].CRLF;
    $query .= "ON {$this->table_name['safe']} ".CRLF;
    $query .= "FOR EACH ".$this->xml_def['action_orientation'].CRLF;
    $query .= "EXECUTE PROCEDURE {$this->xml_def['proc_name']['safe']}()";


    $todo []= $query;
    return $todo;

  }

  function modified(){
    return $this->sql_def != $this->xml_def
        || $this->procedure->modified();
  }


  function sql_infos(){
    $verif = array(
        'trigger_name'   => $this->element_name['name'],
        'trigger_schema' => $this->element_name['schema'],
    );
    $data = sql::row("information_schema.triggers", $verif);
    $keys = array('event_object_schema', 'event_object_table', 'event_manipulation', 'action_orientation', 'condition_timing');

    $proc_name  = preg_reduce("#EXECUTE PROCEDURE (.*)\(#", $data['action_statement']);

    $data = array_intersect_key($data, array_flip($keys));
    if($proc_name) $data['proc_name'] = sql::resolve("{$this->element_name['schema']}.$proc_name");

    $this->procedure->sql_infos();

    $this->sql_def = $data;
  }

  function xml_infos(){
    $this->procedure->xml_infos();
    return;
  }



}
