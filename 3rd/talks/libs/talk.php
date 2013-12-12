<?php

class talk extends _sql_base {
  const sql_key = "talk_id";
  const sql_table = "ks_talks_list";
  protected $sql_key   = self::sql_key;
  protected $sql_table = self::sql_table;

  private $order_by = array();

  private static $cols;
  private static $order = array(SORT_DESC => 'DESC', SORT_ASC => 'ASC');
  public static function init(){
    $tables = array('ks_talks_list', 'ks_talks_contents');
    $cols   = array();
    foreach($tables as $table_name) {
      $col = array_fill_keys(array_keys(fields(yks::$get->tables_xml->$table_name)), $table_name);
      $cols = array_merge($cols, $col);
    } unset($cols[self::sql_key]);
    self::$cols = $cols;
  }

  function order($cols){
    $this->order_by = $cols;
  }

  static function instanciate($talk_id){  return first(self::from_ids(array($talk_id))); }
  static function from_ids($ids) {
    $ret = self::from_where(array(self::sql_key=>$ids));
    return array_sort($ret, $ids);
  }

  static function from_where($where){
    $ret = parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
    self::extend_flesh($ret);
    return $ret;
  }

  /*
  SELECT d0.talk_id, count(talk_date), max(talk_date)
  FROM  ks_talks_tree_depth AS d0,
  ks_talks_tree_depth AS d1,
  ks_talks_list  AS list
  WHERE TRUE
  AND d0.parent_id = 6
  AND d0.talk_depth = 1
  AND d1.parent_id = d0.talk_id
  AND list.talk_id = d1.talk_id
  GROUP BY d0.talk_id
  */

  protected function get_children_tree_ids($max_depth){
    $verif  = array('parent_id'=> $this->talk_id);
    if($max_depth) {
      $verif [] = "talk_depth <= $max_depth";
    }

    $tables = array();
    if($this->order_by) {
      $tables = array_merge($tables, array_unique(array_intersect_key(self::$cols, $this->order_by)));
    }

    $tables_str = "`ks_talks_tree_depth`";
    foreach($tables as $table_name) {
      $tables_str .= sprintf(" LEFT JOIN `%s` USING(%s)", $table_name, self::sql_key);
    }

    $order = array();
    $sort = array_merge(array("talk_depth" => SORT_ASC), $this->order_by);
    foreach($sort as $col_name=>$sort) {
      $order []= "$col_name ".self::$order[$sort];
    }

    $order = "ORDER BY ".join(',', $order);

    sql::select($tables_str, $verif, 'talk_id', $order);
    $children  = sql::fetch_all();

    if(in_array($this->talk_id, $children)) { //root only
      $children = array_diff($children, array($this->talk_id));
    }

    return $children;
  }

  protected function get_children_tree($max_depth = null){
    $children = $this->get_children_tree_ids($max_depth);
    $children = self::from_ids($children);

    $tree_splat  = array_merge_numeric($this->collection, $children );
    foreach($tree_splat as $node) $node->children_tree = array();

    foreach($tree_splat as $node) {
      $tree_splat[$node['parent_id']]->children_tree[$node->talk_id] = $node;
    }

    return $this->children_tree;
  }

  protected function get_children_ids($order_by = null){
    if($order_by) $this->order($order_by);
    return $this->children_ids = $this->get_children_tree_ids(1);
  }

  function get_children_nb(){
    return count($this->children_ids);
  }

  function get_children(){
    $children = $this->children_ids;
    return self::from_ids($children);
  }

  function __toString(){
    return pick($this->talk_title, "#{$this->talk_id}");
  }

  function get_children_range($start, $by, $sort = null){

    if(!$sort) $children = $this->children_ids;
    else $children = $this->get_children_ids($sort);

    $children = array_slice($children, $start, $by, true);
    return self::from_ids($children);
  }

  function update($input){
    $tables   = array(self::sql_table, 'ks_talks_contents');
    foreach($tables as $table_name){
      $fields = fields(yks::$get->tables_xml->$table_name);
      $data = array_intersect_key($input, $fields);
      if(!$data) continue;
      sql::replace($table_name, $data, $this->verif);
      $this->data  = array_merge($this->data, $data);
    }
  }

  static function get_last_stats($roots){
    sql::query("SELECT * FROM `ks_talks_tree_depth`
    LEFT JOIN `ks_talks_list` USING(talk_id)
    WHERE (parent_id, talk_date) IN (
    SELECT parent_id, MAX(talk_date) FROM `ks_talks_tree_depth`
    LEFT JOIN `ks_talks_list` USING(talk_id)
    WHERE ".sql::in_join('parent_id', array_keys($roots))."
    GROUP BY parent_id)");
    $stats = sql::brute_fetch("parent_id");
    foreach($roots as $node_id=>$node)
      $node->last_stats = $stats[$node_id];
  }

  static function create($input){
    if(!$input['user_id']) {
      $input['user_id'] = sess::$sess['user_id'];
    }

    $fields = fields(yks::$get->tables_xml->{self::sql_table});
    $data = array_intersect_key($input, $fields);
    $talk_id = sql::insert(self::sql_table, $data, true);
    $talk = self::instanciate($talk_id);
    if(!$talk) throw rbx::error("Cannot create node".print_r(sql::$queries,1));
    $talk->update($input);
    return $talk;
  }

  function get_parents(){
    $order = "ORDER BY talk_depth DESC";
    sql::select("ks_talks_tree_depth", array('talk_id' => $this->talk_id), "parent_id", $order);
    $parents  = sql::fetch_all();

    return $this->parents = self::from_ids($parents);
  }

  protected function get_verif(){
    return array(self::sql_key => $this->{self::sql_key});
  }

  protected static function extend_flesh($nodes) {
    $tables = "`ks_talks_tree` LEFT JOIN `ks_talks_contents` USING(talk_id)";
    sql::select($tables, array(self::sql_key => array_keys($nodes)));
    $data = sql::brute_fetch(self::sql_key);
    foreach($nodes as $node_id=>$node) {
      if($data[$node_id]) $node->data = array_merge($node->data, $data[$node_id]);
    }
  }

  //as in collection member
  protected function get_collection() {
    return array($this->talk_id => $this);
  }

  protected function get_children_stats() {
    talk_stats::get_children_stats($this->collection);
  }

  function adopt($node) {
    $data = array('talk_id' =>$node->talk_id, 'parent_id' => $this->talk_id);
    sql::insert("ks_talks_tree", $data);
    $this->children_ids [] = $node->talk_id;
  }

  function abandon(talk $node) {
    $verif_delete = array('talk_id' =>$node->talk_id, 'parent_id' => $this->talk_id);
    sql::delete("ks_talks_tree", $verif_delete);
    $this->children_ids  = array_diff($this->children_ids, array($node->talk_id));
  }

  function get_depth() {
    return count($this->parents);
  }

  function get_parents_path() {
    $verif_tree = array('talk_id' => $this->talk_id);
    sql::select("ks_talks_tree", $verif_tree, "parent_id", "ORDER BY talk_depth ASC");
    $this->parents_path = sql::fetch_all();
    return $this->parents_path;
  }

  function can_modify() {
    $is_mine = $this->user_id == sess::$sess['user_id'];
    return $is_mine;
  }

  function can_delete() {
    $is_mine = $this->user_id == sess::$sess['user_id'];
    return $is_mine;
  }
}
