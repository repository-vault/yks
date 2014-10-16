<?php

class myks_indices {

  private  $indices_xml;

  private $parent;

  private $sql_def = array();
  private $xml_def = array();

  function __construct($parent, $indices_xml){
    $this->parent       = $parent;
    $this->indices_xml   = $indices_xml;
  }

  function sql_infos(){
    $cols = join(',', array('index_name', 'fields', 'uni AS unique'));
    $verif_table = array(
        'table_name'   => $this->parent->name['name'],
        'table_schema' => $this->parent->name['schema'],
    ); sql::select("zks_information_schema_indexes", array_merge($verif_table, array("not(pri)")),$cols);
    $this->sql_def = sql::brute_fetch("index_name");
    sql::select("information_schema.columns", $verif_table);
    $cols = sql::brute_fetch("ordinal_position", "column_name");

    foreach($this->sql_def as $index_name=>&$index_infos) {
        $index_infos['fields'] = array_values(array_sort($cols, explode(' ', $index_infos['fields'])));
        $index_infos['unique'] = bool($index_infos['unique']);
    }
    unset($index_infos);
  }

  function xml_infos(){
    $this->xml_def = array(); $i=0;
    foreach($this->indices_xml as $index_xml){ $i++;
        $index_name = pick((string)$index_xml['name'], "{$this->parent->name['name']}_idx_$i");
        $data = array(
            'index_name' => $index_name,
            'mode'       => (string) $index_xml['mode'],
            'unique'     => bool($index_xml['type']=='unique'),
        );
        foreach($index_xml->member as $member) $data['fields'][] = (string)$member['column'];
        $this->xml_def[$index_name] = $data;
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
      $current = (array) $this->sql_def[$to];
      if($current == $def) continue;
      $full_name = "{$esc}{$this->parent->name['schema']}{$esc}.{$esc}$to{$esc}";
      if($current) $todo[] = "DROP INDEX $full_name";
      $mode   = $def['mode'] ? $def['mode'] : "btree";
      $type   = ($mode != 'btree') ? substr($mode, 0, strpos($mode, '_')) : 'btree';
      $unique = ($mode == 'btree' && $def['unique']) ? "UNIQUE" : "";
      $index  = "CREATE $unique INDEX {$esc}$to{$esc} ON {$this->parent->name['safe']} USING $type ";
      if($mode == "btree") $index .= "(".join(',', $def['fields']).")";
      else $index .= "({$def['fields'][0]} {$mode}_ops)";
      $todo[] = $index;
    } foreach($drops as $to=>$def){
        $full_name = "{$esc}{$this->parent->name['schema']}{$esc}.{$esc}$to{$esc}";
        $todo[] = "DROP INDEX $full_name";
    }
    return $todo;
  }


}
