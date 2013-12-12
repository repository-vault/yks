<?php

class tree_integral extends table_abstract {

  protected $proc_name;
  protected $subscribed_table;

  function __construct($table, $abstract_xml){

    $this->abstract_xml = $abstract_xml;
    $this->table = $table;

    $name = (string)$this->table->name['raw'];

    $this->table_keys   = fields(yks::$get->tables_xml->$name, true);
    $this->table_fields = fields(yks::$get->tables_xml->$name);

    $subscription = $this->abstract_xml->subscribe;
    $this->subscribed_table = sql::resolve((string) $subscription['table']);

    $subscribed_xml = yks::$get->tables_xml->{$this->subscribed_table['name']};
    $subscribed_fields = fields($subscribed_xml);

    $key = (string)$subscription['key'];
    $this->proc_name = "rtg_{$this->table->name['name']}_treeint";
    
    $fields = $this->table_fields;
    $type   = $fields[$key];

    unset($fields[$key]); //drop main field
    $parent = first(array_keys($fields, $type));//parent is the second field of same type
    unset($fields[$parent]); //drop parent field
    $depth = key($fields); //last field is depth information


    $this->fields = compact('key', 'parent', 'depth');
    if(!$subscribed_fields[$parent] || !$subscribed_fields[$key])
        throw rbx::error("Could not find required column... `$key` and `$parent` are not present in {$this->subscribed_table['name']}");

    rbx::ok("Start tree integral on $name");
    rbx::ok("Fields ".json_encode($this->fields));

    if(false)
    $this->view       = $this->load_ghost_view();
    $this->procedures = $this->load_procedures();
    $this->triggers   = $this->load_triggers();
  }

  private function load_triggers(){

    $tables_triggers = array();
    $xml  = "<triggers>";
    foreach(array('insert', 'delete', 'update') as $event) {
    $name = $this->procedures->retrieve($event)->name;
    if($name) {
        $proc_name = "{$name['schema']}.{$name['name']}";
        $xml .= "<trigger name='{$name['name']}' on='$event' procedure='$proc_name'/>";
    }
    //else rbx::error("Cannot resolve procedure behind $pid in {$table['name']}");
    }
    $xml .= "</triggers>";

    $triggers_collection = simplexml_load_string($xml)->xpath("./trigger");
    $triggers = new myks_triggers($this->subscribed_table, $triggers_collection);

    $table_triggers[$this->subscribed_table['name']] = $triggers;
    rbx::ok("-- Tree integral view check_triggers ".join(',', array_keys($table_triggers)));

    return $table_triggers;
  }

  private function load_ghost_view(){
    extract($this->fields); //key, parent, depth
    $tables_triggers = array();
    $xml  = "<triggers>";
    foreach(array('insert', 'delete', 'update') as $event) {
    $name = $this->procedures->retrieve($event)->name;
    if($name) {
        $proc_name = "{$name['schema']}.{$name['name']}";
        $xml .= "<trigger name='{$name['name']}' on='$event' procedure='$proc_name'/>";
    }
    //else rbx::error("Cannot resolve procedure behind $pid in {$table['name']}");
    }
    $xml .= "</triggers>";

    $table = $this->table->get_name();
    $v_xml ="<view name='{$this->view_name}'>
<def>
SELECT $key, $parent  FROM {$this->table->name['safe']} WHERE $depth = 1;
</def>
<!-- explicit syntax -->
<rules>
    <rule on='insert'>SELECT {$this->proc_name}_depth('INSERT', NEW.$key, NEW.$parent)</rule>
    <rule on='delete'>SELECT {$this->proc_name}_depth('DELETE', OLD.$key, OLD.$parent)</rule>
    <rule on='update'>SELECT {$this->proc_name}_depth('UPDATE', OLD.$key, NEW.$parent)</rule>
</rules>
</view>";
    $v_xml = simplexml_load_string($v_xml);
    return new view($v_xml);

  }
  protected function load_procedures(){
    $p_list = new procedures_list($this->table);

    extract($this->fields); //key, parent, depth

    $procedures = array(
      "insert" => array(
        'type'  => 'trigger',
        'query' => "
BEGIN
  IF(NEW.$parent IS NOT NULL ) THEN
    PERFORM {$this->proc_name}_depth('INSERT', NEW.$key, NEW.$parent);
  END IF;
  RETURN NULL;
END
      "),

      "delete" => array(
        'type'  => 'trigger',
        'query' => "
BEGIN
  PERFORM {$this->proc_name}_depth('DELETE', OLD.$key, OLD.$parent);
  RETURN NULL;
END
      "),

      "update" => array(
        'type'  => 'trigger',
        'query' => "
BEGIN
  IF(NEW.$parent IS NULL AND OLD.$parent IS NOT NULL) THEN
    PERFORM {$this->proc_name}_depth('DELETE', NEW.$key, null);
  END IF;

  IF(OLD.$parent IS NULL AND NEW.$parent IS NOT NULL ) THEN
    PERFORM {$this->proc_name}_depth('INSERT', NEW.$key, NEW.$parent);
  END IF;

  IF(OLD.$parent IS NOT NULL AND NEW.$parent IS NOT NULL ) THEN
    PERFORM {$this->proc_name}_depth('UPDATE', NEW.$key, NEW.$parent);
  END IF;
  RETURN NULL;
END
      "),

      "depth" => array(
        'type'  => 'bool',
        'args'  => array('operation' => 'string', 'int', 'int'),
        'query' => "
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
  PERFORM {$this->proc_name}_depth('INSERT', $2, $3);

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
      "),
    );

    foreach($procedures as $proc_key=>$proc){
        $p_name = "{$this->proc_name}_{$proc_key}";
        $args = array();
        if($proc['args']) foreach($proc['args'] as $k=>$v)
            $args[] = "<param type='$v' ".(is_numeric($k)?'':"name='$k'")."/>";

        $p_xml  = "<procedure name='$p_name' type='{$proc['type']}' volatility='VOLATILE'>"
                .join('', $args)
                ."<def>{$proc['query']}</def></procedure>";

        $p_name = sql::resolve($p_name);
        $p_xml  = simplexml_load_string($p_xml);
        $p      = new procedure($p_name, $p_xml);
        $p_list->stack($p, $proc_key);
    }
    rbx::ok("-- Materialized view check_procedures ");
    return $p_list;
  }


}