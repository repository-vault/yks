<?php

class materialized_view extends myks_installer {
  private $table;
  private $view;
  private $triggers;
  private $procedures;

  private $table_keys;
  private $table_name;
  private $table_fields;

  private $abstact_xml;
  private $subscribed_tables = array();
  function __construct($table, $abstract_xml){

    $this->abstract_xml = $abstract_xml;
    $this->table = $table;

    $name = $this->table->name['name'];

    $this->table_keys   = fields(yks::$get->tables_xml->$name,true);
    $this->table_fields = fields(yks::$get->tables_xml->$name);

    $this->subscribed_tables = array();
    foreach($this->abstract_xml->subscribe as $subscription)
        $this->subscribed_tables[] = sql::resolve((string)$subscription['table']);
    
    // not ok at all since remote table could use diff keys name
    $this->rtable_keys   = array($this->abstract_xml->subscribe[0]['key']);

    $this->view       = $this->load_ghost_view();
    $this->procedures = $this->load_procedures();
    $this->triggers   = $this->load_triggers();
//print_r($this->procedures);die;
//myks_triggers
  }


  private function load_procedures_materialized_view($data_help){
    extract($data_help); //'key', 'updates_fields', 'rkey'

    return array(
      'insert' => array(
        'type'  => 'trigger',
        'query' => "BEGIN".CRLF
            ."INSERT INTO {$this->table->name['safe']}".CRLF
            ."SELECT * FROM {$this->view->name['safe']}".CRLF
            ."  WHERE $key = NEW.$key".CRLF
            ."-- avoid double entries".CRLF
            ."  AND $key NOT IN (".CRLF
            ."    SELECT $key FROM {$this->table->name['safe']}".CRLF
            ."    WHERE $key = NEW.$key".CRLF
            .");".CRLF
            ."RETURN NULL;".CRLF
            ."END"),
      'delete' => array(
        'type'  => 'trigger',
        'query' => "BEGIN".CRLF
            ."DELETE FROM {$this->table->name['safe']}".CRLF
            ."WHERE $key = OLD.$key;".CRLF
            ."RETURN NULL;".CRLF
            ."END"),
      'update' => array(
        'type'  => 'trigger',
        'query' => "BEGIN".CRLF
            ."UPDATE {$this->table->name['safe']}".CRLF
            ."SET $updates_fields".CRLF
            ."WHERE $key = OLD.$key;".CRLF
            ."RETURN NULL;".CRLF
            ."END"),
      'sync' => array(
        'type'  => 'bool',
        'query' => "BEGIN".CRLF
            ."--disable triggers on materialized table ? / use deferred keys instead ?".CRLF
            ."DELETE FROM {$this->table->name['safe']};".CRLF
            ."INSERT INTO {$this->table->name['safe']}".CRLF
            ."    SELECT * FROM {$this->view->name['safe']};".CRLF
            ."RETURN true;".CRLF
            ."END"),
    );
  }

  private function load_procedures_cached_logs($data_help){
    extract($data_help); //'key', 'updates_fields', 'rkey'

    return array(
      'insert' => array(
        'type'  => 'trigger',
        'query' => "BEGIN".CRLF
            ."INSERT INTO {$this->table->name['safe']}".CRLF
            ."SELECT * FROM {$this->view->name['safe']}".CRLF
            ."  WHERE $key = NEW.$rkey".CRLF
            ."-- avoid double entries".CRLF
            ."  AND $key NOT IN (".CRLF
            ."    SELECT $key FROM {$this->table->name['safe']}".CRLF
            ."    WHERE $key = NEW.$rkey".CRLF
            .");".CRLF
            ."UPDATE {$this->table->name['safe']} AS t".CRLF
            ."    SET $updates_fields".CRLF
            ."    FROM {$this->view->name['safe']} AS ghost".CRLF
            ."    WHERE ghost.$key = NEW.$rkey".CRLF
            ."        AND t.$key = NEW.$rkey;".CRLF
            ."RETURN NULL;".CRLF
            ."END"),
      'sync' => array(
        'type'  => 'bool',
        'query' => "BEGIN".CRLF
            ."--disable triggers on materialized table ? / use deferred keys instead ?".CRLF
            ."DELETE FROM {$this->table->name['safe']};".CRLF
            ."INSERT INTO {$this->table->name['safe']}".CRLF
            ."    SELECT * FROM {$this->view->name['safe']};".CRLF
            ."RETURN true;".CRLF
            ."END"),
    );
  }

  private function load_procedures(){

    $procedures = new procedures_list($this->table);
    $key  = join(',', $this->table_keys); //nok
    $rkey = join(',', $this->rtable_keys);


    $updates_fields = array();
    foreach(array_keys($this->table_fields) as $field_name)
      $updates_fields []= "$field_name = ghost.$field_name";
    $updates_fields = join(',', $updates_fields);
    $data_help = compact('key', 'updates_fields', 'rkey');

    $callback_method = "load_procedures_{$this->abstract_xml['type']}";
    $queries = $this->$callback_method($data_help);

    foreach($queries as $key=>$proc_infos) {
        $p_name = "{$this->table->name['schema']}.rtg_{$this->table->name['name']}_$key";
        $p_xml  = "<procedure name='$_name' type='{$proc_infos['type']}'><def>{$proc_infos['query']}</def></procedure>";

        $p_name = sql::resolve($p_name);
        $p_xml  = simplexml_load_string($p_xml);
        $p      = new procedure($p_name, $p_xml);
        $procedures->stack($p, $key);
    }

    return $procedures;
  }


  private function load_triggers(){

    $tables_triggers = array();
    foreach($this->subscribed_tables as $table) {
       
      $xml  = "<triggers>";

      $name = $this->procedures->retrieve('insert')->name;
      if($name) $xml .= "<trigger name='{$name['name']}' on='insert' procedure='{$name['name']}'/>";

      $name = $this->procedures->retrieve('delete')->name;
      if($name) $xml .= "<trigger name='{$name['name']}' on='delete' procedure='{$name['name']}'/>";

      $name = $this->procedures->retrieve('update')->name;
      if($name) $xml .= "<trigger name='{$name['name']}' on='update' procedure='{$name['name']}'/>";

      $xml .= "</triggers>";

      $triggers_collection = simplexml_load_string($xml)->xpath("./trigger");

      $triggers = new myks_triggers($table, $triggers_collection);

      $table_triggers[$table['name']] = $triggers;
    }
    return $table_triggers;
  }

  
  private function load_ghost_view(){
    $table = $this->table->get_name();
    $v_name = "{$table['schema']}.{$table['name']}_maghost";
    $v_xml = "<view name='$v_name'><def>{$this->abstract_xml->query}</def></view>";
    $v_xml = simplexml_load_string($v_xml);
    return new view($v_xml);
  }

  function modified(){
    return $this->view->modified()
           || $this->procedures->modified()
           || $this->triggers->modified()
    ;
  }

  function get_name(){
    return $this->table->get_name();
  }

  function alter_def(){
    $ret = array_merge(
        $this->view->alter_def(),
        $this->procedures->alter_def());
    foreach($this->triggers as $triggers)
      $ret = array_merge($ret, $triggers->alter_def());
    return $ret;
  }

  function xml_infos(){
    $this->view->xml_infos();
    $this->procedures->xml_infos();

    foreach($this->triggers as $triggers)
      $triggers->xml_infos();
  }

  function sql_infos(){
    $this->view->sql_infos();
    $this->procedures->sql_infos();
    foreach($this->triggers as $triggers)
      $triggers->sql_infos();
  }

  function delete_def(){
    $ret = array_merge(
        $this->view->delete_def(),
        $this->procedures->delete_def(),
        $this->triggers->delete_def()
    );
    foreach($this->triggers as $triggers)
      $ret = array_merge($ret, $triggers->delete_def());

    return $ret;
  }


}