<?
/** 
** this is D:\bin\md5file.bat
@php -r "include 'yks/cli.php';win32_cli::dispatch();" -- %0 %*
**/



class win32_cli {
  private static  $argv;
  public static function dispatch(){
    self::$argv = $_SERVER['argv']; array_shift(self::$argv);
    $script  = array_shift(self::$argv);

    $cb = array(__CLASS__, $script);

    if(is_callable($cb)) 
      return call_user_func_array($cb, self::$argv);

    rbx::error("No defined callback");

  }
  private static function getopt($params, $argv){
    return cli::getopt($params, $argv);
  }

  public static function wget(){
    list($options, $args) = self::getopt(array(
          "O:"  => "output-document:",
          "q"   => "quiet",
    ), self::$argv);
    $url   = $args[0]; $infos = parse_url($url);
    $output = pick($options['O'], $options['output-document'], basename($infos['path']), "index.htm");

    if($output == "-")
      echo file_get_contents($url);
    else {
      copy($url, $output);
      rbx::ok("Wrote $url in $output");
    }
  }

  public static function md5file($file_path){ echo md5_file($file_path); }
  public static function md5($str){ echo md5($str); }
  public static function sha1($str){ echo sha1($str); }
  public static function sleep($delay){  sleep($delay); }

  public static function telnet($ip, $port){
    cli::$dict = compact('ip', 'port');
    cli::$dict ["ir://run"] = true;
    interactive_runner::start("telnet");
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