<?php

class myks_checks {

  private  $checks_xml;

  private $parent;

  private $sql_def = array();
  private $xml_def = array();

  function __construct($parent, $checks_xml){
    $this->parent       = $parent;
    $this->checks_xml   = $checks_xml;
  }

  function sql_infos(){
    $cols = "check_name, check_clause";
    $verif_table = array(
        'table_name'   => $this->parent->name['name'],
        'table_schema' => $this->parent->name['schema'],
    ); sql::select("zks_information_schema_checks", $verif_table, $cols);
    $this->sql_def = sql::brute_fetch("check_name");
  }

  function xml_infos(){
    $this->xml_def = array(); $i=0;
    foreach($this->checks_xml as $check_xml){ $i++;
        $check_name = pick((string)$check_xml['name'], "{$this->parent->name['name']}_chk_$i");
        $check_type = (string)$check_xml['type'];
        if($check_type != "alternative")
            throw new Exception("Unsupported check type"); // ! todo
        $def = array(); //CHECK (((((if((idxas_playlist IS NULL), 0, 1) + if((planning_id IS NULL), 0, 1)) + if((web_id IS NULL), 0, 1)) + if((astouch_application_id IS NULL), 0, 1)) <= 1))
        foreach($check_xml->member as $member)
            $def []= "if(". ((string)$member['column'])." IS NULL, 0, 1)";
        $def = "CHECK ((".join(' + ', $def).") <= 1)";

        $data = array(
            'check_name'    => $check_name,
            'check_clause'  => $def,
        );

        $this->xml_def[$check_name] = $data;
    }
  }

  function modified(){
    return $this->sql_def != $this->xml_def;
  }

  function alter_def(){
    $todo = array();
    if(!$this->modified())
        return $todo;

    //print_r($this->sql_def);print_r($this->xml_def);die;
    $esc = sql::$esc;

    $drops = array_diff_key($this->sql_def, $this->xml_def);
    foreach($this->xml_def as $to=>$def){
        $current = (array)$this->sql_def[$to];
        if($current == $def) continue;
        $name = "{$esc}$to{$esc}";
        if($current) $todo[] = "ALTER TABLE {$this->parent->name['safe']} DROP CONSTRAINT $name";
        $todo[] = "ALTER TABLE {$this->parent->name['safe']} ADD CONSTRAINT $name {$def['check_clause']}";
    } foreach($drops as $to=>$def){
        $name = "{$esc}$to{$esc}";
        $todo[] = "ALTER TABLE {$this->parent->name['safe']} DROP CONSTRAINT $name";
    }
    return $todo;
  }


}
