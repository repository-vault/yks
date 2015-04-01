<?php

//pgsql connector

class sql_sync extends __sql_sync  {


  static protected $sql_bin;
  static protected $sql_dump;

  static function init(){
    self::$sql_bin  = trim(`which psql`);
    self::$sql_dump = trim(`which pg_dump`);
  }

  protected function bin_dump($coord, $tables){
    $args          = "-a";

    $tables_str = '';
    foreach($tables as $table) $tables_str .= ' -t '.$table['safe'];


    $from_cmd      = sprintf("%s %s %s %s", self::$sql_dump, $args, $tables_str, $coord['database']);
    return $from_cmd;
  }


  protected function bin_raw($coord){
    $bin_args = "-tA";
    $cmd = sprintf("%s %s", $this->bin($coords), $bin_args);
    return $cmd;
  }
  
  protected function bin($coords){
    return self::$sql_bin;
  }

  public static function triggers_off($tables){
    return mask_join(LF, array_extract($tables, 'safe'),
        "ALTER TABLE %s DISABLE TRIGGER ALL;");
  }

  public static function triggers_on($tables){
    return mask_join(LF, array_extract($tables, 'safe'),
        "ALTER TABLE %s ENABLE TRIGGER ALL;");
  }



}