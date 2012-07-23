<?

$host  = $argv[1];
$entry = $argv[2];
$args  = array_slice($argv,3);

$_SERVER['SERVER_NAME']     = $host ? $host : 'cli';

define('yks/cli', "Yks cli tools");
define("PUBLIC_PATH", getcwd()."/www");


include "yks/class/yks/loader.php";
exyks::init();

$clyks_config = yks::$get->config->clyks;

$z_e_r_o_entries = array(
  'yks'  => 'yks_runner',
  'myks' => 'myks_runner',
  'sync' => 'sync_runner',
  'sql'  => 'sql_runner',
);
if($bootstrap = $clyks_config->bootstrap['class']){
  $z_e_r_o_entries = array_merge(array($bootstrap=>$bootstrap), $z_e_r_o_entries);
}

$entry = $z_e_r_o_entries[pick_in($entry, array_keys($z_e_r_o_entries))];



if(!$args) $args = null;


interactive_runner::start($entry, $args);
