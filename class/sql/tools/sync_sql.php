<?


/**
* @command_alias up sync up
* @command_alias down sync down
*/
class sync_sql {
  const file_export_safe_size = 1024;
  const file_export_name      = "sync_data.tmp.sql";

  private $tables;
  private $coords;
  private $tmp_file;

  static private $tmp_cmd;

  function __construct($tables, $from, $to, $tmp_file = "tmp.sql") {
    $this->tables   = $tables;
    $this->coords   = array($from, $to);
    $this->tmp_file = $tmp_file;
    
    if(is_file($this->tmp_file)) unlink($this->tmp_file);
  }

        //controler
  function sync($way){
    if($way == 'up') list($from, $to) = $this->coords;
    else            list($to, $from) = $this->coords;

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


  private static function sync_tables($from_database, $tables){
    $tmp_file = tempnam(sys_get_temp_dir(), 'sql-');

    $tables_nb = count($tables);
    if(!$tables_nb) die("Please specify at least one table");
    rbx::ok("Retrieving distant data ($tables_nb table(s))");

    list($from_database, $from_host)    = explode('@', $from_database);
    list($from_database, $from_schema)  = explode('.', $from_database);

    $from_cmd      =  "pg_dump -a %s $from_database"; //tables

    if($from_host)
        $from_cmd = "ssh $from_host \"$from_cmd\"";

    $cmds       = array();
    $from_cmd   = sprintf($from_cmd, mask_join(' ', $tables, "-t %s"));
    $cmds []    = "$from_cmd  > $tmp_file";
    $cmds       = join(LF, $cmds);

        exec($cmds);


    if( !is_file($tmp_file)
        || filesize($tmp_file) < self::file_export_safe_size)
            throw rbx::error("Error while retrieving external data");


    $triggers_off       = mask_join(LF, $tables, "ALTER TABLE %s DISABLE TRIGGER ALL;");
    $triggers_on        = mask_join(LF, $tables, "ALTER TABLE %s ENABLE TRIGGER ALL;");
    $delete_queries     = mask_join(LF, $tables, "DELETE FROM %s;");
    $insert_queries     = file_get_contents($tmp_file);

    $contents = "BEGIN;".LF
        .$triggers_off.LF
        .$delete_queries.LF
        .$insert_queries.LF
        .$triggers_on.LF.
        "COMMIT;".LF
    ; file_put_contents($tmp_file, $contents);


    return file_get_contents($tmp_file);

  }

  private static function send_contents($source_file, $dest_database){
    list($dest_database, $dest_host)    = explode('@', $dest_database);

    $dist_name = self::file_export_name;

    if($dest_host) {
        rbx::ok("Copying to $dest_host");
        exec("scp $source_file $dest_host:$dist_name");
        rbx::ok("Copy to $dest_host : done");
    }else $dist_name = $source_file;

    self::$tmp_cmd = "psql $dest_database < $dist_name";
    if($dest_host) self::$tmp_cmd = "ssh $dest_host \"".self::$tmp_cmd."\"";

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
