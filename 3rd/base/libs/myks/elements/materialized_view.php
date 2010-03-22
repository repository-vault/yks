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


    $this->view       = $this->load_ghost_view();
    $this->procedures = $this->load_procedures();
    $this->triggers   = $this->load_triggers();
//print_r($this->procedures);die;
//myks_triggers
  }


  private function load_procedures(){

    $procedures = new procedures_list($this->table);

    $key = join(',', $this->table_keys);

    $updates_fields = array();
    foreach(array_keys($this->table_fields) as $field_name)
      $updates_fields []= "$field_name = NEW.$field_name";
    $updates_fields = join(',', $updates_fields);
    
    $queries = array(
  'insert' => "BEGIN
INSERT INTO {$this->table->name['safe']}
SELECT * FROM {$this->view->name['safe']}
  WHERE $key = NEW.$key
-- avoid double entries
  AND $key NOT IN (
    SELECT $key FROM {$this->table->name['safe']}
    WHERE $key = NEW.$key
);
RETURN NULL;
END",
  'delete' => "BEGIN
DELETE FROM {$this->table->name['safe']}
WHERE $key = OLD.$key;
RETURN NULL;
END",
  'update' => "BEGIN
UPDATE {$this->table->name['safe']}
SET $updates_fields
WHERE $key = OLD.$key;
RETURN NULL;
END",

    );

    foreach($queries as $key=>$proc) {
        $p_name = "{$this->table->name['schema']}.rtg_{$this->table->name['name']}_$key";
        $p_xml  = "<procedure name='$_name' type='trigger'><def>$proc</def></procedure>";

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
      $xml .= "<trigger name='{$name['name']}' on='insert' procedure='{$name['name']}'/>";

      $name = $this->procedures->retrieve('delete')->name;
      $xml .= "<trigger name='{$name['name']}' on='delete' procedure='{$name['name']}'/>";

      $name = $this->procedures->retrieve('update')->name;
      $xml .= "<trigger name='{$name['name']}' on='update' procedure='{$name['name']}'/>";

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