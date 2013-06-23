<?php

class cli {

  const OS_UNIX = 1;
  const OS_WINDOWS = 2;
  public static $OS = null;

  const pad   = 70;
  public static $cols = self::pad;

  public static $args = array(); //unparsed arguments
  public static $dict = array(); //parsed arguments
  private static $UNATTENDED_MODE = false;

  static function init(){
    if(class_exists('classes') && !classes::init_need(__CLASS__)) return;

    $win = stripos($_SERVER['OS'],'windows')!==false || isset($_SERVER['WINDIR']);
    $tty = isset($_SERVER['TERM']);
    self::$OS = ( $win && !$tty ) ? self::OS_WINDOWS : self::OS_UNIX;

    self::$paths = self::get_path();
      //transcoding UTF-8 to IBM codepage
    if(self::$OS == self::OS_WINDOWS)
      ob_start(array('cli', 'console_out'), 2);

    self::load_args($_SERVER['argv']);
    self::$UNATTENDED_MODE = (bool) self::$dict['cli://unattended'];

  }
  public static function load_args($args){
    self::$dict = self::$args = array();

    foreach($args as $raw ) {
        if (starts_with($raw, "-")) {
            if(!preg_match("#^--?([a-z_0-9/:-]+)(?:=(.*))?#i", $raw, $out))
                continue;
            self::$dict[$out[1]] = $out[2] == "" ? true : $out[2];
        } else self::$args[] = $raw;
    }

  }

  public static function cli_mode(){
    if(!defined('STDIN'))
      define('STDIN', fopen('php://stdin','r'));
    set_time_limit(0);
  }

  private static $paths = false;
  static function extend_path($paths, $putenv = true){
    $new_paths      = is_array($paths)?$paths:func_get_args();
    
    $paths = array_merge(self::get_path(), $new_paths);
    $paths = array_filter(array_unique($paths));

    $_ENV['PATH'] = $_SERVER['PATH'] = join(PATH_SEPARATOR, $paths);
    if(self::$OS == self::OS_WINDOWS && $putenv)
        putenv("PATH=".$_ENV['PATH']);
    return self::$paths = self::get_path();
  }
  
  static function get_path(){
    $tmp            = array_key_map('strtoupper', $_SERVER);
    self::$paths    = array_filter(explode(PATH_SEPARATOR, $tmp['PATH']));
    return self::$paths;
  }
  
  static function which($bin_name, $force_use_path = false){
    if(self::$OS == self::OS_UNIX && !$force_use_path )
      return trim(`which $bin_name`);

    $exts = array_map('strtolower', array_filter(explode(';', $_ENV['PATHEXT'])));

      //all search names
    $search = array_mask($exts, "$bin_name%s");
    array_unshift($search, $bin_name);

    foreach(self::$paths as $path) {
      foreach($search as $bin_full_name) {
      $full_path = $path.DIRECTORY_SEPARATOR.$bin_full_name;
      if(file_exists($full_path) && is_file($full_path))
        return $full_path;
    }}

    return $bin_name;
  }
  
  static function trace($msg) {
    $args = func_get_args();
    if(count($args) > 1) echo vsprintf(array_shift($args), $args).LF;
    else echo $msg.LF;
  }

  static function console_out($str){
    return txt::utf8_to_cp950($str);
  }

  static function console_in($str){
    if(self::$OS != self::OS_WINDOWS) return $str;
    return txt::cp950_to_utf8($str);
  }

  public static function pad($title='', $pad = '─', $MODE = STR_PAD_BOTH, $mask = '%s', $pad_len = null){
    if(is_null($pad_len)) $pad_len = self::$cols;
    $pad_len -= mb_strlen(sprintf($mask, $title));
    $left = ($MODE==STR_PAD_BOTH) ? floor($pad_len/2) : 0;
    return sprintf($mask, 
            str_repeat($pad, max($left,0)) . $title . str_repeat($pad, max($pad_len - $left,0)));
  }

  public static function pause($prompt = ''){
    if(self::$UNATTENDED_MODE) return;
    echo "[PAUSE $prompt Press Any key]";
    self::text_prompt();
  }


  //headers is ['col0', 'col1'] OR ['col0'= > 'colname', .'col1'= > 'colname'..]
  public static function table($headers, $data){
    if(is_numeric(key($headers)))
      $headers = array_combine($headers, $headers);
    $cols = array_keys($headers);
    $table = array_values($data); array_unshift($table, $headers);

    $w = count($cols) - 1; $h = count($table) ;

    $cols_len = array_combine($headers, array_map('strlen', $headers));
    foreach($data as $line)
     $cols_len = array_merge_recursive($cols_len, array_map('strlen', $line));
    $cols_len = array_map('floor',  array_map('array_median', $cols_len));

    $map = array(
      'lu' => '╔', 'mu' => '╦', 'ru' => '╗', 
      'lm' => '╠', 'mm' => '╬', 'rm' => '╣', 
      'ld' => '╚', 'md' => '╩', 'rd' => '╝', 
      'y'  => '║', 'x' => '═',
    );
    $map = array(
      'lu' => '┌', 'mu' => '┬', 'ru' => '┐', 
      'lm' => '├', 'mm' => '┼', 'rm' => '┤', 
      'ld' => '└', 'md' => '┴', 'rd' => '┘', 
      'y'  => '│', 'x' => '─',
    );

    
    $out = "";
    $line = array();
    foreach($cols_len as $col=>$len)
      $line[] = str_repeat($map['x'], $len);

    foreach($table as $y=>$data) {
      $dy = ($y == 0 ? "u" : ($y != $h ? "m" : "d"));

      $row = array();
      foreach($cols_len as $col=>$len) 
        $row[]  = self::str_pad($data[$col], $len, $y == 0 ? STR_PAD_BOTH : STR_PAD_RIGHT, $y == 0 ? $map['x'] : " ");

      $out .= self::table_line($y == 0 ? $row : $line, $map["l{$dy}"], $map["m{$dy}"], $map["r{$dy}"]).CRLF;
      if($y) $out .= self::table_line($row, $map["y"], $map["y"], $map["y"]).CRLF;
    }
    $out .= self::table_line($line, $map["ld"], $map["md"], $map["rd"]).CRLF;
    echo $out;
  }

  function table_line($line, $ml, $mm, $mr) { return $ml . join($mm, $line) . $mr; }


  function str_pad($str, $size, $mode, $pad = ' '){
    if(mb_strlen($str)>$size) $str = txt::truncate($str, $size);
    return self::pad($str, $pad ,  $mode, "%s", $size);
  }


  public static function box($title, $msg){
    $args = func_get_args(); $pad_len = self::$cols;
    $options = count($args)%2==1?array_pop($args) : array();

    $dotrim = in_array('trim', $options);
    $dotrim = true;

    for($a=1;$a<count($args);$a+=2) {
      $msg= &$args[$a];
      if(!is_string($msg)) $msg = print_r($msg, 1);
      $msg = preg_split("#\r?\n#", trim($msg));
      $msg = str_replace("	", "    ", $msg);

      if($dotrim)
        foreach($msg as &$tmp_line)
            $tmp_line = preg_replace('#&[^;]*?$#m','…',mb_strimwidth($tmp_line,0,self::$cols - 2,'…'));

      $pad_len = max($pad_len, max(array_map('mb_strlen', $msg))+2); //2 chars enclosure
    }

    for($a=0; $a<count($args); $a+=2) {
      echo self::pad(" {$args[$a]} ", "═", STR_PAD_BOTH, $a?"╠%s╣":"╔%s╗", $pad_len).LF;
      foreach($args[$a+1] as $line)
          echo self::pad($line, " ", STR_PAD_RIGHT, "║%s║", $pad_len).LF;
    }

    echo self::pad('', "═", STR_PAD_BOTH, "╚%s╝", $pad_len).LF;
  }


  public static function password_prompt($prompt = ""){
    if($prompt) echo "$prompt : ";
    if(self::$OS & self::OS_WINDOWS) {
        $pwObj = new Com('ScriptPW.Password');
        $password = $pwObj->getPassword();
    } else {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
    } echo CRLF;
    return $password;
  }

  public static function bool_prompt($prompt="", $default = null){
    return bool(self::text_prompt("$prompt (Y/n)", $default ));
  }

  public static function text_prompt($prompt=false, $default = null, &$args = null){
    if(starts_with($default, "argv://")) {
        list($key, $default_value)   = explode("=", strip_start($default, "argv://"),2);
        $default = pick(self::$dict[$key], $default_value);
        if($default && self::$UNATTENDED_MODE) //unattended
          return $default;
    }

    if($default) $prompt .= " [{$default}]";
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
    $out = trim($data_str);

    if($out == "" && !is_null($default))
        return $default;
    return $out;
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

  public static function getopt($params, $argv){
    $shorts = array_keys($params);
    $longs = array_values($params);

    $options  = array();
    $unparsed = array();

    while(list(,$chunk) = each($argv)) {

      if($chunk[0] != '-') {
        $unparsed[] = $chunk;
        continue;
      }

      if($chunk[1] == '-') {
        $name = substr($chunk, 2);
        if(in_array($name, $longs ))
          $options[$name][] = false;
        elseif(in_array("$name:", $longs ))
          $options[$name][] = reset(each($argv));
        continue;
      }

      $chunk = substr($chunk, 1);
      do {
        list($name, $chunk) = array($chunk[0], substr($chunk,1));
        if(in_array($name, $shorts))
          $options[$name][] = false;
        elseif(in_array("$name:", $shorts))
          list($options[$name][], $chunk) =  array($chunk ? $chunk : reset(each($argv)), null);
      } while($chunk);

    }

    foreach($options as &$option) //splat
      if(count($option) == 1) $option = $option[0];

    return array($options, $unparsed);
  }

  function exec($cmd, $file_mode = false){
    //write in file so we avoid args parsing issues
    if($file_mode) {
      $temp_file = files::tmppath("bat");
      $cmds = array();
      $cmds[] = "@ $cmd"; //silent
      file_put_contents($temp_file, join(CRLF, $cmds));
      $cmd = sprintf('"%s"', $temp_file);
      passthru($cmd);
      unlink($temp_file);
    } else {
        $WshShell = new COM("WScript.Shell");
        return $WshShell->Run($cmd);
    }
  }
  
  function exec_distant($cmd_mask, $cmds, $file_tick = false){
    cli::box("Commandes", $cmds);

    $survey = (bool)$file_tick;
  
    if(!$survey)
        $file_tick = files::tmppath("chk");

    $wd = dirname($file_tick);
    files::create_dir($wd);

    $dist_file = files::tmppath("bat");
    $log_file  = files::tmppath("log"); touch($log_file);
    $cmds = mask_join(CRLF,  $cmds, "%s >> \"$log_file\"");
    $cmds .= CRLF.'echo "ok">'.$file_tick.CRLF;
    rbx::ok("Loggin in $log_file");
    
    file_put_contents($dist_file, $cmds);

    if(file_exists($file_tick))
        unlink($file_tick);
    
    $cmd = sprintf($cmd_mask, $dist_file);
    rbx::ok($cmd);
    cli::exec($cmd);
    $tail = new cli_tail($log_file);
        //waiting for smartassembly
    do {
      sleep(1);
      $msg = date('[H:i:s]')." Watching folder";

      if(file_exists($file_tick))
          break;
      $line = $tail->pick_line();
      if($line) echo $line;
    } while(true);
    echo CRLF;
    unlink($dist_file ); //drop remote file
    $tail->close();
    unlink($log_file ); //drop remote file
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
