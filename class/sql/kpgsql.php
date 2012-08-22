<?php

class ksql extends isql {
  public static $esc = '"';
  static function init() {
    ksql::$pfx['search'][]  = "#`(.*?)`#";
    ksql::$pfx['replace'][] = "\"$1\"";
  }

  static function connect(){
    $serv = ksql::$config->links->search(ksql::$link);

    if(!$serv['port'])$serv['port']= 5432;
    $sql_infos = "host='{$serv['host']}' port={$serv['port']}"
                ." dbname='{$serv['db']}' user='{$serv['user']}' password='{$serv['pass']}'";

    ksql::$links[ksql::$link] = pg_connect($sql_infos);
    if(!ksql::$links[ksql::$link])
      throw new Exception("Unable to load link #{".ksql::$link."} configuration");

    return ksql::$links[ksql::$link];
  }

  static function close($link = false){
    if(!$link) $link = ksql::$link;
    if(!($serv = ksql::$links[$link])) return;
    pg_close($serv); unset(ksql::$links[$link]);
  }

  static function free(&$r=null){
    if($r=$r?$r:ksql::$result) pg_free_result($r);
    return $r=null;
  }



  static function query($query, $params = array(), $arows=false){


    if(!$lnk = ksql::get_lnk()) return false;

    $query = ksql::unfix($query);
    $query = ksql::format_raw_query($query, $params, $lnk);

    if(ksql::$log) ksql::$queries[] = $query;


    ksql::$result = pg_query($lnk, $query);

    if(ksql::$result===false) {
        $error = ksql::error(htmlspecialchars($query));
        return $error;
    }

    if($arows) {
        $arows = pg_affected_rows(ksql::$result);
        return $arows; 
    }
    return ksql::$result;
  }

  static function fetch($r=false){
    $tmp = pg_fetch_assoc( pick($r, ksql::$result));
    return $tmp?$tmp:array();
  }
  

  static function fetch_all(){
    return pg_fetch_all_columns(ksql::$result);
  }


  static function error($msg=''){
    $pg_error = pg_last_error(ksql::$links[ksql::$link]);
    $msg = "<b>".htmlspecialchars($pg_error)."</b> in $msg";
    if(yks::$get->config->is_debug() && !ksql::$transaction) error_log($msg);
    return false;
  }


  static function rows($r=false){ return  pg_num_rows(pick($r, ksql::$result)); }
  static function auto_indx($table){
    $name = ksql::resolve($table);
    return (int)ksql::qvalue("SELECT auto_increment_retrieve('{$name['name']}')");
  }


  static function clean($str){
    if(is_numeric($str)) return $str;
    if(!$lnk = ksql::get_lnk()) return false;
    return pg_escape_string($lnk, $str);
  }

  static function query_raw($query, $lnk = false){
    if(!$lnk = pick($lnk, ksql::get_lnk())) return false;
    return pg_query($lnk, $query);
  }

}

class sql extends ksql {}


