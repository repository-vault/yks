<?
$host = $argv[1];

$_SERVER['SERVER_NAME']     = $host ? $host : 'cli';

define('yks/cli', "Yks cli tools");

define("PUBLIC_PATH", getcwd()."/www");

include "yks/class/yks/loader.php";
include "yks/class/functions.php";


interactive_runner::start(new myks_runner());