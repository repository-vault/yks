<?php



abstract class table_base  extends myks_installer {

  protected $table_name;

  protected $keys_xml_def   = array();
  protected $fields_xml_def = array();

  protected $keys_sql_def   = array();
  protected $fields_sql_def = array();


  protected $keys_name = array(        // $this->table_name, $field, $type
    'PRIMARY'=>"%s_pkey", 
    'UNIQUE'=>"%s_%s_%s",
    'FOREIGN'=>"%s_%s_%s",
  );

  function get_name(){
    return $this->table_name;
  }

  function delete_def(){
    return array(
        "DROP TABLE {$this->table_name['safe']}"
    );
  }

  function __construct($table_xml){
    $this->xml = $table_xml;
    $this->table_name = sql::resolve( (string) $table_xml['name']);

    $this->keys_def=array();
  }
  
  protected function table_where(){
    return array(
        'table_name'   => $this->table_name['name'],
        'table_schema' => $this->table_name['schema'],
    );
  }


  function alter_def(){

    if(in_array($this->table_name['name'], myks_gen::$tables_ghosts_views)) {
        rbx::ok("-- Double sync from view {$this->table_name['name']}, skipping");
        return false;
    }

    $this->xml_infos();
    $table_exists = $this->sql_infos();
    if(!$table_exists) $todo = $this->create();
    else {
        if(!$this->modified())  return array();

        //print_r(array_show_diff($this->fields_sql_def, $this->fields_xml_def,"sql","xml"));die;
        //print_r(array_show_diff($this->keys_sql_def, $this->keys_xml_def,"sql","xml" ));die;
        //print_r($this->privileges);die;
        $todo  = $this->update();
    }
    if(!$todo) throw rbx::error("Error while looking for differences in {$this->table_name['name']}");
    $todo = array_map(array('sql', 'unfix'), $todo);
    return $todo;
  }

  function modified(){
    return $this->fields_xml_def != $this->fields_sql_def
        || $this->keys_xml_def != $this->keys_sql_def;
  }


/*
    populate fields_sql_def and keys_sql_def definition based on the SQL structure
    return (boolean) whereas this table already exists (alter mode) or not (create mode)
*/

  public function sql_infos(){
    $this->sql = sql::row("information_schema.tables", $this->table_where());

    if(!$this->sql) return false;
    $this->fields_sql_def = $this->table_fields();
    $this->keys_sql_def   = $this->table_keys();
    return true;
  }

/*
    populate fields_xml_def and keys_xml_def definition based on the xml structure
*/

  function xml_infos(){
    foreach($this->xml->fields->field as $field_xml){
        $mykse=new mykse($field_xml,$this);
        $this->fields_xml_def[$mykse->field_def['Field']] = $mykse->field_def;
    }
  }

  function key_add($type, $field, $refs=array()){$TYPE=strtoupper($type);
    $key_name = sprintf($this->keys_name[$TYPE], $this->table_name['name'], $field, $type);

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

  function update(){

    return array_merge(
        $this->alter_fields(),
        $this->alter_keys()
    );
  }

  function alter_fields(){ return array(); }
  function alter_keys(){ return array(); }


  protected function table_keys(){
    $where = $this->table_where();
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

 public static function build_ref($table_schema, $table_name, $table_fields){
    return compact('table_schema', 'table_name', 'table_fields');
 }

 public static function output_ref($ref){
    return  sprintf('"%s"."%s"(%s)',
        $ref['table_schema'],
        $ref['table_name'],
        join(',',$ref['table_fields']) );
 }

}

