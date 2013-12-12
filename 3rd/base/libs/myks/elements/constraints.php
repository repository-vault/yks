<?php

abstract class myks_constraints_base {

  private  $constraints_xml;

  protected $escape_char="`";
  public $key_mask=array("PRIMARY"=>'PRIMARY KEY',  "UNIQUE"=>'UNIQUE', 'FOREIGN'=>'FOREIGN KEY' );

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

    if($TYPE == "PRIMARY")
        $refs['defer'] = 'strict'; //primary keys cannot be defered

    $key_name = sprintf($this->keys_name[$TYPE], $this->parent->name['name'], $member, $type);
    $key_name = substr($key_name, 0, 63);
    $this->key_stack($type, array($member => $member), $key_name, $refs);
  }

  //use this insteadof key_add
  protected function key_stack($type, $members, $key_name, $refs=array()){
    $TYPE=strtoupper($type);
    if(!isset($this->xml_def[$key_name]))
      $this->xml_def[$key_name] = array('members' => array());

    if($refs['defer'] == 'best')
        $refs['defer'] = ($TYPE == "FOREIGN") ? "defer" : "strict"; //8.2 compat


    $this->xml_def[$key_name] = array_merge($this->xml_def[$key_name], $refs);
    $this->xml_def[$key_name]['type'] = $TYPE;
    $this->xml_def[$key_name]['members'] = array_merge($this->xml_def[$key_name]['members'], $members);


  }


  function sql_infos(){
   throw new Exception("To be tested...");

  }

  function xml_infos(){
    $i=0;
    foreach($this->constraints_xml as $constraint_xml){
        $i++;
        $type  = strtoupper((string)$constraint_xml['type']);

        $target_members = array();
        $members = array();
        foreach($constraint_xml->member as $member) {
          $members[(string)$member['column']] = (string)$member['column'];
          $target_members[(string)$member['column']] = (string)$member['target'];;
        }

        if(!($key_name = (string)$constraint_xml['name']))
          $key_name = "{$this->parent->name['name']}_".strtolower($type)."_$i";


        $refs = array(
            "defer"    => (string)$constraint_xml['defer']
        );

        if($type == "FOREIGN") {
          $fk_name = sql::resolve((string)$constraint_xml['fk_table']);
           $refs = array_merge($refs, array(
            "refs"     => self::build_ref($fk_name['schema'], $fk_name['name'], $target_members),
            "update"   => (string)$constraint_xml['update'],
            "delete"   => (string)$constraint_xml['delete'],
          ));
        }
        

        $this->key_stack($type, $members, $key_name, $refs);
    }

  }

  public static function build_ref($table_schema, $table_name, $table_fields){
    return compact('table_schema', 'table_name', 'table_fields');
 }

  function modified(){
    //print_r(array_show_diff($this->sql_def, $this->xml_def,"sql","xml"));die;
    return $this->sql_def != $this->xml_def;
  }

  function alter_def(){
    //print_r(array_show_diff($this->sql_def, $this->xml_def,"sql","xml"));

    $ec = '"';
    $table_alter = "ALTER TABLE {$this->parent->name['safe']} ";
    $todo = array();
    if($this->xml_def == $this->sql_def) return $todo;

    foreach($this->sql_def as $key=>$def){
        if($this->xml_def[$key] != $def)
            array_unshift($todo, $drop = "$table_alter DROP CONSTRAINT {$ec}$key{$ec}" );
        else unset($this->xml_def[$key]);
    }

    foreach($this->xml_def as $key=>$def){
        $members = ' ("'.join('","', $def['members']).'")';
        $type    = strtoupper($def['type']);
        $add     = "ADD CONSTRAINT $key ".$this->key_mask[$type]." $members ";

        if($type == "FOREIGN"){
            $add .= " REFERENCES ".self::output_ref($def['refs'])." ";
            if($def['delete']) $add .= " ON DELETE ".self::$fk_actions_out[$def['delete']];
            if($def['update']) $add .= " ON UPDATE ".self::$fk_actions_out[$def['update']];
        }

        if($def['defer']=='defer')
            $add .= " DEFERRABLE INITIALLY DEFERRED";

        $todo[] = "$table_alter $add";
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
