<?php

/*  "Exyks MySQL" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2005-2006-2007-2008
*/

class sql {

   static public $queries=array();
   static private $link='db_link';
   static private $result;
   static public $servs=null;
   static private $pfx=array();
   static private $lnks=array();
   static private $transaction=false;
   static public $rows;
   static public $log=true;


  static function init() {
    if(!self::$servs) self::$servs = &yks::$get->config->sql;
    if(!self::$servs) throw rbx::error("Unable to load sql configuration.");

    if(self::$servs->prefixs)
    foreach(self::$servs->prefixs->attributes() as $prefix=>$trans)
        self::$pfx["#`{$prefix}_([a-z0-9_-]+)`#"] = "`".str_replace(".", "`.`", $trans)."$1`";

    self::$pfx = array('search'=> array_keys(self::$pfx), 'replace'=>array_values(self::$pfx));
  }

  static function &connect($lnk = false){
    $lnk  = $lnk ? self::set_link($lnk) : self::$link;
    $serv = attributes_to_assoc(self::$servs->links->$lnk);

    $credentials = array($serv['host'],$serv['user'],$serv['pass'],$serv['db']);
    self::$lnks[$lnk] = @call_user_func_array('mysqli_connect', $credentials);

    if(!self::$lnks[$lnk] ) {
        throw new Exception("Unable to connect sql server ".print_r($serv,1));
    }

    mysqli_set_charset(self::$lnks[$lnk],"utf8");
    return self::$lnks[$lnk];
  }

  static function query($query,$lnk=false){

    $lnk = $lnk?$lnk:self::$link;
    $serv = self::$lnks[$lnk];
    if(!($serv||$serv = self::connect($lnk))) return false;
    
    $query = self::unfix($query); if(self::$log) self::$queries[]=$query;
    self::$result = mysqli_query($serv,$query);

    if(self::$result===false) return self::error(htmlspecialchars($query));
    if(! is_object(self::$result)) return mysqli_affected_rows($serv);
    return self::$result;
  }

  static function fetch($lnk=false){
    if(!($lnk=$lnk?$lnk:self::$result))return array();
    $tmp=mysqli_fetch_assoc($lnk);
    return $tmp?$tmp:array();
  }

  public static function get_lnk($lnk = false){
    $serv = self::$lnks[$lnk=$lnk?$lnk:self::$link];
    if(!($serv||$serv=self::connect($lnk))) return false;
    return $serv;
  }

  static function brute_fetch($id=false,$val=false,$start=false,$by=false){
    $tmp=array();$c=0;$line=0;
    if($start)mysqli_data_seek(self::$result,$start);
    while(($l=self::fetch()) && ($by?$line++<$by:true))$tmp[$id?$l[$id]:$c++]=$val?$l[$val]:$l;
    if($start || $by)self::$rows=sql::rows();
    //sql::free();
    return $tmp;
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

  static function fetch_all(){
    $res = array();
    while($l=mysqli_fetch_row(self::$result)) $res[]=$l[0];
    return $res;
  }



  static function format($vals,$set=true){ $r='';
    $vals=array_map(array('sql','vals'),$vals);
    if($set) return "SET ".mask_join(',',$vals,'`%2$s`=%1$s');
    return "(`".join('`,`',array_keys($vals))."`) VALUES(".join(',',$vals).")";
  }

  static function vals($v){
    return is_array($v) && (list($type,$val)=each($v))
			? ( $type==="sql" ? $val : '' )
			: "'".self::clean($v)."'";
  }

  static function close($lnk=false){
    $serv=&self::$lnks[$lnk?$lnk:self::$link]; if(!$serv)return;
    mysqli_close($serv);$serv=false;
  }

  static function insert($table, $vals=false, $auto_indx=false, $keys=false){
    if(is_array($keys)) $vals=array_intersect_key($vals,array_flip($keys));
    $vals = $vals?sql::format($vals):'VALUES (DEFAULT)';
    $res=sql::query("INSERT INTO `$table` $vals");
    return $auto_indx && $res ? self::auto_indx() : $res;
  }

  static function error($msg=''){
    $msg = "<b>".htmlspecialchars(mysqli_error(self::$lnks[self::$link]))."</b> in $msg";
    if(DEBUG && !self::$transaction) error_log($msg);
    return false;
  }
  
  static function update($table,$vals,$where='',$extras="LIMIT 1") {
    if(!$vals) return false;
    return self::query("UPDATE `$table` ".sql::format($vals)." ".sql::where($where, $table)." $limit");
  }

  static function replace($table, $vals, $where=array(), $auto_indx=false, $limit=''){
    $res=sql::query("REPLACE INTO `$table` ".sql::format(array_merge($vals,$where))." $limit");
    return $auto_indx?self::auto_indx():$res;
  }


  static function delete($table,$where,$extras=''){
    if(!$where) return false;
    $query = "DELETE FROM `$table` ".sql::where($where, $table)." $extras";
    return sql::query($query);
  }

  static function select($table,$where='TRUE',$cols="*",$extra=''){
    return sql::query("SELECT $cols ".sql::from($table).sql::where($where, $table)." $extra");
  }

  static function row($table,$where='TRUE',$cols="*", $extras=""){
    sql::select($table, $where, $cols, " $extras LIMIT 1"); return sql::fetch();
  }

    /** move the #nth item down */
  static function set_order($table,$col,$nth,$where='TRUE'){
    sql::query("SET @pos:=0,@down:=$nth;");
    return sql::query("UPDATE `$table` SET
        `$col` = IF((@pos:=@pos+1)=@down, @pos+1,IF(@pos=@down+1,@down,@pos))
        WHERE $where ORDER BY `$col` ;");
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
       $conds[]= is_array($v)
           ?((list($type,$val)=each($v)) && $type==='sql'?
               " $k $val": ($v?sql::in_join($k,$v):"FALSE") )
           :"$k ".(is_string($v)?"='$v'":(is_int($v)?"=$v":(is_null($v)?"IS NULL":(is_bool($v)&&!$v?"!=TRUE":''))));
    return $conds?"WHERE ".join(" $mode ",$conds):'';
  }

  static function from($tables){
    $ret=''; if(!is_array($tables)) $ret = preg_match("#[ ]#",$tables)?$tables:"`$tables`";
    else foreach($tables as $k=>$table)
        $ret.=is_numeric($k)?(($k?',':'')." `$table` "):
            (((is_array($table)&&list($join,$v)=each($table))
                ?"$join `$v`":"INNER JOIN `$table`")." USING($k) ");
    return 'FROM '.str_replace('.','`.`',$ret).' ';
  }

  static function begin(){ sql::$transaction=true;  }
  static function rollback($error=false){
        if(self::$transaction);
        sql::$transaction=false;return $error?rbx::error($error):false;
  }
  static function commit(){ sql::$transaction=false;}
  static function query_raw($query){ return mysqli_query(self::$lnks[self::$link], $query); }
  static function limit_rows(){$tmp=sql::qrow("SELECT FOUND_ROWS() as tmp");return $tmp['tmp'];}
  static function unfix($str){ return preg_replace(self::$pfx['search'],self::$pfx['replace'],$str);}
  static function in_join($field,$vals,$not=''){ return "`$field` $not IN('".join("','",$vals)."')"; }
  static function in_set($field,$vals){ return "FIND_IN_SET($field,'".join(",",$vals)."')"; }
  static function qrow($query,$lnk=false){ self::query($query,$lnk); return self::fetch(); }
  static function rows($lnk=false){ return  mysqli_num_rows($lnk?$lnk:self::$result); }
  static function auto_indx($lnk=false){ return mysqli_insert_id(self::$lnks[$lnk?$lnk:self::$link]); }
  static function free($lnk=false){ mysqli_free_result($lnk=$lnk?$lnk:self::$result);$lnk=null; }
  static function truncate($table){ return sql::query("TRUNCATE `$table`"); }
  static function value(){
    $arg = func_get_args(); return reset(call_user_func_array(array(__CLASS__, 'row'), $arg)); }
  static function clean($str){
    return is_numeric($str)?$str:mysqli_escape_string(self::get_lnk(),$str); }
  static function set_link($lnk){ return self::$link = $lnk; }

  static function table_infos($table_name){ 
    return sql::qrow("SHOW TABLE STATUS LIKE '".trim(sql::unfix("`$table_name`"),'`')."'");
 }
  static function table_cols($table_name){
    sql::query("SHOW FULL COLUMNS FROM `$table_name`"); return sql::brute_fetch('Field');
  }
  static function table_keys($table_name){
    sql::query("SHOW KEYS FROM `$table_name`");$keys=array();
    while($l=sql::fetch()) $keys[$l['Key_name']][$l['Column_name']]=$l; return $keys;
  }

    // return an unquoted associative array of schema , name, safe name
  static function resolve($name){

    if(!$name) return array();
    $tmp = explode('.', str_replace('`', '', sql::unfix("`$name`")) , 2);
    $name = array_pop($tmp); $schema = $tmp[0];
    if(!$schema) $schema = (string) self::$servs->links->{self::$link}['db'];
    $safe = sprintf('`%s`.`%s`', $schema, $name ); $hash = str_replace('`','', $safe);
    return compact('name', 'schema', 'safe', 'hash');
  }


}
