<?

class yphar {
  private static $in_phar;
  public static function init(){
    self::$in_phar = defined('PHAR_CURRENT');
  }

  public static function phar_include($path){
    if(self::$in_phar)
      include PHAR_CURRENT."/".$path;
    else include $path;
  }

  public static function phar_exec($cmd, &$out){
    if(self::$in_phar) {
      list($path, $args) = explode(' ', $cmd, 2);
      $tmp = tempnam(sys_get_temp_dir(), "tmp");
      $phar_path = PHAR_CURRENT."/".$path;
      copy($phar_path, $tmp);
      exec("$tmp $args", $out);
      unlink($tmp);
    } else {
      exec($cmd, $out);
    }

  }

  public static function phar_forge($phar_name, $files, $stub_code, $use_yks = false) {
    $out_phar = "$phar_name.phar";
    if(is_file($out_phar))
      unlink($out_phar);

    $phar = new Phar($out_phar, 0, $phar_name);

    $stub = "<?\n";
    $stub .= "define('PHAR_CURRENT', 'phar://$phar_name');".CRLF;
    $stub .= "include 'phar://$phar_name/boot';".CRLF;
    $stub .= "__HALT_COMPILER();";

    $init_code = "<?\n";
    if($use_yks) $init_code.= "include 'yks/cli.php';";
    else $init_code.= classes::get_class_code("yphar").CRLF."yphar::init();";
    $init_code .= "?>".$stub_code;

    $phar->setStub($stub);
    $phar->addFromString('boot', $init_code);
    foreach($files as $file)
      $phar->addFile($file, basename($file));

    //Compress 
    $phar->compressFiles(Phar::BZ2);

  }


}