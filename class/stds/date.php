<?php

class date {

  static function validate($date, $format=DATE_MASK, $zero_time = false){
    $format=preg_replace("#[a-z]#i","%$0",strtr($format,array('i'=>'M','s'=>'S')));

    if(!($tm=strptime($date,$format)))
        return false;
    $tm['tm_mon']+=1;

    $date = gmmktime($tm['tm_hour'],$tm['tm_min'],$tm['tm_sec'],
        $tm['tm_mon'], pick($tm['tm_mday'],1), 1900+pick($tm['tm_year'], 70));

    if(!$zero_time)
        $date -= exyks::retrieve("USER_TZ");

    return $date;
  }


  static function add($time,$dd=0,$mm=0,$YY=0){
    list($d,$m,$Y,$H,$i,$s)=explode(',',date('d,m,Y,H,i,s',$time));
    return mktime($H,$i,$s,$mm+$m,$dd+$d,$YY+$Y);
  }


  static function uday($time, $user_tz = false){
    if($user_tz) $time += exyks::retrieve("USER_TZ");
    return floor($time/86400);
  }

    /** get universal day **/
  static function mkuday($d=false, $m=false, $y=false){
    return self::uday(gmmktime(0, 0, 0,
        $m!==false?$m:idate('m'),
        $d!==false?$d:idate('d'),
        $y!==false?$y:idate('Y')));
  }


  static function sprintf($date=_NOW, $format=DATE_MASK){
    return self::sprintfc($date, preg_replace("#[a-z]#i",'$$0', $format));
  }


  static function sprintfc($date=_NOW,$format=DATE_DAY,$format_rel=false){
    if(is_null($date))
        return "&date.undefined;";
    if($date==0)
        return "&date.0;";
    if($date==2147483647)
        return "&date.never;";

    static $rs=false;
        if(!$rs) $rs=array(
            date('z/Y',_NOW)=>'&date.today;',
            date('z/Y',_NOW-86400)=>'&date.yesterday;');

    static $USER_TZ = false;
        if($USER_TZ===false && class_exists("exyks"))
            $USER_TZ = exyks::retrieve("USER_TZ");

    $datef = $date+$USER_TZ; //date to display (use client TZ to format)
    list($d, $m, $n, $Y, $H, $i, $s, $z, $N) = explode(',',date("d,m,n,Y,H,i,s,z,N",$datef));
    $p = $USER_TZ/3600; $p = ($p>0?'+':'').substr("0$p", -2);$P = "{$p}:00"; $O="{$p}00";
    //P=+02:00 O=+0200

    if($z<79 or $z>354)$a=4; elseif($z<172)$a=1; elseif($z<265)$a=2; else $a=3; //a = season


    $t=ceil($n/3); $rel=$rs["$z/$Y"]; 
    return preg_replace(VAR_MASK,VAR_REPL,$rel&&$format_rel?$format_rel:$format);
  }



}



