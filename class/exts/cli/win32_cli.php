<?
/** 
** this is D:\bin\md5file.bat
@php -r "include 'yks/cli.php';win32_cli::dispatch();" -- %0 %*
**/



class win32_cli {
  private static  $argv;
  public static function dispatch(){
    self::$argv = cli::$args;
    $cmd  = array_shift(self::$argv);

    $cb = array(__CLASS__, $cmd);

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

  public static function touch($file_path, $from_file = null){
    touch($file_path, is_file($from_file) ? filemtime($from_file) : time() );
  }

  public static function crypt($str, $salt = null){
    $algo   = pick(cli::$dict['algo'], "blowfish");
    $rounds = pick(cli::$dict['rounds'], 7);

    if($salt);
    elseif($algo == "blowfish") {
      $rounds = min(31,max(4, $rounds));
      $salt = sprintf("$2a$%02d$%s$", $rounds, self::dummysalt(22));
    }
    echo crypt($str, $salt);
  }

  public static function encrypt($string, $key){
    echo crypt::encrypt($string, $key, true);
  }

  public static function decrypt($string, $key){
    echo crypt::decrypt($string, $key, true);
  }


  private function dummysalt($length){
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
    $size = strlen( $chars );
    for( $i = 0; $i < $length; $i++ )
        $str .= $chars[ mt_rand( 0, $size - 1 ) ];
    return $str;
  }



}