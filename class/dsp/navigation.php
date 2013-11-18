<?php

class navigation{
  private $tree = array();
  

  public function __construct($conf = null){
    if(is_null($conf))
      $this->tree = array();
    else $this->load($conf);
  }

  function load($conf){
    $this->tree = $this->parse($conf);
  }

  function stack($flow){
    $this->tree = array_merge($this->tree, $flow);
  }

  function exclude($k){
    unset($this->tree[$k]);
  }

    //apply filter & exclusions pattern from (specific) context
  function export($base = null){
    if(is_null($base)) $base = $this->tree;
    $output = array();
    foreach($base as $k=>$v) {
      $valid = true;

      if($v['children'])
        $v['children'] = $this->export($v['children']);
      if($v['access'])
       foreach($v['access'] as $access_zone => $access_level)
            $valid &= auth::verif($access_zone, pick($access_level, 'access'));
      if($valid)
        $output[$k] = $v;
    }

    return $output;
  }

  private function parse($conf){
    $tree = array();
    foreach($conf->iterate('page') as $page){
      $nav = array_sort($page, array('title', 'href', 'target', 'theme', 'effects', 'help'));

      foreach($page->iterate('right') as $right)
        $nav['access'][$right['name']] = $right['level'];

      if($children = $this->parse($page))
        $nav['children'] = $children;

      $tree[$page['key']] = $nav;
    }
    return $tree;
  }

  
  public function output($id = "") {
    return self::render($this->export(), $id, 0);
  }

  // Find documentation in the manual
  static function render($tree, $id , $depth){
    $ul="<ul ".($id?"id='$id'":'').">"; $str = '';
    foreach($tree as $link_key=>$link_infos){
        $title    = $link_infos['title'];
        $children = (bool)$link_infos['children'];
        $current  = (substr(exyks::$href,0,strlen($link_key))==$link_key);
        $target   = $link_infos['target']?"target=\"{$link_infos['target']}\"":'';
        $class    = $children?"class='parent'":'';
        $id       = isset($link_infos['id'])? "id='{$link_infos['id']}'":'';
        $str.="<li $class $id>";
        $href  = $link_infos['href']?"href='{$link_infos['href']}'":'';
        $class = $current?"class='current'":'';

        if($theme=$link_infos['theme']){
            if($current) $theme .= ":on";
            $element = $href ? "button" : "title";
            $effects = $link_infos['effects']?"effects=\"{$link_infos['effects']}\"":"";
            $str.="<$element $target $effects $href theme='{$theme}'>$title</$element>";
        } else {
            $element = $href ? "a" : "span";
            $str.="<$element $class $target $href>$title</$element>";
        }

        if($children) $str .= self::render($link_infos['children'], false, $depth+1)."";
       $str.="</li>";
    }
    $ul.= $str;
    $ul.= "</ul>";
    return $str?$ul:"";
 }


}