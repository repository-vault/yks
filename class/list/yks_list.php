<?php

class yks_list {
  public $href;
  public $target;
  public $tables_name;
  private $tables_xml;

  private $filters;
  public $results;
  public $table_index;
  private $by=20;
  private $page_id=0;

  private $results_nb=0;


  function __sleep(){
    foreach($this->tables_xml as $table_name=>$table_xml) {
        if($table_xml instanceOf SimpleXMLElement) 
           $this->tables_xml[$table_name] = $table_xml->asXML();

    }
    $unexport = array( 'by', 'page_id');
    $me = new ReflectionObject($this); $props = array();
    foreach($me->getProperties() as $prop)
        $props[] = $prop->name;
    return array_diff($props, $unexport);
  }

  function __wakeup(){
    foreach($this->tables_xml as $table_name=>$table_xml_str)
        $this->tables_xml[$table_name] = simplexml_load_string($table_xml_str);
  }

  function __construct($table_name, $filters = true){

    $this->tables_set($table_name);

    if(!$filters['filter_context'])
        $filters['filter_context'] = array();
    $this->filters = $filters;

    $this->order_by($this->table_index);

    $this->filters_apply($this->filters['filter_initial']);

  }

  function tables_set($table_name){
        //table_name est une liste de table
    if(!is_array($table_name))
        $table_name = array($table_name);
    $this->tables_name = $table_name;
    $table_name = first($this->tables_name);

        //retourne le nom de la clée primaire de la table
        //on ne s'interesse qu'à la premiere table ici
    $types = yks::$get->types_xml;
    $xpath  ="//*[@birth='$table_name']";
    $this->table_index=current($types->xpath($xpath))->getName();
    $this->tables_xml = array();

    foreach($this->tables_name as $table_name){
        $tmp = yks::$get->tables_xml->$table_name;
        if($tmp) $this->tables_xml[$table_name] = $tmp;
    }
  }

  function filters_apply($filters){
    if(!is_array($filters)) $filters = array();
    $this->filters['filters_results'] = array_merge($this->filters['filter_context'], $filters);
    $this->build_list();
  }

  function build_list(){
    sql::select($this->tables_name, $this->filters['filters_results'], $this->table_index);
    $liste = sql::brute_fetch(false, $this->table_index);
    $this->results = $liste;
    $this->results_nb = count($liste);
  }
  function order_by($field=false,$way="DESC"){
    $this->order_by = array($field?$field:$this->table_index=>$way);
  }
  function get_infos(){
    $order_by =array();
    foreach($this->order_by as $field=>$way)$order_by[]="`$field` $way";
    $order_by = $order_by?"ORDER BY ".join(',', $order_by):'';
    $filters = array( $this->table_index=> $this->results);
    $start = $this->page_id * $this->by;
    sql::select($this->tables_name,  $filters, "*", "$order_by LIMIT $this->by OFFSET $start");
    return sql::brute_fetch($this->table_index);
    
  }
  function repage(){
    jsx::js_eval("Jsx.open('$this->href', '$this->target', this)");
  }
  function page_set($page_id){ $this->page_id = $page_id; }
  function navigation_show(){
    return dsp::pages($this->results_nb, $this->by, $this->page_id, "$this->href//", $this->target);
  }
}


