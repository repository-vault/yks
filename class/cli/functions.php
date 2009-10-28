<?php

function password_prompt(){
  throw new Exception("Depreceated ".__FUNCTION__." please use cli::");
}

function text_prompt($prompt=false){
  throw new Exception("Depreceated ".__FUNCTION__." please use cli::");
}

function load_constants_ini($file) { 
  $data = parse_ini_file ($file);
  foreach($data as $key=>$value){
    if(is_numeric($value)) $value = (int)$value;
    $key =  strtoupper(strtr($key,array('.'=>'_')));
    define($key, $value);
  } 
}

