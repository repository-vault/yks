<?php


define('JSX_PLACE',         jsx::PLACE);
define('JSX_MODAL',         jsx::MODAL);
define('JSX_PARENT_RELOAD', jsx::PARENT_RELOAD);
define('JSX_PARENT_CLOSE',  jsx::PARENT_CLOSE);
define('JSX_RELOAD',        jsx::RELOAD);
define('JSX_CLOSE',         jsx::CLOSE);
define('JSX_WALK_INIT',     jsx::WALK_INIT);
define('JSX_WALKER',        jsx::WALKER);


class jsx {
  const JS_EVAL         = 'jsx_eval';
  const PLACE           = 'place';
  const MODAL           = 'modal';
  const PARENT_RELOAD   = 'this.getBox().opener.reload();';
  const PARENT_CLOSE    = 'this.getBox().opener.close();';
  const RELOAD          = 'this.getBox().reload();';
  const CLOSE           = 'this.getBox().close();';
  const WALK_INIT       = 'jsx.rbx.loader(0);';
  const WALKER          = 'jsx.rbx.loader();';

  static public $rbx=false; //only rbx mode

  static function end($var=false, $force_array=false){
    header(TYPE_JSON);
    if(is_string($var)) {jsx::js_eval($var); $var=rbx::$rbx;}
    die(($force_array && !$var)? "[]" : jsx::encode($var===false?rbx::$rbx:$var));
  }
  static function encode($var){
    if(!$var) return "{}";
    if($eval = $var[jsx::JS_EVAL]){
        unset($var[jsx::JS_EVAL]);
        $eval = jsx::JS_EVAL.':function(jsx){'.$eval.'}';
    }

    $json = json_encode_lite($var);
    //eval'd code is untouched
    if($eval){if($var)$json=substr($json,0,-1).",$eval}"; else $json='{'.$eval.'}';}

    return locales_manager::translate($json);
  }

  static function set($key, $val=false){
    if(!is_array($key)) $key = array($key=>$val);
    foreach($key as $k=>$v) exyks::$head->jsx[$k] = $v;
  }
  static function export($key,$val){ rbx::$rbx['set'][$key]=$val; }
  static function js_eval($msg) { rbx::msg(jsx::JS_EVAL,"$msg;"); }
  static function walk($step){ rbx::msg("walk", floor(100*$step)); jsx::end();}

  //Raw version of reloc
  public static function reloc($url){
    header(TYPE_JSON);
    die(json_encode(array('go' => $url)));
  }

}





