<?


class table extends table_base {
  public $key_mask=array("PRIMARY"=>'PRIMARY KEY',  "UNIQUE"=>'UNIQUE', 'FOREIGN'=>'FOREIGN KEY' );
  public $tmp_refs=array();
  static $fk_actions_in = array('NO ACTION'=>'no_action', 'CASCADE'=> 'cascade', 'SET NULL'=>'set_null');
  static $fk_actions_out = array('no_action'=>'NO ACTION', 'cascade'=>'CASCADE','set_null'=> 'SET NULL');

  private $rules; //rules only exists in this driver
  private $privileges;

  function __construct($table_xml){
    parent::__construct($table_xml);

    $this->privileges  = new privileges($table_xml->grants, $this->uname, 'table');
    $this->rules = new rules($table_xml->rules, $this);
  }


  function sql_infos(){
    parent::sql_infos();
    $this->privileges->sql_infos();
    $this->rules->sql_infos();
  }

  function xml_infos(){
    parent::xml_infos();
    $this->rules->xml_infos();
    $this->privileges->xml_infos();
    foreach($this->keys_xml_def as $k=>&$key){
        if($key['type']!='FOREIGN' || !in_array($key['table'], myks_gen::$tables_ghosts_views))
            continue;
        //the key reference to a ghost table
        unset($this->keys_xml_def[$k]);
        rbx::ok("-- $k is a ghost reference, skipping");
    }
  }


  function modified(){
    return parent::modified()
        || $this->privileges->modified()
        || $this->rules->modified();
  }


  function update(){
    return array_merge(
        parent::update(),
        $this->privileges->alter_def(),
        $this->rules->alter_rules()
    );
  }

  function alter_fields() {
    $table_alter = "ALTER TABLE `$this->name` ";
    $todo = array();
    //fields sync
    foreach($this->fields_xml_def as $field_name=>$field_xml){
        $field_sql = $this->fields_sql_def[$field_name]; 
        if($field_sql){
            unset($this->fields_sql_def[$field_name]);
            if($field_sql==$field_xml) continue;

            $diff = array_diff_assoc($field_xml,$field_sql);
            foreach($diff as $diff_type=>$new_value){
                if($diff_type=="Null"){
                    if(!$new_value && !is_null($field_xml['Default']))
                        $todo[] = "UPDATE `$this->name` "
                            ."SET `$field_name`={$field_xml['Default']} WHERE `$field_name` IS NULL";
                    $todo[] = "$table_alter ALTER COLUMN `$field_name` "
                              .($new_value?"DROP NOT NULL":"SET NOT NULL");
                }elseif($diff_type == "Type")
                    $todo[] = "$table_alter ALTER COLUMN `$field_name` TYPE $new_value";
                elseif($diff_type == "Default"){
                    $value="SET DEFAULT $new_value";
                    if(is_null($new_value))$value="DROP DEFAULT";
                    $todo[] = "$table_alter ALTER COLUMN `$field_name` $value";
                } else { rbx::error("-- UNKNOW type of diff : $diff_type"); }
            }
        } else { //ajout de colonne
            $todo[] = "$table_alter ADD COLUMN `$field_name` {$field_xml['Type']}";
            if(!is_null($field_xml['Default'])){
                $todo[] = "$table_alter ALTER COLUMN `$field_name` "
                          ." SET DEFAULT {$field_xml['Default']}";
                $todo[] = "UPDATE `$this->name` SET `$field_name`={$field_xml['Default']}";
            }
            $todo[] = "$table_alter ALTER COLUMN `$field_name` "
                .($field_xml['Null']?"DROP NOT NULL":"SET NOT NULL");
        }

    } foreach(array_keys($this->fields_sql_def) as $field_name)
        $todo[]="$table_alter DROP `$field_name`";

    return $todo;
  }

  function alter_keys(){
    $table_alter = "ALTER TABLE `$this->name` ";
    $todo = array();
    if($this->keys_xml_def == $this->keys_sql_def) return $todo;

    foreach($this->keys_sql_def as $key=>$def){
        if($this->keys_xml_def[$key] != $def)
            array_unshift($todo, $drop = "$table_alter DROP ".
                (($def['type']=="PRIMARY" || $def['type']=="FOREIGN"|| $def['type']=="UNIQUE")?
                    "CONSTRAINT \"$key\""
                    :"INDEX `$key`") );
        else unset($this->keys_xml_def[$key]);
    }

    foreach($this->keys_xml_def as $key=>$def){
        $members=" (`".join('`,`',$def['members']).'`)';$type=$def['type'];
        $add = "ADD CONSTRAINT $key ".$this->key_mask[$type]." $members ";
        if($type=="INDEX") { $todo[]="CREATE INDEX $key ON `{$this->name}` $members";continue;}
        elseif($type=="FOREIGN"){
            $add.=" REFERENCES {$def['refs']} ";
            if($def['delete']) $add.=" ON DELETE ".self::$fk_actions_out[$def['delete']];
            if($def['update']) $add.=" ON UPDATE ".self::$fk_actions_out[$def['update']];
            if($def['defer']=='defer') $add.=" DEFERRABLE INITIALLY DEFERRED";
        } $todo[]="$table_alter $add";
    }
    return $todo;
  }

  function create() {
    $todo=array();

    foreach($this->fields_xml_def as $field_name=>$field_xml)
        $todo[]="`$field_name` {$field_xml['Type']}";


    foreach($this->keys_xml_def as $key=>$def) {
        if($def['type']!="PRIMARY")continue;
        $members=" (`".join('`,`',$def['members']).'`)';$type=$def['type'];
        $add = "CONSTRAINT $key ".$this->key_mask[$type]." $members ";
        if($def['type']=="INDEX")$todo_exts[]="CREATE INDEX $key ON `{$this->name}` $members";
        else $todo[]=$add;
    }

    $query="CREATE TABLE `$this->name` (\n\t".join(",\n\t",$todo)."\n);\n";

    return $query;
  }




/*
    retourne la définition des colonnes d'une table formaté pour une comparaison avec les tables_xml
*/

 static function table_fields($table_name){
    $strip_types = array("#::[a-z ]+#"=>"","#,\s+#"=>",");
    $where=array('table_name'=>$table_name);
    sql::select("information_schema.columns",$where);
    $columns=sql::brute_fetch('column_name');$table_cols=array();


    foreach($columns as $column_name=>$column){
        if($column['data_type']=="character varying"){
            $column['data_type']="varchar({$column['character_maximum_length']})";
        }
        if($column['column_default'])
            $column['column_default']= preg_areplace($strip_types, $column['column_default']); 

        $table_cols[$column_name]=array(
            'Extra'=>'',
            'Default'=>$column['column_default'],
            'Field'=>$column['column_name'],
            'Type'=>myks_gen::$type_resolver->convert($column['data_type'], 'in'),
            'Null'=>(int)bool($column['is_nullable']),
        );
    } return $table_cols;
  }


}
