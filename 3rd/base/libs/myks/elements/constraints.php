<?php


abstract class constraints_base extends myks_base {
  private $rules_xml = null;
  protected $sql_def = array();
  protected $xml_def = array();
  private $signatures = array();

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


  function key_add($type, $field, $refs=array()){$TYPE=strtoupper($type);
    $key_name = sprintf($this->keys_name[$TYPE], $this->table_name, $field, $type);

    if($TYPE=="PRIMARY"){
        $this->keys_xml_def[$key_name]['type'] = $TYPE;
        $this->keys_xml_def[$key_name]['members'][$field] = $field;
    } elseif($TYPE=="UNIQUE"){
        $this->keys_xml_def[$key_name]['type'] = $TYPE;

        $this->keys_xml_def[$key_name]['members'] = &$this->tmp_key[$field];
        $this->tmp_key[$field][$field] = $field;
    } elseif($TYPE == "FOREIGN" && SQL_DRIVER == "pgsql"){

        $this->keys_xml_def[$key_name]['type'] = $TYPE;
        $this->keys_xml_def[$key_name]['members'] = &$this->tmp_key[$key_name];
        $this->tmp_key[$key_name][$field] = $field;

        $this->keys_xml_def[$key_name]=array_merge($this->keys_xml_def[$key_name],$refs);
    } else {
        $this->tmp_key[$type][$field]=$field;
    }

  }

  function alter_rules(){
    if($this->xml_def == $this->sql_def) return array();


    $todo = array();
    foreach($this->xml_def  as $rule_name=>$rule_infos){
        if($this->sql_def[$rule_name] == $rule_infos) continue;
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
    return $this->signatures['xml'] != $this->signatures['sql'];
  }

  function sql_infos(){

    if(!$this->sql_def) {




  protected function table_keys(){
    $where = $this->table_where;
    $cols = 'constraint_catalog, constraint_schema, constraint_name, table_schema, table_name, constraint_type';
    if(SQL_DRIVER=="pgsql") $cols.=",is_deferrable";
    sql::select("information_schema.table_constraints", $where, $cols);


    $keys = sql::brute_fetch('constraint_name');$table_keys=array();

    $keys = array_map('array_change_key_case', $keys);

    $usages=array(); $behavior=array();
    $where['constraint_name']=array_keys($keys);

    if(SQL_DRIVER=="pgsql") $order ="ORDER BY position_in_unique_constraint ASC";
    sql::select("information_schema.key_column_usage", $where, "constraint_name,column_name", $order);
    while($l=sql::fetch())
        $table_keys[$l['constraint_name']]['members'][$l['column_name']]=$l['column_name'];
            //une clée est basé sur au moins UNE colonne ( élimine les checks )

    if(SQL_DRIVER=="pgsql"){ ///FOREIGN_KEYS
        sql::select("information_schema.constraint_column_usage",
            array('constraint_name'=>array_keys($table_keys)) );
        while($l=sql::fetch())
            $usages[$l['constraint_name']][$l['table_schema']][$l['table_name']][] = $l['column_name'];
                //="{$l['table_name']}({$l['column_name']})";
        sql::select("information_schema.referential_constraints",
            array('constraint_name'=>array_keys($table_keys)));
        $behavior=sql::brute_fetch('constraint_name');
    }


    foreach($table_keys as $constraint_name=>&$constraint_infos){
        $key=$keys[$constraint_name];
        $types=array('PRIMARY KEY'=>'PRIMARY','FOREIGN KEY'=>'FOREIGN','UNIQUE'=>'UNIQUE','INDEX'=>'INDEX');

        $constraint_infos['type']=$type=$types[$key['constraint_type']];
        if($type=="FOREIGN") {
 
            list($usage_schema, $usage_fields) = each($usages[$constraint_name]);
            list($usage_table, $usage_fields)  = each($usage_fields);


            $constraint_infos['table']  = $usage_table;
            $constraint_infos['update'] = table::$fk_actions_in[$behavior[$constraint_name]['update_rule']];
            $constraint_infos['delete'] = table::$fk_actions_in[$behavior[$constraint_name]['delete_rule']];
            $constraint_infos['refs']   = table::build_ref($usage_schema, $usage_table, $usage_fields);
            $constraint_infos['defer']  = bool($key['is_deferrable'])&&bool($key['is_deferrable'])?'defer':'strict';

        }
    }

    return $table_keys;
 }




     sql::query($query = "
        SELECT (rs.nspname)::information_schema.sql_identifier AS constraint_schema,
               (con.conname)::information_schema.sql_identifier AS constraint_name,
               ("substring"(pg_get_constraintdef(con.oid), 7))::text AS check_clause,
               d.description AS check_descr
        FROM 
            pg_constraint con
            LEFT JOIN pg_namespace rs
                ON rs.oid = con.connamespace
            LEFT JOIN pg_class c
                ON c.oid = con.conrelid
            LEFT JOIN pg_type t
                ON t.oid = con.contypid
            LEFT JOIN pg_description d
                ON d.objoid = con.oid


        WHERE
            pg_has_role(COALESCE(c.relowner, t.typowner), 'USAGE'::text)
            AND con.contype = 'c'::"char"



SELECT
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
            'where'=>'',
        );
     } $this->sql_def = $rules;
    }
    $this->signatures['sql'] = array_extract($this->sql_def, 'signature');
  }


  protected function calc_signature(){
    $signatures = array();
    foreach($this->xml_def as $rule_name=>$rule_infos){
        $signature =  $this->crpt(
            $rule_infos['compiled_definition'],
            $rule_infos['definition']);

        $this->xml_def[$rule_name]['signature'] =  $signature;
        $signatures[$rule_name] = $signature;
    }
    return $signatures;
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

      $this->xml_def[$rule_name] = compact(
          'compiled_definition',
          'definition',
          'event',
          'where'
      );
    }
    $this->signatures['xml'] =  $this->calc_signature();

  }


}


