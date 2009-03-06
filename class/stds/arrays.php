<?
function make_tree($splat, $root=false, $parent_col ="parent", $children_col=false){
    $tree=array(); $tmp=array();
    while( list($id, $v)=each($splat) ){ $parent = $v[$parent_col]?$v[$parent_col]:$id;
        if(!$tmp[$id]) $tmp[$id]=$children_col?$v:array();
        elseif($children_col) $tmp[$id]=array_merge($v,$tmp[$id]); 
        if($parent==$id ) {
            if(!$tree[$parent]) $tree[$parent] = &$tmp[$id];
            continue;
        }
        if($children_col) $tree[$parent][$children_col][$id] = &$tmp[$id];
        else $tree[$parent][$id] = &$tmp[$id];
    } return $root?array($root=>$tmp[$root]):$tree;
}

function array_next_val($array,$val){ return array_step($array, $val, 1, false); }
function array_step($array,$val,$way=1,$loop=true){
    $tmp=array_search($val,$array)+$way;
    return $array[$loop?(($tmp+count($array))%count($array)):$tmp];
}

function array_sort($array,$keys){
    $keys = array_flip(is_array($keys)?$keys:array_slice(func_get_args(),1));
    return array_intersect_key(array_merge_numeric($keys, $array),$keys,$array);
}

    //!!dont sprintf($v,$k) ! bad thing © use mask_join else
function array_mask($array,$mask){ foreach($array as &$v)$v=sprintf($mask,$v);return $array; }


function mask_join($glue,$array,$mask){
    foreach($array as $k=>&$v) $v=sprintf($mask,$v,$k);
    return join($glue,$array);
}


function array_extract($array, $col, $unique=false){
    $ret=array(); foreach($array as $v) $ret[]=$v[$col];
    return $unique?array_unique($ret):$ret;
}
function array_get($array,$col){return $col?$array[$col]:$array; }

function array_merge_numeric($a,$b){
  foreach($b as $k=>$v)$a[$k]=is_array($v)&&is_array($a[$k])?array_merge($a[$k],$v):$v;return $a;
}
function attributes_to_assoc($x){$tmp=array(); //php 5.3 grrrr
    foreach($x->attributes() as $k=>$v)$r[$k]=(string)$v;
    return $r;
}



function array_sublinearize($a,$c){$ret=array();foreach($a as $k=>$val)$ret[$k]=$val[$c];return $ret;}



function linearize_tree($tree,$depth=0){
    $ret=array();
    foreach($tree as $cat_id=>$children){
        $ret[$cat_id]=array('id'=>$cat_id,'depth'=>$depth);
        if($children)$ret+=linearize_tree($children,$depth+1);
    }return $ret;
}

function array_sort_deep($array,$sort_by,$order='asort'){ 
    $keys=array(); foreach($array as $k=>$v)$keys[$k]=$v[$sort_by]; $order($keys);
    return array_merge_numeric($keys,$array);
}
function array_filter_deep($array,$sort_by,$val){
    $keys=array(); foreach($array as $k=>$id)$keys[$k]=$id[$sort_by]; asort($keys);
    return array_merge($keys,$array);
}


  // Find documentation in the manual
function array_reindex($array,$cols=array()){
    $res=array();if(!is_array($cols))$cols=array($cols);
    foreach($array as $v){
      $tmp=&$res;
      foreach($cols as $col) $tmp=&$tmp[$v[$col]];
      $tmp=$v;
    }return $res;
}
