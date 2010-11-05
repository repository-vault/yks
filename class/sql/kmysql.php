<?php

/**
*  @alias sql
**/
class _sql_mysql extends isql{


  static function connect(){
    $serv = sql::$config->links->search(sql::$link);
    $credentials = array();
    sql::$links[sql::$link] = mysql_connect($serv['host'], $serv['user'], $serv['pass']);
    if(!sql::$links[sql::$link])
      throw new Exception("Unable to load link #{".sql::$link."} configuration");

    mysql_select_db($serv['db'], sql::$links[sql::$link]);
    //mysql_set_charset ( "UTF-8",  sql::$links[sql::$link]);
    mysql_query("SET NAMES utf8", sql::$links[sql::$link]);
    return sql::$links[sql::$link];
  }


  static function close($link = false){
    if(!$link) $link = sql::$link;
    if(!($serv = sql::$links[$link])) return;
    mysql_close($serv); unset(sql::$links[$link]);
  }

  static function free(&$r=null){
    if($r=$r?$r:sql::$result) mysql_free_result($r);
    return $r=null;
  }


  public static function query($query, $params=null, $arows=false){

    if(!$lnk = sql::get_lnk()) return false;
    $query = sql::unfix($query);
    $query = sql::format_raw_query($query, $params, $lnk);

    sql::$result = mysql_query($query, $lnk);

    if(sql::$log) sql::$queries[] = $query;

    if(sql::$result===false) {
        $error = sql::error(htmlspecialchars($query));
        return $error;
    }

    if($arows) {
        $arows = mysql_affected_rows($lnk);
        return $arows; 
    }
    return sql::$result;
  }

  static function fetch($r=false){
    $tmp = mysql_fetch_assoc( pick($r, sql::$result));
    return $tmp?$tmp:array();
  }
  

  static function fetch_all(){
    $res = array();
    while($l=mysql_fetch_row(sql::$result)) $res[]=$l[0];
    return $res;
  }

  static function error($msg='') {
    $error = mysql_error(sql::$links[sql::$link]);
    $msg = "<b>".htmlspecialchars($error)."</b> in $msg";
    if(DEBUG && !sql::$transaction) error_log($msg);
    return false;
  }

  static function clean($str){
    if(is_numeric($str)) return $str;
    if(!$lnk = sql::get_lnk()) return false;
    return mysql_real_escape_string($str, $lnk);
  }


  static function rows($r=false){ return  mysql_num_rows(pick($r, sql::$result)); }
  static function auto_indx(){
    return (int)mysql_insert_id(sql::$links[sql::$link]);
  }

  static function query_raw($query){
    if(!$lnk = sql::get_lnk()) return false;
    return mysql_query($lnk, $query);
  }

//************** Extras ************
  static function limit_rows(){return sql::qvalue("SELECT FOUND_ROWS()");}
}

