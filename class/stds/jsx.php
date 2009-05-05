<?
//if( 0&& DEBUG && preg_match_all('#&[\#a-z0-9\._]*?[^;a-z0-9\._]#',$str,$out))

define('JSX_EVAL','jsx_eval');
define('JSX_PLACE','place');
define('JSX_MODAL','modal');
define('JSX_PARENT_RELOAD',"this.getBox().opener.reload();");
define('JSX_PARENT_CLOSE',"this.getBox().opener.close();");
define('JSX_RELOAD',"this.getBox().reload();");
define('JSX_CLOSE',"this.getBox().close();");
define('JSX_WALK_INIT', "jsx.rbx.loader(0);");
define('JSX_WALKER', "jsx.rbx.loader();");


class jsx {
  const MASK_INVALID_ENTITIES = "#&(?!lt;|gt;|\#[0-9]+;|quot;|amp;)#";

  static public $rbx=false; //only rbx mode
  static function end($var=false, $force_array=false){
    header(TYPE_JSON);
    if(is_string($var)) {jsx::js_eval($var); $var=rbx::$rbx;}
    die(($force_array && !$var)? "[]" : jsx::encode($var===false?rbx::$rbx:$var));
  }
  static function encode($var){
    if(!$var) return "{}";
    if($eval=$var[JSX_EVAL]){unset($var[JSX_EVAL]);$eval=JSX_EVAL.':function(jsx){'.$eval.'}';}

    $json=str_replace(array('<\/','\/>'),array('</','/>'),json_encode($var));
    if($eval){if($var)$json=substr($json,0,-1).",$eval}"; else $json='{'.$eval.'}';}

    $json=preg_replace("#([\"])([0-9]+)\\1#","$2",$json);//dequote ints
    $json=utf8_decode(html_entity_decode($json,ENT_NOQUOTES,"UTF-8"));
    $json=unicode_decode($json);
    $json=str_replace("&quot;","\\\"",$json);

    return jsx::translate($json);
  }

  static function set($key, $val=false){
    if(!is_array($key)) $key = array($key=>$val);
    foreach($key as $k=>$v) yks::$get->config->head->jsx[$k] = $v;
  }
  static function export($key,$val){ rbx::$rbx['set'][$key]=$val; }
  static function js_eval($msg) { rbx::msg(JSX_EVAL,"$msg;"); }
  static function walk($step){ rbx::msg("walk", floor(100*$step)); jsx::end();}

  static function translate($str, $lang = USER_LANG){
    $entities = yks::$get->get("entities",$lang);
    foreach(exyks::$entities as $k=>$v) $entities["&$k;"] = $v;
    if($entities){while($tmp!=$str){ $tmp=$str; $str=strtr($str,$entities);} $str=$tmp;}
    
    if(strpos($str,"&")!==false)$str = renderer::process_entities($str, $lang);

    if(preg_match(self::MASK_INVALID_ENTITIES, $str)) {
        error_log("There are invalid entities in your document");
        $str = preg_replace(self::MASK_INVALID_ENTITIES,'&amp;',$str);
        if(preg_match("#<!\[CDATA\[(?s:.*?)\]\]>#",$str,$out)){
          $str= str_replace($out[0], str_replace("&amp;",'&',$out[0]),$str );
        }
    }

    return $str;
  }
}




