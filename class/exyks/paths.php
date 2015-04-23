<?php


class exyks_paths {

  static private $paths  = array();
  static private $consts_cache = array();
  const default_ns = 'default';

  public static function init(){

    if(!classes::init_need(__CLASS__)) return;

    self::register("yks", YKS_PATH);

    self::register("here", ROOT_PATH);
    self::register("skin",    RSRCS_PATH."/themes/Yks", self::default_ns, true);
    self::register("skin.js", RSRCS_PATH."/js", self::default_ns, true);

    self::register("bootstrap", RSRCS_PATH."/themes/bootstrap", self::default_ns, true);
    self::register("skin.bootstrap", RSRCS_PATH."/themes/yks-bootstrap", self::default_ns, true);



    stream_wrapper_register("path", "ExyksPathsResolver");

    self::$consts_cache = array_merge(
        retrieve_constants(),
        array_mask($_ENV, "%s", "{%s}")
    );

    self::$consts_cache = retrieve_constants();

    if(is_null(yks::$get) || is_null(yks::$get->config))
      return;

    self::register("public",  PUBLIC_PATH, self::default_ns, true);
    self::register("cache",   realpath(CACHE_PATH), self::default_ns, true);
    self::register("config",  CONFIG_PATH); //NOT Public
    self::register("tmp",     TMP_PATH); //NOT Public


    if(yks::$get->config)
      foreach(yks::$get->config->paths->iterate("ns") as $ns)
        self::register($ns['name'], self::resolve($ns['path']), self::default_ns, $ns['public']=='public' );

  }

  public static function expose_public_paths(){
    return array_column( array_restrict(self::$paths, array(
        'public'=> true,
        'ns' => self::default_ns
    )), "dest", "key");
  }

  public static function register($key, $dest, $ns = self::default_ns, $public = false){

    //"ns/key" index prevents double declaration (could have been [] as key is irrevelant
    self::$paths["$ns/$key"] = compact('key', 'dest', 'ns', 'public');
  }


  public static function expose($path){
    return "/?/Yks/Scripts/Contents|$path";

  }

  public static function merge($path0, $path1){
    if(starts_with($path1, "path://")) return $path1;

    $info = parse_url($path0);

    $path0 = '/'.strip_start($path0, "path://");
    $path = files::paths_merge($info['path'], $path1);
    return "path://{$info['host']}/".ltrim($path, '/');
  }

   public static function resolve_url($path) {
    if(!starts_with($path, "path://public/"))
        return SITE_URL;
     return SITE_URL.'/'.strip_start($path, "path://public/");
   }

  public static function resolve_public($path, $ns = self::default_ns){

    $path_infos = parse_url($path);
    $domain = self::$paths["$ns/{$path_infos['host']}"];

    if(!$domain['public'])
        throw new Exception("Unaccessible path $path");

    $full = realpath(self::resolve($path));
    if(!starts_with($full, $domain['dest']))
        throw new Exception("Cannot traverse in public path");

    return $full; 
  }

  public static function resolve($path, $ns = false){

    $path  = str_set($path, self::$consts_cache);

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
    $mask = '#^path://('.join('|',array_keys($replaces)).')(?:/(.*?)|$)#iem'; //cooool

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





class ExyksPathsResolver { //implements streamWrapper
  private $file_path;
  private $fp;


    //r, r+, w, w+, a, a+, x, x+
  private static $write_modes = array('r+', 'w', 'w+', 'a', 'a+', 'x+');

  function stream_open($path, $mode, $options, &$opened_path) {
    $write = in_array(trim($mode, 'bt'), self::$write_modes);
    $this->file_path = exyks_paths::resolve($path);

    if(!$write && !is_file($this->file_path)) {
        trigger_error ("Invalid file path resolution {$this->file_path}", E_USER_WARNING);
        return false;
    }
    $this->fp        = fopen($this->file_path, $mode);
    $this->position = 0;
    return true;
  }

  static function url_stat($path, $flags) {
    //if($flags & STREAM_URL_STAT_QUIET)
    $path = exyks_paths::resolve($path);
    if(file_exists($path)) return stat($path);
    return false;
  }

  static function mkdir($path)        {
    $path = exyks_paths::resolve($path);
    files::create_dir($path);
    return file_exists($path);
  }


  function unlink($path)        {
    $path = exyks_paths::resolve($path);
    unlink($path);
  }

  function stream_read($count) {  return fread($this->fp, $count); }
  function stream_write($data) {  return fwrite($this->fp, $data); }
  function stream_stat()       {  return stat($this->file_path); }
  function stream_tell()       {  return ftell($this->fp); }
  function stream_eof()        {  return feof($this->fp); }
  function stream_seek($offset, $whence) { return fseek($this->fp, $offset, $whence); }
}



