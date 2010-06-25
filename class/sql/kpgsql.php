<?php

class ksql extends isql {

  static function init(){
    self::$pfx['search']  = "#`(.*?)`#";
    self::$pfx['replace'] = "\"$1\"";
  }

  static function connect(){
    $serv = self::$links_xml->search(self::$link);

    if(!$serv['port'])$serv['port']= 5432;
    $sql_infos = "host='{$serv['host']}' port={$serv['port']}"
                ." dbname='{$serv['db']}' user='{$serv['user']}' password='{$serv['pass']}'";

    self::$links[self::$link] = pg_connect($sql_infos);
    if(!self::$links[self::$link])
      throw new Exception("Unable to load link #{".self::$link."} configuration");

    return self::$links[self::$link];
  }

  static function close($link = false){
    if(!$link) $link = self::$link;
    if(!($serv = self::$links[$link])) return;
    pg_close($serv); unset(self::$links[$link]);
  }

  static function free(&$r=null){
    if($r=$r?$r:self::$result) pg_free_result($r);
    return $r=null;
  }


  static function query($query, $arows=false){
    if(!$lnk = self::get_lnk()) return false;

    $query = self::unfix($query);
    self::$result = pg_query($lnk, $query);

    if(self::$log) self::$queries[] = $query;
    if(self::$result===false) {
        $error = self::error(htmlspecialchars($query));
        return $error;
    }

    if($arows) {
        $arows = pg_affected_rows(self::$result);
        return $arows; 
    }
    return self::$result;
  }

  static function fetch($r=false){
    $tmp = pg_fetch_assoc( pick($r, self::$result));
    return $tmp?$tmp:array();
  }
  

  static function fetch_all(){
    return pg_fetch_all_columns(self::$result);
  }


  static function error($msg=''){
    $pg_error = pg_last_error(self::$links[self::$link]);
    $msg = "<b>".htmlspecialchars($pg_error)."</b> in $msg";
    if(DEBUG && !self::$transaction) error_log($msg);
    return false;
  }


  static function rows($r=false){ return  pg_num_rows(pick($r, self::$result)); }
  static function auto_indx($table){
    $name = self::resolve($table);
    return (int)ksql::qvalue("SELECT auto_increment_retrieve('{$name['name']}')");
  }

  static function query_raw($query){
    if(!$lnk = self::get_lnk()) return false;
    return pg_query($lnk, $query);
  }

}



