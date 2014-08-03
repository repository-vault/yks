<?
ob_start("ob_gzhandler");

$uids = array_filter(explode("," , $argv0));
if($argv0[0]=='{') {
    $nojsx_ctx = json_parser::parse($argv0);
    $uids = array_filter(array($nojsx_ctx['uid']));
}

$compress = false;

classes::register_class_paths(array(
   "js_node"      => CLASS_PATH."/exts/js_loader/node.php",
   "js_module"    => CLASS_PATH."/exts/js_loader/module.php",
   "js_package"   => CLASS_PATH."/exts/js_loader/package.php",
   "js_packager"  => CLASS_PATH."/exts/js_loader/packager.php",
   "js_packer"    => CLASS_PATH."/exts/js_loader/packer.php",
   "exyks_js_packer" => CLASS_PATH."/dsp/js/packer.php",
));



$lang_key  = pick($nojsx_ctx['Yks-Language'], $_SERVER['HTTP_YKS_LANGUAGE']);

$lang_key = pick(preg_clean("a-z_-", $lang_key), 'en-us');

$yks_root = RSRCS_PATH."/js/Yks";
$mt_root  = RSRCS_PATH."/js/Mootools";
$trd_root = RSRCS_PATH."/js/3rd";



$packer   = new exyks_js_packer();
$packer->ctx("USER_LANG", $lang_key);

$packager = new js_packager();


$manifests_list = array_merge(
    files::find($mt_root,  '#\.xml$#'),
    files::find($yks_root, '#\.xml$#'),
    files::find($trd_root, '#\.xml$#')
);

foreach($manifests_list as $file_path) 
    $packager->manifest_register($file_path);

//print_r($package);//dont, this is too big for you



//packager is now ready 

$package_root = "yks";


$packer->register("mt.core",  "$mt_root/core");
$packer->register("mt.more",  "$mt_root/more");
$packer->register("yks.root", "$yks_root");
$packer->register("3rd",      "$trd_root");
$packer->register("yks",      "$yks_root/mts");

if($uids) {

    $packager->output_node($package_root);

    foreach($uids as $uid)
        $packer->feed( $packager->output_node($uid) );




//    $packer->feed( $packager->output_node($uid, $package_root) );
//
} else {
//debugbreak();

    $files_list = $packager->output_node($package_root);
//    print_r($files_list);
    $packer->feed( $files_list );

    if(yks::$get->config->is_debug())
        $packer->feed("path://yks.root/tmp/trash/trace.js");

    $headers = $packager->output_headers($package_root);
//    print_r($headers);

    $packer->feed_var("Doms.loaders", $headers);
    $packer->feed_script("window.addEvent('domready', Screen.initialize);");

}



//packer is now ready


 header(TYPE_JS);

 files::highlander();
 list($js_file, $hash) = $packer->build($compress);

if(!$uids)
  data::store("JS_CACHE_KEY", $hash);

 readfile($js_file);
die;


