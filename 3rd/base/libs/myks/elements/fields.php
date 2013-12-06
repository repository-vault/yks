<?php
  abstract class myks_fields {
    protected  $fields_xml;

    protected $parent;

    protected $sql_def = array();
    protected $xml_def = array();

    abstract function sql_infos();
    abstract function alter_def();

    function __construct($parent, $fields_xml){
      $this->parent     = $parent;
      $this->fields_xml = $fields_xml;
    }

    function xml_infos(){
      foreach($this->fields_xml->field as $field_xml){
        $mykse=new mykse($field_xml,$this->parent);
        $this->xml_def[$mykse->field_def['Field']] = $mykse->field_def;
      }
    }

    function modified(){
      return $this->sql_def != $this->xml_def;
    }

  }
