<?php

abstract class cms_node extends _sql_base {
  const sql_key   = "node_id";
  const sql_table = "ks_cms_nodes_list";

  protected $sql_table = self::sql_table;
  protected $sql_key   = self::sql_key;

  protected $manager  = "cms_nodes_manager";
  public static $table_type = array();

  public $editables = array();


  static function init(){
    $tables_xml = yks::$get->tables_xml;
    $node_types  = vals(yks::$get->types_xml->node_type);
    foreach($node_types as $node_type) {
        $table_name = sprintf("ks_cms_nodes_%s", $node_type);
        if($tables_xml->$table_name)
            self::$table_type[$node_type] = $table_name;
    }

  }

  function __construct($from){

    if(is_a($from, __CLASS__) && get_class($this) !== __CLASS__){
        $this->data = $from->data;
    } else {
        if(is_numeric($from)) {
            $data = first(cms_nodes_manager::build_nodes(array('node_id' => $from)));
            parent::__construct($data);
        } else parent::__construct($from);

    }


    if(get_class($this) == __CLASS__)
        return ;

    if($this->base_type != get_class($this))
        throw new Exception("Inconsistant type ");
  }


  protected function get_base_type(){
    return "cms_node_".strtolower($this->node_type);
  }



  public static function instanciate($value){
    $data = first(cms_nodes_manager::build_nodes(array('node_id' => $value)));
    if(!$data)
        throw new Exception("Invalid instanciate $from");

    $node_class = "cms_node_{$data['node_type']}";
    return new $node_class($data);

  }

  function native_element(){
    $node_type  = $this->node_type;
    
    return new $node_class($this);
  }


  function save($data){
    return cms_nodes_manager::save($this, $data);
  }

  function __toString(){
    $args = func_get_args();  array_unshift($args, $this->node_type);
    if($this->node_key) $args[] = sprintf('"%s"', $this->node_key);
    return "#{$this->node_id} (".join(', ', $args).")";
  }

  static function from_where($where){
    return cms_nodes_manager::from_where($where);
  }

  protected function get_parent_node(){
    $parent_id = $this->parent_id;

    return $this->parent_node = ($parent_id) ? self::instanciate($parent_id) : null;
    
  }
  
  public function get_pagination(){
    $filter = array(
      'node_type' => 'article',
      'parent_id' => $this->parent_id,
    );
    sql::select(self::sql_table, $filter, "*", "ORDER BY node_order ASC");
    $nodes = sql::brute_fetch();
    
    $pagination = array('total' => 0, 'current' => 0);
    foreach($nodes as $n){
      $pagination['total']++;
      if($n['node_id'] == $this->node_id)
        $pagination['current'] = $pagination['total'];
    }
    return $pagination;
  }

  static function from_ids($ids){
    die("UNIMPLEMENTED");
    return parent::from_ids(__CLASS__, self::sql_table, self::sql_key, $ids);
  }

}
