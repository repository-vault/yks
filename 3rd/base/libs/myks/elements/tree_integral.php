<?php

class tree_integral extends table_abstract {

  protected $view_name;
  protected $proc_name;

  function __construct($table, $abstract_xml){

    $this->abstract_xml = $abstract_xml;
    $this->table = $table;

    $name = $this->table->name['raw'];

    $this->table_keys   = fields(yks::$get->tables_xml->$name, true);
    $this->table_fields = fields(yks::$get->tables_xml->$name);

    $subscription = $this->abstract_xml->subscribe;
    $key = (string)$subscription['key'];
    $this->view_name = (string)$subscription['table'];
    $this->proc_name = "rtg_{$this->table->name['name']}_sync";

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
    $this->triggers   = array();
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
    <rule on='insert'>SELECT {$this->proc_name}('INSERT', NEW.$key, NEW.$parent)</rule>
    <rule on='delete'>SELECT {$this->proc_name}('DELETE', OLD.$key, OLD.$parent)</rule>
    <rule on='update'>SELECT {$this->proc_name}('UPDATE', OLD.$key, NEW.$parent)</rule>
</rules>
</view>";
    $v_xml = simplexml_load_string($v_xml);
    return new view($v_xml);
  }


  protected function load_procedures(){
    $procedures = new procedures_list($this->table);

    extract($this->fields); //key, parent, depth

    $p_xml  = "<procedure name='{$this->proc_name}' type='bool' volatility='VOLATILE'>
    <param type='string' name='operation'/>
    <param type='int'/>
    <param type='int'/>
<def>
BEGIN

IF(operation = 'DELETE') THEN
    DELETE FROM {$this->table->name['safe']}
    WHERE $key = $2
      OR $key IN ( SELECT $key FROM {$this->table->name['safe']} WHERE $parent = $2 );
END IF;

IF(operation = 'UPDATE')  THEN 

  --suppression des paths des enfants
  DELETE FROM {$this->table->name['safe']}
  WHERE TRUE
  -- parmis mes enfants
  AND $key IN (SELECT $key FROM {$this->table->name['safe']} WHERE $parent = $2)
  -- delete extra parents informations (but not mine)
  AND $parent IN (SELECT $parent FROM {$this->table->name['safe']} WHERE $key = $2 AND $parent != $key );

  --suppression de mes infos
  DELETE FROM {$this->table->name['safe']} WHERE $key = $2;

  -- insertion à la bonne position - yeah
  PERFORM {$this->proc_name}('INSERT', $2, $3);

  INSERT INTO {$this->table->name['safe']} ($key, $parent, $depth)
    SELECT 
      children.$key AS $key,
      parents.$parent AS $parent, 
      parents.$depth + children.$depth AS $depth
    FROM {$this->table->name['safe']} AS parents, {$this->table->name['safe']} AS children
    WHERE TRUE
      AND parents.$key = $2
      AND children.$parent = $2
      --allow standalone roots  
      AND parents.$parent != parents.$key
      AND children.$key != parents.$parent
    ;
END IF;

IF(operation = 'INSERT')  THEN 
  INSERT INTO {$this->table->name['safe']} ($key, $parent, $depth) VALUES ($2, $3, 1);
  INSERT INTO {$this->table->name['safe']} ($key, $parent, $depth)
    SELECT $2 AS $key, $parent, $depth + 1 AS $depth
      FROM {$this->table->name['safe']}  WHERE $key = $3 AND $key != $parent;
END IF;

RETURN true;
END;
</def></procedure>";

    $p_name = sql::resolve($this->proc_name);
    $p_xml  = simplexml_load_string($p_xml);
    $p      = new procedure($p_name, $p_xml);
    $procedures->stack($p, "sync");
    rbx::ok("-- Materialized view check_procedures ");
    return $procedures;
  }


}