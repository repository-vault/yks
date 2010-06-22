<?

class exyks_js_packer extends js_packer {

  const ns = "js ns"; //namespace js \o/
  private $JS_CACHE_PATH;


  function __construct(){
    parent::__construct();
    $this->JS_CACHE_PATH = CACHE_PATH."/js";
  }

  public function register($prefix, $path) {
    return exyks_paths::register($prefix, $path, self::ns);
  }

  public function resolve($path) {
    //verify safe path here
    return exyks_paths::resolve($path, self::ns);
  }

  public function feed($files){
    if(!is_array($files)) $files = array($files);

    $files = array_combine($files, array_map(array($this, 'resolve'), $files)); //comment this ?

    parent::feed( $files );
  }


  public function build($compress, $etag = false){
    $hash         = $this->gen_hash();
    $cache_full   = "{$this->JS_CACHE_PATH}/{$hash}.uncompressed.js";
    $cache_packed = "{$this->JS_CACHE_PATH}/{$hash}.packed.js";
    $cache_file   = $compress ? $cache_packed : $cache_full;
    //if(is_file($cache_file)) return array($cache_file, $hash);

    $contents="";
    foreach($this->files_list as $file_key=>$file_path) {
        if(!is_file($file_path) ) die("!! $file_path is unavaible");
        $contents.=file_get_contents($file_path);
    } $contents .= $this->additional_script;

    //files::delete_dir(JS_CACHE_PATH,false);
    files::create_dir($this->JS_CACHE_PATH);

    file_put_contents($cache_full, $contents);
    $cmd = JAVA_PATH." -jar ".YUI_COMPRESSOR_PATH.
        " --charset UTF-8 -o $cache_packed  $cache_full 2>&1";
    if($compress) exec($cmd, $out, $err);
    if($err) die("$err : ".print_r($out,1));
    return array($cache_file, $hash);
  }

}