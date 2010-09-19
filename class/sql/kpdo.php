<?php

/**
*  @alias sql
**/
class _sql_pdo extends isql {
  const true  = '1';
  const false = '0';

  static function init() {
    self::$pfx['search'][]  = "#`(.*?)`#";
    self::$pfx['replace'][] = "\"$1\"";
  }

  static function connect(){
    $serv = sql::$config->links->search(sql::$link);

    list($pdo_driver, $pdo_dsn)  = explode(':', $serv["dsn"], 2);

    if($pdo_driver=="sqlite")
      $pdo_dsn = exyks_paths::resolve($pdo_dsn);

    $sql_dsn = "$pdo_driver:$pdo_dsn";

    sql::$links[sql::$link] = $lnk = new pdo($sql_dsn, $serv['user'], $serv['pass']);
    if(!$lnk)
      throw new Exception("Unable to load link #{".sql::$link."} configuration");

    if($pdo_driver=="sqlite")
      $lnk->query("PRAGMA foreign_keys=1;");
    return $lnk;
  }

  static function close($link = false){
    if(!$link) $link = sql::$link;
    if(!($serv = sql::$links[$link])) return;
    pg_close($serv); unset(sql::$links[$link]);
  }

  static function free(&$r=null){
    $r=$r?$r:sql::$result;
    return $r=null;
  }


  public static function query($query, $params=null, $arows=false){

    if(!$lnk = sql::get_lnk()) return false;

    $query = sql::unfix($query);
    
    
    sql::$result = $lnk->prepare($query);
    if(sql::$result === false) {
        $error = sql::error($query);
        return $error;
    }
    $success =  sql::$result->execute($params); //sql::$result
    if($success === false) {
        $error = sql::error($query);
        return $error;
    }
    

    return sql::$result;
  }

  static function fetch($r=false){
    $tmp = sql::$result->fetch(PDO::FETCH_ASSOC);
    return $tmp?$tmp:array();
  }
  

  static function fetch_all(){
    return sql::$result->fetchAll(PDO::FETCH_COLUMN,0);
  }


  static function error($msg=''){
    $lnk = sql::$links[sql::$link];
    $error = json_encode($lnk->errorInfo());
    $msg = ($error)." in $msg";
    
    if(!sql::$transaction) error_log($msg);
    return false;
  }


  static function rows($r=false){ return  pg_num_rows(pick($r, sql::$result)); }
  static function auto_indx($table){
    if(!$lnk = sql::get_lnk()) return false;
    return $lnk->lastInsertId();
    $name = sql::resolve($table);
    //SELECT * FROM SQLITE_SEQUENCE
    return (int)sql::qvalue("SELECT auto_increment_retrieve('{$name['name']}')");
  }

  static function query_raw($query){
    if(!$lnk = sql::get_lnk()) return false;
    return $lnk->query($query);
  }

}
