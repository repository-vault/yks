<?php


abstract class rules_base extends myks_base {
  private $rules_xml = null;
  protected $sql_def = array();
  protected $xml_def = array();


  const rule_nothing = 'NOTHING';

  function __construct($rules_xml, $element_infos, $element_type){
    $this->element_type = $element_type;

    $this->element_infos = $element_infos;
    $this->element_name  = $element_infos['name'];
    $this->element_name_safe  = $element_infos['safe'];

    if(!in_array($this->element_type, array('table', 'view')))
        throw rbx::error("-- Rules can only be applied to tables or views");
    $this->rules_xml = $rules_xml;

  }

  function alter_rules(){

    $todo = array();
    foreach($this->xml_def  as $rule_name=>$rule_infos){
        if($this->sql_def[$rule_name]['signature'] == $rule_infos['signature']) continue;
        //print_r($this->sql_def[$rule_name]);print_r($rule_infos);die;

        $event = strtoupper($rule_infos['event']);
        $where = $rule_infos['where']?"WHERE {$rule_infos['where']}":'';
        $definition = (string) $rule_infos['definition'];
        $signature  = $rule_infos['signature'];
        $todo []= "CREATE OR REPLACE RULE \"$rule_name\" AS
            ON $event TO $this->element_name_safe $where
            DO INSTEAD $definition;";
        $todo []= $this->sign("RULE", $rule_name, $definition, $signature);
    }
    foreach(array_keys(array_diff_key($this->sql_def, $this->xml_def)) as $rule_name)
        $todo[] = "DROP RULE IF EXISTS \"$rule_name\" ON $this->element_name_safe;";

    return $todo;
  }



  function modified(){
    //print_r($this->signatures['xml']);print_r($this->signatures['sql']);die;
   $signatures_xml = array_extract($this->xml_def, 'signature');
   $signatures_sql = array_extract($this->sql_def, 'signature');


    return $signatures_xml != $signatures_sql;
  }

  function sql_infos(){
    if(!$this->sql_def) {
     sql::query($query = "SELECT
        n.nspname                   AS schema_name,
        c.relname                   AS view_name,
        r.rulename                  AS rule_name,
        pg_get_ruledef(r.oid, true) AS compiled_definition,
        d.description               AS full_description,
        CASE ev_type WHEN 2 THEN 'update' WHEN 3 THEN 'insert' WHEN 4 THEN 'delete' END AS rule_event

      FROM 
        pg_rewrite AS r
        LEFT JOIN pg_class AS c ON c.oid = r.ev_class
        LEFT JOIN pg_namespace AS n ON n.oid = c.relnamespace
        LEFT JOIN pg_description AS d ON r.oid = d.objoid
      WHERE (r.rulename <> '_RETURN' AND c.relname='$this->element_name')
        AND (CASE relkind WHEN 'v' THEN 'view' WHEN 'r' THEN 'table' END) = '$this->element_type'
      ORDER BY r.rulename
     "); $tmp = sql::brute_fetch('rule_name');
     $rules = array();

     foreach($tmp as $rule_name=>$rule){
        $sign = $this->parse_signature_contents($rule['full_description']);
        $rules[$rule_name] = array(
            'compiled_definition'=>$rule['compiled_definition'],
            'definition'=>rtrim($sign['base_definition'],";"),
            'event'=>$rule['rule_event'],
            'signature'=> $sign['signature'],
            'where'=>'', //where is post-compiled
        );
     } $this->sql_def = $rules;
    }

  }




  function xml_infos() {

    $this->sql_infos(); //we need to self reflect

    $this->xml_def = array();

    foreach($this->rules_xml->rule as $rule) {
      $event = (string)$rule['on'];
      $where = (string)$rule['where'];
      $definition  = rtrim(myks_gen::sql_clean_def($rule),";");
      if(!$definition) $definition = self::rule_nothing;
      if($definition!=self::rule_nothing){
        $definition = "($definition)";
        $hash = substr(md5($event.$definition),0,5);
      } else $hash = "nothing";
      $rule_name = "{$this->element_name}_{$event}_{$hash}";

      $compiled_definition = $this->sql_def[$rule_name]['compiled_definition'];
      $signature = $this->crpt($compiled_definition, $definition);

      $this->xml_def[$rule_name] = compact(
          'compiled_definition',
          'definition',
          'event',
          'where',
          'signature'
      );
      
    }


  }


}
