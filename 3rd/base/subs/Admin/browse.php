<?php

$myks_type  = $sub0;
$myks_value = $sub1;
$recurse    = true;

if($action == "browse") {
    $myks_type  = (string) $_POST['myks_type'];
    $myks_value = (string) $_POST['myks_value'];
    $recurse    = bool($_POST['recurse']);
    jsx::$rbx = false;
}


if($myks_type != "" && $myks_value != "")
    $myks_data = mykses::dump_key($myks_type, $myks_value, array(), $recurse);


