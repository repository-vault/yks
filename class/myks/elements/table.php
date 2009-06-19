<?



abstract class table_base {
  public $name;
  protected $xml;
  protected $sql;
  protected $keys_xml_def=array();
  protected $fields_xml_def=array();
  protected $keys_sql_def=array();
  protected $fields_sql_def=array();

  protected $table_schema;

  protected $keys_name = array(        // $this->uname, $field, $type
    'PRIMARY'=>"%s_pkey", 
    'UNIQUE'=>"%s_%s_%s",
    'FOREIGN'=>"%s_%s_%s",
  );


  function __construct($table_xml){
    $this->xml=$table_xml;
    $this->name=$this->xml["name"];
    $this->uname=sql::unquote($this->xml['name']);
    $this->table_shema =  (string) yks::$get->config->sql->links->db_link['db'];
    $this->keys_def=array();
  }
  
  function check(){

    if(in_array($this->name, myks_gen::$tables_ghosts_views)) {
        rbx::ok("-- Double sync from view $this->name, skipping");
        return false;
    }

    $this->xml_infos();

    $this->sql = sql::table_infos($this->name);
    if(!$this->sql) return $this->create();

    $this->sql_infos();
    if(!$this->modified())  return false;


    //print_r(array_show_diff($this->fields_sql_def, $this->fields_xml_def));die;
    //print_r(array_show_diff($this->keys_sql_def, $this->keys_xml_def));die;
    //print_r($this->privileges);die;

    $todo  = $this->update();
    if(!$todo) throw rbx::error("Error while looking for differences in $this->name");
    $todo = array_map(array('sql', 'unfix'), $todo);
    return $todo;
  }

  function modified(){
    return $this->fields_xml_def != $this->fields_sql_def
        || $this->keys_xml_def != $this->keys_sql_def;

  }

/*
    populate fields_sql_def and keys_sql_def definition based on the SQL structure
*/

  function sql_infos(){
    $this->fields_sql_def=table::table_fields($this->uname);
    $this->keys_sql_def=table::table_keys($this->uname, $this->table_shema);
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
    $key_name = sprintf($this->keys_name[$TYPE], $this->uname, $field, $type);

    if($TYPE=="PRIMARY"){
        $this->keys_xml_def[$key_name]['type']=$TYPE;
        $this->keys_xml_def[$key_name]['members'][]=$field;
    } elseif($TYPE=="UNIQUE"){
        $this->keys_xml_def[$key_name]['type']=$TYPE;

        $this->keys_xml_def[$key_name]['members']=&$this->tmp_key[$field];
        $this->tmp_key[$field][]=$field;
    } elseif($TYPE=="FOREIGN" && SQL_DRIVER=="pgsql"){

        $this->keys_xml_def[$key_name]['type']=$TYPE;
        $this->keys_xml_def[$key_name]['members']=&$this->tmp_key[$key_name];
        $this->tmp_key[$key_name][]=$field;

        $this->keys_xml_def[$key_name]=array_merge($this->keys_xml_def[$key_name],$refs);
    } else {
        $this->tmp_key[$type][]=$field;
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


  static function table_keys($table_name, $table_schema){
    $where = array('table_name'=>$table_name);
    if(SQL_DRIVER!="pgsql") $where['table_schema']=$table_schema; //TODO, pgsql

    $cols = 'constraint_catalog, constraint_schema, constraint_name, table_schema, table_name, constraint_type';
    if(SQL_DRIVER=="pgsql") $cols.=",is_deferrable";
    sql::select("information_schema.table_constraints", $where, $cols);

    $keys = sql::brute_fetch('constraint_name');$table_keys=array();
    $keys = array_map('array_change_key_case', $keys);

    $usages=array(); $behavior=array();
    $where['constraint_name']=array_keys($keys);

    if(SQL_DRIVER=="pgsql") $order ="ORDER BY position_in_unique_constraint ASC";
    sql::select("information_schema.key_column_usage",$where,"constraint_name,column_name",$order);
    while($l=sql::fetch()) $table_keys[$l['constraint_name']]['members'][]=$l['column_name'];
            //une clée est basé sur au moins UNE colonne ( élimine les checks )

    if(SQL_DRIVER=="pgsql"){ ///FOREIGN_KEYS
        sql::select("information_schema.constraint_column_usage",
            array('constraint_name'=>array_keys($table_keys)) );
        while($l=sql::fetch())
            $usages[$l['constraint_name']][$l['table_name']][]=$l['column_name'];
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

            $usage_str = '';
            list($usage_table, $usage_fields) = each($usages[$constraint_name]);
            $usage_str = $usage_table.'('.join(',',$usage_fields).')';

            $constraint_infos['table']=$usage_table;
            $constraint_infos['update']=table::$fk_actions_in[$behavior[$constraint_name]['update_rule']];
            $constraint_infos['delete']=table::$fk_actions_in[$behavior[$constraint_name]['delete_rule']];
            $constraint_infos['refs'] = $usage_str;
            $constraint_infos['defer']=bool($key['is_deferrable'])&&bool($key['is_deferrable'])?'defer':'strict';

        }
    }

    return $table_keys;
 }


}

