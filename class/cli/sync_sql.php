<?

class sync_sql {
  const file_export_safe_size = 1024;
  const file_export_name      = "sync_data.tmp.sql";

    //from_database is francois_ivs@ivs-webtest
  function sync_tables($from_database, $tables){
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
  function send_contents($source_file, $dest_database){

    list($dest_database, $dest_host)    = explode('@', $dest_database);

    $dist_name = self::file_export_name;

    if($dest_host) {
        rbx::ok("Copying to $dest_host");
        exec("scp $source_file $dest_host:$dist_name");
        rbx::ok("Copy to $dest_host : done");
    }else $dist_name = $source_file;

    $cmd = "psql $dest_database < $dist_name";
    if($dest_host) $cmd = "ssh $dest_host \"$cmd\"";

    $cmd= "!!! Please Check source file $source_file before running this !!!".LF.$cmd;
    rbx::box("cmd", $cmd);

  }
}
