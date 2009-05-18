<?
ob_start("ob_gzhandler");

$uid = $argv0;
$compress = false;
include "$class_path/dsp/js/loader.php";

$JS_YKS_PATH = EXYKS_PATH.'/js/Yks';

include "$JS_YKS_PATH/exts/mootools.php";
include "$JS_YKS_PATH/exts/mootools-extended.php";
include "$JS_YKS_PATH/yks.php";
if(DEBUG)
    $js_build_list["[YKS/ROOT]/tmp/trash/trace"] = true;

$js_build_list["[YKS/ROOT]/loader"] = true;


if($action == "load_js" || $uid) try {
    $uid = $uid?$uid:$_POST['uid'];

    $build_list = Js::dynload($uid, $js_build_list,  $JS_YKS_PATH);
    if(!$build_list)
        throw rbx::error("Invalid script request");
 } catch(rbx $e){exit;} else {
    header(HTTP_CACHED_FILE);
    $build_list = array_keys(array_filter($js_build_list, 'bool'));
    $build_list = array_map(array('Js','realpath'), $build_list);
}

 header(TYPE_JS);
 $js_file = Js::build($build_list, $compress);
 readfile($js_file);
die;
