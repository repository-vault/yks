<?php

// Bencode - decode by 131, rosk :]

class bencode {

  static function decode($str,&$i=0){
    switch($str{$i++}){
      case 'd':
        $out=array();
        while($str{$i}!='e'){
          $key=bencode::decode($str,&$i);
          $out[$key]=bencode::decode($str,&$i);
        } $i++; return $out;
      case 'l':
        $out=array();
        while($str{$i}!='e'){
          $out[]=bencode::decode($str,&$i);
        } $i++; return $out;
      case 'i':
        $out=intval(substr($str,$i,($e=strpos($str,'e',$i))-$i));$i=$e+1;
        return $out;
      default:
        $end=strpos($str,':',$i)+1;
        $out=substr($str,$end,$len=substr($str,$i-1,$end-$i));$i=$end+$len;
        return $out;
    }
  }
  static function encode($struct){
    if(is_int($struct))
      return "i{$struct}e";
    if(is_string($struct))
      return strlen($struct).":$struct";
    
    $str = "";
    if(is_int(key($struct))) {
      $str .= "l";      
     foreach($struct as $v) $str .= self::encode($v);
    } else {
      $str .= "d";
      foreach($struct as $k=>$v){
       $str .=  self::encode($k);
       $str .=  self::encode($v);
      }
    } return $str.'e';
  }
}

