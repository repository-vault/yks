<?

class packlib {
  const  class_mask = "#class\s+([^{\s]*?)\s*\{#";
  private $files_list; //file_path => file contents
  public $init_safe_class = array();
  
  const MODE_RAW = 1;
  const MODE_BC  = 2;
  const MODE_BCZ = 4;
  const MODE_DEFAULT = 7;  

  function __construct(){
    $tmp_dir   = sys_get_temp_dir();
    $tmp_drive = preg_reduce("#([a-z]:)#i", $tmp_dir);
    $this->tmp_file = $tmp_drive.'\@';
  }

  function scan_path($dir, $file_mask = '.*\.(php|lib)$'){
    $files_list = files::find($dir, $file_mask);
    foreach($files_list as $file_path)
      $this->add_file($file_path);
  }

  //search for classes in $file_path
  function add_file($file_path){
    $contents   = file_get_contents($file_path);
      //parser here ?
    $class_name = strtolower(preg_reduce(self::class_mask, $contents));

    if(!$class_name) {
      rbx::error("Cannot find classes in $file_path");
      return;
    }

    rbx::ok("Joinging '$class_name' in $file_path");

      //remove php header
    $contents = preg_replace('#^<\?(php)?\s*#si',"",  $contents);
    $has_init = preg_match("#^\s*(public)?\s*static\s*function\s*init\s*\(#m", $contents);

    if(false && $has_init && !in_array($class_name, $this->init_safe_class)) {
        $new_name = "_$class_name";
        $aliases[$class_name] = $new_name;
        $contents = preg_replace(self::class_mask, "class $new_name {", $contents);
    }

    $this->stack_code($contents);

    if($aliases) {
      $contents = "classes::register_classes_alias(".var_export($aliases, true).");";
      $this->stack_code($contents);
    }

  }

  public function add_code($php_code){
    $this->stack_code($php_code);
  }

  private function stack_code($php_code){
    $this->files_list[] = "$php_code";
  }

  function output($out_file, $mode = self::MODE_DEFAULT) {

    $infos = pathinfo($out_file);
    $outz_file = "{$infos['dirname']}/{$infos['filename']}_z.dll";
    $outr_file = "{$infos['dirname']}/{$infos['filename']}_r.dll";

    $code = "<?php\r\n";
    foreach($this->files_list as $file_path=>$file_contents)
        $code .= $file_contents.CRLF.CRLF;
    file_put_contents($outr_file, $code);
    
    if($mode & self::MODE_DEFAULT)
      copy($outr_file, $out_file);
    
    if($mode & self::MODE_BC) {
      $fh = fopen($out_file, "w");
      bcompiler_write_header($fh);
      file_put_contents($this->tmp_file, $code);
      bcompiler_write_file($fh, $this->tmp_file);
      bcompiler_write_footer($fh);
      fclose($fh);
    }

    //write bz compressed version

  }
}