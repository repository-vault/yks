<?php

while(@ob_end_clean());

      //for CLI SAPI
$cli_cmd      = $argv0;

      // for http SAPI
$myks_cmd     = "manage_{$sub0}";
$sql_cmd      = "scan_{$sub1}";
$run_queries  = $argv0 == 'RUN';


$myks_runner = new myks_runner();

$cli_commands = array('manage_types', 'manage_locales', 'manage_xsl');

if(in_array($cli_cmd, $cli_commands)) {  //from  CLI tunnel..        
    call_user_func(array($myks_runner, $cli_cmd));
} elseif(in_array($myks_cmd, $cli_commands)) {  //from  http SAPI        
    call_user_func(array($myks_runner, $myks_cmd));
} elseif($myks_cmd == 'manage_sql') {
    $sql_runner = new sql_runner();

    $sql_commands = array('scan_views', 'scan_procedures', 'scan_tables');

    if(in_array($sql_cmd, $sql_commands))
         call_user_func(array($sql_runner, $sql_cmd), $run_queries );
    else $sql_runner->go($run_queries);

} else $myks_runner->go();

die(sys_end(exyks::tick('generation_start')));
