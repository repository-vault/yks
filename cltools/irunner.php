<?
include "yks/class/yks/loader.php";
include "yks/class/functions.php";

$include_path = $argv[1];

$classes0 = get_declared_classes();
include $include_path;
$classes1 = get_declared_classes();

$first_class = reset(array_diff($classes1, $classes0));
if(!$first_class)
  exit("No valid class");

interactive_runner::start(new $first_class());

//die;//exit code must be 0
