<?



define("PUBLIC_PATH", getcwd()."/www");

if(is_file($argv[1])) {
  $entry = $argv[1];
  $args  = array_slice($argv, 2);
  $_SERVER['YKS_FREE'] = true;
  include "yks/class/yks/loader.php";
  require CLASS_PATH."/functions.php";
} else {
  $host  = $argv[1];
  $entry = $argv[2];
  $args  = array_slice($argv,3);
  $_SERVER['SERVER_NAME']     = $host ? $host : 'cli';
  define('yks/cli', "Yks cli tools");
  include "yks/class/yks/loader.php";
  exyks::init();
}

$clyks_config = yks::$get->config->clyks;



$helpers = array(
  'yks'  => 'yks_runner',
  'myks' => 'myks_runner',
  'sync' => 'sync_runner',
  'sql'  => 'sql_runner',
);

$entry = pick($helpers[$entry], $entry, (string)$clyks_config->bootstrap['class'], 'yks_runner');

if(is_file($entry)) {
  $first_class = first(php::file_get_php_classes($entry));
  classes::register_class_path($first_class, $entry);
  $entry = $first_class;
}

classes::extend_include_path("scripts");
classes::extend_include_path("crons");

cli::load_args($args);
interactive_runner::start($entry, cli::$args);
