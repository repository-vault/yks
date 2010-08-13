<?

class packlib {
  const  tmp_file    = 'R:\@';
  const  class_mask = "#class\s+([^{\s]*?)\s*\{#";
  private $files_list; //file_path => file contents
  public $init_safe_class = array();
  function __construct(){

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

  function output($out_file) {

    $infos = pathinfo($out_file);
    $outz_file = "{$infos['dirname']}/{$infos['filename']}_z.dll";
    $outr_file = "{$infos['dirname']}/{$infos['filename']}_r.dll";

    $fh = fopen($out_file, "w");

    bcompiler_write_header($fh);

    if(false) { //file per file 
      foreach($this->files_list as $file_path=>$file_contents){
          $code = "<?php\r\n$file_contents";
          file_put_contents(self::tmp_file, $code);
          bcompiler_write_file($fh, self::tmp_file);
      }
    }
    if(true) { //one buffer only
      $code = "<?php\r\n";
      foreach($this->files_list as $file_path=>$file_contents)
          $code .= $file_contents.CRLF.CRLF;
      file_put_contents($outr_file, $code);
      file_put_contents(self::tmp_file, $code);
      bcompiler_write_file($fh, self::tmp_file);
    }

    bcompiler_write_footer($fh);

    fclose($fh);

    //write bz compressed version
    $contents = file_get_contents($out_file);

    $bz = bzopen($outz_file, "w"); bzwrite($bz, $contents); bzclose($bz);

  }
}