<?
/*	"Exyks data (APC)" by Leurent F. (131)
	distributed under the terms of GNU General Public License - © 2008
*/


//Find documentation in the manual
class data {
  static $callbacks=array();
  static $includes=array();

  static function store($flag, $v, $ttl=0){return apc_store(FLAG_APC."_$flag", $v, $ttl)?$v:false; }
  static function delete($flag) { return apc_delete(FLAG_APC."_$flag"); }
  static function fetch($flag){ return apc_fetch(FLAG_APC."_$flag"); }
  static function register($flag, $callback,$file=false){
    self::$callbacks[$flag]=$callback;
    if($file) self::$includes[$flag]=$file;
  }

  static function load($flag,$zone=''){
    if(!($tmp=self::fetch(trim("{$flag}_{$zone}",'_')) ))
        return self::reload($flag,$zone);
    return substr($flag,-4)=="_xml"?simplexml_load_string($tmp):$tmp;
  }
  static function reload($flag,$zone=''){ //locked signature ($args[2])
    $args = func_get_args(); $tmp=false; 
    if($callback =self::$callbacks[$flag]) {
        if(!(is_array($callback) && class_exists(reset($callback))
                || is_string($callback) && function_exists($callback))
            && is_file($file=self::$includes[$flag])) require $file;
        $tmp=call_user_func($callback, $flag,$zone );
    } else $tmp=include('data_def.php');

    if($tmp) self::store(trim("{$flag}_{$zone}",'_'),$tmp);
    return substr($flag,-4)=="_xml"?simplexml_load_string($tmp):$tmp;
  }
}
