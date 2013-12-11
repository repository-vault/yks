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

    foreach($this->parent->comment_xml->xpath("checks/check") as $check)
        $this->sql_def[(string)$check['name']]['check_sign'] = (string)$check['sign'];

    $this->parent->comment_xml->checks = new SimpleXMLElement("<checks/>");

  }

  private function generate_sign_xml(){
    foreach($this->xml_def as $check_name => $check_def)  {
        $sign = hash_hmac( 'md5', $check_def['check_clause'], $this->sql_def[$check_name] ['check_clause']);
        $this->xml_def[$check_name]['check_sign'] = $sign;
        $tmp = $this->parent->comment_xml->checks->addChild("check");
        $tmp['name'] = $check_name; $tmp['sign'] = $sign;
    }
  }

  function xml_infos(){
    if(empty($this->xml_def)){
      $this->xml_def = array();
      $i=0;
    }
    else{
      $i = count($this->xml_def);
    }

    foreach($this->checks_xml as $check_xml){ $i++;
        $check_name = pick((string)$check_xml['name'], "{$this->parent->name['name']}_chk_$i");
        $check_type = (string)$check_xml['type'];
        if($check_type == 'def') {
          $def = trim((string) $check_xml);
        } elseif($check_type == "alternative") {
            $def = array(); //CHECK (((((if((idxas_playlist IS NULL), 0, 1) + if((planning_id IS NULL), 0, 1)) + if((web_id IS NULL), 0, 1)) + if((astouch_application_id IS NULL), 0, 1)) <= 1))
            foreach($check_xml->member as $member)
                $def []= "if(". ((string)$member['column'])." IS NULL, 0, 1)";
            $def = "((".join(' + ', $def).") <= 1)";
        } else {
            throw new Exception("Unsupported check type");
        }
        $data = array(
            'check_name'    => $check_name,
            'check_clause'  => $def,
        );

        $this->xml_def[$check_name] = $data;
    }
  }

  function add_check($name, $def){
    $this->xml_def[$name] = array(
      'check_name'   => $name,
      'check_clause' => $def,
    );
  }

  function modified(){
    $this->generate_sign_xml();
    $sql = array_extract($this->sql_def, "check_sign");
    $xml = array_extract($this->xml_def, "check_sign");
    return $sql != $xml;
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
        if($current['check_sign'] == $def['check_sign']) continue;
        $name = "{$esc}$to{$esc}";
        if($current) $todo[] = "ALTER TABLE {$this->parent->name['safe']} DROP CONSTRAINT $name";
        $todo[] = "ALTER TABLE {$this->parent->name['safe']} ADD CONSTRAINT $name CHECK ( {$def['check_clause']} )";
    } foreach($drops as $to=>$def){
        $name = "{$esc}$to{$esc}";
        $todo[] = "ALTER TABLE {$this->parent->name['safe']} DROP CONSTRAINT $name";
    }
    if($todo)
        $todo = array_merge($todo, $this->parent->save_comment());
    return $todo;
  }


}
