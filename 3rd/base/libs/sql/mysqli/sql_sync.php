<?

//mysql  connector

class sql_sync extends __sql_sync {

  static protected $sql_bin;
  static protected $sql_dump;

  static function init(){
    self::$sql_bin  = "/usr/local/mysql/bin/mysql";
    self::$sql_dump = "/usr/local/mysql/bin/mysqldump";
  
  }
  
  static function bin_dump($coord, $tables){
    $tables_str = '';
    foreach($tables as $table) $tables_str .= ' '.$table['table'];
    
    $args = "--no-create-info --password={$coord['password']} -u {$coord['user']} --skip-set-charset --skip-opt --quick --extended-insert";
    $from_cmd      = sprintf("%s %s %s %s", self::$sql_dump, $args, $coord['database'], $tables_str);
    return $from_cmd;
  }

  
  protected function bin_raw($coord){
    $args = "--skip-column-names";
    $cmd  = $this->bin($coord);
    return sprintf("%s %s",  $cmd, $args);
  }

  protected function bin($coord){
    $bin_args = "--password={$coord['password']} -u {$coord['user']} ";
    $bin      = sprintf("%s %s", self::$sql_bin, $bin_args);
    return $bin;
  }

  public static function triggers_off($tables){
    return "";
  }

  public static function triggers_on($tables){
    return "";
  }

  
}