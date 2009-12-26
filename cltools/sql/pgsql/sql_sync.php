<?

//pgsql connector

class sql_sync extends __sql_sync  {


  static protected $sql_bin;
  static protected $sql_dump;

  static function init(){
    self::$sql_bin  = `which psql`;
    self::$sql_dump = `which pg_dump`;
  }

  protected function bin_dump($coord, $tables){
    $args          = "-a";
    $tables        = mask_join(' ', $tables, "-t %s"); //tables

    $from_cmd      = sprintf("%s %s %s", self::$sql_dump, $args, $tables);
    return $from_cmd;
  }


  protected function bin_raw($coord){
    $bin_args = "-tA";
    $cmd = sprintf("%s %s", $this->bin($coords), $bin_args);
    return $cmd
  }
  
  protected function bin($coords){
    return self::$sql_bin;
  }

  public static function triggers_off($tables){
    return mask_join(LF, array_keys($tables),
        "ALTER TABLE %s DISABLE TRIGGER ALL;");
  }

  public static function triggers_on($tables){
    return mask_join(LF, array_keys($tables),
        "ALTER TABLE %s ENABLE TRIGGER ALL;");
  }



}