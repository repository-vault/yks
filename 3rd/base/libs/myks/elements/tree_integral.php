<?php

class tree_integral extends table_abstract {

  protected $view_name;

  function __construct($table, $abstract_xml){

    $this->abstract_xml = $abstract_xml;
    $this->table = $table;

    $name = $this->table->name['name'];
    $this->table_keys   = fields(yks::$get->tables_xml->$name, true);
    $this->table_fields = fields(yks::$get->tables_xml->$name);

    $subscription = $this->abstract_xml->subscribe;
    $key = (string)$subscription['key'];
    $this->view_name = (string)$subscription['table'];

    $fields = $this->table_fields;
    $type   = $fields[$key];

    unset($fields[$key]); //drop main field
    $parent = reset(array_keys($fields, $type));//parent is the second field of same type
    unset($fields[$parent]); //drop parent field
    $depth = key($fields); //last field is depth information

    $this->fields = compact('key', 'parent', 'depth');
    rbx::ok("Start tree integral on $name");
    rbx::ok("Fields ".json_encode($this->fields));

    $this->view       = $this->load_ghost_view();
    $this->procedures = $this->load_procedures();
    $this->triggers   = $this->load_triggers();
  }


  private function load_triggers(){

    $table_name = $this->table->name;
    $tables_triggers = array();

    $xml  = "<triggers>";
    foreach(array('insert', 'update') as $event) {
        $name = $this->procedures->retrieve("sync")->name;
        if(!$name) throw rbx::error("Cannot resolve procedure behind $pid");
        $proc_name = "{$name['schema']}.{$name['name']}";
        $t_name    = "{$name['name']}_$event";
        $xml .= "<trigger name='$t_name' on='$event' procedure='$proc_name'/>";
    } $xml .= "</triggers>";


    $triggers_collection = simplexml_load_string($xml)->xpath("./trigger");
    $triggers = new myks_triggers($table_name, $triggers_collection);

    $table_triggers[$table_name['name']] = $triggers;
    rbx::ok("-- Tree integral view check_triggers ");

    return $table_triggers;
  }

  private function load_ghost_view(){
    extract($this->fields); //key, parent, depth

    $table = $this->table->get_name();
    $v_xml ="<view name='{$this->view_name}'>
<def>
SELECT $key, $parent  FROM {$this->table->name['safe']} WHERE $depth = 1;
</def>
<!-- explicit syntax -->
<rules>
<rule on='insert'>
INSERT INTO {$this->table->name['safe']} ($key, $parent, $depth) VALUES (NEW.$key, NEW.$parent, 1)
</rule>
<rule on='delete'>
DELETE FROM {$this->table->name['safe']}
WHERE $key = OLD.$key
      OR $key IN ( SELECT $key FROM {$this->table->name['safe']} WHERE $parent = OLD.$key )
</rule>
    <rule on='update'>
UPDATE {$this->table->name['safe']} SET $parent = NEW.$parent WHERE $key = OLD.$key AND $depth = 1
</rule>
</rules>
</view>";
    $v_xml = simplexml_load_string($v_xml);
    return new view($v_xml);
  }

  private function load_procedures_tree_integral(){

    extract($this->fields); //key, parent, depth
    return array(
      "sync" => array(
        'type'  => 'trigger',
        'query' => "BEGIN

IF(TG_OP = 'UPDATE' AND new.$depth = 1)  THEN 
  --suppression des paths des enfants
  DELETE FROM {$this->table->name['safe']}
  WHERE TRUE
  -- parmis mes enfants
  AND $key IN (SELECT $key FROM {$this->table->name['safe']} WHERE $parent = OLD.$key)
  -- delete extra parents informations (but not mine)
  AND $parent IN (SELECT $parent FROM {$this->table->name['safe']} WHERE $key = OLD.$key AND $parent != $key );

  --suppression de mes infos
  DELETE FROM {$this->table->name['safe']} WHERE $key = OLD.$key;

  -- insertion à la bonne position - yeah
  INSERT INTO {$this->table->name['safe']} ($key, $parent, $depth)
    VALUES (OLD.$key, NEW.$parent, 1);

  INSERT INTO {$this->table->name['safe']} ($key, $parent, $depth)
    SELECT 
      children.$key AS $key,
      me.$parent AS $parent, 
      me.$depth + children.$depth AS $depth
    FROM {$this->table->name['safe']} AS me, {$this->table->name['safe']} AS children
    WHERE TRUE
      AND me.$key = OLD.$key
      AND children.$parent = OLD.$key
      --allow standalone roots  
      AND me.$parent != me.$key
      AND children.$key != me.$parent
    ;
END IF;

IF(TG_OP = 'INSERT' AND new.$depth = 1)  THEN 
  INSERT INTO {$this->table->name['safe']} ($key, $parent, $depth)
    SELECT NEW.$key AS $key, $parent, $depth + 1 AS $depth
      FROM {$this->table->name['safe']}  WHERE $key = NEW.$parent AND $key != $parent;
END IF;

RETURN NEW;

END;"),
    );
  }


  protected function load_procedures(){
    $procedures = new procedures_list($this->table);
    $queries    = $this->load_procedures_tree_integral();
    foreach($queries as $pid=>$proc_infos) {
        $p_name = "{$this->table->name['schema']}.rtg_{$this->table->name['name']}_$pid";
        $p_xml  = "<procedure name='$p_name' type='{$proc_infos['type']}' volatility='VOLATILE'>
                      <def>{$proc_infos['query']}</def></procedure>";
        $p_name = sql::resolve($p_name);
        $p_xml  = simplexml_load_string($p_xml);
        $p      = new procedure($p_name, $p_xml);
        $procedures->stack($p, $pid);
    }

    rbx::ok("-- Materialized view check_procedures ");

    return $procedures;
  }


}