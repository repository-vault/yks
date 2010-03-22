<?php

/**
*  No verification of proc_name schema/beware
*/

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
  private $table_ref;

  private $procedure;
  private $procedure_name;


  function __construct($table, $xml){
    //table ref as we might have no informations (other than its name) on remote table
    $this->table_ref = pick($table->name, $table);

    $this->xml_def = array(
      'event_object_schema' => $this->table_ref['schema'],
      'event_object_table'  => $this->table_ref['name'],
      'event_manipulation'  => strtoupper($xml['on']),
      'condition_timing'    => pick(strtoupper($xml['timing']), 'AFTER'),
      'action_orientation'  => pick(strtoupper($xml['orientation']), 'ROW'),
    );
    $query                = (string)$xml;
    $trigger_hash         = substr(md5(json_encode($this->xml_def).$query), 0, 5);


    $element_name         = pick((string)$xml['name'],
                              "{$this->table_ref['name']}_{$trigger_hash}");
    $element_sql_name     = "{$this->table_ref['schema']}.$element_name";
    $this->element_name   = sql::resolve($element_sql_name);

    $proc_name = (string) $xml['procedure'];
    $exists    = (bool)$proc_name;


    $proc_name   = pick($proc_name,
                  "{$this->element_name['schema']}.tg_{$this->element_name['name']}");

    $this->xml_def['proc_name'] = $proc_name;
    $this->procedure_name       = sql::resolve($proc_name);

    if($exists)
        return;

    $proc = array(
        'type' => 'trigger',
        'def'  => $query
    );
    $this->procedure = new procedure($this->procedure_name, $proc);
  }

  function get_name(){
    return $this->element_name;
  }

  function delete_def(){
    $queries = array();
    $queries[] =  "DROP TRIGGER {$this->element_name['name']} ".
                   "ON {$this->table_ref['safe']}";
    return $queries;
  }

  function alter_def(){
    $todo = array();
    if(!$this->modified())
        return $todo;

    if($this->sql_def)
        $todo = array_merge($todo, $this->delete_def());

    if($this->procedure)
        $todo = array_merge( $todo, $this->procedure->alter_def() );

    $query  = "CREATE TRIGGER {$this->element_name['name']} "; //dont use safe here..
    $query .= $this->xml_def['condition_timing'].' '. $this->xml_def['event_manipulation'].' ';
    $query .= "ON {$this->table_ref['safe']} ".CRLF;
    $query .= "FOR EACH ".$this->xml_def['action_orientation'].' ';
    $query .= "EXECUTE PROCEDURE {$this->procedure_name['safe']}()";


    $todo []= $query;


    return $todo;

  }

  function modified(){

    $modified = $this->sql_def != $this->xml_def;

    if($this->procedure)
        $modified |= $this->procedure->modified();

    return $modified;
  }


  function sql_infos(){
    $verif = array(
        'trigger_name'        => $this->element_name['name'],
        'trigger_schema'      => $this->element_name['schema'],
        'event_object_table'  => $this->table_ref['name'],
        'event_object_schema' => $this->table_ref['schema'],
    );
    $data = sql::row("information_schema.triggers", $verif);
    $keys = array('event_object_schema', 'event_object_table', 'event_manipulation', 'action_orientation', 'condition_timing');


    $proc_name  = preg_reduce("#EXECUTE PROCEDURE (.*)\(#", $data['action_statement']);

    $data = array_intersect_key($data, array_flip($keys));
    if($proc_name) $data['proc_name'] = $proc_name;

    if($this->procedure)
        $this->procedure->sql_infos();

    $this->sql_def = $data;
  }

  function xml_infos(){
    if($this->procedure)
        $this->procedure->xml_infos();
  }



}
