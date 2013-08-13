<?
header(TYPE_TEXT);
while(@ob_end_clean()); //all script are now unbuffered

$runner   = $sub0;
$from_cli = $argv0 == "cli"; //from cli tunnel


if(!$runner) 
    return;

  //******* valid access ***************
$valid_runners = array('yks_runner', 'myks_runner', 'sql_runner');
$access = 
  ( yks::$get->config->is_debug() || $_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR'] ) //REMOTE
  && in_array($runner, $valid_runners);

if(!$access)
    die("Yks script cannot be started from untrusted location ({$_SERVER['REMOTE_ADDR']})");


if(!defined('STDERR'))
  define('STDERR', fopen('php://output', 'w')); //rbx compatibility


  //********* Exec runner *************
$runner = new $runner();
$cmd  = pick($sub1, 'go'); //default cmd is go on all runner :x (should)
$arg0 = $sub2;
$arg1 = $sub3;


call_user_func(array($runner, $cmd), $arg0, $arg1);

if($from_cli) die; //no meta here
die(sys_end(exyks::tick('generation_start')));