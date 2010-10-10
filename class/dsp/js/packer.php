<?

class exyks_js_packer extends js_packer {

  const ns = "js ns"; //namespace js \o/
  protected $cache_path;


  function __construct(){
    parent::__construct();
    $this->cache_path = "path://cache/js";
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
    $cache_full   = "{$this->cache_path}/{$hash}.uncompressed.js";
    $cache_packed = "{$this->cache_path}/{$hash}.packed.js";
    $cache_file   = $compress ? $cache_packed : $cache_full;
    //if(is_file($cache_file)) return array($cache_file, $hash);

    $contents="";
    foreach($this->files_list as $file_key=>$file_path) {
        $file_path = strtr($file_path, $this->ctx_elements);
        if(!is_file($file_path) ) die("!! $file_path is unavaible");
        $contents.=file_get_contents($file_path);
    } $contents .= $this->additional_script;

    //files::delete_dir(cache_path,false);
    files::create_dir($this->cache_path);

    file_put_contents($cache_full, $contents);
    $cmd = JAVA_PATH." -jar ".YUI_COMPRESSOR_PATH.
        " --charset UTF-8 -o $cache_packed  $cache_full 2>&1";
    if($compress) exec($cmd, $out, $err);
    if($err) die("$err : ".print_r($out,1));
    return array($cache_file, $hash);
  }

}