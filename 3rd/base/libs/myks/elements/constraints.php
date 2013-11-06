<?php

abstract class myks_constraints_base {

  private  $constraints_xml;

  protected $escape_char="`";
  public $key_mask=array("PRIMARY"=>'PRIMARY KEY',  "INDEX" => "INDEX", "UNIQUE"=>'UNIQUE', 'FOREIGN'=>'FOREIGN KEY' );

  protected $parent;

  static $fk_actions_in = array('NO ACTION'=>'no_action', 'CASCADE'=> 'cascade', 'SET NULL'=>'set_null');
  static $fk_actions_out = array('no_action'=>'NO ACTION', 'cascade'=>'CASCADE','set_null'=> 'SET NULL');

  protected $sql_def = array();
  protected $xml_def = array();


  protected $keys_name = array(        // $this->table_name, $field, $type
    'PRIMARY'=>"%s_pkey",
    'UNIQUE'=>"%s_%s_u_%s",
    'FOREIGN'=>"%s_%s_%s",
  );

  function __construct($parent, $constraints_xml){
    $this->parent       = $parent;
    $this->constraints_xml   = $constraints_xml;
  }

  //retro mykse
  public function key_add($type, $member, $refs=array()){
    $TYPE = strtoupper($type);

    $key_name = sprintf($this->keys_name[$TYPE], $this->parent->name['name'], $member, $type);
    $key_name = substr($key_name, 0, 63);

    $this->key_stack($type, array($member => $member), $key_name, $refs);
  }

  //use this insteadof key_add
  protected function key_stack($type, $members, $key_name, $refs=array()){
    $TYPE=strtoupper($type);

    $this->xml_def[$key_name] = $refs;
    $this->xml_def[$key_name]['type'] = $TYPE;
    $this->xml_def[$key_name]['members'] = $members;


  }


  function sql_infos(){
   throw new Exception("To be tested...");

  }

  function xml_infos(){
    $i=0;
    foreach($this->constraints_xml as $constraint_xml){
        $i++;
        $type  = strtoupper((string)$constraint_xml['type']);

        foreach($constraint_xml->member as $member) $members[(string)$member['column']] = (string)$member['column'];

        if(!($key_name = (string)$constraint_xml['name'])){

          $key_name = "{$this->parent->name['name']}_".strtolower($type)."_$i";
        }

        $fk_name = sql::resolve((string)$constraint_xml['fk_table']);

        $refs = array(
          "refs"     => self::build_ref($fk_name['schema'], $fk_name['name'], array_values($members)),
          "update"   => (string)$constraint_xml['update'],
          "delete"   => (string)$constraint_xml['delete'],
          "defer"    => (string)$constraint_xml['defer'],
        );

        $this->key_stack($type, $members, $key_name, $refs);
    }

  }

  public static function build_ref($table_schema, $table_name, $table_fields){
    return compact('table_schema', 'table_name', 'table_fields');
 }

  function modified(){
    return $this->sql_def != $this->xml_def;
  }

  function alter_def(){
    $ec = '"';
    $table_alter = "ALTER TABLE {$this->parent->name['safe']} ";
    $todo = array();
    if($this->xml_def == $this->sql_def) return $todo;

    foreach($this->sql_def as $key=>$def){
        if($this->xml_def[$key] != $def)
            array_unshift($todo, $drop = "$table_alter DROP ".
                (($def['type']=="PRIMARY" || $def['type']=="FOREIGN"|| $def['type']=="UNIQUE")?
                    "CONSTRAINT {$ec}$key{$ec}"
                    :"INDEX {$ec}$key{$ec}") );
        else unset($this->xml_def[$key]);
    }

    foreach($this->xml_def as $key=>$def){
        $members=' ("'.join('","',$def['members']).'")';
        $type=strtoupper($def['type']);
        $add = "ADD CONSTRAINT $key ".$this->key_mask[$type]." $members ";
        if($type=="INDEX") { $todo[]="CREATE INDEX $key ON {$this->table_name['safe']} $members";continue;}
        elseif($type=="FOREIGN"){
            $add.=" REFERENCES ".self::output_ref($def['refs'])." ";
            if($def['delete']) $add.=" ON DELETE ".self::$fk_actions_out[$def['delete']];
            if($def['update']) $add.=" ON UPDATE ".self::$fk_actions_out[$def['update']];
            if($def['defer']=='defer') $add.=" DEFERRABLE INITIALLY DEFERRED";
        } $todo[]="$table_alter $add";
    }
    return $todo;
  }

   public static function output_ref($ref){
    return  sprintf('"%s"."%s"("%s")',
        $ref['table_schema'],
        $ref['table_name'],
        join('","',$ref['table_fields']) );
 }
}
