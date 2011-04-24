<?

abstract class isql {

   const true  = 'TRUE';
   const false = 'FALSE';
   protected static $transaction = false;
   protected static $pfx = array();

   protected static $link;  //current link
   protected static $links; //opened links
   protected static $config = null;
   protected static $result;

   public static $queries=array();
   public static $log=true;

   private static $_tmp_rows;

  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;
    self::$config = yks::$get->config->search('sql');
    self::set_link('db_link');


    if(self::$config->search('prefixs'))
    foreach(self::$config->prefixs->attributes() as $prefix=>$trans)
        self::$pfx["#(?<!\.)`{$prefix}_([a-z0-9_-]+)`#"] = '`'.str_replace('.', '`.`', $trans).'$1`';

    self::$pfx = array('search'=> array_keys(self::$pfx), 'replace'=>array_values(self::$pfx));
  }

  protected static function get_lnk(){
    return  isset(sql::$links[sql::$link]) ? sql::$links[sql::$link] : sql::connect();
  }

  public static function set_link($link){
    self::$link = $link;
    if(!self::$config->links->search(self::$link))
      throw rbx::error("Unable to load sql configuration.");
  }


/***************** Basics *******************/

  static function select($table, $where = sql::true, $cols="*", $extra=''){
    list($where, $params) = sql::iwhere($where, $table);
    $query = "SELECT $cols ".sql::from($table)." $where $extra";
    return sql::query($query, $params);
  }

  static function insert($table, $vals=array(), $auto_indx=false, $keys=false){
    if(is_array($keys)) $vals = array_intersect_key($vals, array_flip($keys));

    if(!$vals) $format = 'VALUES (DEFAULT)';
    else list($format, $vals) = sql::format_prepare($vals,false);

    $query  = 'INSERT INTO '.sql::fromf($table)." $format";

    $result = sql::query($query, $vals);
    return $auto_indx && $result ? sql::auto_indx($table) : $result;
  }


  static function update($table, $vals, $where='', $extras="") {
    if(!$vals) return false;
    list($format, $params) = sql::format_prepare($vals);
    list($where, $vher)  = sql::iwhere($where, $table);

    if($vher) $params = array_merge($params, $vher);
    $query  = 'UPDATE '.sql::fromf($table)." $format $where $extras";
    return sql::query($query, $params);
  }

  static function delete($table, $where, $extras=''){
    if(!$where) return false;
    list($where, $params) = sql::iwhere($where, $table);
    $query = 'DELETE FROM '.sql::fromf($table)." $where $extras";
    return sql::query($query, $params, true);
  }

  static function truncate($table){
    $query = 'DELETE FROM '.sql::fromf($table);
    return sql::query($query);
  }

/***************** Version classic ************/
    //format values
    //you'll need an sql::clean implementation to use this
  static function format_raw_query($query, $params, $lnk = null){

    if(!$params)
        return $query;

    foreach($params as $k=>&$v) {
        if(is_null($v))  $v = 'NULL';
        elseif(is_int($v));
        elseif(is_bool($v)) $v = $v?sql::true:sql::false;
        else $v = "'".sql::clean($v)."'";
    }
    return strtr($query, $params);
  }

/***************** Extended *******************/


  static function row($table, $where = sql::true, $cols='*', $extras=''){
    sql::select($table, $where, $cols, "$extras LIMIT 1");
    return sql::fetch();
  }

  static function value($table, $where = sql::true, $cols='*', $extras=''){
    return reset(sql::row($table, $where,  $cols, $extras));
  }

  static function lines($table){
    return sql::value($table, true, "COUNT(*)");
  }

  static function qrow($query)   { sql::query($query); return sql::fetch(); }
  static function qvalue($query) { return reset(sql::qrow($query)); }

  static function replace($table, $vals, $where=array(), $auto_indx=false){
    $data = sql::row($table, $where);
    if(!$data)
        return sql::insert($table, array_merge($vals, $where), $auto_indx);
    return sql::update($table, $vals, $where);
  }


/***************** Data helpers *******************/

  static function in_join($field,$vals,$not=''){
    if((!$vals) && (!$not)) return sql::false;
    return "$field $not IN('".join("','",$vals)."')";
  }
  static function in_set($field,$vals){ return "FIND_IN_SET($field,'".join(",",$vals)."')"; }


  static function format_prepare($data, $set=true){ $r='';
    $keys = array_keys($data);
    $vals = array_combine($keys, array_mask($keys, ":i%s"));
    if($set) $format = "SET ".mask_join(',',$vals,'`%2$s`=%1$s');
    else $format = "(`".join('`,`',array_keys($vals))."`) VALUES(".join(',',$vals).")";

    $vals = array_combine($vals, $data);
    return array($format, $vals);
  }


  static function from($tables){
    if(!is_array($tables))
        return 'FROM '.(preg_match('#[^a-z0-9_.-]#', $tables)? $tables : sql::fromf($tables));

    $ret  = 'FROM '.sql::fromf(array_shift($tables));
    foreach($tables as $k=>$table)
      $ret .= is_numeric($k) ? $table : "INNER JOIN ".sql::fromf($table)." USING($k)";
    return $ret;
  }


    //format conditions
  static function conds($k, $v, &$params = null){
    if(is_array($v))  return sql::in_join($k,$v);

    if(is_string($v) || is_int($v)) {
        $params[":w$k"] = $v;
        return "`$k`=:w$k";
    }
    if(is_null($v))   return "`$k` IS NULL";
    if(is_bool($v))   return $v?"`$k`":"not(`$k`)";
  }

  static function where($cond, $table = false, $mode = 'AND') {
    list($where_str, $args) = sql::iwhere($cond, $table, $mode);
    $where_str = sql::format_raw_query($where_str, $args);
    return $where_str;
  }

 //return array(str, args)
  protected static function iwhere($cond, $table=false, $mode='AND'){

    if(is_bool($cond) || !$cond)
        return array($cond?'':'WHERE '.sql::false, array());

    if(is_object($cond)) $cond = array($cond);

    if(!is_array($cond))
        return array($cond && starts_with($cond, "WHERE") ? $cond : "WHERE $cond", array());

    foreach(array_filter($cond, 'is_object') as $k=>$obj){
        if(!method_exists($obj, '__sql_where'))continue;
        unset($cond[$k]); $cond = array_merge($cond, $obj->__sql_where($table));
    }
    $slice = array_filter(array_keys($cond), 'is_numeric');
    $conds = array_intersect_key($cond, array_flip($slice));

    $params = array();
    foreach(array_diff_key($cond,array_flip($slice)) as $k=>$v)
       $conds[]= sql::conds($k, $v, $params);
    return array($conds?"WHERE ".join(" $mode ",$conds):'', $params);
  }




/***************** Transactions *******************/

  static function begin(){ sql::$transaction=true; sql::query('BEGIN');  }
  static function commit($msg = false){
    sql::$transaction = false;
    $result = sql::query('COMMIT');
    if(!$result)
        throw new Exception("Transaction commit failed");
    if($msg) rbx::ok($msg);    
  }

  static function rollback($error = false){
    if(sql::$transaction) sql::query('ROLLBACK');
    sql::$transaction = false;
    return $error ? rbx::error($error) : false;
  }


/***************** Internals *******************/

    //unespace tables prefixes
  static function unfix($str){ return preg_replace(sql::$pfx['search'], sql::$pfx['replace'] ,$str);}

  protected static function fromf($table) {
    return ' `'.str_replace('.','`.`',$table).'` ';
  }


    // return an unquoted associative array of schema , name, safe name
  static function resolve($raw){
    if(!$raw) return array();
    $tmp  = explode('.', str_replace('"', '', sql::unfix("`$raw`")) , 2);
    $name = array_pop($tmp); $schema = $tmp[0]; if(!$schema) $schema = "public";
    $safe = sprintf('"%s"."%s"', $schema, $name ); $hash = str_replace('"','', $safe);
    return compact('name', 'schema', 'safe', 'raw', 'hash');
  }



/***************** Generics *******************/

    //This function works the same way array_reindex does, please refer to the manual
  static function brute_fetch_depth(){
    $result = array(); $cols = func_get_args();
    if($end = (end($cols)==false)) array_pop($cols);
    while(($l = sql::fetch())) {
          $tmp = &$result;
          foreach($cols as $col) $tmp=&$tmp[$l[$col]];
          $tmp = $end?$l[$col]:$l;
    } return $result;
  }


  static function brute_fetch($id=false, $val=false){
    $tmp=array();$c=0;
    while($l = sql::fetch() )
        $tmp[$id?$l[$id]:$c++] = $val?$l[$val]:$l;
    sql::free();
    return $tmp;
  }

  static function partial_fetch($id, $val, $start, $by) {
    $tmp=array();$c=0;$line=0;
    pg_result_seek(self::$result, $start);
    while(($l=sql::fetch())&&  ($by!==false?$line++<$by:true)   )
        $tmp[$id?$l[$id]:$c++]=$val?$l[$val]:$l;
    self::$_tmp_rows = sql::rows();
    sql::free();
    return array($tmp, self::$_tmp_rows);
  }


  static function limit_rows(){
    if(self::$_tmp_rows!==null) {
        $tmp = self::$_tmp_rows;
        self::$_tmp_rows = null;
        return $tmp;
    }

    $query    = end(sql::$queries);
    $begin_at = strpos($query, "FROM");
    $query = "SELECT  COUNT(*) as nb_line ".substr($query, $begin_at);
    $remove_from = min(strripos($query, "ORDER"), strripos($query, "LIMIT"));
    if($remove_from)
        $query = substr($query, 0, $remove_from);
    return sql::qvalue($query);
  }


}