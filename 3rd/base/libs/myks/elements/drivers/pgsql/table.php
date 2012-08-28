<?php


class table extends table_base {
  protected $escape_char="\"";

  public $key_mask=array("PRIMARY"=>'PRIMARY KEY',  "INDEX" => "INDEX", "UNIQUE"=>'UNIQUE', 'FOREIGN'=>'FOREIGN KEY' );
  public $tmp_refs=array();


  private $rules;
  private $privileges;
  private $triggers;
  private $indices;
  private $checks;

  private $ghost_keys;

  function __construct($table_xml){
    parent::__construct($table_xml);

    $this->privileges  = new privileges($this, $table_xml->grants, 'table');
    $this->rules    = new rules($this, $table_xml->xpath('rules/rule'), 'table');
    $this->triggers = new myks_table_triggers($this, $table_xml->xpath('triggers/trigger'));
    $this->indices  = new myks_indices($this, $table_xml->xpath('indices/index'));
    $this->checks   = new myks_checks($this, $table_xml->xpath('checks/check'));
  }


  function sql_infos(){
    parent::sql_infos();
    if(!$this->fields_sql_def)
        return;

    foreach($this->keys_sql_def as $k=>$key)
        if($this->ghost_keys[$k]) {
            rbx::ok("-- Ignore $k fk");
            unset($this->keys_sql_def[$k]);
        }

    $this->privileges->sql_infos();
    $this->rules->sql_infos();
    $this->triggers->sql_infos();
    $this->indices->sql_infos();
    $this->checks->sql_infos();
  }

  function xml_infos(){

    parent::xml_infos();
    $this->rules->xml_infos();
    $this->triggers->xml_infos();
    $this->privileges->xml_infos();
    $this->indices->xml_infos();
    $this->checks->xml_infos();

    foreach($this->keys_xml_def as $k=>$key){
        if($key['type']!='FOREIGN' || !in_array($key['table'], myks_gen::$tables_ghosts_views))
            continue;
        //the key reference to a ghost table

        $this->ghost_keys[$k] = true;
        unset($this->keys_xml_def[$k]);
        rbx::ok("-- $k is a ghost reference, skipping");
    }
  }


  function modified(){
    return parent::modified()
        || $this->privileges->modified()
        || $this->triggers->modified()
        || $this->indices->modified()
        || $this->checks->modified()
        || $this->rules->modified();

  }


  function alter_def(){
    return array_merge(
        parent::alter_def(),
        $this->privileges->alter_def(),
        $this->triggers->alter_def(),
        $this->indices->alter_def(),
        $this->checks->alter_def(),
        $this->rules->alter_def()
    );
  }



  function create() {
    $todo  = array();
    $parts = array();

    foreach($this->fields_xml_def as $field_name=>$field_xml)
        $parts[]="\"$field_name\" {$field_xml['Type']}";

    foreach($this->keys_xml_def as $key=>$def) {
        if($def['type']!="PRIMARY")continue;
        $members=' ("'.join('","',$def['members']).'")';$type=$def['type'];
        $add = "CONSTRAINT $key ".$this->key_mask[$type]." $members ";
        if($def['type']=="INDEX")$parts_exts[]="CREATE INDEX $key ON {$this->table_name['safe']} $members";
        else $parts[]=$add;
    }

    $query = "CREATE TABLE {$this->table_name['safe']} (\n\t".join(",\n\t", $parts)."\n)";

    $todo  []= $query;
    return $todo;
  }




/*
    retourne la définition des colonnes d'une table formaté pour une comparaison avec les tables_xml
*/

 protected function table_fields(){

    sql::select("zks_information_schema_columns", $this->table_where());
    $columns = sql::brute_fetch('column_name'); $table_cols=array();


    foreach($columns as $column_name=>$column){

        $table_cols[$column_name] = array(
            'Extra'     => '',
            'Default'   => $column['column_default'],
            'Field'     => $column['column_name'],
            'Type'      => $column['data_type'],
            'Null'      => bool($column['is_nullable']),
        );
    } return $table_cols;
  }

}
