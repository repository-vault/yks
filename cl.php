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



$helpers = array(
  'yks'  => 'yks_runner',
  'myks' => 'myks_runner',
  'sync' => 'sync_runner',
  'sql'  => 'sql_runner',
);

$entry = pick($helpers[$entry], $entry, (string)$clyks_config->bootstrap['class'], 'yks_runner');
if(is_file($script = "scripts/$entry.php") || is_file($script = "crons/$entry.php"))
    classes::register_class_path($entry, $script);

classes::extend_include_path("scripts");
classes::extend_include_path("crons");

cli::load_args($args);
interactive_runner::start($entry, cli::$args);
