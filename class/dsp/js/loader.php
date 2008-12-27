<?
include_once "$class_path/stds/files.php";
define("YUI_COMPRESSOR","yuicompressor-2.2.4");
define("JAVA_PATH", ($tmp=$config->apis->java['bin_path'])?$tmp:"java");

define("YUI_COMPRESSOR_PATH",RSRCS_DIR."/yui_compressor/yui_compressor.jar");
define("JS_CACHE_DIR",$js_cache_dir);

$js_prefixs=array();
$js_build_list=array();

function build_js_cache($build_list,$prefixs,$compress=null){
  $hash="";

        //generate hash based on mtime & filename
  foreach($build_list as $k=>$file){
	$build_list[$k] = $file = strtr($file,$prefixs);
	if(!is_file($file) ) die("!! $file is unavaible");
    $time=filemtime($file);
	$hash.="$file:$time;";
  } $hash=md5($hash);

  $cache_full=JS_CACHE_DIR."/{$hash}.uncompressed.js";
  $cache_packed=JS_CACHE_DIR."/{$hash}.packed.js";

  $cache_file=$compress?$cache_packed:$cache_full;
  if(is_file($cache_file)) return $cache_file;

  delete_dir(JS_CACHE_DIR,false);
  create_dir(JS_CACHE_DIR);

  $contents="";
  foreach($build_list as $file) $contents.=file_get_contents($file);
  file_put_contents($cache_full, $contents);

  $cmd = JAVA_PATH." -jar ".YUI_COMPRESSOR_PATH.
		" --charset UTF-8 -o $cache_packed  $cache_full 2>&1";
  if($compress) exec($cmd, $out, $err);
  if($err) die("$err : ".print_r($out,1));
  return $cache_file;
}


