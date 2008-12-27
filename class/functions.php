<?

/*	"Yks functions" by Leurent F. (131)
    distributed under the terms of GNU General Public License - ? 2008
*/

require "$class_path/stds/rbx.php";
require "$class_path/stds/txt.php";
require "$class_path/stds/jsx.php";
require "$class_path/stds/date.php";
require "$class_path/stds/data.php";
require "$class_path/myks/input.php";

if(defined('Yks')) rbx::$output_mode = yks::$href?0:1;

function sys_end($generation_time,$display_time=0){
    return sprintf("\n<!-- powerdé by exyks in - subs : %0-5Fs - tpls : %0-5Fs %s-->",
        $generation_time,$display_time,"");//,
    ;
}

    // return boolean state of a variable ( in string mode if asked )
function bool($val,$str=false){
    if(is_string($val)) {
        $val=strtolower($val);
        $val=$val && $val!="false" && $val !="no" && $val !="n" && $val !="f";
    }else $val=(bool)$val;
    return $str?($val?"true":"false"):$val;
}


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


function ip2int($ip){return sprintf("%u",ip2long($ip));}
function vals($enum,$chld="val"){
    $tmp=array(); if($enum->$chld) foreach($enum->$chld as $v)$tmp[]="$v"; return $tmp;
}


function fields($table){
    $res=array();
    foreach($table->field as $test)
        $res["$test"]=(string)($test['type']?$test['type']:$test);
    return $res;
}


function between($a,$min,$max){return $a>=$min && $a<=$max; }
function array_next_val($array,$val){return $array[array_search($val,$array)+1]; }

function is_not_null($a){return !is_null($a);}
function array_step($array,$val,$way=1,$loop=true){
    $tmp=array_search($val,$array)+$way;
    return $array[$loop?(($tmp+count($array))%count($array)):$tmp];
}

function preg_areplace($tmp,$str){ return preg_replace(array_keys($tmp),array_values($tmp),$str); }
function preg_clean($filter,$txt,$rem='^'){ return preg_replace("#[{$rem}{$filter}]#i",'',$txt); }
function array_sort($array,$keys){
    $keys = is_array($keys)?$keys:array_slice(func_get_args(),1);
    return array_intersect_key(array_merge($keys=array_flip($keys),$array),$keys,$array);
}

    //!!dont sprintf($v,$k) ! bad thing © use mask_join else
function array_mask($array,$mask){ foreach($array as &$v)$v=sprintf($mask,$v);return $array; }


function mask_join($glue,$array,$mask){
    foreach($array as $k=>&$v) $v=sprintf($mask,$v,$k);
    return join($glue,$array);
}

function array_extract($array, $col){
    $ret=array();
    foreach($array as $v) $ret[]=$v[$col];
    return array_unique($ret);
}
function array_get($array,$col){return $col?$array[$col]:$array; }

function array_merge_numeric($a,$b){
  foreach($b as $k=>$v)$a[$k]=is_array($v)&&is_array($a[$k])?array_merge($a[$k],$v):$v;return $a;
}
function attributes_to_assoc($x){$tmp=array(); //php 5.3 grrrr
    foreach($x->attributes() as $k=>$v)$r[$k]=(string)$v;
    return $r;
}


function specialchars_encode($v){ return htmlspecialchars($v,ENT_QUOTES,'utf-8'); }
function specialchars_decode($str){ return htmlspecialchars_decode($str,ENT_QUOTES); }
function specialchars_deep($v){return is_array($v)?array_map(__FUNCTION__,$v):specialchars_encode($v);}
function mailto_escape($str){ return rawurlencode(utf8_decode(specialchars_decode($str))); }


function array_sublinearize($a,$c){$ret=array();foreach($a as $k=>$val)$ret[$k]=$val[$c];return $ret;}





function mail_valid($mail){ return (bool) filter_var($mail, FILTER_VALIDATE_EMAIL ); }


function join_keys($array,$sep=' '){
  $res=array();foreach(array_filter($array) as $k=>$v)$res[]="$k=\"$v\"";return join($sep,$res);
}


function reloc($url) {
    if(substr($url,0,1)=="/") $url=SITE_URL.'/'.ltrim($url,'/');
    if(class_exists('rbx') && rbx::$rbx) rbx::delay();
    if(JSX===true) {rbx::msg('go',$url);jsx::end();}
    header("Location: $url"); exit;
}

function abort($code) {
    if(ERROR_PAGE==yks::$href) return; //empeche les redirections en boucle
    $dest=ERROR_PAGE."//$code";
    if($code==404 && $dest==yks::$href_ks) reloc("?/Yks/error//404");
    $_SESSION[SESS_TRACK_ERR]="?".yks::$href_ks;

    if(JSX){if($code!=403)rbx::error($code);
        else jsx::js_eval("Jsx.open('?$dest','error_box',this)");
        jsx::end();
    } reloc("?$dest");
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


  // Find documentation in the manual
function array_reindex($array,$cols=array()){
    $res=array();if(!is_array($cols))$cols=array($cols);
    foreach($array as $v){
      $tmp=&$res;
      foreach($cols as $col) $tmp=&$tmp[$v[$col]];
      $tmp=$v;
    }return $res;
}


