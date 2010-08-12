<?php

class ksql extends isql {
  const true  = '1';
  const false = '0';

  static function init() {
    ksql::$pfx['search'][]  = "#`(.*?)`#";
    ksql::$pfx['replace'][] = "\"$1\"";
  }

  static function connect(){
    $serv = ksql::$config->links->search(ksql::$link);

    list($pdo_driver, $pdo_dsn)  = explode(':', $serv["dsn"], 2);

    if($pdo_driver=="sqlite")
      $pdo_dsn = exyks_paths::resolve($pdo_dsn);

    $sql_dsn = "$pdo_driver:$pdo_dsn";

    ksql::$links[ksql::$link] = $lnk = new pdo($sql_dsn, $serv['user'], $serv['pass']);
    if(!$lnk)
      throw new Exception("Unable to load link #{".ksql::$link."} configuration");

    if($pdo_driver=="sqlite")
      $lnk->query("PRAGMA foreign_keys=1;");
    return $lnk;
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


  public static function query($query, $params=null, $arows=false){

    if(!$lnk = ksql::get_lnk()) return false;

    $query = ksql::unfix($query);
    
    
    ksql::$result = $lnk->prepare($query);
    if(ksql::$result === false) {
        $error = ksql::error($query);
        return $error;
    }
    $success =  ksql::$result->execute($params); //ksql::$result
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
    return ksql::$result->fetchAll(PDO::FETCH_COLUMN,0);
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
    if(!$lnk = ksql::get_lnk()) return false;
    return $lnk->lastInsertId();
    $name = ksql::resolve($table);
    //SELECT * FROM SQLITE_SEQUENCE
    return (int)ksql::qvalue("SELECT auto_increment_retrieve('{$name['name']}')");
  }

  static function query_raw($query){
    if(!$lnk = ksql::get_lnk()) return false;
    return $lnk->query($query);
  }

}

class sql extends ksql {}


