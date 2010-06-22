<?

class js_packer {

  private $JS_CACHE_PATH;
  protected $files_list;
  protected $additional_script;

  function __construct(){
    $this->JS_CACHE_PATH = sys_get_temp_dir();
    $this->files_list = array();
    $this->additional_script = "";
  }

  public function feed($files){
    if(!is_array($files)) $files = array($files);
    $this->files_list = array_merge($this->files_list, $files);
  }

  public function feed_var($key, $value){
    $value = json_encode_lite($value);
    $this->feed_script("$key=$value;");
  }

  public function feed_script($script){
    $this->additional_script .= CRLF.$script;
  }

  protected function gen_hash(){
    $hash="";
        //generate hash based on mtime & filename
    foreach($this->files_list as $file_key=>$file_path){
        $time = filemtime($file_path);
        $hash.= "$file_key:$time;";
    } $hash.= $this->additional_script;
    return md5($hash);
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

    file_put_contents($cache_full, $contents);
    return array($cache_file, $hash);
  }
}