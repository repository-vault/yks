<?
/** 
<? // this is D:\bin\md5file.phpx
include 'yks/cli.php';
win32_cli::dispatch();
**/

class win32_cli {

  public static function dispatch(){
    
    $script = strip_end(basename(array_shift($_SERVER['argv'])), ".phpx");
    $cb = array(__CLASS__, $script);

    if(is_callable($cb)) 
      return call_user_func_array($cb, $_SERVER['argv']);

    rbx::error("No defined callback");

  }
  private static function getopt($params){
    return cli::getopt($params, $_SERVER['argv']);
  }

  public static function wget($url){
    list($options, $args) = self::getopt(array(
          "O:"  => "output-document:",
          "q"   => "quiet",
    ));
    $url   = $args[0]; $infos = parse_url($url);
    $output = pick($options['O'], $options['output-document'], basename($infos['path']), "index.htm");

    if($output == "-")
      echo file_get_contents($url);
    else {
      copy($url, $output);
      rbx::ok("Wrote in $output");
    }
  }

  public static function md5file($file_path){
    echo md5_file($file_path);
  }

  public static function which($file_path){
    echo cli::which($file_path);
  }

  public static function touch($file_path){
    touch($file_path, time());
  }

  public static function crypt($string, $key){
    echo crypt::encrypt($string, $key, true);
  }

  public static function decrypt($string, $key){
    echo crypt::decrypt($string, $key, true);
  }




}