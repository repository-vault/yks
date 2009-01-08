<?
/*	"Exyks MySQL" by Leurent F. (131)
	distributed under the terms of GNU General Public License - © 2005-2006-2007 
*/

include 'functions.php';

class sql {
   static public $queries=array();
   static private $link='db_link';
   static private $result;
   static public $servs=null;
   static private $pfx=array();
   static private $lnks=array();

  static function &connect($lnk=false){
	if(!self::$servs) self::$servs=&yks::$get->config->sql;

	foreach(self::$servs->prefixs->attributes() as $prefix=>$trans){
		self::$pfx['search'][]="#`$prefix(_[a-z0-9_-]+)`#";
		self::$pfx['replace'][]="`$trans$1`";
	} //prefix initialisation

	$serv=self::$servs->links->{$lnk=$lnk?$lnk:self::$link};
	self::$lnks[$lnk]=@mysql_connect($serv['host'],$serv['user'],$serv['pass'],true);
	if(!self::$lnks[$lnk]) return self::error();
			
	mysql_select_db($serv['db'],self::$lnks[$lnk]);
	mysql_query("SET NAMES utf8",self::$lnks[$lnk]);
	return self::$lnks[$lnk];
  }

  static function query($query,$lnk=false){
	$serv=self::$lnks[$lnk=$lnk?$lnk:self::$link];
	if(!($serv||$serv=self::connect($lnk))) return false;
	
	self::$queries[]=$query=self::unfix($query);
	self::$result = mysql_query($query,$serv);

	if(self::$result===false) return self::error(htmlspecialchars($query));
	if(!is_resource(self::$result)) return mysql_affected_rows($serv);
	return self::$result;
  }

  static function fetch($lnk=false){
	if(!($lnk=$lnk?$lnk:self::$result))return array();
	$tmp=mysql_fetch_assoc($lnk);
	return $tmp===false?array():$tmp;
  }

  static function brute_fetch($id=false,$val=false){
	$tmp=array();$c=0;
	while($l=self::fetch())$tmp[$id?$l[$id]:$c++]=$val?$l[$val]:$l;
	return $tmp;
  }

  static function format($array){$r='';
	foreach($array as $k=>$v)$r.=",`$k`='".self::clean($v)."' ";
	return "SET ".substr($r,1);
  }

  static function close($lnk=false){
	$serv=&self::$lnks[$lnk?$lnk:self::$link]; if(!$serv)return;
	mysql_close($serv);$serv=false;
  }
	/** move the #nth item down */
  static function set_order($table,$col,$nth,$where='TRUE'){
	sql::query("SET @pos:=0,@down:=$nth;");
	return sql::query("UPDATE `$table` SET
		`$col` = IF((@pos:=@pos+1)=@down, @pos+1,IF(@pos=@down+1,@down,@pos))
		WHERE $where ORDER BY `$col` ;");
  }

  static function error($msg=''){
	$msg=DEBUG?"<b>".htmlspecialchars(mysql_error())."</b> $msg":'';
	rbx::error("&err_sql; $msg");return false;
  }

  static function insert($table,$vals,$auto_indx=false,$keys=false){
	if(is_array($keys)) $vals=array_intersect_key($vals,array_flip($keys));
	$res=sql::query("INSERT INTO `$table` ".sql::format($vals));
	return $auto_indx?self::auto_indx():$res;
  }
  static function update($table,$vals,$where='',$limit="LIMIT 1") {
	return self::query("UPDATE `$table` ".sql::format($vals)." ".sql::where($where)." $limit");
  }

  static function replace($table,$vals,$where='',$limit='',$auto_indx=false){
	$res=sql::query("REPLACE INTO `$table` ".sql::format($vals)." ".sql::where($where)." $limit");
	return $auto_indx?self::auto_indx():$res;
  }
  static function delete($table,$where,$limit='LIMIT 1'){
	return sql::query("DELETE FROM `$table` ".sql::where($where)." $limit");
  }
  static function select($table,$where='1',$cols="*",$extra=''){
	return sql::query("SELECT $cols FROM `$table` ".sql::where($where)." $extra");
  }
  static function row($table,$where='1',$cols="*"){
	return sql::qrow("SELECT $cols FROM `$table` ".sql::where($where)." LIMIT 1");
  }
  static function where($cond,$mode='&&'){
	if(!is_array($cond)) return $cond&&strpos($cond,"WHERE")===false?"WHERE $cond":$cond;
	$slice=array_filter(array_keys($cond),'is_numeric');
	$conds=array_intersect_key($cond,array_flip($slice));
	foreach(array_diff_key($cond,array_flip($slice)) as $k=>$v)
		$conds[]=is_array($v) && (list($type,$val)=each($v))
			? ( $type==="LIKE" ? "$k $type $val" : sql::in_join($k,$v) )
			: "`$k`='$v'";
	return $conds?"WHERE ".join(" $mode ",$conds):'';
  }
  static function limit_rows(){$tmp=sql::qrow("SELECT FOUND_ROWS() as tmp");return $tmp['tmp'];}
  static function unfix($str){ return preg_replace(self::$pfx['search'],self::$pfx['replace'],$str);}
  static function in_join($field,$vals){ return "$field IN('".join("','",$vals)."')"; }
  static function in_set($field,$vals){ return "FIND_IN_SET($field,'".join(",",$vals)."')"; }
  static function qrow($query,$lnk=false){ self::query($query,$lnk); return self::fetch(); }
  static function rows($lnk=false){ return  mysql_num_rows($lnk?$lnk:self::$result); }
  static function auto_indx($lnk=false){ return mysql_insert_id(self::$lnks[$lnk?$lnk:self::$link]); }
  static function free($lnk=false){ mysql_free_result($lnk=$lnk?$lnk:self::$result);$lnk=null; }
  static function truncate($table){ return sql::query("TRUNCATE `$table`"); }

  static function clean($str){ return is_numeric($str)?$str:addslashes($str); }
  static function set_link($lnk){ self::$link=$lnk; }

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
}
