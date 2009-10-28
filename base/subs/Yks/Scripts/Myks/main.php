<?php

while(@ob_end_clean());

exyks::$page_def = "_all"; //tpls is not loaded

$limited = $argv0;


$myks_runner = new myks_runner();

$cli_commands = array('manage_types', 'manage_locales', 'manage_sql', 'manage_xsl'); //for CLI tunnel..

if(in_array($limited, $cli_commands)) {
    call_user_func(array($myks_runner, $limited));
    die;
}

$myks_runner->go();

//die(sys_end(exyks::tick('generation_start')));
