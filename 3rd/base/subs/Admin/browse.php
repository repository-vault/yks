<?

$myks_type  = $sub0;
$myks_value = $sub1;


if($action == "browse") {
    $myks_type  = (string) $_POST['myks_type'];
    $myks_value = (string) $_POST['myks_value'];
    jsx::$rbx = false;
}


if($myks_type != "" && $myks_value != "")
    $myks_data = mykses::dump_key($myks_type, $myks_value);


