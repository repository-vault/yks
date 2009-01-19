<?
include_once "$class_path/stds/files.php";
define("YUI_COMPRESSOR","yuicompressor-2.2.4");
define("JAVA_PATH", ($tmp=$config->apis->java['bin_path'])?$tmp:"java");

define("YUI_COMPRESSOR_PATH",RSRCS_DIR."/yui_compressor/yui_compressor.jar");
define("JS_CACHE_DIR",$js_cache_dir);

$js_prefixs=array();
$js_build_list=array();


class Js {
  static $prefixs;

  static function realpath($file){
    return strtr($file, self::$prefixs).'.js';
  }


  static function dynload($uid, $js_build_list, $commons_dir){

    $build_list = array( $uid );
    $list = glob("$commons_dir/mts/Headers/*.js");

    foreach($list as $file){
        $contents = file_get_contents($file);
        unset($out);
        $blk = "[\s\r\n]*";
        $mask  = "#Doms.loaders\[\s*(['\"])(.*?)\\1\s*\]$blk=$blk(\{.*?\})#s";
        if(preg_match_all($mask, $contents, $out, PREG_SET_ORDER))
        foreach($out as $line) {
            list(, , $key, $body) = $line;
            if(!isset($js_build_list[$key]))continue;
            $body = json_decode($body, true);
            if(!$body) continue;
            $body['active'] = $js_build_list[$key];
            $js_build_list[$key] = $body;
        }
    }

    $load = $js_build_list[$uid];
    if(is_array($load['deps']))    //need true recursion stack here
        foreach($load['deps'] as $dep)
            array_unshift($build_list, $dep);


    $build_list = array_map(array('Js','realpath'), $build_list);

    return $build_list;
  }


  static function build($build_list, $compress){
    $hash="";
        //generate hash based on mtime & filename
    foreach($build_list as $file){
        if(!is_file($file) ) die("!! $file is unavaible");
        $time = filemtime($file);
        $hash.= "$file:$time;";
    } $hash = md5($hash);

    $cache_full   = JS_CACHE_DIR."/{$hash}.uncompressed.js";
    $cache_packed = JS_CACHE_DIR."/{$hash}.packed.js";
    $cache_file   = $compress ? $cache_packed : $cache_full;
    if(is_file($cache_file)) return $cache_file;

    //delete_dir(JS_CACHE_DIR,false);
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
}
Js::$prefixs = &$js_prefixs;