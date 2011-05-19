<?php

class date {

  static function validate($date, $format=DATE_MASK, $zero_time = false){

    $format=preg_replace("#[a-z]#i","%$0",strtr($format,array('i'=>'M','s'=>'S')));

    if(!($tm=self::strptime($date,$format)))
        return false;
    $tm['tm_mon']+=1;

    $date = gmmktime($tm['tm_hour'],$tm['tm_min'],$tm['tm_sec'],
        $tm['tm_mon'], pick($tm['tm_mday'],1), 1900+pick($tm['tm_year'], 70));

    if(!$zero_time && $format =! DATE_MASK)
        $date -= exyks::retrieve("USER_TZ");

    return $date;
  }

  static function strptime($date, $format) {
    $masks = array(
      '%d' => '(?P<d>[0-9]{2})',
      '%m' => '(?P<m>[0-9]{2})',
      '%Y' => '(?P<Y>[0-9]{4})',
      '%H' => '(?P<H>[0-9]{2})',
      '%M' => '(?P<M>[0-9]{2})',
      '%S' => '(?P<S>[0-9]{2})',
    ); 
    $rexep = "#".strtr(preg_quote($format), $masks)."#";
    if(!preg_match($rexep, $date, $out))
      return false;

    $ret = array(
      "tm_sec"  => (int) $out['S'],
      "tm_min"  => (int) $out['M'],
      "tm_hour" => (int) $out['H'],
      "tm_mday" => (int) $out['d'],
      "tm_mon"  => $out['m']?$out['m']-1:0,
      "tm_year" => $out['Y'] > 1900 ? $out['Y'] - 1900 : 0,
    );
    return $ret;
  }


  static function add($time, $dd=0, $mm=0, $YY=0){
    list($d, $m, $Y, $H, $i, $s) = explode(',',date('d,m,Y,H,i,s',$time));
    return mktime($H, $i, $s, $mm+$m, $dd+$d, $YY+$Y);
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


  static function sprintfc($date=_NOW, $format=DATE_DAY, $format_rel=false){
    return self::sprintfc_time($date, $format, $format_rel, false);
  }

  static function sprintfc_time($date=_NOW, $format=DATE_DAY, $format_rel=false, $time = true){
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
        if($USER_TZ===false && class_exists("exyks") && $time)
            $USER_TZ = exyks::retrieve("USER_TZ");

    $datef = $date+$USER_TZ; //date to display (use client TZ to format)
    list($d, $m, $n, $Y, $H, $i, $s, $z, $N, $j) = explode(',',date("d,m,n,Y,H,i,s,z,N,j",$datef));
    $p = $USER_TZ/3600; $p = ($p>0?'+':'').substr("0$p", -2);$P = "{$p}:00"; $O="{$p}00";
    //P=+02:00 O=+0200

    if($z<79 or $z>354)$a=4; elseif($z<172)$a=1; elseif($z<265)$a=2; else $a=3; //a = season


    $t=ceil($n/3); $rel=$rs["$z/$Y"]; 
    return preg_replace(VAR_MASK,VAR_REPL,$rel&&$format_rel?$format_rel:$format);
  }

  
  public static function human_diff($timestamp, $max = 2){
    $steps = array(
      's' => 60,
      'min' => 60,
      'hour' => 24,
      'day' => 30,
      'month' => 12,
      'year' => 0,
    );
    
    $t = $timestamp;
    $out = array();
    foreach($steps as $name => $step_time){
      if($step_time == 0){
        $current = floor($t);
      }else{
        $current = $t % $step_time;
        $t /= $step_time;        
      }
      if($current > 0)
        $out[] = "$current $name".($name != 's' && $current > 1 ? 's' : '');
    }
    return implode(' ', array_slice(array_reverse($out), 0, $max));
  }


}



