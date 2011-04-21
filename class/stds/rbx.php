<?php

/** rbx result boxes && walker by 131 2009
*/


class rbx extends Exception {

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

    echo cli::pad($msg, ' ', STR_PAD_RIGHT, "%s: $zone").LF;
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

  static function title($title){ echo cli::pad(" $title ").LF; }
  static function line(){ echo cli::pad().LF; }

  static function init($max,$flag=false){
    self::$max=$max;
    if(self::$flag=$flag) data::store(self::$flag,0,600);
    if(self::$output_mode) echo cli::pad('', ' ', STR_PAD_RIGHT, '[%s]');
    return self::$pos=0;
  }
  static function box($title, $msg){
    $args = func_get_args();
    call_user_func_array(array('cli', 'box'), $args);
  }

  static function walk($step){
    $current = round(  self::$max ? ($step/self::$max) : 1,3);
    $step = floor($current * 100); //max 100 steps
    $old = self::$pos; self::$pos= $step;
    if($step == $old) return $current;
    if(self::$flag) return data::store(self::$flag, $current, 600);

    if(!self::$output_mode) return;
    echo "\r".cli::pad(str_repeat("─", floor($current*(cli::pad-2))), ' ', STR_PAD_RIGHT, "[%s]"); flush();
    if($current == 1) echo LF;
  }
  function __toString(){ return $this->message; }
  static function end(){ if(self::$pos!=100) self::walk(100); }
}
