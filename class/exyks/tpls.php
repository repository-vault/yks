<?php

class tpls {
 const REPLACE = 1;
 const ERASE = 2;
 const TOP = 3;
 const STD = 0;

 static $nav=array();
 static private $top=array();
 static private $bottom=array();
 static $body=false; //if!tpls::$body

 static private $paths = array('search'=>array(), 'replace'=>array());

 static public $entities = array();
 static private $cmds = array(
    'top'    => array(
        'replace'=> 'array_pop',
        'action'=>'array_push' ),
    'bottom' => array(
        'replace'=> 'array_shift',
        'action'=>'array_unshift' ),
    );

 static function top($href, $where = tpls::STD, $render_mode = 'full'){
    self::tpl_add("top", $href, $where, $render_mode);
 }

 static function bottom($href, $where = tpls::STD, $render_mode = 'full'){
    self::tpl_add("bottom", $href, $where, $render_mode);
 }


  static function tpl_add($side, $href, $where, $render_mode){
    if($render_mode=='all') $render_mode = array_keys(self::$$side);
    if(is_array($render_mode)) {
        foreach($render_mode as $mode)self::tpl_add($side, $href, $where, $mode);
        return;
    }

    $tpl_ref = &self::${$side}[$render_mode]; if(!$tpl_ref) $tpl_ref = array();
    $cmds    = self::$cmds[$side];

    if($where==self::REPLACE)  call_user_func_array($cmds['replace'], array(&$tpl_ref));
    elseif($where==self::ERASE)  $tpl_ref = array();
    elseif($where==self::TOP) return array_unshift($tpl_ref, self::tpl($href));
    call_user_func_array($cmds['action'], array(&$tpl_ref, self::tpl($href)) );
  }


 static function export_list($render_mode = "full"){
    $list = array_merge(
            (array)tpls::$top[$render_mode],
            array(tpls::$body),
            (array)tpls::$bottom[$render_mode]); 
    return $list;
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


 static public function add_resolver($key, $path){
        //reaggregate paths
    if(self::$paths['search']) {
        $paths = array_combine(self::$paths['search'], self::$paths['replace']);
        $paths["#^$key#"] = $path;
        krsort($paths);
    } else $paths = array("#^$key#" => $path);

    self::$paths['search']  = array_keys($paths);
    self::$paths['replace'] = array_values($paths);
 }


 static function nav($tree){
    self::$nav = array_merge(self::$nav, $tree);
 }
}
