<?php

class js_builder {
  static private $js_namespaces =array();
  const js_fpi = "-//YKS//JS";
  static $build_depths = array();
  static $files_list = array();
  static $build_order = array();

  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;

$js_config = yks::$get->config->js;


    //parse js_namespaces from configuration file
$js_namespaces = array();
foreach($js_config->js_namespaces->ns as $ns){
    $path = preg_replace(CONST_MASK, CONST_REPL, $ns['path']);
    $fpi = (string) $ns['fpi'];
    $js_namespaces[$fpi] = $path;
}
$js_headers_path = paths_merge(ROOT_PATH, $js_config["headers_path"], "config/js");

if(!is_dir($js_headers_path))
    throw rbx::error("Invalid js headers path");

include "$class_path/dsp/js/builder.php";


    self::$js_namespaces = $js_namespaces;
    xml::register_fpi(self::js_fpi, RSRCS_PATH."/dtds/js.dtd", "js");

  }

  static function parse_headers($files_list){
    self::$files_list = array();
    foreach($files_list as $file)
        self::parse_header($file);


    $build_list = self::build_dependencies(self::$files_list);

        //Step 2 : retrieving maximum depths and roots for each script
    self::$build_depths = array(); $roots = array();
    foreach($build_list as $name=>$infos){
            //un fichier pourrait avoir plusieurs racines - todo ?
        $root = reset(self::scan_depth($name, $infos));
        $roots[$name] = $root;
    }
        //Group depths by root name
    $depths = array();
    foreach(self::$build_depths as $name=>$depth)
        $depths[$roots[$name]][$name] = $depth;

        //Step 3 - re-order roots
    $roots = array_values(array_unique($roots)); $tmp =array();
    foreach(self::$build_order as $before=>$after){
        $tmp[] = $before;
        $tmp[] = $after;
    } $tmp = array_unique(array_merge($tmp, $roots));
    $depths = array_sort($depths, $tmp);


        //Step 3 : Reversing depth && re-ordering base list
    $files_order = array();
    foreach($depths as $root=>$depths){
        arsort($depths);
        $files_order = array_merge($files_order, array_keys($depths));
    }

    $build_list = array_sort($build_list, $files_order );

print_r($build_list);
    return $build_list;
  }

  private static function parse_header($file){
    $files_list = array();
    try {
        $res = simplexml_import_dom(xml::load_file($file, LIBXML_YKS, self::js_fpi));
    } catch(Exception $e){ rbx::error("Error parsing ".basename($file).", skipping"); }

    foreach($res->module as $module){


        $file_infos = array("mandatory"=>(int)bool((string)$module['mandatory']));
        $module_key = self::resolve_file($module['key']);
        foreach($module->dep as $dep) {
            $file_infos[(string) $dep['mode']][] = self::resolve_file($dep['key']);
        }
        self::$files_list[$module_key] = $file_infos;
    }
  }

  private static function build_dependencies($files_list){

        //Step 1 : building recursive tree
    $build_list = array(); $after_list = array();
    foreach($files_list as $file_name=>$file_infos){
        $dependencies = $file_infos['depend'];
        $after = $file_infos['after'];
        if($after)  $after_list[reset($after)] = $file_name;
        unset($file_infos['depend']);
        $build_list[$file_name]=array_merge(
            $build_list[$file_name]?$build_list[$file_name]:array(),
            $file_infos
        );

        foreach($dependencies as $dependency)
             $build_list[$file_name]['depend'][$dependency]= &$build_list[$dependency];
    }
    self::$build_order = $after_list;
    return $build_list;
  }

  static function scan_depth($name, $infos, $depth=0, $path=array()){
    if(in_array($name, $path)) return array($name=>$name);
    self::$build_depths[$name] = max((int)self::$build_depths[$name],$depth);
    if(!$infos['depend']) return array($name=>$name);

    $path = array_merge($path, array($name));$was = array();
    foreach($infos['depend'] as $name=>$infos)
        $was = array_merge($was, self::scan_depth($name, $infos, $depth+1, $path));
    return $was;
  }

  private static function resolve_file($file_uri){
    preg_match('#^(mt)://([^:/]+)(?::?([0-9.]+))?(.*?)$#', $file_uri, $file_infos);
    list(, $scheme, $host, $version, $path) = $file_infos;
    
    if($scheme != 'mt') throw rbx::error("Invalid scheme in $file_uri");
    if(!isset(self::$js_namespaces[$host])) throw rbx::error("Invalid namespace in $file_uri");

    $file_path = self::$js_namespaces[$host].$path.".js";
    if(!file_exists($file_path)) throw rbx::error("Invalid file $file_path");
    return (string) $file_uri;
  }


}