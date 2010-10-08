<?php


class sql {
   static $esc = '"';
   static public $queries=array();
   static private $link='db_link';
   static private $result;
   static public $servs=null;
   static public $rows=0;
   static public $log=true;
   static private $transaction=false;
   static private $pfx=array(
          '#&&#' => 'AND' // || is for concatenation !
   );


  const true  = 'TRUE';
  const false = 'FALSE';

   static private $lnks = array();

  static function init(){
    if(!self::$servs) self::$servs=&yks::$get->config->sql;
    if(!self::$servs)
      throw rbx::error("Unable to load sql configuration.");

    if(self::$servs->prefixs)
    foreach(self::$servs->prefixs->attributes() as $prefix=>$trans)
        self::$pfx["#(?<!\.)`{$prefix}_([a-z0-9_-]+)`#"] = "`".str_replace(".", "`.`", $trans)."$1`";

    self::$pfx["#`(.*?)`#"] = "\"$1\"";
    self::$pfx = array('search'=> array_keys(self::$pfx), 'replace'=>array_values(self::$pfx));
  }

  static function &connect($lnk = false){
    $lnk  = $lnk?self::set_link($lnk):self::$link;
    $serv = self::$servs->links->$lnk;

    if(!$serv['port'])$serv['port']= 5432;
    $sql_infos = "host='{$serv['host']}' port={$serv['port']} dbname='{$serv['db']}' user='{$serv['user']}' password='{$serv['pass']}'";

    self::$lnks[$lnk] = pg_connect($sql_infos);
    if(!self::$lnks[$lnk])
      throw rbx::error("Unable to load link #{$lnk} configuration");

    return self::$lnks[$lnk];
  }

  static function &query($query, $lnk=false, $arows=false){
    $lnk = $lnk?$lnk:self::$link;
    $serv = isset(self::$lnks[$lnk])?self::$lnks[$lnk]:self::connect($lnk);
    if(!$serv) return false;

    $query = self::unfix($query);
    self::$result = pg_query($serv, $query);

    if(self::$log) self::$queries[] = $query;
    if(self::$result===false) {
        $error = self::error(htmlspecialchars($query));
        return $error;
    }
    
    if($arows) {
        $arows = pg_affected_rows(self::$result);
        return $arows; 
    }
    return self::$result ;
  }

  static function fetch($lnk=false){
    if(!($lnk=$lnk?$lnk:self::$result))return array();
    $tmp=pg_fetch_assoc($lnk);
    return $tmp?$tmp:array();
  }

    //This function works the same way array_reindex does, please refer to the manual
  static function brute_fetch_depth(){
    $result = array(); $cols = func_get_args();
    if($end = (end($cols)==false)) array_pop($cols);
    while(($l = self::fetch())) {
          $tmp = &$result;
          foreach($cols as $col) $tmp=&$tmp[$l[$col]];
          $tmp = $end?$l[$col]:$l;
    } return $result;
  }

  static function partial_fetch($id, $val, $start, $by) {
    $tmp=array();$c=0;$line=0;
    pg_result_seek(self::$result, $start);
    while(($l=sql::fetch())&&  ($by!==false?$line++<$by:true)  )
        $tmp[$id?$l[$id]:$c++]=$val?$l[$val]:$l;
    $_tmp_rows = sql::rows();
    sql::free();
    return array($tmp, $_tmp_rows);
  }

  static function brute_fetch($id=false,$val=false,$start=false,$by=false){
    $tmp=array();$c=0;$line=0;
    if($start)pg_result_seek(self::$result,$start);
    while(($l=self::fetch()) && ($by?$line++<$by:true))$tmp[$id?$l[$id]:$c++]=$val?$l[$val]:$l;
    if($start || $by)self::$rows=sql::rows();
    sql::free();
    return $tmp;
  }

  static function fetch_all(){
    return pg_fetch_all_columns(self::$result);
  }

  static function format($vals,$set=true){ $r='';
  $vals=array_map(array('sql','vals'),$vals);
  if($set) return "SET ".mask_join(',',$vals,'`%2$s`=%1$s');
  return "(`".join('`,`',array_keys($vals))."`) VALUES(".join(',',$vals).")";
  }

  static function close($lnk=false){
    $serv=&self::$lnks[$lnk = ($lnk?$lnk:self::$link)]; if(!$serv)return;
    pg_close($serv);unset(self::$lnks[$lnk]);
  }
    /** move the #nth item down */
  static function set_order($table,$col,$nth,$where=sql::true){
    sql::query("SET @pos:=0,@down:=$nth;");
    return sql::query("UPDATE $table SET
        $col = IF((@pos:=@pos+1)=@down, @pos+1,IF(@pos=@down+1,@down,@pos))
        WHERE $where ORDER BY $col ;");
  }

    //format values
  static function vals($v){
    if(is_array($v) && (list($type,$val)=each($v)))
        return ( $type==="sql" ? $val : '' );
    if(is_null($v)) return 'NULL';
    if(is_int($v)) return $v;
    if(is_bool($v)) return $v?sql::true:sql::false;
    return "'".self::clean($v)."'";
  }
    //format conditions
  static function conds($k, $v){
    if(is_array($v)) {
        list($type,$val) = each($v);
        if($type === "sql") return "$k $val";
        return $v ? sql::in_join($k,$v) : "FALSE";
    }
    if(is_string($v)) return "$k='$v'";
    if(is_int($v))    return "$k=$v";
    if(is_null($v))   return "$k IS NULL";
    if(is_bool($v))   return $v?"$k":"not($k)";
  }
  
 static function insert($table,$vals=false, $auto_indx=false, $keys=false){
    if(is_array($keys)) $vals=array_intersect_key($vals,array_flip($keys));
    $vals = $vals?sql::format($vals,false):'VALUES (DEFAULT)';

    $result = &sql::query("INSERT INTO `$table` $vals", false, true);
    return $auto_indx && $result ? self::auto_indx($table) : $result;
  }
  
  static function error($msg=''){
    $pg_error  = ($serv = self::$lnks[self::$link]) ? pg_last_error($serv) : "?? unknow serv ??";
    $msg = "<b>".htmlspecialchars($pg_error)."</b> in $msg";
    if(DEBUG && !self::$transaction) error_log($msg);
    return false;
  }

  
  static function update($table,$vals,$where='',$extras="") {
    if(!$vals) return false;
    return self::query("UPDATE ".self::fromf($table)." ".sql::format($vals)." ".sql::where($where, $table).$extras,false,true);
  }

  static function replace($table, $vals, $where=array(), $auto_indx=false){
    $data = sql::row($table,$where);
    if(!$data) return sql::insert($table,array_merge($vals,$where),$auto_indx);
    return sql::update($table,$vals,$where);
  }
  static function delete($table,$where,$extras=''){
    return sql::query("DELETE FROM `$table` ".sql::where($where, $table)." $extras",false,true);
  }
  static function select($table,$where=sql::true,$cols="*",$extra=''){
    return sql::query("SELECT $cols ".sql::from($table).' '.sql::where($where, $table)." $extra");
  }
  static function row($table,$where=sql::true,$cols="*",$extras=''){
    sql::select($table, $where, $cols, " $extras LIMIT 1"); return sql::fetch();
  }
  static function where($cond, $table=false, $mode='&&'){
    if(is_bool($cond) || !$cond) return $cond?'':'WHERE FALSE';
    if(is_object($cond)) $cond = array($cond);
    if(!is_array($cond)) return $cond&&strpos($cond,"WHERE")===false?"WHERE $cond":$cond;
    foreach(array_filter($cond,'is_object') as $k=>$obj){
        if(!method_exists($obj, '__sql_where'))continue;
        unset($cond[$k]); $cond = array_merge($cond, $obj->__sql_where($table));
    }
    $slice=array_filter(array_keys($cond),'is_numeric');
    $conds=array_intersect_key($cond,array_flip($slice));
    foreach(array_diff_key($cond,array_flip($slice)) as $k=>$v)
       $conds[]= sql::conds($k, $v);
    return $conds?"WHERE ".join(" $mode ",$conds):'';
  }


  private static function fromf($table) {
    return ' `'.str_replace('.','`.`',$table).'` ';
  }

  static function from($tables){
        $ret='';
    if(!is_array($tables))
            return 'FROM '.(preg_match('#^[a-z0-9_.-]+$#',$tables)? self::fromf($tables):$tables);
    foreach($tables as $k=>$table)
        $ret.=is_numeric($k)?(($k?',':'FROM ').self::fromf($table)):
            (((is_array($table)&&list($join,$v)=each($table))
                ?"$join `$v`":"INNER JOIN `$table`")." USING($k) ");
    return $ret;
  }
  static function begin(){ sql::$transaction=true;sql::query('begin');  }
  static function rollback($error=false){
        if(self::$transaction) sql::query('rollback');
        sql::$transaction=false;return $error?rbx::error($error):false;
  }
  static function commit($msg=false){
    sql::$transaction = false;
    $result = sql::query('commit');
    if(!$result)
        throw new Exception("Transaction commit failed");
    if($msg) rbx::ok($msg);

    
  }

  static function limit_rows(){
    $query = end(sql::$queries);
    $begin_at = strpos($query, "FROM");
    $query = "SELECT  COUNT(*) as nb_line ".substr($query, $begin_at);
    $remove_from = min(strripos($query, "ORDER"), strripos($query, "LIMIT"));
    if($remove_from)
            $query = substr($query, 0, $remove_from);
            
    //preg_replace messed up with big strings
    //$query=preg_replace('#SELECT (.*?) FROM (.*?)(\s*(?:ORDER BY|LIMIT).*)$#is',"SELECT COUNT(*) as nb_line FROM $2",$query);
    return sql::qvalue($query);
  }
  
  static function unfix($str){
    $str = preg_replace( self::$pfx['search'], self::$pfx['replace'],$str);
    return $str;
  }


  static function lines($table){ return sql::value($table, true, "COUNT(*)");}
  static function in_join($field,$vals,$not=''){ return "$field $not IN('".join("','",$vals)."')"; }
  static function in_set($field,$vals){ return "FIND_IN_SET($field,'".join(",",$vals)."')"; }
  static function qrow($query,$lnk=false){ self::query($query,$lnk); return self::fetch(); }
  static function qvalue($query) { return current(sql::qrow($query)); }
  static function value(){ $arg=func_get_args(); return reset(call_user_func_array(array(__CLASS__, 'row'), $arg)); }
  static function rows($lnk=false){ return  pg_num_rows($lnk?$lnk:self::$result); }
  static function auto_indx($table){
    $name = self::resolve($table);
    return (int)current(sql::qrow("SELECT auto_increment_retrieve('{$name['name']}')"));
  }
  static function free(&$lnk=null){ if($lnk=$lnk?$lnk:self::$result) pg_free_result($lnk);return $lnk=null; }
  static function truncate($table){ return sql::query("DELETE FROM `$table`"); }
  static function query_raw($query){ return pg_query(self::$lnks[self::$link], $query); }
  static function clean($str){ return is_numeric($str)?$str:addslashes($str); }
  static function set_link($lnk){ return self::$link= (string)$lnk; }
  static function reset($res){ self::$result = $res; }

  static function table_infos($table_name){
    $where=array('table_schema'=>'public','table_name'=>sql::unquote($table_name));
    return sql::row("information_schema.tables",$where);
 }
    // return an unquoted associative array of schema , name, safe name
  static function resolve($raw){
    if(!$raw) return array();
    $tmp = explode('.', str_replace('"', '', sql::unfix("`$raw`")) , 2);
    $name = array_pop($tmp); $schema = $tmp[0]; if(!$schema) $schema = "public";
    $safe = sprintf('"%s"."%s"', $schema, $name ); $hash = str_replace('"','', $safe);
    return compact('name', 'schema', 'safe', 'raw', 'hash');
  }


}



