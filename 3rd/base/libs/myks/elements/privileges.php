<?php

class privileges {
  private static $root_privileges = array();
  private  $grants_xml;

  private $parent_type;
  private $parent;

  private $sql_def = array();
  private $xml_def = array();

  function __construct($parent, $grants_xml, $parent_type){
    $this->parent_type  = $parent_type;
    $this->parent       = $parent;
    $this->grants_xml   = $grants_xml;
  }

  static function declare_root_privileges($config){
    if(!$config) return; $privileges = array();
    foreach($config->grant_all as $grant){
        $type = (string)$grant['on'];
        $privileges[$type] = self::merge($privileges[$type] , self::parse($grant));
    }self::$root_privileges = $privileges;
  }

  function modified(){
    return $this->sql_def != $this->xml_def;
  }

  function alter_def(){
    $todo = array();
    if(!$this->modified())
        return $todo;

    $drops = array_diff_key($this->sql_def, $this->xml_def);
    foreach($this->xml_def as $to=>$def){
        $current = (array)$this->sql_def[$to];
        if($current == $def) continue;
        if($erase = array_diff($current, $def)) $drops[$to] = $erase;
        if(!($missing = array_diff($def, $current))) continue;
        $missing = join(',', $missing);
        $todo[] = "GRANT $missing ON {$this->parent->name['safe']} TO ".self::to($to);
    } foreach($drops as $to=>$def){
        $def = join(',', $def);
        $todo[] = "REVOKE $def ON {$this->parent->name['safe']} FROM  ".self::to($to);
    }
    return $todo;

  }


  function to($to){
    return ($to != "PUBLIC")?'"'.$to.'"':$to;
  }

  function sql_infos(){
    $verif_table = array(
        'table_name'   => $this->parent->name['name'],
        'table_schema' => $this->parent->name['schema']
    ); sql::select("information_schema.table_privileges", $verif_table);
    $this->sql_def = sql::brute_fetch_depth('grantee', 'privilege_type', false);
  }

  function xml_infos(){
    $privileges = self::$root_privileges[$this->parent_type];

    if($this->grants_xml->grant)
      foreach($this->grants_xml->grant as $grant)
        $privileges = self::merge($privileges, self::parse($grant));
    $this->xml_def = (array)$privileges;
  }

  private static function parse($grant){
    $vals = array_filter(preg_split(VAL_SPLITTER, strtoupper($grant['actions'])));
    $to   = array_filter(preg_split(VAL_SPLITTER, $grant['to']));
    if(!$vals)
      throw rbx::error("Invalid actions on grant ! ");
    if(!$to)
      throw rbx::error("Invalid to on grant !");
    return array_fill_keys($to, array_combine($vals, $vals));
  }

  private static function merge($grant1, $grant2){
    return array_merge_numeric((array)$grant1, (array)$grant2);
  }


}
