<?php

class ksql extends isql {

  static function init() {
    ksql::$pfx['search'][]  = "#`(.*?)`#";
    ksql::$pfx['replace'][] = "\"$1\"";
  }

  static function connect(){
    $serv = ksql::$config->links->search(ksql::$link);

    list($sqlite_scheme, $sqlite_path)  = explode(':', $serv["dsn"], 2);
    $sqlite_path = exyks_paths::resolve($sqlite_path);
    $sql_dsn = "$sqlite_scheme:$sqlite_path";


    ksql::$links[ksql::$link] =  new pdo($sql_dsn);
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
    $r=$r?$r:ksql::$result;
    return $r=null;
  }


  static function query($query, $arows=false){

    if(!$lnk = ksql::get_lnk()) return false;

    $query = ksql::unfix($query);
    
    
    ksql::$result = $lnk->prepare($query);
    if(ksql::$result === false) {
        $error = ksql::error($query);
        return $error;
    }
    $success =  ksql::$result->execute(); //ksql::$result
    if($success === false) {
        $error = ksql::error($query);
        return $error;
    }
    

    return ksql::$result;
  }

  static function fetch($r=false){
    $tmp = ksql::$result->fetch(PDO::FETCH_ASSOC);
    return $tmp?$tmp:array();
  }
  

  static function fetch_all(){
    return pg_fetch_all_columns(ksql::$result);
  }


  static function error($msg=''){
    $lnk = ksql::$links[ksql::$link];
    $error = json_encode($lnk->errorInfo());
    $msg = ($error)." in $msg";
    
    if(!ksql::$transaction) error_log($msg);
    return false;
  }


  static function rows($r=false){ return  pg_num_rows(pick($r, ksql::$result)); }
  static function auto_indx($table){
    $name = ksql::resolve($table);
    return (int)ksql::qvalue("SELECT auto_increment_retrieve('{$name['name']}')");
  }

  static function query_raw($query){
    if(!$lnk = ksql::get_lnk()) return false;
    return pg_query($lnk, $query);
  }

}

class sql extends ksql {}


