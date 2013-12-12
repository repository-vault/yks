<?php

abstract class rule_base extends myks_base {
  protected $sql_def = array();
  protected $xml_def = array();
  private $rule_xml = null;

  protected $rule_name;
  protected $parent_name;
  protected $parent_type;
  const rule_nothing = 'NOTHING';

  function __construct($parent, $rule_xml, $parent_type){
    $this->parent_type = $parent_type;
    $this->parent      = $parent;

    if(!in_array($this->parent_type, array('table', 'view')))
        throw rbx::error("-- Rules can only be applied to tables or views");
    $this->rule_xml = $rule_xml;

    $event = (string)$this->rule_xml['on'];
    $where = (string)$this->rule_xml['where'];


    //rule_xml['name'] is only provided in sql_search (tmp)
    $rule_name = $rule_xml['name'];
    if(!$rule_name) {
        list($event, $where, $definition, $hash) = $this->get_sp_infos();
        $rule_name = "{$this->parent->name['name']}_{$event}_{$hash}";        
    }
    $this->rule_name = sql::resolve($rule_name);
  }


  function get_name(){
    return $this->rule_name;
  }

  function alter_def(){
    $todo = array();

    if(!$this->modified())
        return $todo;

    $event = strtoupper($this->xml_def['event']);
    $where = $this->xml_def['where']?"WHERE {$this->xml_def['where']}":'';
    $definition = (string) $this->xml_def['definition'];
    $signature  = $this->xml_def['signature'];
    $todo []= "CREATE OR REPLACE RULE {$this->rule_name['name']} AS
        ON $event TO {$this->parent->name['safe']} $where
        DO INSTEAD $definition;";
    $todo []= $this->sign("RULE", $this->rule_name['name'], $definition, $signature);

    return $todo;
  }

  function delete_def(){
    return array(
        "DROP RULE IF EXISTS {$this->rule_name['name']} ON {$this->parent->name['safe']}"
    );
  }

  function modified(){
    //print_r($this->xml_def);print_r($this->sql_def);die;
    return $this->xml_def['signature'] != $this->sql_def['signature'];
  }


  static function sql_search($parent, $parent_type){
    $find = self::raw_sql_search($parent->name, $parent_type);
    $ret = array();
    foreach($find as $infos){
        $xml = "<rule name='{$infos['rule_name']}'/>";
        $xml = simplexml_load_string($xml);
        $tmp = new rule($parent, $xml, $parent_type);
        $ret[$tmp->hash_key()] = $tmp;
    }
    return $ret;
  }


//STATIC
  private static function raw_sql_search($parent_name, $parent_type, $rule_name = false){
    $where = array();
    if($rule_name)
        $where ["rule_name"] = $rule_name;
    $where ["element_name"]   = $parent_name['name'];
    $where ["element_schema"] = $parent_name['schema'];

    try {
        sql::select("zks_information_schema_rules", $where);
        return sql::brute_fetch('rule_name');
    }catch(Exception $e){
        rbx::error("-- unable to lookup rules");
        return array();
    }
  }

  function sql_infos(){
    if($this->sql_def) 
      return;

    $rule = first(self::raw_sql_search(
        $this->parent->name,
        $this->parent_type,
        $this->rule_name['name']
    ));

    if(!$rule)
        return false;

    $sign = $this->parse_signature_contents($rule['full_description']);

    $this->sql_def = array(
        'compiled_definition' => $rule['compiled_definition'],
        'definition'          => rtrim($sign['base_definition'],";"),
        'event'               => $rule['rule_event'],
        'signature'           => $sign['signature'],
        'where'               => '', //where is post-compiled
    );
  }

//cleanup & returns tuple : $event, $where, $definition, $hash
  private function get_sp_infos(){

    $event = (string)$this->rule_xml['on'];
    $where = (string)$this->rule_xml['where'];
    $definition  = rtrim(myks_gen::sql_clean_def($this->rule_xml),";");
    if(!$definition) $definition = self::rule_nothing;

    if($definition != self::rule_nothing){
        $definition = "($definition)";
        $hash = substr(md5($event.$definition),0,5);
    } else $hash = "nothing";
    return array($event, $where, $definition, $hash);
  }

  function xml_infos() {

    $this->sql_infos(); //we need recursive reflection
    $this->xml_def = array();

    if(!$this->rule_xml)
      return;

    list($event, $where, $definition) = $this->get_sp_infos();

    $compiled_definition = $this->sql_def['compiled_definition'];
    $signature = $this->crpt($compiled_definition, $definition);

    $this->xml_def = compact(
      'compiled_definition',
      'definition',
      'event',
      'where',
      'signature'
    );

  }


}
