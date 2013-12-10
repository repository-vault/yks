<?php
  abstract class myks_fields extends myks_parsed{
    protected $escape_char="`";

    protected  $fields_xml;

    protected $parent;

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
