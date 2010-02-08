<?php

class cli {

  const OS_UNIX = 1;
  const OS_WINDOWS = 2;
  private static $OS = null;

  const pad = 70;

  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;

    $win = stripos($_SERVER['OS'],'windows')!==false;
    $tty = isset($_SERVER['SSH_TTY']);
    self::$OS = $win && !$tty ? self::OS_WINDOWS : self::OS_UNIX;


      //transcoding UTF-8 to IBM codepage
    if(self::$OS == self::OS_WINDOWS)
      ob_start(array('cli', 'console_out'), 2);
  }

  static function trace($msg) {
    $args = func_get_args();
    if(count($args) > 1) echo vsprintf(array_shift($args), $args).LF;
    else echo $msg.LF;
  }

  static function console_out($str){
    return charset_map::Utf8StringDecode($str, charset_map::$_toUtfMap);
  }

  static function console_in($str){
    return charset_map::Utf8StringEncode($str, charset_map::$_toUtfMap);
  }

  public static function pad($title='', $pad = '─', $MODE = STR_PAD_BOTH, $mask = '%s', $pad_len = self::pad){
    $pad_len -= mb_strlen(sprintf($mask, $title));
    $left = ($MODE==STR_PAD_BOTH) ? floor($pad_len/2) : 0;
    return sprintf($mask, 
            str_repeat($pad, max($left,0)) . $title . str_repeat($pad, max($pad_len - $left,0)));
  }


  public static function box($title, $msg){
    $args = func_get_args(); $pad_len = self::pad;
    $options = count($args)%2==1?array_pop($args) : array();

    $dotrim = in_array('trim', $options);

    for($a=1;$a<count($args);$a+=2) {
      $msg= &$args[$a];
      if(!is_string($msg)) $msg = print_r($msg, 1);
      $msg = explode("\n", trim($msg));
      if($dotrim)
        foreach($msg as &$tmp_line)
            $tmp_line = preg_replace('#&[^;]*?$#m','...',mb_strimwidth($tmp_line,0,self::pad-2,'...'));

      $pad_len = max($pad_len, max(array_map('mb_strlen', $msg))+2); //2 chars enclosure
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

  public static function text_prompt($prompt=false, &$args = null){
    if($prompt) echo "$prompt : ";

    $data_str = "";
    do {
        $line =  fread(STDIN, 1024);

        if(preg_match("#[\x01-\x1F]\r?\n$#", $line, $out)) {
            $control = ord($out[0]);
            $line = substr($line, 0, -strlen($out[0]));
        } else $control = false;

        if(self::$OS == self::OS_WINDOWS) 
            $line = self::console_in($line);

        $data_str .= $line;
        $args = self::parse_args(trim($data_str), $complete);
    } while( ! ($complete || in_array($control, array(26))) );

    if($control == 26) $args = array();
    return trim($data_str);
  }

/**
* @param int control return the control key (if present) of the last input
*/
  public static function parse_args($str, &$complete = null) {
    $mask      = "#(\s+)|([^\s\"']+)|\"([^\"]*)\"|'([^']*)'#s";

    $args = array(); $need_value = true; $digest = "";
    preg_match_all($mask, $str, $out, PREG_SET_ORDER);

    foreach($out as $part_id => $step){
        list($sep, $value) = array($step[1]!='', pick($step[2], $step[3], $step[4]));
        $digest .= $step[0];

        if($digest != substr($str,0, strlen($digest)) )
            break;

            //check "value"/separator alternance
        if($sep) { $need_value = true; continue; }
        if(!$need_value) break; $need_value = false;

        $args[] = $value;
    }
    $complete = ($digest == $str);
    return $args;
  }


  function exec($cmd, $file_mode = false){
    //write in file so we avoid args parsing issues
    if($file_mode) {
      $temp_file = files::tmppath("bat");
      $cmds = array();
      $cmds[] = "@ $cmd"; //silent
      file_put_contents($temp_file, join(CRLF, $cmds));
      $cmd = sprintf('"%s"', $temp_file);
      return passthru($cmd);
    } else {
        $WshShell = new COM("WScript.Shell");
        return $WshShell->Run($cmd);
    }
  }
  
  public static function winpath($path){
    return self::cygpath($path, "-w");
  }

  public static function cygpath($path, $options = ''){
    $quot = '"%s"';
    if(self::$OS == self::OS_WINDOWS)
      return sprintf($quot, $path);
    return sprintf($quot, trim(`cygpath $options "$path"`));
  }
}
