<?

class tpls {
 const REPLACE = 1;
 const ERASE = 2;
 const TOP = 3;

 static $nav=array();
 static $top=array();
 static $bottom=array();
 static $body=false; //if!tpls::$body
 static $paths = array('search'=>array(), 'replace'=>array());
 static public $entities = array();

 static function top($href,$mode=0){
    if($mode==self::REPLACE)array_pop(self::$top);
    elseif($mode==self::ERASE) self::$top=array();
    elseif($mode==self::TOP) return array_unshift(self::$top, self::tpl($href));
    array_push(self::$top, self::tpl($href));
 }

 static function bottom($href,$mode=0){
    if($mode==self::REPLACE) array_shift(self::$top);
    elseif($mode==self::ERASE) self::$bottom=array();
    array_unshift(self::$bottom, self::tpl($href));
 }
    
 static function body($href, $raw=false) {
    self::$body = $raw?$href:self::tpl($href);
 }
 static function page_def($subs_file){
    exyks::$page_def = $subs_file;
 }
 public static function css_add($href,$media=false){
    $tmp=yks::$get->config->head->styles->addChild("css");
    if($media)$tmp['media']=$media;
    $tmp['href']=$href;
 }
 public static function css_clean(){
    yks::$get->config->head->styles = null;
 }

/* register entities (k=>v) that will be available in &k; for .tpl file */
 public static function export($vals){
    self::$entities = array_merge(self::$entities, $vals);
 }

 static function js_add($href,$defer=false){
    $tmp=yks::$get->config->head->scripts->addChild("js");
    if($defer)$tmp['defer']="true";
    $tmp['src']=$href;
 }

 static function call($page,$vars=array()){
    global $href_fold, $class_path, $action;
    $href=$page;
    extract($vars);
    include "subs/$page.php";
    include "tpls/$page.tpl";
 }

  static function tpl($tpl){
    $tpl = "/".ltrim("$tpl.tpl","/");
    $tmp = preg_replace(self::$paths['search'], self::$paths['replace'], $tpl);

    return $tmp == $tpl?ROOT_PATH."/tpls$tpl":$tmp;
  }


 static function nav($tree){
    self::$nav = array_merge(self::$nav, $tree);
 }
}