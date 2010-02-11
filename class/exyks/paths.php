<?php


class exyks_paths {

  static private $paths  = array();
  static private $consts_cache = array();
  const default_ns = 'default';

  public static function init(){

    if(!classes::init_need(__CLASS__)) return;

    self::register("yks", YKS_PATH);
    self::register("here", ROOT_PATH);

    foreach(yks::$get->config->paths->iterate("ns") as $ns)
        self::register($ns['name'], self::resolve($ns['path']));

    self::$consts_cache = retrieve_constants();
  }

  public static function register($key, $dest, $ns = self::default_ns){
    //"ns/key" index prevents double declaration (could have been [] as key is irrevelant
    self::$paths["$ns/$key"] = compact('key', 'dest', 'ns');
  }


  public static function resolve($path, $ns = false){

    $path  = strtr($path, self::$consts_cache);
    
        //namespace list resolution order
    if(!$ns) $ns_list = array_values(array_extract(self::$paths, "ns", true));
    elseif(!is_array($ns)) $ns_list = array($ns);
    else $ns_list = $ns;
    $ns_list[] = self::default_ns;

    $replaces = array();
    foreach($ns_list as $ns) {
      foreach(self::$paths as $path_infos){
        if($path_infos['ns'] != $ns) continue;
        if(isset($replaces[$path_infos['key']])) continue;
        $replaces[$path_infos['key']] = $path_infos['dest'];
    }}

        //resolve
    $mask = '#^path://('.join('|',array_keys($replaces)).')(.*?)(/|$)#ie'; //cooool

    $repl = '$replaces["$1"]."$2"';

    if(starts_with($path, "path://")) {
      if(preg_match($mask, $path, $out)) {
        $path = preg_replace($mask, "$repl.'/'", $path);
        return $path;
      }
      throw new Exception("Unresolved path : '$path'");
    }

    $str = files::paths_merge(ROOT_PATH."/", files::rp($path)); //ROOT_PATH is a directory
    return $str;
  }


}
