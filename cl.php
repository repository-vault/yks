<?
$_SERVER['SERVER_NAME']     = 'cli';

define('yks/cli', "Yks cli tools");


include "yks/class/yks/loader.php";
include "yks/class/functions.php";


interactive_runner::start(new myks_runner());