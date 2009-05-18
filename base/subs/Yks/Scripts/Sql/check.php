<?

$auto_inc_table_name = "zks_autoincrement";
$tables_xml = yks::$get->tables_xml;

sql::select($auto_inc_table_name);
$current_values = sql::brute_fetch_depth( 'increment_table', 'increment_key');

rbx::title("Auto increment maintainance");

$queries  = array();
foreach($tables_xml as $table_name=>$table_xml){
    $table_uname = sql::unquote($table_name);
    $birth_key = (string)$table_xml['birth'];
    if(!$birth_key) continue;
    $mykse_type = myks::resolve_base($birth_key);
    if($mykse_type['type']!='int') {
        rbx::ok("-- $table_name(`$birth_key`) is '{$mykse_type['type']}', skipping");
        continue;
    }
    $max = sql::value($table_name, true, "max($birth_key)");
    if(!$max){
        rbx::error("Unable to retrieve max from `$table_name`, please check");
        continue;
    }
    $current = $current_values[$table_uname][$birth_key];
    if($current['increment_value'] == $max 
        && $current['increment_lastval'] == $max ) continue;

    $data = array(
        'increment_value'   => $max,
        'increment_lastval' => $max,
    );
    $verif = array(
        'increment_key'     => $birth_key,
        'increment_table'   => $table_uname
    );
    $queries[] = "UPDATE `$auto_inc_table_name` ".sql::format($data)." ".sql::where($verif).";";
}
if(!$queries)
  rbx::ok("-- Nothing to do in $auto_inc_table_name");
else {
  rbx::title("$auto_inc_table_name is not up to date");
  echo sql::unfix(join(CRLF, $queries)).CRLF;
}

rbx::line();

die;

