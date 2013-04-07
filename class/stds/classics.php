<?php

function sys_end($generation_time,$display_time=0){
    return sprintf("\n<!-- powerdé by exyks in - subs : %0-5Fs - tpls : %0-5Fs %s-->",
        $generation_time,$display_time,"");//,
    ;
}

function abort($code) {
    $dest=ERROR_PAGE."//$code";
    if($code==404 && $dest==exyks::$href_ks) yks::fatality(yks::FATALITY_404);
    if(ERROR_PAGE==exyks::$href) return; //empeche les redirections en boucle

    $_SESSION[SESS_TRACK_ERR]="/?".exyks::$href_ks;

    if(JSX){if($code!=403)rbx::error($code);
        else jsx::js_eval("Jsx.open('/?$dest','error_box',this)");
        jsx::end();
    } reloc("?$dest");
}

function reloc($url) {
  if(substr($url,0,1)=="/"){
    $url = '/'.ltrim($url,'/');
    if($_SERVER['HTTP_ORIGIN']){
      $parse_origin_url = parse_url($_SERVER['HTTP_ORIGIN']);      
      if($parse_origin_url['host'] == SITE_DOMAIN)
        $url = $parse_origin_url['scheme'].'://'.SITE_DOMAIN.$url;
    } else {
       $url=SITE_URL.$url;
    }
  }
    
  if(class_exists('rbx') && rbx::$rbx) rbx::delay();
  if(JSX===true) {rbx::msg('go',$url);jsx::end();}
  header("Location: $url"); exit;
}

function fields($table, $key=false){
    $res=array();
    if($table->field) foreach($table->field as $field)
        if(!$key || $field['key']==$key)
        $res[(string) $field['name']] = (string) $field['type'];
    return $res;
}
 

