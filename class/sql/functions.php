<?php

class sql_func {
  static public function get_children($key, $table, $field, $depth=-1, $where = array()){
    return self::get_tree($key,$table,$field,$depth, "parent_id", $where);
  }

  static public function get_parents($key, $table, $field, $depth=-1, $parent = "parent_id",
        $where = array()){
    return array_unique(array_merge((array)$key,
        self::get_tree($key, $table, $parent, $depth, $field, $where)));
  }

  static public function get_parents_path($start, $table, $field){
    $tree = array();
    do{  $tree[]=$start; $l=sql::row($table,array($field=>$start),'parent_id');
    } while( !in_array($start=$l['parent_id'],$tree) && $start);
    return array_reverse(array_filter($tree));
  }

  static public function get_tree($key, $table, $field, $depth, $parent, $where=array()) {
    if(!$key || $depth==0)return array(); if(!is_array($key))$key=array($key);
    $where[$parent] = $key; sql::select($table, $where, $field);
    $list = array_filter(sql::fetch_all());
    return array_merge($list, self::get_tree(array_diff($list,$key),$table,$field,$depth-1,$parent) );
  }

/**
    Return a linear list of all access needed nodes based on an initial leafs list
**/
  function filter_parents($keys,$table,$col){
    sql::select($table,array($col=>$keys),'parent_id');
    return ($diff=array_diff(sql::brute_fetch(false,'parent_id'),$keys))?
        self::filter_parents(array_unique(array_merge($keys,$diff)),$table,$col)
        :$keys;
  }


/** Make a recursive tree based from a SQL query
    need column : id, parent
 */
  function make_tree_query($query, $root=false, $inverted = false){ sql::query($query);
    return make_tree(sql::brute_fetch('id', 'parent'), $root, $inverted);
  }



//***************** Metas *******************
  static function table_infos($table_name){
    $name = sql::resolve($table);
    $where=array('table_schema'=>$name['schema'], 'table_name'=>$name['name']);
    return sql::row("information_schema.tables",$where);
 }

  /**
  * Explode in array a search string
  *
  * @param String $string
  * @return arrray|bool
  */
  static function explode_search_blob($qs){
    $qs = specialchars_decode(trim($qs));
    $mask = "#(-)?(\\#)?(?:\"([^\"]+)\"|'([^']+)'|([^\s,]+))|(\s*,\s*)#";

    if(!preg_match_all($mask, $qs, $out, PREG_SET_ORDER))
        return false;

    return $out;
  }

  static function make_search_blob($search_field, $qs, $main_field = null, $LIKE = "ILIKE"){

    $out = self::explode_search_blob($qs);
    if(!$out)
      return false;

    $part = 0;
    $ret = array();
    foreach($out as $arg){

        if($arg[6]) {  $part ++; continue; }
        $mode  = $arg[1];
        $numeric = $arg[2] == '#';

        $value = specialchars_encode(pick($arg[3], $arg[4], $arg[5]));
        if($numeric && $main_field) {
          $ret[$part][] = sql::conds($main_field, $value);
        } else {
          if($mode == "-")
              $ret [$part][] = "$search_field NOT $LIKE '%$value%'";
          else
              $ret [$part][] = "$search_field $LIKE '%$value%'";
        }
    }

    $search = "";
    foreach($ret as $part=>&$alternatives)
        $alternatives = '('.join(' AND ', $alternatives).')';
    $search = "(".join(' OR ', $ret).")";

    return $search;
  }

}


function enum_to_int($user_vals,$type){
    $res=0;$lvl=0;$vals=array();if(!is_array($user_vals))$user_vals=explode(',',$user_vals);
    foreach($type->enum->val as $val)$vals[pow(2,$lvl++)]="$val";
    return array_sum(array_intersect_key(array_flip($vals),array_flip($user_vals)));
}

function set_export($array,$val){
    foreach(array_keys($array) as $k)if(($k&$val)!=$val)unset($array[$k]);
    return join(',',$array);
}

