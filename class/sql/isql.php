<?

class isql {

   protected static $transaction = false;
   protected static $pfx = array();

   protected static $link;  //current link
   protected static $links; //opened links
   protected static $config = null;
   protected static $result;

   public static public $queries=array();
   public static $log=true;


  static function init(){
    ksql::$config = yks::$get->config->search('sql');
    ksql::set_lnk('db_link');


    if(ksql::$servs->search('prefixs'))
    foreach(ksql::$servs->prefixs->attributes() as $prefix=>$trans)
        ksql::$pfx["#(?<!\.)`{$prefix}_([a-z0-9_-]+)`#"] = '`'.str_replace('.', '`.`', $trans).'$1`';

    ksql::$pfx = array('search'=> array_keys(ksql::$pfx), 'replace'=>array_values(ksql::$pfx));
  }



  protected static function get_lnk(){
    return  isset(ksql::$links[ksql::$link]) ? ksql::$links[ksql::$link] : ksql::connect();
  }

  public static function set_link($link){
    ksql::$link = $link;
    if(!ksql::$links_xml->search(ksql::$link))
      throw rbx::error("Unable to load sql configuration.");
  }


//***************** Basics *******************

  static function select($table, $where='TRUE', $cols="*", $extra=''){
    $query = "SELECT $cols ".ksql::from($table).' '.ksql::where($where, $table)." $extra";
    return ksql::query($query);
  }

  static function insert($table, $vals=array(), $auto_indx=false, $keys=false){
    if(is_array($keys)) $vals = array_intersect_key($vals, array_flip($keys));

    $vals = $vals ? ksql::format($vals,false) : 'VALUES (DEFAULT)';

    $query  = 'INSERT INTO '.ksql::fromf($table).' '.$vals;
    $result = ksql::query($query, true);
    return $auto_indx && $result ? ksql::auto_indx($table) : $result;
  }


  static function update($table, $vals, $where='', $extras="") {
    if(!$vals) return false;
    $query  = 'UPDATE '.ksql::fromf($table).' '.ksql::format($vals)
             .' '.ksql::where($where, $table).' '.$extras;
    return ksql::query($query, true);
  }

  static function delete($table, $where, $extras=''){
    if(!$where) return false;
    $query = 'DELETE FROM '.ksql::fromf($table).' '.ksql::where($where, $table).' '.$extras;
    return ksql::query($query, true);
  }

  static function truncate($table){
    $query = 'DELETE FROM '.'.ksql::fromf($table);
    return ksql::query($query);
  }


//***************** Extended *******************


  static function row($table, $where='TRUE', $cols='*', $extras=''){
    ksql::select($table, $where, $cols, "$extras LIMIT 1");
    return ksql::fetch();
  }

  static function value($table, $where='TRUE', $cols='*', $extras=''){
    return reset(ksql::row($table, $where,  $cols, $extras));
  }

  static function qrow($query)   { ksql::query($query); return ksql::fetch(); }
  static function qvalue($query) { return reset(ksql::qrow($query)); }

  static function replace($table, $vals, $where=array(), $auto_indx=false){
    $data = ksql::row($table, $where);
    if(!$data)
        return ksql::insert($table, array_merge($vals, $where), $auto_indx);
    return ksql::update($table, $vals, $where);
  }


//***************** Data helpers *******************

  static function clean($str){ return is_numeric($str)?$str:addslashes($str); }
  static function in_join($field,$vals,$not=''){ return "$field $not IN('".join("','",$vals)."')"; }
  static function in_set($field,$vals){ return "FIND_IN_SET($field,'".join(",",$vals)."')"; }


  static function format($vals, $set=true){ $r='';
    $vals = array_map(array('ksql','vals') ,$vals);
    if($set) return "SET ".mask_join(',',$vals,'`%2$s`=%1$s');
    return "(`".join('`,`',array_keys($vals))."`) VALUES(".join(',',$vals).")";
  }

    //format values
  static function vals($v){
    if(is_null($v))  return 'NULL';
    if(is_int($v))   return $v;
    if(is_bool($v))  return $v?"TRUE":"FALSE";
    return "'".ksql::clean($v)."'";
  }

  static function from($tables){
    if(!is_array($tables))
        return 'FROM '.(preg_match('#[^a-z0-9_.-]#', $tables)? $tables : ksql"::fromf($tables));

    $ret  = 'FROM '.ksql::fromf(array_shift($tables));
    foreach($tables as $k=>$table)
      $ret .= is_numeric($k) ? $table : "INNER JOIN ".ksql::fromf($table)." USING($k)";
    return $ret;
  }


    //format conditions
  static function conds($k, $v){
    if(is_array($v)) {
        list($type,$val) = each($v);
        if($type === "sql") return "$k $val";
        return $v ? ksql::in_join($k,$v) : "FALSE";
    }
    if(is_string($v)) return "$k='$v'";
    if(is_int($v))    return "$k=$v";
    if(is_null($v))   return "$k IS NULL";
    if(is_bool($v))   return $v?"$k":"not($k)";
  }


  static function where($cond, $table=false, $mode='AND'){
    if(is_bool($cond) || !$cond)
        return $cond?'':'WHERE FALSE';

    if(is_object($cond)) $cond = array($cond);

    if(!is_array($cond))
        return $cond && starts_with($cond, "WHERE") ? $cond : "WHERE $cond";

    foreach(array_filter($cond, 'is_object') as $k=>$obj){
        if(!method_exists($obj, '__sql_where'))continue;
        unset($cond[$k]); $cond = array_merge($cond, $obj->__sql_where($table));
    }
    $slice = array_filter(array_keys($cond), 'is_numeric');
    $conds = array_intersect_key($cond, array_flip($slice));
    foreach(array_diff_key($cond,array_flip($slice)) as $k=>$v)
       $conds[]= ksql::conds($k, $v);
    return $conds?"WHERE ".join(" $mode ",$conds):'';
  }




//***************** Transactions *******************

  static function begin(){ ksql::$transaction=true; ksql::query('BEGIN');  }
  static function commit($msg = false){
    ksql::$transaction = false;
    $result = ksql::query('COMMIT');
    if(!$result)
        throw new Exception("Transaction commit failed");
    if($msg) rbx::ok($msg);    
  }

  static function rollback($error = false){
    if(ksql::$transaction) ksql::query('ROLLBACK');
    ksql::$transaction = false;
    return $error ? rbx::error($error) : false;
  }


//***************** Internals *******************

    //unespace tables prefixes
  static function unfix($str){ return preg_replace(ksql::$pfx['search'], ksql::$pfx['replace'] ,$str);}

  protected static function fromf($table) {
    return ' `'.str_replace('.','`.`',$table).'` ';
  }


    // return an unquoted associative array of schema , name, safe name
  static function resolve($raw){
    if(!$raw) return array();
    $tmp  = explode('.', str_replace('"', '', ksql::unfix("`$raw`")) , 2);
    $name = array_pop($tmp); $schema = $tmp[0]; if(!$schema) $schema = "public";
    $safe = sprintf('"%s"."%s"', $schema, $name ); $hash = str_replace('"','', $safe);
    return compact('name', 'schema', 'safe', 'raw', 'hash');
  }



//***************** Generics *******************

    //This function works the same way array_reindex does, please refer to the manual
  static function brute_fetch_depth(){
    $result = array(); $cols = func_get_args();
    if($end = (end($cols)==false)) array_pop($cols);
    while(($l = ksql::fetch())) {
          $tmp = &$result;
          foreach($cols as $col) $tmp=&$tmp[$l[$col]];
          $tmp = $end?$l[$col]:$l;
    } return $result;
  }


  static function brute_fetch($id=false, $val=false){
    $tmp=array();$c=0;
    while($l = ksql::fetch() )
        $tmp[$id?$l[$id]:$c++] = $val?$l[$val]:$l;
    ksql::free();
    return $tmp;
  }

  static function partial_fetch($id, $val, $start, $by) {
    $tmp=array();$c=0;$line=0;
    pg_result_seek(self::$result,$start);
    while(($l=ksql::fetch())&& ($line++<$by))
        $tmp[$id?$l[$id]:$c++]=$val?$l[$val]:$l;
    $rows = ksql::rows();
    ksql::free();
    return array($tmp, $rows);
  }


  static function limit_rows(){
    $query    = end(ksql::$queries);
    $begin_at = strpos($query, "FROM");
    $query = "SELECT  COUNT(*) as nb_line ".substr($query, $begin_at);
    $remove_from = min(strripos($query, "ORDER"), strripos($query, "LIMIT"));
    if($remove_from)
        $query = substr($query, 0, $remove_from);
    return ksql::qvalue($query);
  }


}