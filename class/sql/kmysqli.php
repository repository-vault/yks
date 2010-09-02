<?php

class ksql extends isql {


  static function connect(){
    $serv = ksql::$config->links->search(ksql::$link);
    $credentials = array($serv['host'], $serv['user'], $serv['pass'], $serv['db']);
    ksql::$links[ksql::$link] = @call_user_func_array('mysqli_connect', $credentials);

    if(!ksql::$links[ksql::$link])
      throw new Exception("Unable to load link #{".ksql::$link."} configuration");

    mysqli_set_charset(ksql::$links[ksql::$link], "utf8");
    return ksql::$links[ksql::$link];
  }


  static function close($link = false){
    if(!$link) $link = ksql::$link;
    if(!($serv = ksql::$links[$link])) return;
    mysqli_close($serv); unset(ksql::$links[$link]);
  }

  static function free(&$r=null){
    if($r=$r?$r:ksql::$result) mysqli_free_result($r);
    return $r=null;
  }



  public static function query($query, $params=null, $arows=false){
    
    if(!$lnk = ksql::get_lnk()) return false;
    $query = ksql::unfix($query);
    $query = ksql::format_raw_query($query, $params, $lnk);

    ksql::$result = mysqli_query($lnk, $query);

    if(ksql::$log) ksql::$queries[] = $query;
    if(ksql::$result===false) {
        $error = ksql::error(htmlspecialchars($query));
        return $error;
    }

    if($arows) {
        $arows = mysqli_affected_rows(ksql::$result);
        return $arows; 
    }
    return ksql::$result;
  }

  static function fetch($r=false){
    $tmp = mysqli_fetch_assoc( pick($r, ksql::$result));
    return $tmp?$tmp:array();
  }
  

  static function fetch_all(){
    $res = array();
    while($l=mysqli_fetch_row(ksql::$result)) $res[]=$l[0];
    return $res;
  }

  static function error($msg='') {
    $error = mysqli_error(ksql::$links[ksql::$link]);
    $msg = "<b>".htmlspecialchars($error)."</b> in $msg";
    if(DEBUG && !ksql::$transaction) error_log($msg);
    return false;
  }


  static function clean($str){
    if(is_numeric($str)) return $str;
    if(!$lnk = ksql::get_lnk()) return false;
    return mysqli_real_escape_string($lnk, $str);
  }

  static function rows($r=false){ return  mysqli_num_rows(pick($r, ksql::$result)); }
  static function auto_indx($table){
    $name = ksql::resolve($table);
    return (int)ksql::qvalue("SELECT auto_increment_retrieve('{$name['name']}')");
  }

  static function query_raw($query){
    if(!$lnk = ksql::get_lnk()) return false;
    return mysqli_query($lnk, $query);
  }

//************** Extras ************
  static function limit_rows(){return ksql::qvalue("SELECT FOUND_ROWS()");}
}




class sql extends ksql {}
