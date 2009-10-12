<?

class cli {
  const OS_UNIX = 1;
  const OS_WINDOWS = 2;
  private static $OS = null;

  const pad = 70;

  public static function init(){

    $win = stripos($_SERVER['OS'],'windows')!==false;
    self::$OS = $win ? self::OS_WINDOWS : OS_LINUX;

      //transcoding UTF-8 to IBM codepage
    if(self::$OS & self::OS_WINDOWS)
      ob_start(array('cli', 'console_out'), 2);

  }


  static function console_out($str){
    return charset_map::Utf8StringDecode($str, charset_map::$_toUtfMap);
  }


  public static function pad($title='', $pad = '─', $MODE = STR_PAD_BOTH, $mask = '%s', $pad_len = self::pad){
    $pad_len -= mb_strlen(sprintf($mask, $title));
    $left = ($MODE==STR_PAD_BOTH) ? floor($pad_len/2) : 0;
    return sprintf($mask, 
            str_repeat($pad, max($left,0)) . $title . str_repeat($pad, max($pad_len - $left,0)));
  }


  public static function box($title, $msg){
    $args = func_get_args(); $pad_len = self::pad;

    for($a=1;$a<count($args);$a+=2) {
      $msg= &$args[$a];
      if(!is_string($msg)) $msg = print_r($msg, 1);
      $msg = explode("\n", trim($msg));
      $pad_len = max($pad_len, max(array_map('strlen', $msg))+2); //2 chars enclosure
    }

    for($a=0; $a<count($args); $a+=2) {
      echo self::pad(" {$args[$a]} ", "═", STR_PAD_BOTH, $a?"╠%s╣":"╔%s╗", $pad_len).LF;
      foreach($args[$a+1] as $line)
          echo self::pad($line, " ", STR_PAD_RIGHT, "║%s║", $pad_len).LF;
    }

    echo self::pad('', "═", STR_PAD_BOTH, "╚%s╝", $pad_len).LF;
  }


  public static function password_prompt(){
    if(self::$OS & self::OS_WINDOWS) {
        $pwObj = new Com('ScriptPW.Password');
        $password = $pwObj->getPassword();
    } else {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
    } return $password;
  }

  public static function text_prompt($prompt=false){
    if($prompt) echo "$prompt : ";
    return trim(fread(STDIN, 1024));
  }


  function exec($cmd){
    $WshShell = new COM("WScript.Shell");
    return $WshShell->Run($cmd);
  }
}