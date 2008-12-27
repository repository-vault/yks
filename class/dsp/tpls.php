<?

define('TPLS_REPLACE',1);
define('TPLS_ERASE',2);


class tpls {
 static $nav=array();
 static $top=array();
 static $bottom=array();
 static $body=false; //if!tpls::$body

 static function top($href,$mode=0){
    if($mode==TPLS_REPLACE)array_pop(self::$top);
    elseif($mode==TPLS_ERASE) self::$top=array();
    array_push(self::$top,$href);
 }
 static function bottom($href,$mode=0){
    if($mode==TPLS_REPLACE) array_shift(self::$top);
    elseif($mode==TPLS_ERASE) self::$bottom=array();
    array_unshift(self::$bottom,$href);
 }
    
 static function body($href) {
    self::$body=$href;
 }
 static function page_def($subs_file){
    yks::$page_def = $subs_file;
 }
 static function css_add($href,$media=false){
    $tmp=yks::$get->config->head->styles->addChild("css");
    if($media)$tmp['media']=$media;
    $tmp['href']=$href;
 }

 static function js_add($href,$defer=false){
    $tmp=yks::$get->config->head->scripts->addChild("js");
    if($defer)$tmp['defer']="true";
    $tmp['src']=$href;
 }

 static function build($vars=array()){
    global $config,$href,$href_fold,$user_lang;
    extract($vars);

    ob_start();
    include 'tpls/Yks/xml_head.tpl';
    foreach(self::$top as $top) include "tpls/$top";
    include "tpls/".tpls::$body;
    foreach(self::$bottom as $bottom ) include "tpls/$bottom";
    $str=ob_get_contents();ob_end_clean();

    $str=jsx::translate($str,$user_lang);
    return $str;
 }

 static function call($page,$vars=array()){

    global $href_fold;
    global $class_path;
    $href=$page;
    extract($vars);
    include "subs/$page.php";
    include "tpls/$page.tpl";
 }
 static function sub($href){ return "subs/".ltrim($href,"/").".php"; }
 static function tpl($href){ return "tpls/".ltrim($href,"/").".tpl"; }


 static function nav($tree){
    self::$nav = array_merge(self::$nav, $tree);
 }
}