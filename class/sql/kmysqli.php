<?php

/**
*  @alias sql
**/
class _sql_mysqli extends isql {

  public static $esc = '"';

  static function connect(){
    $serv = sql::$config->links->search(sql::$link);

    $credentials = array($serv['host'], $serv['user'], $serv['pass'], $serv['db']);
    if($serv['port']) $credentials[] = $serv['port'];

    sql::$links[sql::$link] = @call_user_func_array('mysqli_connect', $credentials);

    if(!sql::$links[sql::$link])
      throw new Exception("Unable to load link #{".sql::$link."} configuration");

    mysqli_set_charset(sql::$links[sql::$link], "utf8");
    return sql::$links[sql::$link];
  }


  static function close($link = false){
    if(!$link) $link = sql::$link;
    if(!($serv = sql::$links[$link])) return;
    mysqli_close($serv); unset(sql::$links[$link]);
  }

  static function free(&$r=null){
    if($r=$r?$r:sql::$result) mysqli_free_result($r);
    return $r=null;
  }



  public static function query($query, $params=null, $arows=false){

    if(!$lnk = sql::get_lnk()) return false;
    $query = sql::unfix($query);
    $query = sql::format_raw_query($query, $params, $lnk);

    sql::$result = mysqli_query($lnk, $query);

    if(sql::$log) sql::$queries[] = $query;
    if(sql::$result===false) {
        $error = sql::error(htmlspecialchars($query));
        return $error;
    }

    if($arows) {
        $arows = mysqli_affected_rows(sql::$result);
        return $arows;
    }
    return sql::$result;
  }

  static function fetch($r=false){
    $tmp = mysqli_fetch_assoc( pick($r, sql::$result));
    return $tmp?$tmp:array();
  }


  static function fetch_all(){
    $res = array();
    while($l=mysqli_fetch_row(sql::$result)) $res[]=$l[0];
    return $res;
  }

  static function error($msg='') {
    $error = mysqli_error(sql::$links[sql::$link]);
    $msg = "<b>".htmlspecialchars($error)."</b> in $msg";
    if(yks::$get->config->is_debug() && !sql::$transaction) error_log($msg);
    return false;
  }


  static function clean($str){
    if(is_numeric($str)) return $str;
    if(!$lnk = sql::get_lnk()) return false;
    return mysqli_real_escape_string($lnk, $str);
  }

  static function rows($r=false){ return  mysqli_num_rows(pick($r, sql::$result)); }
  static function auto_indx($table){
    if(!$lnk = sql::get_lnk()) return false;
    return mysqli_insert_id($lnk);
  }

  static function query_raw($query){
    if(!$lnk = sql::get_lnk()) return false;
    return mysqli_query($lnk, $query);
  }

//************** Extras ************
  static function limit_rows(){return sql::qvalue("SELECT FOUND_ROWS()");}
}



