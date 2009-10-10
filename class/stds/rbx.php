<?
/** rbx result boxes && walker by 131 2009
*/




class rbx extends Exception {
  const pad = 70;

  static public $rbx=array();
  static public $output_mode=1; //1 = direct output,0 = JSX & YKS

  static $pos;
  static $max;
  static $flag=false;
  function __construct($zone,$msg,$jsx=0){ self::msg($zone,$this->message = $msg,$jsx); }

  static function msg($zone, $msg, $jsx=0){
    if(!is_string($msg))$msg=trim(strtr(print_r($msg,1),array("\r"=>'',"\n"=>'')));
    self::$rbx[$zone].=(self::$rbx[$zone]?' ':'').$msg;
    if($jsx!==0)jsx::$rbx=$jsx;
    if(self::$output_mode!=1) return;
    self::$rbx['log'].="$zone : $msg".LF;

    echo self::pad($msg, ' ', STR_PAD_RIGHT, "%s: $zone").LF;
  }

  static function box($title, $msg){
    $args = func_get_args(); $pad_len = self::pad;

    for($a=1;$a<count($args);$a+=2) {
      $msg= &$args[$a];
      if(!is_string($msg)) $msg = print_r($msg, 1);
      $msg = explode("\n", trim($msg));
      $pad_len = max($pad_len, max(array_map('strlen', $msg))+1);
    }

    for($a=0; $a<count($args); $a+=2) {
      echo self::pad(" {$args[$a]} ", "═", STR_PAD_BOTH, $a?"╠%s╣":"╔%s╗", $pad_len).LF;
      foreach($args[$a+1] as $line)
          echo self::pad($line, " ", STR_PAD_RIGHT, "║%s║", $pad_len).LF;
    }

    echo self::pad('', "═", STR_PAD_BOTH, "╚%s╝", $pad_len).LF;
  }

  private static function pad($title='', $pad = '─', $MODE = STR_PAD_BOTH, $mask = '%s', $pad_len = self::pad){
    $pad_len -= mb_strlen(sprintf($mask, $title));
    $left = ($MODE==STR_PAD_BOTH) ? floor($pad_len/2) : 0;
    return sprintf($mask, 
            str_repeat($pad, $left) . $title . str_repeat($pad, $pad_len - $left));
  }

  static function delay(){ $_SESSION['rbx']=rbx::$rbx;rbx::$rbx=array(); }
  static function error($msg,$severity=0,$jsx=0){
    return new self("error",is_numeric($msg)?"&err_$msg; (#$msg)":(is_string($msg)?$msg:print_r($msg,1)),$jsx);
  }
  static function warn($msg, $element=false){
    if($element) self::msg("warn",$element);
    return new self("error", $msg);
  }
  static function ok($msg,$jsx=0){ return new self("ok",$msg,$jsx); }

  static function title($title){ echo self::pad(" $title ").LF; }
  static function line(){ echo self::pad().LF.LF; }

  static function init($max,$flag=false){
    self::$max=$max;
    if(self::$flag=$flag) data::store(self::$flag,0,600);
    if(self::$output_mode) echo self::pad('', ' ', STR_PAD_RIGHT, '[%s]');
    return self::$pos=0;
  }
  static function walk($step){
    $current = round(  self::$max ? ($step/self::$max) : 1,3);
    $step = floor($current* (self::pad-2) );
    $old = self::$pos; self::$pos= $step;
    if($step == $old) return $current;
    if(self::$flag) return data::store(self::$flag, $current, 600);

    if(!self::$output_mode) return;
    echo "\r".self::pad(str_repeat("─",$step), ' ', STR_PAD_RIGHT, "[%s]"); flush();
    if($current == 1) echo LF;
  }
  function __toString(){ return $this->message; }
  static function end(){ if(self::$pos!=self::pad) self::walk(self::$max); }
}
