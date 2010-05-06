<?
ob_start("ob_gzhandler");

$uids = array_filter(explode("," , $argv0));
$compress = false;

classes::register_class_paths(array(
   "js_node"      => CLASS_PATH."/dsp/js/node.php",
   "js_module"    => CLASS_PATH."/dsp/js/module.php",
   "js_package"   => CLASS_PATH."/dsp/js/package.php",
   "js_packager"  => CLASS_PATH."/dsp/js/packager.php",
   "js_packer"    => CLASS_PATH."/dsp/js/packer.php",
));

$yks_root = RSRCS_PATH."/js/Yks";
$mt_root  = RSRCS_PATH."/js/Mootools";



$packager = new js_packager();


$mt_files  = glob("$mt_root/*.xml");
$yks_files = glob("$yks_root/*.xml");


foreach($mt_files as $file_path)
    $packager->manifest_register($file_path);
foreach($yks_files as $file_path)
    $packager->manifest_register($file_path);

//packager is now ready 

$package_root = "yks";


$packer  = new js_packer();
$packer->register("mt.core", "$mt_root/core");
$packer->register("mt.more", "$mt_root/more");
$packer->register("yks.root", $yks_root);
$packer->register("yks.libs", "$yks_root/libs");
$packer->register("yks", "$yks_root/mts");
$packer->register("patch", "$yks_root/Patchs");


if($uids) {

    $packager->output_node($package_root);

    foreach($uids as $uid)
        $packer->feed( $packager->output_node($uid) );




//    $packer->feed( $packager->output_node($uid, $package_root) );
//
} else {
    $packer->feed( $packager->output_node($package_root) );

    if(DEBUG)
        $packer->feed("path://yks.root/tmp/trash/trace.js");

    $headers = $packager->output_headers($package_root);

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


