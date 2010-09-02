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

   public static public $queries=array();
   public static $log=true;


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
    return  isset(ksql::$links[ksql::$link]) ? ksql::$links[ksql::$link] : ksql::connect();
  }

  public static function set_link($link){
    self::$link = $link;
    if(!self::$config->links->search(self::$link))
      throw rbx::error("Unable to load sql configuration.");
  }


/***************** Basics *******************/

  static function select($table, $where = sql::true, $cols="*", $extra=''){
    list($where, $params) = ksql::iwhere($where, $table);
    $query = "SELECT $cols ".ksql::from($table)." $where $extra";
    return ksql::query($query, $params);
  }

  static function insert($table, $vals=array(), $auto_indx=false, $keys=false){
    if(is_array($keys)) $vals = array_intersect_key($vals, array_flip($keys));

    if(!$vals) $format = 'VALUES (DEFAULT)';
    else list($format, $vals) = ksql::format_prepare($vals,false);

    $query  = 'INSERT INTO '.ksql::fromf($table)." $format";

    $result = ksql::query($query, $vals);
    return $auto_indx && $result ? ksql::auto_indx($table) : $result;
  }


  static function update($table, $vals, $where='', $extras="") {
    if(!$vals) return false;
    list($format, $params) = ksql::format_prepare($vals);
    list($where, $vher)  = ksql::iwhere($where, $table);

    if($vher) $params = array_merge($params, $vher);
    $query  = 'UPDATE '.ksql::fromf($table)." $format $where $extras";
    return ksql::query($query, $params);
  }

  static function delete($table, $where, $extras=''){
    if(!$where) return false;
    list($where, $params) = ksql::iwhere($where, $table);
    $query = 'DELETE FROM '.ksql::fromf($table)." $where $extras";
    return ksql::query($query, $params, true);
  }

  static function truncate($table){
    $query = 'DELETE FROM '.ksql::fromf($table);
    return ksql::query($query);
  }

/***************** Version classic ************/
    //format values
    //you'll need an sql::clean implementation to use this
  static function format_raw_query($query, $params, $lnk = null){
    if(!$params ) {
        error_log($query);
        error_log(print_r($params,1));
    }
    foreach($params as $k=>&$v) {
        if(is_null($v))  $v = 'NULL';
        elseif(is_int($v));
        elseif(is_bool($v)) $v = $v?sql::true:sql::false;
        else $v = "'".ksql::clean($v)."'";
    }
    return strtr($query, $params);
  }

/***************** Extended *******************/


  static function row($table, $where = sql::true, $cols='*', $extras=''){
    ksql::select($table, $where, $cols, "$extras LIMIT 1");
    return ksql::fetch();
  }

  static function value($table, $where = sql::true, $cols='*', $extras=''){
    return reset(ksql::row($table, $where,  $cols, $extras));
  }

  static function lines($table){
    return sql::value($table, true, "COUNT(*)");
  }

  static function qrow($query)   { ksql::query($query); return ksql::fetch(); }
  static function qvalue($query) { return reset(ksql::qrow($query)); }

  static function replace($table, $vals, $where=array(), $auto_indx=false){
    $data = ksql::row($table, $where);
    if(!$data)
        return ksql::insert($table, array_merge($vals, $where), $auto_indx);
    return ksql::update($table, $vals, $where);
  }


/***************** Data helpers *******************/

  static function in_join($field,$vals,$not=''){ return "$field $not IN('".join("','",$vals)."')"; }
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
        return 'FROM '.(preg_match('#[^a-z0-9_.-]#', $tables)? $tables : ksql::fromf($tables));

    $ret  = 'FROM '.ksql::fromf(array_shift($tables));
    foreach($tables as $k=>$table)
      $ret .= is_numeric($k) ? $table : "INNER JOIN ".ksql::fromf($table)." USING($k)";
    return $ret;
  }


    //format conditions
  static function conds($k, $v, &$params = null){
    if(is_array($v))  return ksql::in_join($k,$v);

    if(is_string($v) || is_int($v)) {
        $params[":w$k"] = $v;
        return "$k=:w$k";
    }
    if(is_null($v))   return "$k IS NULL";
    if(is_bool($v))   return $v?"$k":"not($k)";
  }

  static function where($cond, $table = false, $mode = 'AND') {
    list($where_str, $args) = ksql::iwhere($cond, $table, $mode);
    $where_str = ksql::format_raw_query($where_str, $args);
    return $where_str;
  }

 //return array(str, args)
  protected static function iwhere($cond, $table=false, $mode='AND'){

    if(is_bool($cond) || !$cond)
        return array($cond?'':'WHERE '.ksql::false, array());

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
       $conds[]= ksql::conds($k, $v, $params);
    return array($conds?"WHERE ".join(" $mode ",$conds):'', $params);
  }




/***************** Transactions *******************/

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


/***************** Internals *******************/

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



/***************** Generics *******************/

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