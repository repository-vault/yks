<?php


class table extends table_base {
  protected $escape_char="\"";

  public $tmp_refs=array();


  private $rules;
  private $privileges;
  private $fields;
  private $triggers;
  private $indices;
  private $checks;

  function __construct($table_xml){
    parent::__construct($table_xml);

    $this->fields      = new fields($this, $this->xml->fields);
    $this->privileges  = new privileges($this, $table_xml->grants, 'table');
    $this->rules    = new rules($this, $table_xml->xpath('rules/rule'), 'table');
    $this->triggers = new myks_table_triggers($this, $table_xml->xpath('triggers/trigger'));
    $this->indices  = new myks_indices($this, $table_xml->xpath('indices/index'));
    $this->checks   = new myks_checks($this, $table_xml->xpath('checks/check'));
  }

   /**
   * Use it when check cannot be in xml
   */
   function check_add($name, $def){
      $this->checks->add_check($name, $def);
   }

  function sql_infos(){
    parent::sql_infos();

    $this->fields->sql_infos();
    $this->privileges->sql_infos();
    $this->rules->sql_infos();
    $this->triggers->sql_infos();
    $this->indices->sql_infos();
    $this->checks->sql_infos();
    $this->constraints->sql_infos();
  }

  function xml_infos(){

    parent::xml_infos();

    $this->fields->xml_infos();
    $this->rules->xml_infos();
    $this->triggers->xml_infos();
    $this->privileges->xml_infos();
    $this->indices->xml_infos();
    $this->checks->xml_infos();
    $this->constraints->xml_infos();
  }


  function modified(){
    if($this->virtual)
      return false;

    return parent::modified()
        || $this->fields->modified()
        || $this->privileges->modified()
        || $this->triggers->modified()
        || $this->indices->modified()
        || $this->checks->modified()
        || $this->constraints->modified()
        || $this->rules->modified();

  }


  function alter_def(){
    if($this->virtual)
      return array();

    return array_merge(
        parent::alter_def(),
        $this->fields->alter_def(),
        $this->privileges->alter_def(),
        $this->triggers->alter_def(),
        $this->indices->alter_def(),
        $this->checks->alter_def(),
        $this->constraints->alter_def(),
        $this->rules->alter_def()
    );
  }

  function create() {
    $todo  = array();
    $parts = array();

    return array("CREATE TABLE {$this->table_name['safe']} ()");
  }
}
