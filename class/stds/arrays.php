<?php

function make_tree($splat, $root=false, $inverted = false){
    $tree = array();
    if(!$inverted) { $_parent = "parent"; $_id = "id"; }
    else { $_parent = "id"; $_id = "parent"; }

    foreach($splat as $id=>$parent){
        if(!$tree[$$_id]) $tree[$$_id]=array();
        if($$_parent!=$$_id) $tree[$$_parent][$$_id] = &$tree[$$_id];
    } return $root!==false?array($root=>$tree[$root]):$tree;
}


    //return the first non empty valid value (last arg is a list of possibles values)
function pick_in(){
    $args = func_get_args();

    $possibles = array_pop($args);
    $value = reset(array_filter($args));
    return in_array($value, $possibles) ? $value : reset($possibles);
}

function pick_between($i, $min, $max) { return  min(max($min, (int) $i), $max); }

function array_next_val($array,$val){ return array_step($array, $val, 1, false); }
function array_step($array,$val,$way=1,$loop=true){
    $tmp=array_search($val,$array)+$way;
    return $array[$loop?(($tmp+count($array))%count($array)):$tmp];
}

function array_sort($array, $keys){
    $keys = is_array($keys)?$keys:array_slice(func_get_args(),1);
    if(is_object($array)) {
        $tmp = array(); foreach($keys as $k) if(isset($array[$k]))$tmp[$k] = $array[$k];
        return $tmp;
    }
    $keys = array_flip($keys);
    return array_intersect_key(array_merge_numeric($keys, $array), $keys, $array);
}



function mask_join($glue,$array,$mask){
    foreach($array as $k=>&$v) $v=sprintf($mask,$v,$k);
    return join($glue,$array);
}



function json_encode_lite($json){
    $json = json_encode($json);
    $json = preg_replace("#\\\/(?!script)#", "/", $json);

    $json = preg_replace("#([\"])([0-9]+)\\1#","$2",$json);//dequote ints
    $json = unicode_decode($json);
    $json = str_replace("&quot;","\\\"",$json);

    return $json;
}


function array_extract($array, $col, $clean=false){
    $ret=array();
    if(is_array($col)) foreach($array as $k=>$v) $ret[$k] = array_sort($v, $col);
    elseif($array instanceof simplexmlelement) foreach($array as $k=>$v) $ret[] = (string)$v[$col];
    else foreach($array as $k=>$v) $ret[$k]=$v[$col];
    return $clean?array_filter(array_unique($ret)):$ret;
}
function array_get($array,$col){return $col?$array[$col]:$array; }

function array_merge_numeric($a,$b, $depth="array_merge"){
    $args = func_get_args(); $res = array_shift($args);
    $depth = is_string(end($args)) ? array_pop($args) : "array_merge";

    for($i=0;$i<count($args);$i++)
      foreach($args[$i] as $k=>$v)
        $res[$k] = is_array($v) && is_array($res[$k]) ? $depth($res[$k], $v) : $v;
    return $res;
}

function attributes_to_assoc($x, $ns=null, $prefix = false){$r=array(); //php 5.3 grrrr
    if($x) foreach($x->attributes($ns, $prefix) as $k=>$v)$r[$k]=(string)$v;
    return $r;
}



function array_sublinearize($a,$c){$ret=array();foreach($a as $k=>$val)$ret[$k]=$val[$c];return $ret;}


//thx cagret 

function array_msort($array, $cols) {
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $params = array();
    foreach ($cols as $col => $order) {
        $params[] =& $colarr[$col];
        $params = array_merge($params, (array)$order);
    }
    call_user_func_array('array_multisort', $params);
    $ret = array();
    $keys = array();
    $first = true;
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            if ($first) { $keys[$k] = substr($k,1); }
            $k = $keys[$k];
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
        $first = false;
    }
    return $ret;
}


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



function array_merge_deep($array0, $array1){
    foreach($array1 as $k=>$v){
        $array0[$k] = is_array($v) ? array_merge_deep($array0[$k], $v) : $v;

    }
    return $array0;

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

function array_filter_criteria($list, $criteria){
    $result = array(); 
    if(!$criteria) return $result;
    foreach($list as $k=>$v) {
        $match = true;
        foreach($criteria as $criteria_name=>$value) {
            if(is_array($v[$criteria_name]) && !is_array($value)) $match &= in_array($value, $v[$criteria_name]);
            elseif(is_array($value) && !is_array($v[$criteria_name])) $match &= in_array($v[$criteria_name], $value);
            else $match &= $v[$criteria_name] == $value;
        }
        if($match) $result[$k] = $v;
    }
    return $result;
}


/**
* LOL
**/
function xml_to_constants($xml, $pfx, $set = false){
    if(is_string($xml))
        $xml = simplemxl_load_file($xml);
    $ret  = array();
    $name = strtoupper($xml->getName());
    if($pfx) $name = $pfx.$name;

    $children = $xml->children();
    if(!$children)
        $ret[$name] = (string)$xml;
    foreach($xml->attributes() as $k=>$v)
        $ret["{$name}_".strtoupper($k)] = (string)$v;
        
    foreach($children as $child)
        $ret = array_merge($ret, xml_to_constants($child, $name.'_'));
        
    if($set)
      foreach($ret as $k=>$v)
        define($k,$v);
    return $ret;
}



