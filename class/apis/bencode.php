<?php

// Bencode - decode by 131, rosk :]

class bencode {

  static function decode($str,&$i=0){
    switch($str{$i++}){
      case 'd':
        $out=array();
        while($str{$i}!='e'){
          $key=bencode_decode($str,&$i);
          $out[$key]=bencode_decode($str,&$i);
        } $i++; return $out;
      case 'l':
        $out=array();
        while($str{$i}!='e'){
          $out[]=bencode_decode($str,&$i);
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

}

