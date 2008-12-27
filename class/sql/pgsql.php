<?
include "functions.php";

class sql {
   static public $queries=array();
   static private $link='db_link';
   static private $result;
   static public $servs=null;
   static public $rows=0;
   static public $log=true;
   static private $transaction=false;
   static private $pfx=array(
          'search'=>array('#&&#'),
          'replace'=>array('AND') // || is for concatenation !
   );
   static private $lnks=array();

  static function &connect($lnk=false){
    if(!self::$servs) self::$servs=&yks::$get->config->sql;
    if(!self::$servs) throw rbx::error("Unable to load sql configuration.");
    if(self::$servs->prefixs)
    foreach(self::$servs->prefixs->attributes() as $prefix=>$trans){
        self::$pfx['search'][]="#`{$prefix}_([a-z0-9_-]+)`#";
        self::$pfx['replace'][]="$trans$1";
    }	self::$pfx['search'][]="#`(.*?)`#";
    self::$pfx['replace'][]="\"$1\"";

    $serv=self::$servs->links->{$lnk=$lnk?$lnk:self::$link};
    if(!$serv['port'])$serv['port']= 5432;
    $sql_infos = "host='{$serv['host']}' port={$serv['port']} dbname='{$serv['db']}' user='{$serv['user']}' password='{$serv['pass']}'";
    self::$lnks[$lnk]=pg_connect($sql_infos);
    if(!self::$lnks[$lnk]) return self::error();

    return self::$lnks[$lnk];
  }

  static function &query($query,$lnk=false,$arows=false){


    $serv=isset(self::$lnks[$lnk=$lnk?$lnk:self::$link])
         ?self::$lnks[$lnk]:self::connect($lnk);
    if(!$serv) return false;

    if(sql::$transaction) self::$result = @pg_query($serv,$query=self::unfix($query)); 
    else self::$result = pg_query($serv,$query=self::unfix($query));  //i want to see errors

    if(self::$log)self::$queries[]=$query;
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
    while(($l = self::fetch())) {
          $tmp = &$result;
          foreach($cols as $col) $tmp=&$tmp[$l[$col]];
          $tmp = $l;
    } return $result;
  }

  static function brute_fetch($id=false,$val=false,$start=false,$by=false){
    $tmp=array();$c=0;$line=0;
    if($start)pg_result_seek(self::$result,$start);
    while(($l=self::fetch()) && ($by?$line++<$by:true))$tmp[$id?$l[$id]:$c++]=$val?$l[$val]:$l;
    if($start || $by)self::$rows=sql::rows();
    sql::free();
    return $tmp;
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
  static function set_order($table,$col,$nth,$where='TRUE'){
    sql::query("SET @pos:=0,@down:=$nth;");
    return sql::query("UPDATE $table SET
        $col = IF((@pos:=@pos+1)=@down, @pos+1,IF(@pos=@down+1,@down,@pos))
        WHERE $where ORDER BY $col ;");
  }
  static function vals($v){
    return is_array($v) && (list($type,$val)=each($v))
            ? ( $type==="sql" ? $val : '' )
            : (is_null($v)?'NULL': "'".self::clean($v)."'");
  }
  
 static function insert($table,$vals=false,$auto_indx=false,$keys=false){
    if(is_array($keys)) $vals=array_intersect_key($vals,array_flip($keys));
    $vals = $vals?sql::format($vals,false):'VALUES (DEFAULT)';
    $res = &sql::query("INSERT INTO `$table` $vals",false,true);
    //if(is_resource($res)){$rows=pg_affected_rows($res);self::free($res);}
    return $auto_indx && $res?self::auto_indx($table):$res;
  }
  
  static function error($msg=''){
        $msg = "<b>".htmlspecialchars(pg_last_error(self::$lnks[self::$link]))."</b> in $msg";
        if(DEBUG && !self::$transaction) error_log($msg);
        return false;
  }

  
  static function update($table,$vals,$where='',$extras="") {
        if(!$vals) return false;
    return self::query("UPDATE `$table` ".sql::format($vals)." ".sql::where($where).$extras,false,true);
  }

  static function replace($table,$vals,$where=array(),$auto_indx=false){
    $data=sql::row($table,$where);
    if(!$data) return sql::insert($table,array_merge($vals,$where),$auto_indx);
    return sql::update($table,$vals,$where);
  }
  static function delete($table,$where,$extras=''){
    return sql::query("DELETE FROM `$table` ".sql::where($where)." $extras",false,true);
  }
  static function select($table,$where='TRUE',$cols="*",$extra=''){
    return sql::query("SELECT $cols ".sql::from($table).sql::where($where)." $extra");
  }
  static function row($table,$where='TRUE',$cols="*",$extras=''){
    return sql::qrow("SELECT $cols ".sql::from($table)." ".sql::where($where)." $extras LIMIT 1");
  }
  static function where($cond,$mode='&&'){
    if(is_bool($cond) || !$cond) return $cond?'':'WHERE FALSE';
    if(!is_array($cond)) return $cond&&strpos($cond,"WHERE")===false?"WHERE $cond":$cond;
    $slice=array_filter(array_keys($cond),'is_numeric');
    $conds=array_intersect_key($cond,array_flip($slice));
    foreach(array_diff_key($cond,array_flip($slice)) as $k=>$v)
       $conds[]= is_array($v)
           ?((list($type,$val)=each($v)) && $type==='sql'?
               " $k $val": ($v?sql::in_join($k,$v):"FALSE") )
           :"$k ".(is_string($v)?"='$v'":(is_int($v)?"=$v":(is_null($v)?"IS NULL":(is_bool($v)&&!$v?"!=TRUE":''))));
    return $conds?"WHERE ".join(" $mode ",$conds):'';
  }
  static function from($tables){
        $ret='';
    if(!is_array($tables))
            return 'FROM '.(preg_match('#^[a-z0-9_.-]+$#',$tables)? ' `'.str_replace('.', '`.`',$tables).'` '
                    :$tables);
    foreach($tables as $k=>$table)
        $ret.=is_numeric($k)?(($k?',':'FROM ').' `'.str_replace('.','`.`',$table).'` '):
            (((is_array($table)&&list($join,$v)=each($table))
                ?"$join `$v`":"INNER JOIN `$table`")." USING($k) ");
    return $ret.' ';
  }
  static function begin(){ sql::$transaction=true;sql::query('begin');  }
  static function rollback($error=false){
        if(self::$transaction) sql::query('rollback');
        sql::$transaction=false;return $error?rbx::error($error):false;
  }
  static function commit($msg=false){
    if($msg) rbx::ok($msg);
    sql::$transaction=false;
    sql::query('commit');
  }

  static function limit_rows(){
    $query=end(sql::$queries);
    $query=preg_replace('#SELECT (.*?) FROM (.*?)(\s*(?:ORDER BY|LIMIT).*)$#is',"SELECT COUNT(*) as nb_line FROM $2",$query);$ret=sql::qrow($query);
    return $ret['nb_line'];
}
  static function unfix($str){ return preg_replace(self::$pfx['search'],self::$pfx['replace'],$str);}
  static function unquote($key){ return trim(sql::unfix("`$key`"),'"'); }
  static function in_join($field,$vals){ return "$field IN('".join("','",$vals)."')"; }
  static function in_set($field,$vals){ return "FIND_IN_SET($field,'".join(",",$vals)."')"; }
  static function qrow($query,$lnk=false){ self::query($query,$lnk); return self::fetch(); }
  static function rows($lnk=false){ return  pg_num_rows($lnk?$lnk:self::$result); }
  static function auto_indx($table){
    return (int)current(sql::qrow("SELECT auto_increment_retrieve('`$table`')"));
  }
  static function free(&$lnk=null){ if($lnk=$lnk?$lnk:self::$result) pg_free_result($lnk);return $lnk=null; }
  static function truncate($table){ return sql::query("DELETE FROM `$table`"); }

  static function clean($str){ return is_numeric($str)?$str:addslashes($str); }
  static function set_link($lnk){ self::$link=$lnk; }

  static function table_infos($table_name){
    $where=array('table_schema'=>'public','table_name'=>sql::unquote($table_name));
    return sql::row("information_schema.tables",$where);
 }

}

