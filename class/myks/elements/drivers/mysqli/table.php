<?

class table extends table_base {

  public $tmp_refs=array();
  static $fk_actions_in = array('NO ACTION'=>'no_action', 'CASCADE'=> 'cascade', 'SET NULL'=>'set_null');
  static $fk_actions_out = array('no_action'=>'NO ACTION', 'cascade'=>'CASCADE','set_null'=> 'SET NULL');
  protected $key_mask=array("PRIMARY"=>"PRIMARY KEY","INDEX"=>"INDEX `%s`","UNIQUE"=>"UNIQUE `%s`");

  protected $key_update=array("PRIMARY"=>"PRIMARY KEY", "UNIQUE"=>"UNIQUE ");

  protected $keys_name = array(        // $this->uname, $field, $type
    'PRIMARY'=>"PRIMARY", 
    'UNIQUE'=>"%s_%s_%s",
  );

  function update(){
    return array_merge(
        $this->alter_fields(),
        $this->alter_keys()
    );
  }


  function alter_fields(){
    $todo = array();
    $table_alter = "ALTER TABLE `$this->name` ";


    foreach($this->fields_xml_def as $field_name=>$field_xml){
        $field_sql = $this->fields_sql_def[$field_name]; 
        if($field_sql){
            unset($this->fields_sql_def[$field_name]);
            if($field_sql==$field_xml) continue;
            $todo[] = "$table_alter MODIFY ".mykse::linearize($field_xml);
        } else { //ajout de colonne
            $todo[] = "$table_alter ADD COLUMN `$field_name` {$field_xml['Type']}";
            if(!is_null($field_xml['Default'])){
                $todo[] = "UPDATE `$this->name` SET `$field_name`={$field_xml['Default']}";
            }
            $todo[] = "$table_alter MODIFY ".mykse::linearize($field_xml);
        }
    } foreach(array_keys($this->fields_sql_def) as $field_name)
        $todo[]="$table_alter DROP `$field_name`";
    return $todo;
  }

  function alter_keys(){
    $todo = array();
    $table_alter = "ALTER TABLE `$this->name` ";
    if($this->keys_xml_def == $this->keys_sql_def) return $todo;

    foreach($this->keys_sql_def as $key=>$def){
        if($this->keys_xml_def[$key] != $def){
                    if($def['type']=="PRIMARY") $drop = "$table_alter DROP PRIMARY KEY";
            else $drop = "$table_alter DROP INDEX `$key`";//TODO
                    array_unshift($todo, $drop );
            } else unset($this->keys_xml_def[$key]);
    }

    foreach($this->keys_xml_def as $key=>$def){
        $members=" (`".join('`,`',$def['members']).'`)';$type=$def['type'];
        $add = "ADD CONSTRAINT ".sprintf($this->key_mask[$type],$key)." $members ";
        if($type=="INDEX") { $todo[]="CREATE INDEX $key ON `{$this->name}` $members";continue;}
        if($type=="FOREIGN"){
            $add.=" REFERENCES {$def['refs']} ";
            if($def['delete']) $add.=" ON DELETE ".self::$fk_actions_out[$def['delete']];
            if($def['update']) $add.=" ON UPDATE ".self::$fk_actions_out[$def['update']];
            if($def['defer']=='defer') $add.=" DEFERRABLE INITIALLY DEFERRED";
        }
        $todo[]="$table_alter $add";
    }
    return $todo;
  }




 static function table_fields($table_name){

    sql::query("SHOW FULL COLUMNS FROM `$table_name`");
    $test = sql::brute_fetch('Field');
    $table_cols=array();

    foreach($test as $column_name=>$column){

        $data=array(
            'Extra'=>$column['Extra'],
            'Default'=>$column['Default']?"'{$column['Default']}'":$column['Default'],
            'Field'=>$column_name,
            'Type'=> $column['Type'],
            'Null'=>($column['Null']=="YES"),
        );
        if($data['Default']==='' || ($data['Type']=='text' && !$data['Null']) ){
            $type = reset(explode('(',$data['Type']));
            if($type=="enum" || $type=="set") $data['Default']=null;
            else  $data['Default']="''";
        }
        $table_cols[$column_name]=$data;

    } return $table_cols;
  }

  function create() {
    $todo=array();

    foreach($this->fields_xml_def as $field_name=>$field_xml)
        $todo[] = mykse::linearize($field_xml);

    foreach($this->keys_xml_def as $key=>$def) {
        if(($type=$def['type'])!='PRIMARY') continue;
        $todo[]=$this->key_mask[$type]." (`".join('`,`',$def['members']).'`)';   
    }

    $query="CREATE TABLE `$this->name` (\n\t".join(",\n\t",$todo)."\n)";
    $query.=";\n";
    return $query;
    die($query);

    $description=(string)$this->xml->description;
    if($description) $query.="\n\t COMMENT '".addslashes($description)."'";
    $query.=";\n";
    return $query;
  }


}
