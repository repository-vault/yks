<?php



abstract class __sql_sync {
  const file_export_safe_size = 1024;
  const file_export_name      = "sync_data.tmp.sql";

  protected $tables;
  protected $coords;
  protected $tmp_file;

  static protected $tmp_cmd;

  
  function __construct($tables, $from, $to, $tmp_file = self::file_export_name) {

    $this->coords   = array(
        'from' => $this->parse($from),
        'to'   => $this->parse($to)
    );

    if(!is_array($tables)) $tables = $this->tablify($tables);
    $this->tables   = $tables;

    cli::box("Tables list", array_keys($this->tables));

    $this->tmp_file = $tmp_file;
    
    if(is_file($this->tmp_file)) unlink($this->tmp_file);
  }

  static private function parse($table_dsn){
    if(!is_array($table_dsn)) {
        list($database, $host)    = explode('@', $table_dsn);
        list($database, $schema)  = explode('.', $database);
        $table_dsn = compact('database', 'host', 'schema');
    }
    if(!$table_dsn['schema']) $table_dsn['schema'] = "public";

    return $table_dsn;
  }


  private function escape($str){
    return str_replace('"', '\"', str_replace("`", "\\`", ($str)));
  }

  private function tablify($table_filter){

    $coord = $this->coords['from'];
    $col   = "(table_schema || ',' || table_name)";
    $query = "SELECT $col FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND $table_filter";

    $query = sql::unfix($query);


    $cmd   = sprintf('echo "%s" | %s %s', self::escape($query), $this->bin_raw($coord), $coord['database']);

    if($coords['host'])
        $cmd = "ssh {$coords['host']} \"".self::escape($cmd)."\"";

    $out = array();
    exec($cmd, $out, $success);
    
    $tables_list = array();
    foreach($out as $line) {
      list($schema, $table) = explode(",", "$line,,");
      $table_name = "`$schema`.`$table`";
      $tables_list[$table_name] = sql::resolve($table_name);
    }

    if(!$out)
        throw rbx::error("Unable to retrieve tables_list, aborting");
        
    return $tables_list;
  }

/**  controler
* @alias up up
* @alias down down
*/
  function sync($way){
    if($way == 'up') list($from, $to) = array($this->coords['from'], $this->coords['to']);
    else             list($to, $from) = array($this->coords['from'], $this->coords['to']);

    try {
        rbx::title("Starting");
        $contents = self::sync_tables($from, $this->tables);
        file_put_contents($this->tmp_file, $contents);
        self::send_contents($this->tmp_file, $to);

    } catch(Exception $e){
        rbx::error("end");
        die;
    }

    rbx::line();
  }


  private static function sync_tables($from, $tables){
    $tmp_file = tempnam(sys_get_temp_dir(), 'sql-');

    $tables_nb = count($tables);
    if(!$tables_nb) die("Please specify at least one table");
    rbx::ok("Retrieving distant data ($tables_nb table(s))");


    $from_cmd   = sql_sync::bin_dump($from, $tables);

    if($from['host'])
        $from_cmd = "ssh {$from['host']} \"".self::escape($from_cmd)."\"";


    
    $triggers_off       = sql_sync::triggers_off($tables);
    $triggers_on        = sql_sync::triggers_on($tables);
    $delete_queries     = sql_sync::delete_tables($tables);

    $cmd = "$from_cmd  > $tmp_file";
    exec($cmd);

    if( !is_file($tmp_file)
        || filesize($tmp_file) < self::file_export_safe_size)
            throw rbx::error("Error while retrieving external data");

    $insert_queries     = file_get_contents($tmp_file);

    $contents = "BEGIN;".LF
        .$triggers_off.LF
        .$delete_queries.LF
        .$insert_queries.LF
        .$triggers_on.LF.
        "COMMIT;".LF
    ; file_put_contents($tmp_file, $contents);


    rbx::ok("Ouput to $tmp_file");
    return file_get_contents($tmp_file);

  }
  
  protected static function delete_tables($tables){
    $tables = array_extract($tables, 'safe');
    return mask_join(LF, $tables, "DELETE FROM %s;");
  }

  protected static function send_contents($source_file, $dest){
    $dist_name = self::file_export_name;

    if($dest['host']) {
        rbx::ok("Copying to {$dest['host']}");
        $cmd = "scp $source_file {$dest['host']}:$dist_name";
        exec($cmd);
        rbx::ok("Copy to {$dest['host']} : done");
    }else $dist_name = $source_file;

    self::$tmp_cmd = sprintf("%s %s < %s", sql_sync::bin($dest), $dest['database'], $dist_name);
    if($dest['host']) self::$tmp_cmd = "ssh {$dest['host']} \"".self::$tmp_cmd."\"";

    $cmd= "!!! Please Check source file $source_file before running this !!!".LF.self::$tmp_cmd;
    rbx::box("cmd", self::$tmp_cmd);
  }

  public static function doit(){
    if(self::$tmp_cmd) {
        rbx::ok("Running ".self::$tmp_cmd);
        passthru (self::$tmp_cmd);
        //rbx::box("Result", $out);
        self::$tmp_cmd = false;
    } else rbx::ok("Nothing to do");

  }

}
