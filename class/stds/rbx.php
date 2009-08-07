<?
/** rbx result boxes && walker by 131 2009
*/


define('RBX_PAD',70);

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
    self::$rbx['log'].="$zone : $msg\n";
    $pad_len = RBX_PAD-2-strlen($zone);
    echo $msg.str_repeat(' ',max(0,$pad_len-mb_strlen($msg) )).": $zone\n";
  }

  static function box($title, $msg){
    if(!is_string($msg)) $msg = print_r($msg, 1);
    $lines = explode("\n", trim($msg));
    $pad_len = max(array_map('strlen', $lines))+1;

    echo self::pad(" $title ", $pad_len, "═", STR_PAD_BOTH, "╔%s╗\n");
    foreach($lines as $line)
        echo self::pad($line, $pad_len, " ", STR_PAD_RIGHT, "║%s║\n");
    echo self::pad('', $pad_len, "═", STR_PAD_BOTH, "╚%s╝\n");
  }

  static function pad($title, $pad_len = RBX_PAD, $pad = '-', $MODE=STR_PAD_BOTH, $mask='%s'){
    $title_len = mb_strlen($title);
    $left = $MODE==STR_PAD_BOTH?floor(($pad_len - $title_len)/2):"";
    return sprintf($mask, 
            str_repeat($pad, $left) . $title . str_repeat($pad, $pad_len - $title_len - $left));

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

  static function title($title){
    $title=" $title ";$len=strlen($title);$left=floor((RBX_PAD-$len)/2);
    $pad=str_repeat("-",$left).$title.str_repeat("-",RBX_PAD-$len-$left);
    echo $pad."\n";
  }
  static function line(){ echo str_repeat("-",RBX_PAD)."\n\n"; }

  static function init($max,$flag=false){
    self::$max=$max;
    if(self::$flag=$flag) data::store(self::$flag,0,600);
    if(self::$output_mode)echo "[";
    return self::$pos=0;
  }
  static function walk($step){
    $current = round(self::$max?($step/self::$max):1,3);
    $step = floor($current*RBX_PAD);
    $old = self::$pos; self::$pos= $step;
    if($step == $old) return $current;
    if(self::$flag) return data::store(self::$flag, $current, 600);

    if(!self::$output_mode) return;
    echo str_repeat("=",$step - $old).($step==RBX_PAD?"]\n":'');flush();
  }
  function __toString(){ return $this->message; }
  static function end(){ if(self::$pos!=RBX_PAD) self::walk(self::$max); }
}
