<?php


  /**	Myks_gen by 131 for Exyks distributed under GPL V2
	this class export the basic field SQL definition from a myks_xml structure
  */

abstract class mykse_base {

  public $field_def = array();
  protected $table;
  protected $types_tree = array();
  protected $birth       = false;
  protected $birth_table = null;
  protected $depth_max  = 10;
  protected $depth      = 0;
  public $base_type     = 0;

  function  __construct($field_xml, $table = null){

    $this->table = $table;
    $this->type  = (string)$field_xml['type'];
    $this->field_def = array(
        'Field'   => (string) $field_xml['name'],
        'Extra'   => '',
        'Null'    => isset($field_xml['null'])?$field_xml['null']=='null':null,
        'Default' => isset($field_xml['default'])?(string)$field_xml['default']:null,
    );  $this->resolve($this->type);



    // faut faire tomber les key sur les types qui ne sont pas directs..
    // OU si le name dans le  birth est différent du type
        // SAUF si on est sur une primary explicite
    // depth==1 est ok


    $birth_root   = sql::resolve($this->birth_table);
    if($birth_root && $this->table){
       $table = $this->table->get_name();
       if($birth_root['name']==(string)$table['name']
        && $this->depth==1
        && ($field_xml['type']==$this->field_def['Field']
            || $field_xml['key'] == "primary")
        && $field_xml['key'] != "unique"  ){
            $this->table->key_add('primary', $this->field_def["Field"]);
            $this->field_def['Null'] = false;
            $this->birth = true;
      } else $this->fk($field_xml, $birth_root);
    }

    $this->get_def();

    if(is_null($this->field_def['Null']))
        $this->field_def['Null'] = false;

    $birth_deep = sql::resolve($this->birth_table);
    if($birth_deep
        && !$birth_root
        && $this->depth > 1)
            $this->fk($field_xml, $birth_deep);

    if($field_xml['key'] && $this->table )
        $this->table->key_add("{$field_xml['key']}","{$this->field_def['Field']}");

    if(((string)$this->base_type) == "enum" && $this->table){
      $table_name = $this->table->get_name();

      $name = sprintf('chk_%s_%s_enum', $table_name['name'], $this->field_def['Field']);
      $def  = sprintf("find_in_set(\"%s\", '%s')", $this->field_def['Field'], join(',', vals($this->mykse_xml)));

      if($this->field_def['Null'] == 'null')
        $def .= sprintf(' OR "%s" IS NULL', $this->field_def['Field']);

      $this->table->check_add($name, $def);
    }
  }

  private function fk($field_xml, $birth){
     //rbx::ok("FK to ".$field_xml->asXML()." ".json_encode($birth));

    $local_field = $this->field_def["Field"];
    $table_name  = $birth['raw'];
    $birth_xml   = myks_gen::$tables_xml->$table_name;

        //resolve distant table fields name
    $fields = array_keys(fields($birth_xml, true), $this->type);

        //this is complicated, see http://doc.exyks.org/wiki/Myks:External_references_resolutions
    if($birth_xml)
      $fields = array( $local_field => in_array($this->type, $fields)? (string)$this->type : first(array_slice($fields,0,1)) );
    else
      $fields = array( $local_field => $local_field); // for non declared tables

    if(!$fields)
        throw rbx::error("-- Unresolved ext ref on {$this->table}/{$this->type} to {$birth['name']}");

    $this->table->key_add('foreign', $local_field, array(
        "refs"     => table::build_ref($birth['schema'], $birth['name'], $fields ),
        "update"   => (string)$field_xml['update'],
        "delete"   => (string)$field_xml['delete'],
        "defer"    => (string)$field_xml['defer'],
    ));

  }

  protected function resolve($type){

    //debugbreak("1@172.19.103.21");
    $this->mykse_xml = myks_gen::$mykse_xml->$type;
    $this->base_type = (string)$this->mykse_xml['type'];
    if($this->depth++ > $this->depth_max && !$this->mykse_xml)
        throw rbx::error("Unable to resolve `{$this->field_def['Field']}`");


    if(is_null($this->field_def['Null']) && isset($this->mykse_xml['null']))
        $this->field_def['Null'] = $this->mykse_xml['null']=='null';

    if(is_null($this->birth_table) && $this->mykse_xml['birth']) //override
        $this->birth_table = (string)$this->mykse_xml['birth'];

    $this->default_value($type);

    $this->type = $type;
    $this->types_tree[]=$type;
    return $this;
  }

  protected function get_def(){
    if($this->base_type=="int") $this->int_node();
    elseif($this->base_type=="string") $this->string_node();
    elseif($this->base_type=="enum") $this->enum_node();
    elseif($this->base_type=="text") $this->text_node();
    elseif($this->base_type=="bool") $this->bool_node();
    elseif($this->base_type=="json") $this->json_node();
    elseif($this->base_type=="guid") $this->guid_node();
    else $this->resolve($this->base_type)->get_def();

    return $this->field_def;
  }


  function bool_node(){
    $this->field_def["Type"]="boolean";
  }

  function guid_node(){
    $this->field_def["Type"]="uuid";
  }

  function text_node(){
    $this->field_def["Type"]="text";
  }

  function string_node(){
    $this->field_def["Type"]="varchar({$this->mykse_xml['length']})";
  }


}
