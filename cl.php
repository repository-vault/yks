<?

$host  = $argv[1];
$entry = $argv[2];
$args  = array_slice($argv,3);

$_SERVER['SERVER_NAME']     = $host ? $host : 'cli';

define('yks/cli', "Yks cli tools");
define("PUBLIC_PATH", getcwd()."/www");


include "yks/class/yks/loader.php";
include "yks/class/functions.php";

$z_e_r_o_entries = array(
  'yks'  => 'yks_runner',
  'myks' => 'myks_runner',
  'sync' => 'sync_runner',
  'sql'  => 'sql_runner',
); $entry = $z_e_r_o_entries[pick_in($entry, array_keys($z_e_r_o_entries))];





interactive_runner::start($entry, $args);
