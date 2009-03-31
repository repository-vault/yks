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
    $this->xml_infos();

    $this->sql = sql::table_infos($this->name);
    if(!$this->sql) return $this->create();

    $this->sql_infos();
    $diff = $this->fields_xml_def != $this->fields_sql_def
        || $this->keys_xml_def != $this->keys_sql_def;
    if(!$diff)  return false;
    //print_r($this->fields_xml_def);print_r($this->fields_sql_def);die;
    //print_r($this->keys_sql_def);print_r($this->keys_xml_def);die;
    return $this->update();
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

  function key_add($type,$field,$refs=array()){$TYPE=strtoupper($type);
    $key_name = sprintf($this->keys_name[$TYPE], $this->uname, $field, $type);

    if($TYPE=="PRIMARY"){
        $this->keys_xml_def[$key_name]['type']=$TYPE;
        $this->keys_xml_def[$key_name]['members'][]=$field;
    }
    elseif($TYPE=="UNIQUE"){
        $this->keys_xml_def[$key_name]['type']=$TYPE;

        $this->keys_xml_def[$key_name]['members']=&$this->tmp_key[$field];
        $this->tmp_key[$field][]=$field;
    }
    elseif($TYPE=="FOREIGN" && SQL_DRIVER=="pgsql"){    // je ne pense pas que les fk multi colonne n'entrent pas en conflit avec les uniques
        $this->keys_xml_def[$key_name]['type']=$TYPE;
        $this->keys_xml_def[$key_name]['members']=&$this->tmp_key[$key_name];
        $this->tmp_key[$key_name][]=$field;

        $this->keys_xml_def[$key_name]=array_merge($this->keys_xml_def[$key_name],$refs);
    }
    else {
        $this->tmp_key[$type][]=$field;
    }

  }

  static function table_keys($table_name, $table_schema){
    
    $where=array('table_name'=>$table_name,'table_schema'=>$table_schema);
    sql::select("information_schema.table_constraints",$where,
        'CONSTRAINT_CATALOG as constraint_catalog,
        CONSTRAINT_SCHEMA as constraint_schema,
        CONSTRAINT_NAME as constraint_name,
        TABLE_SCHEMA as table_schema,
        TABLE_NAME as table_name,
        CONSTRAINT_TYPE as constraint_type');

    $keys= sql::brute_fetch('constraint_name');$table_keys=array();$usage=array();$behavior=array();
    $where['constraint_name']=array_keys($keys);
    $where['table_schema'] = $table_schema;

    if(SQL_DRIVER=="pgsql") $order ="ORDER BY position_in_unique_constraint ASC";
    sql::select("information_schema.key_column_usage",$where,"constraint_name,column_name",$order);
    while($l=sql::fetch()) $table_keys[$l['constraint_name']]['members'][]=$l['column_name'];
            //une clée est basé sur au moins UNE colonne ( élimine les checks )

    if(SQL_DRIVER=="pgsql"){
        sql::select("information_schema.constraint_column_usage",
            array('constraint_name'=>array_keys($table_keys)) );
        while($l=sql::fetch())
            $usage[$l['constraint_name']]
                ="{$l['table_name']}({$l['column_name']})"; //!compilation error 
        sql::select("information_schema.referential_constraints",
            array('constraint_name'=>array_keys($table_keys)));
        sql::brute_fetch('constraint_name');
    }


    foreach($table_keys as $constraint_name=>&$constraint_infos){
        $key=$keys[$constraint_name];
        $types=array('PRIMARY KEY'=>'PRIMARY','FOREIGN KEY'=>'FOREIGN','UNIQUE'=>'UNIQUE','INDEX'=>'INDEX');

        $constraint_infos['type']=$type=$types[$key['constraint_type']];
        if($type=="FOREIGN") {
            $constraint_infos['update']=self::$fk_actions_in[$behavior[$constraint_name]['update_rule']];
            $constraint_infos['delete']=self::$fk_actions_in[$behavior[$constraint_name]['delete_rule']];
            $constraint_infos['refs']=$usage[$constraint_name];
            $constraint_infos['defer']=bool($key['is_deferrable'])&&bool($key['is_deferrable'])?'defer':'strict';

        }
    }

    return $table_keys;
 }


}

