<?php


  /**	Myks_gen by 131 for Exyks distributed under GPL V2
	this class export the basic field SQL definition from a myks_xml structure
  */

abstract class mykse_base  {

  public $field_def = array();
  protected $table;
  protected $types_tree = array();
  protected $birth      = false;
  protected $depth_max  = 10;
  protected $depth      = 0;
  public $base_type     = 0;

  function  __construct($field_xml, $table){

    $this->table = $table;
    $this->type  = $field_xml['type'];
    $this->field_def=array(
        'Field'=>$field_xml->get_name(),
        'Extra'=>'',
        'Null'=>null,
        'Default'=>isset($field_xml['default'])?(string)$field_xml['default']:null,
    ); $this->resolve($this->type);

    // faut faire tomber les key sur les types qui ne sont pas directs.. 
    // OU si le name dans le  birth est différent du type
        // SAUF si on est sur une primary explicite
    // depth==1 est ok




    $birth_root   = sql::resolve((string)$this->mykse_xml['birth']);
    if($birth_root){
       $table = $this->table->get_name();
       if($birth_root['name']==(string)$table['name']
        && $this->depth==1
        && ($field_xml['type']==$this->field_def['Field']
            || $field_xml['key'] == "primary") ){
            $this->table->key_add('primary',$this->field_def["Field"]);
            $this->birth = true;
            if($this->field_def['Null']) $this->field_def['Null']=false; //pas de null dans le birth
      } else  $this->fk($field_xml, $birth_root);
    }

    $this->get_def(); 


    if(is_null($this->field_def['Null']))
        $this->field_def['Null'] = true;


    $birth_deep = sql::resolve((string)$this->mykse_xml['birth']);
    if($birth_deep
        && !$birth_root
        && $this->depth > 1)
            $this->fk($field_xml, $birth_deep);

    if($field_xml['key'])
        $this->table->key_add("{$field_xml['key']}","{$this->field_def['Field']}");

  }


  private function fk($field_xml, $birth){
     //rbx::ok("FK to ".$field_xml->asXML()." ".json_encode($birth));

    $local_field = $this->field_def["Field"];
    $table_name  = $birth['raw'];
    $birth_xml   = myks_gen::$tables_xml->$table_name;
        //resolve distant table fields name
    $fields = array_keys(fields($birth_xml, true), $this->type);

        //this is complicated, see http://doc.exyks.org/wiki/Myks:External_references_resolutions
    $fields = in_array($this->type, $fields)?array($this->type):array_slice($fields,0,1);

    if(!$fields)
        throw rbx::error("-- Unresolved ext ref on {$this->table}/{$this->type} to {$birth['name']}");

    $this->table->key_add('foreign', $local_field, array(
        "table"    => $birth['name'],
        "refs"     => table::build_ref($birth['schema'], $birth['name'], $fields ), 
        "update"   => (string)$field_xml['update'],
        "delete"   => (string)$field_xml['delete'],
        "defer"    => (string)$field_xml['defer'],
    ));

  }

  protected function resolve($type){
    $this->mykse_xml = myks_gen::$mykse_xml->$type;
    $this->base_type = (string)$this->mykse_xml['type'];
    if($this->depth++ > $this->depth_max && !$this->mykse_xml)
        throw rbx::error("Unable to resolve `{$this->field_def['Field']}`"); 

    $this->field_def['Null']|=$this->mykse_xml['null']=='null';
    $this->default_value($type);

    $this->type=$type;
    $this->types_tree[]=$type;
    return $this;
  }

  protected function get_def(){
    if($this->base_type=="int") $this->int_node();
    elseif($this->base_type=="string") $this->string_node();
    elseif($this->base_type=="enum") $this->enum_node();
    elseif($this->base_type=="text") $this->text_node();
    elseif($this->base_type=="bool") $this->bool_node();
    else $this->resolve($this->base_type)->get_def();

    return $this->field_def;
  }

    //leave it to myks, might be overloaded by driver like $this->field_def["Type"]="boolean";
  function bool_node(){  $this->resolve($this->base_type)->get_def();  }

  function text_node(){
    $this->field_def["Type"]="text";
  }

  function string_node(){ $this->field_def["Type"]="varchar({$this->mykse_xml['length']})"; }


}
